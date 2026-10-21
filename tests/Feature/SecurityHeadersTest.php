<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_menu_carries_the_hardening_headers(): void
    {
        $response = $this->get("/")->assertOk();

        $response->assertHeader("X-Frame-Options", "DENY");
        $response->assertHeader("X-Content-Type-Options", "nosniff");
        $response->assertHeader("Referrer-Policy", "strict-origin-when-cross-origin");
        $response->assertHeader("Permissions-Policy", "camera=(), microphone=(), geolocation=()");
    }

    public function test_admin_pages_are_not_cacheable_even_for_guests(): void
    {
        // El middleware mira la ruta pedida, no la respuesta: también cubre la 302.
        $guest = $this->get("/admin/products")->assertRedirect("/login");

        $admin = $this->actingAs(User::factory()->create())
            ->get("/admin/products")
            ->assertOk();

        foreach (["invitado" => $guest, "administrador" => $admin] as $label => $response) {
            // Symfony no devuelve el string escrito: reconstruye la cabecera desde
            // su bolsa de directivas y añade "private" cuando la respuesta lleva
            // cookie de sesión ("max-age=0, no-store, private"). Lo que importa es
            // la directiva, no el orden del string.
            $this->assertTrue(
                $response->headers->hasCacheControlDirective("no-store"),
                "La página de administrador ({$label}) no declara no-store."
            );
            $this->assertSame("0", (string) $response->headers->getCacheControlDirective("max-age"));
            $this->assertSame("no-cache", (string) $response->headers->get("Pragma"));
        }
    }

    public function test_error_responses_keep_the_headers(): void
    {
        // El 404 lo fabrica el exception handler fuera del pipeline de
        // middleware: si alguien quita el respond() de bootstrap/app.php,
        // una carta con ?category=inexistente quedaría sin endurecer.
        $this->get("/pagina-que-no-existe")
            ->assertNotFound()
            ->assertHeader("X-Frame-Options", "DENY")
            ->assertHeader("X-Content-Type-Options", "nosniff");

        $this->get("/?category=inexistente")
            ->assertNotFound()
            ->assertHeader("X-Frame-Options", "DENY");
    }

    public function test_public_menu_is_not_marked_no_store(): void
    {
        $cacheControl = (string) $this->get("/")->headers->get("Cache-Control");

        $this->assertStringNotContainsString("no-store", $cacheControl);
    }

    public function test_every_response_ships_a_restrictive_content_security_policy(): void
    {
        $csp = (string) $this->get("/")->assertOk()->headers->get("Content-Security-Policy");

        foreach ([
            "default-src 'self'",
            "script-src 'self'",
            "style-src 'self'",
            "img-src 'self'",
            "font-src 'self'",
            "connect-src 'self'",
            "form-action 'self'",
            "frame-ancestors 'none'",
            "base-uri 'self'",
            "object-src 'none'",
        ] as $directive) {
            $this->assertStringContainsString($directive, $csp, "Falta la directiva: {$directive}");
        }
    }

    public function test_script_policy_allows_alpine_but_never_inline_code(): void
    {
        $csp = (string) $this->get("/")->headers->get("Content-Security-Policy");
        $scriptSrc = $this->directive($csp, "script-src");

        // Alpine compila sus expresiones con new Function(): sin 'unsafe-eval'
        // la carta se queda sin filtros. 'unsafe-inline' es el que no puede
        // aparecer: es lo que impide que un payload inyectado se ejecute.
        $this->assertStringContainsString("'unsafe-eval'", $scriptSrc);
        $this->assertStringNotContainsString("unsafe-inline", $scriptSrc);
    }

    public function test_content_security_policy_never_opens_a_third_party_origin(): void
    {
        // Con `npm run dev` puede colarse el dev server de Vite (localhost), que
        // es de confianza; un CDN externo, nunca.
        $csp = (string) $this->get("/")->headers->get("Content-Security-Policy");

        foreach (["jsdelivr", "unpkg", "googleapis", "gstatic", "bunny.net", "placehold", "cloudflare"] as $origin) {
            $this->assertStringNotContainsString($origin, $csp, "La CSP vuelve a abrir {$origin}");
        }
    }

    public function test_report_only_mode_is_available_through_config(): void
    {
        config(["security.csp_report_only" => true]);

        $response = $this->get("/")->assertOk();

        $response->assertHeaderMissing("Content-Security-Policy");
        $this->assertNotEmpty($response->headers->get("Content-Security-Policy-Report-Only"));
    }

    public function test_hsts_is_declared_only_when_the_response_is_https(): void
    {
        // Prometer HSTS por HTTP enviaría al navegador a un protocolo que el
        // host puede no tener montado.
        $this->get("/")->assertOk()->assertHeaderMissing("Strict-Transport-Security");

        // withServerVariables(["HTTPS" => "on"]) no basta: Laravel convierte la
        // ruta en la URL absoluta de APP_URL (http://localhost) y el esquema del
        // URI manda sobre los server vars, así que $request->secure() sigue
        // siendo false. Se pide la URL https directamente.
        $https = $this->get("https://localhost/")->assertOk();

        $https->assertHeader("Strict-Transport-Security", "max-age=31536000; includeSubDomains");
        $this->assertStringContainsString(
            "upgrade-insecure-requests",
            (string) $https->headers->get("Content-Security-Policy")
        );
    }

    public function test_the_menu_hydrates_alpine_from_json_not_from_inline_javascript(): void
    {
        $html = $this->get("/")->assertOk()->getContent();

        $this->assertStringContainsString('<script type="application/json" id="menu-data">', $html);
        $this->assertStringNotContainsString("<script>", $html);
        $this->assertStringNotContainsString("onclick=", $html);
        $this->assertStringNotContainsString("cdn.jsdelivr.net", $html);
        $this->assertStringNotContainsString("fonts.googleapis.com", $html);
        $this->assertStringNotContainsString("placehold.co", $html);

        // Todo script con src es propio (build) o del dev server local.
        preg_match_all('~<script[^>]*\bsrc="([^"]*)"~', $html, $matches);

        $this->assertNotEmpty($matches[1], "La carta debería cargar el bundle de Vite.");

        foreach ($matches[1] as $src) {
            $this->assertMatchesRegularExpression('~^(https?://((\[::1\])|(127\.0\.0\.1)|(localhost))(:\d+)?/|/)~', $src);
        }
    }

    public function test_no_view_ships_inline_handlers_or_font_cdns(): void
    {
        // Breeze trae middleware "guest" en /login: con una sesión abierta
        // responde 302 a /dashboard, así que las páginas de invitado se piden
        // sin autenticar y las del panel con sesión.
        $this->assertNoInlineHandlers("/", "la carta");
        $this->assertNoInlineHandlers("/login", "el acceso");

        $this->actingAs(User::factory()->create());

        foreach (["/dashboard", "/profile", "/admin/products", "/admin/categories", "/admin/allergens", "/admin/qr"] as $uri) {
            $this->assertNoInlineHandlers($uri, $uri);
        }
    }

    /**
     * Ninguna página puede devolver JavaScript incrustado (un payload XSS
     * inyectado se ejecutaría con la CSP tal como está) ni tipografías de red:
     * ambas son las dos puertas que quedaban abiertas tras el endurecimiento.
     */
    private function assertNoInlineHandlers(string $uri, string $label): void
    {
        $html = $this->get($uri)->assertOk()->getContent();

        $this->assertStringNotContainsString("onclick=", $html, "Handler inline en {$label}");
        $this->assertStringNotContainsString("onchange=", $html, "Handler inline en {$label}");
        $this->assertStringNotContainsString("fonts.bunny.net", $html, "CDN de tipografías en {$label}");
    }

    private function directive(string $csp, string $name): string
    {
        foreach (explode(";", $csp) as $part) {
            $part = trim($part);

            if (str_starts_with($part, $name." ")) {
                return $part;
            }
        }

        $this->fail("La CSP no declara {$name}.");
    }
}
