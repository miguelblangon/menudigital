<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Services\QrCodeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QrControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_access_the_qr_page(): void
    {
        $this->get("/admin/qr")->assertRedirect("/login");
        $this->get("/admin/qr/download")->assertRedirect("/login");
    }

    public function test_index_renders_the_qr_inline(): void
    {
        $this->actingAs(User::factory()->create())
            ->get("/admin/qr")
            ->assertOk()
            ->assertViewIs("admin.qr.index")
            ->assertSee("<svg", false);
    }

    public function test_generate_returns_an_svg_qr(): void
    {
        $response = $this->actingAs(User::factory()->create())->get("/admin/qr/generate");

        $response->assertOk();
        $response->assertHeader("Content-Type", "image/svg+xml");
        $this->assertStringContainsString("<svg", $response->getContent());
    }

    public function test_download_returns_a_real_png(): void
    {
        $response = $this->actingAs(User::factory()->create())->get("/admin/qr/download");

        $response->assertOk();
        $response->assertHeader("Content-Type", "image/png");
        $response->assertHeader("Content-Disposition", 'attachment; filename="menu-qr.png"');

        // Cabecera mágica PNG: el PngWriter de endroid v6 exige imagick y aquí solo hay GD,
        // así que el PNG se rasteriza a mano desde la matriz de módulos.
        $this->assertStringStartsWith("\x89PNG\r\n\x1a\n", $response->getContent());
    }

    public function test_service_returns_distinct_matrices_for_distinct_urls(): void
    {
        $service = app(QrCodeService::class);

        $this->assertNotSame($service->png("https://ejemplo.com/a", 300), $service->png("https://ejemplo.com/b", 300));
    }
}
