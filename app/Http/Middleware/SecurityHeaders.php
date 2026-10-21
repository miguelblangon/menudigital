<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cabeceras de seguridad endurecidas para toda la aplicación.
 *
 * La carta es pública y el panel es un CRUD con imágenes subidas por el
 * comerciante: convienen DENY en frames, nosniff en los assets y no-store en
 * las páginas autenticadas para que el histórico del navegador no guarde datos
 * del negocio.
 * apply() es pública porque las respuestas que nacen fuera del pipeline de
 * middleware (404, la 302 de invitado, el 419 de CSRF caducado, los 500) nunca
 * pasan por handle(): el exception handler las construye cuando el pipeline ya
 * se ha desmontado. Por eso se registra también en bootstrap/app.php.
 *
 * La Content-Security-Policy es la que corta de raíz un XSS: para que pueda
 * declararse cerrada, el JS de la aplicación (Alpine incluido) viaja en el
 * bundle de Vite y las vistas no tienen ni un <script> inline ni un manejador
 * onclick.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        return static::apply($request, $next($request));
    }

    /**
     * Añade las cabeceras a una respuesta, venga del pipeline o del handler
     * de excepciones.
     */
    public static function apply(Request $request, Response $response): Response
    {
        $response->headers->set("X-Frame-Options", "DENY");
        $response->headers->set("X-Content-Type-Options", "nosniff");
        $response->headers->set("Referrer-Policy", "strict-origin-when-cross-origin");
        $response->headers->set("Permissions-Policy", "camera=(), microphone=(), geolocation=()");

        // Con CSP_REPORT_ONLY=true la política sólo informa: sirve para ver en la
        // consola del navegador qué rompería antes de aplicarla de verdad.
        $cspHeader = config("security.csp_report_only")
            ? "Content-Security-Policy-Report-Only"
            : "Content-Security-Policy";

        $response->headers->set($cspHeader, static::contentSecurityPolicy($request));

        // HSTS sólo se declara sobre HTTPS: prometerlo en una respuesta HTTP
        // forzaría al navegador a un protocolo que el host puede no tener.
        if ($request->secure()) {
            $response->headers->set("Strict-Transport-Security", "max-age=31536000; includeSubDomains");
        }

        if ($request->is("admin/*", "dashboard", "profile*", "login", "password")) {
            // remove() antes de set(): Symfony guarda las directivas de
            // Cache-Control en un array interno y las fusiona, así que un
            // set() a pelo terminaba en "max-age=0, no-store, private".
            $response->headers->remove("Cache-Control");
            $response->headers->remove("Pragma");
            $response->headers->set("Cache-Control", "no-store, max-age=0");
            $response->headers->set("Pragma", "no-cache");
            $response->headers->set("Expires", "Sun, 19 Nov 1978 05:00:00 GMT");
        }

        return $response;
    }

    /**
     * Content-Security-Policy de la aplicación.
     *
     * - default-src 'self' cubre lo que no se lista abajo (media, workers,
     *   embed, navegación de frames...).
     * - script-src 'self': ni un solo script inline, así que un HTML inyectado
     *   (XSS en un nombre de producto, por ejemplo) no llega a ejecutarse.
     *   'unsafe-eval' es obligatorio porque Alpine compila sus expresiones con
     *   new Function(); no reabre la puerta al código incrustado en el HTML,
     *   que sigue bloqueado por la ausencia de 'unsafe-inline'.
     * - style-src 'unsafe-inline': los componentes de Breeze usan style="" en
     *   dropdowns y modales. Los estilos no ejecutan código; el riesgo residual
     *   (exfiltrar datos con selectores de atributo) no aplica porque ninguna
     *   vista mete contenido del usuario dentro de un style.
     * - img-src 'self' data: y font-src 'self': placeholder, imágenes del disco
     *   público y tipografías @fontsource empaquetadas por Vite. Sin CDNs.
     * - form-action 'self': un formulario inyectado no puede mandar las
     *   credenciales del panel a un origen ajeno.
     * - frame-ancestors 'none' / object-src 'none': clickjacking y plugins.
     */
    protected static function contentSecurityPolicy(Request $request): string
    {
        $script = ["'self'", "'unsafe-eval'"];
        $style = ["'self'", "'unsafe-inline'"];
        $connect = ["'self'"];

        // Con `npm run dev` el bundle lo sirve el dev server de Vite (otro
        // origen) y el HMR abre un websocket: sin esta excepción la carta
        // quedaría sin JavaScript en local. En producción no existe public/hot.
        if (($devUrl = Vite::devServerUrl()) !== null) {
            $script[] = $devUrl;
            $style[] = $devUrl;
            $connect[] = $devUrl;
            $connect[] = preg_replace("#^http#", "ws", $devUrl);
        }

        $directives = [
            "default-src 'self'",
            "script-src ".implode(" ", $script),
            "style-src ".implode(" ", $style),
            "img-src 'self' data:",
            "font-src 'self'",
            "connect-src ".implode(" ", $connect),
            "form-action 'self'",
            "frame-ancestors 'none'",
            "base-uri 'self'",
            "object-src 'none'",
        ];

        if ($request->secure()) {
            $directives[] = "upgrade-insecure-requests";
        }

        return implode("; ", $directives);
    }
}
