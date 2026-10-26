<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regresiones de "mobile friendliness".
 *
 * El comercio gestiona el menú desde el móvil y el cliente abre la carta desde
 * la mesa escaneando un QR: el 100% del tráfico es un teléfono. Estos tests
 * fijan las decisiones que sostienen ese escenario (viewport, safe areas,
 * drawer accesible, contenedor escalable del QR) para que un cambio futuro de
 * layout no vuelva a romper la pantalla de 375px en silencio.
 */
class ResponsiveLayoutTest extends TestCase
{
    use RefreshDatabase;

    private const VIEWPORT = 'width=device-width, initial-scale=1, viewport-fit=cover';

    private function admin(): User
    {
        return User::factory()->create();
    }

    public function test_the_menu_declares_the_mobile_viewport_with_safe_areas(): void
    {
        $this->get("/")
            ->assertOk()
            ->assertSee('name="viewport" content="'.self::VIEWPORT.'"', false);
    }

    /**
     * El panel ya no depende de la navegación de Breeze: todas sus pantallas
     * comparten layouts.admin.blade.php, y ese layout es el que declara el
     * viewport con safe areas.
     */
    public function test_every_admin_screen_declares_the_mobile_viewport(): void
    {
        foreach ([
            "dashboard" => "/dashboard",
            "products" => "/admin/products",
            "categories" => "/admin/categories",
            "allergens" => "/admin/allergens",
            "qr" => "/admin/qr",
            "profile" => "/profile",
        ] as $page => $uri) {
            $html = $this->actingAs($this->admin())
                ->get($uri)
                ->assertOk()
                ->getContent();

            $this->assertStringContainsString(
                'name="viewport" content="'.self::VIEWPORT.'"',
                $html,
                "la pantalla '{$page}' no declara el viewport de móvil"
            );
        }
    }

    public function test_the_admin_sidebar_reaches_every_section_and_can_log_out(): void
    {
        $html = $this->actingAs($this->admin())
            ->get("/admin/products")
            ->assertOk()
            ->getContent();

        // El drawer es la única navegación en móvil: si le falta una sección,
        // desde el teléfono simplemente no existe.
        foreach ([
            'href="'.route("dashboard").'"',
            'href="'.route("admin.products.index").'"',
            'href="'.route("admin.categories.index").'"',
            'href="'.route("admin.allergens.index").'"',
            'href="'.route("admin.qr.index").'"',
            'href="'.route("profile.edit").'"',
            'action="'.route("logout").'"',
        ] as $link) {
            $this->assertStringContainsString($link, $html);
        }
    }

    public function test_the_qr_svg_is_wrapped_because_endroid_hardcodes_its_width(): void
    {
        $html = $this->actingAs($this->admin())
            ->get("/admin/qr")
            ->assertOk()
            ->getContent();

        // endroid/qr-code escribe width="464px" en la raíz del SVG: sin el
        // contenedor data-qr (y la regla [data-qr] svg de app.css) ese ancho
        // fijo desborda un móvil de 375px y obliga a desplazarse en horizontal.
        $this->assertStringContainsString("data-qr", $html);
        $this->assertMatchesRegularExpression('/width="\d+px"/', $html);

        // Y el único bloque que debe salir en papel al pulsar "Imprimir".
        $this->assertStringContainsString("data-print-block", $html);
    }

    public function test_the_category_bar_is_part_of_the_sticky_header_not_a_second_offset(): void
    {
        Category::create(["name" => "Cafés", "slug" => "cafes"]);
        $dulces = Category::create(["name" => "Dulces", "slug" => "dulces"]);
        Product::create(["category_id" => $dulces->id, "name" => "Flan", "price" => 5.0, "available" => true]);

        $html = $this->get("/")->assertOk()->getContent();

        $headerStart = strpos($html, '<header class="sticky top-0');
        $navPosition = strpos($html, 'aria-label="Categorías de la carta"');
        $headerEnd = strpos($html, "</header>");

        $this->assertNotFalse($headerStart, "la carta debe tener un header pegajoso");
        $this->assertNotFalse($navPosition, "faltan los enlaces de categoría sin JavaScript");

        // Un único bloque sticky: la barra vive DENTRO del header. Si volviera
        // a ser un segundo elemento pegajoso necesitaría un offset en píxeles
        // (top-[57px]) que se descoloca con el zoom o el texto largo.
        $this->assertGreaterThan($headerStart, $navPosition);
        $this->assertLessThan($headerEnd, $navPosition);
        $this->assertStringNotContainsString("top-[57px]", $html);
    }

    /**
     * Todas las categorías a la vista: los chips envuelven en filas.
     *
     * El carrusel horizontal (`overflow-x-auto`) no tiene pista táctil: en una
     * pantalla sin barra de scroll el cliente leía "sólo estas 5 categorías" y
     * el resto quedaba escondido. Con `flex flex-wrap` no hay nada que deslizar
     * y, si mañana hay 20 categorías, el bloque simply crea filas nuevas.
     */
    public function test_the_category_bar_wraps_into_rows_instead_of_hiding_categories(): void
    {
        Category::create(["name" => "Cafés", "slug" => "cafes"]);
        $dulces = Category::create(["name" => "Dulces", "slug" => "dulces"]);
        Product::create(["category_id" => $dulces->id, "name" => "Flan", "price" => 5.0, "available" => true]);

        $html = $this->get("/")->assertOk()->getContent();

        $navStart = strpos($html, 'aria-label="Categorías de la carta"');
        $this->assertNotFalse($navStart, "no existe la barra de categorías");

        $nav = substr($html, $navStart, strpos($html, "</nav>", $navStart) - $navStart);

        $this->assertStringContainsString("flex flex-wrap", $nav, "la barra de categorías no envuelve en filas");

        // Cada chip viaja entero a la fila siguiente: `whitespace-nowrap` evita
        // que un nombre largo se parta por la mitad dentro de la píldora.
        $this->assertStringContainsString("whitespace-nowrap", $nav);

        // Nada de carrusel de vuelta.
        foreach (["overflow-x-auto", "overflow-x-scroll", "flex-nowrap", "snap-x", "shrink-0"] as $carousel) {
            $this->assertStringNotContainsString($carousel, $nav, "la barra ha vuelto al scroll horizontal ({$carousel})");
        }
    }

    public function test_the_allergen_sheet_is_cloaked_until_alpine_starts(): void
    {
        // El bundle de Vite es un módulo diferido: sin x-cloak el overlay a
        // pantalla completa se pinta durante un instante por encima de la carta.
        $this->get("/")
            ->assertOk()
            ->assertSee('x-show="showAllergenFilter" x-cloak', false);
    }

    public function test_the_mobile_drawer_is_declared_accessibly(): void
    {
        $html = $this->actingAs($this->admin())
            ->get("/dashboard")
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('id="menu-toggle"', $html);
        $this->assertStringContainsString('aria-controls="sidebar"', $html);
        $this->assertStringContainsString('aria-expanded="false"', $html);
    }

    public function test_the_stylesheets_carry_the_mobile_specific_rules(): void
    {
        $css = file_get_contents(base_path("resources/css/app.css"));

        $this->assertIsString($css);

        foreach ([
            "pb-safe",                    // barra de gestos del iPhone
            "env(safe-area-inset-bottom",
            "[x-cloak]",                  // nada de flash del modal
            "[data-qr] svg",              // QR escalable
            "@media print",               // el QR se imprime solo
        ] as $rule) {
            $this->assertStringContainsString($rule, $css, "falta la regla CSS '{$rule}' en resources/css/app.css");
        }
    }

    /**
     * El inset superior se aplica con una clase arbitraria de Tailwind
     * (`pt-[env(...)]`) en cada <header> pegajoso, no en app.css: sólo así el
     * offset respeta la muesca del iPhone sin tocar la hoja de estilos.
     */
    public function test_the_sticky_headers_reserve_the_top_safe_area(): void
    {
        $blade = fn (string $path) => (string) file_get_contents(resource_path("views/{$path}"));

        $this->assertStringContainsString(
            "pt-[env(safe-area-inset-top)]",
            $blade("components/menu-header.blade.php"),
            "la carta no reserva la safe area superior"
        );
        $this->assertStringContainsString(
            "pt-[env(safe-area-inset-top)]",
            $blade("layouts/admin.blade.php"),
            "el panel no reserva la safe area superior"
        );
    }

    /**
     * Una sola navegación en el panel. Breeze sigue trayendo layouts/app y su
     * navigation.blade.php (responsive, sí, pero con otra estructura y otro
     * logout): si una vista volviera a usar <x-app-layout>, el panel tendría dos
     * navegaciones conviviendo y la siguiente pantalla admin perdería el drawer
     * con el que se gestiona desde el móvil.
     */
    public function test_no_view_uses_the_breeze_app_layout(): void
    {
        $views = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(resource_path("views"), \FilesystemIterator::SKIP_DOTS)
        );

        $offenders = [];

        foreach ($views as $file) {
            if (! $file->isFile() || $file->getExtension() !== "blade") {
                continue;
            }

            if (preg_match("/<x-app-layout/", (string) file_get_contents($file->getPathname())) === 1) {
                $offenders[] = str_replace(resource_path("views/"), "", $file->getPathname());
            }
        }

        $this->assertSame(
            [],
            $offenders,
            "estas vistas usan la navegación de Breeze en lugar de layouts.admin: ".implode(", ", $offenders)
        );
    }
}
