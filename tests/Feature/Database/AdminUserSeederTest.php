<?php

namespace Tests\Feature\Database;

use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Tests\TestCase;

/**
 * El administrador es la única puerta del sistema y antes sus credenciales
 * estaban escritas a mano en database/seeders/AdminUserSeeder.php (fichero
 * versionado → contraseña expuesta en el historial de git). Estos tests fijan
 * que ahora vienen del entorno y que el seeder se niega a crear cuentas flojas.
 */
class AdminUserSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            "security.admin.name" => "Administrador",
            "security.admin.email" => "admin@local.test",
            "security.admin.password" => "una-fase-larga-y-aleatoria-1",
            // En los tests el entorno no es producción: la política se fija a
            // mano en cada test para no depender de APP_ENV.
            "security.admin.enforce_strong_password" => false,
        ]);
    }

    /**
     * Sembrar vía consola: es el camino real (`db:seed`) y ejercita la rama
     * que imprime la contraseña generada por salida.
     */
    private function seed(): string
    {
        Artisan::call("db:seed", ["--class" => AdminUserSeeder::class]);

        return Artisan::output();
    }

    /**
     * Sembrar llamando al seeder a pelo. `Artisan::call` captura las excepciones
     * y las convierte en un exit code 1 (el kernel las renderiza), así que para
     * comprobar los rechazos hay que propagar la excepción directamente.
     */
    private function seedDirectly(): void
    {
        app(AdminUserSeeder::class)->run();
    }

    public function test_creates_the_administrator_from_the_environment_values(): void
    {
        $this->seed();

        $user = User::where("email", "admin@local.test")->first();

        $this->assertNotNull($user);
        $this->assertSame("Administrador", $user->name);
        $this->assertNotNull($user->email_verified_at);
        $this->assertTrue(Hash::check("una-fase-larga-y-aleatoria-1", $user->password));
        // Nunca se guarda la contraseña en claro.
        $this->assertStringNotContainsString("una-fase-larga", $user->password);
    }

    public function test_it_refuses_to_seed_without_a_valid_admin_email(): void
    {
        config(["security.admin.email" => ""];

        $this->expectException(RuntimeException::class);

        Artisan::call("db:seed", ["--class" => AdminUserSeeder::class]);
    }

    public function test_it_rejects_an_email_placeholder_left_in_the_env(): void
    {
        // El placeholder de .envProduccion no es un email: sembrar debe fallar.
        config(["security.admin.email" => "[CAMBIA-tu-email@tudominio.com]"]);

        $this->expectException(RuntimeException::class);

        Artisan::call("db:seed", ["--class" => AdminUserSeeder::class]);
    }

    public function test_it_generates_a_strong_password_when_none_is_configured(): void
    {
        config(["security.admin.password" => ""]);

        Artisan::call("db:seed", ["--class" => AdminUserSeeder::class]);
        $output = Artisan::output();

        $user = User::where("email", "admin@local.test")->first();
        $this->assertNotNull($user);

        // La contraseña generada se imprime una sola vez por consola.
        $this->assertMatchesRegularExpression("/[^\s]+/", $output);
        $generated = trim(explode("\n", trim($output))[1]);

        $this->assertGreaterThanOrEqual(32, strlen($generated));
        $this->assertTrue(Hash::check($generated, $user->password));
    }

    public function test_it_refuses_a_weak_password_in_production(): void
    {
        $this->app["env"] = "production";

        config(["security.admin.password" => "admin123"]);

        $this->expectException(RuntimeException::class);

        Artisan::call("db:seed", ["--class" => AdminUserSeeder::class]);
    }

    public function test_it_does_not_duplicate_the_admin_nor_reset_its_password(): void
    {
        $this->seed();

        $original = User::where("email", "admin@local.test")->value("password");

        // Volver a sembrar (migrate:refresh --seed, un deploy...) no debe cambiar
        // una contraseña que el administrador ya pudo cambiar a mano.
        config(["security.admin.password" => "otra-contraseña-distinta-1234"]);
        Artisan::call("db:seed", ["--class" => AdminUserSeeder::class]);

        $this->assertSame(1, User::where("email", "admin@local.test")->count());
        $this->assertSame($original, User::where("email", "admin@local.test")->value("password"));
    }
}
