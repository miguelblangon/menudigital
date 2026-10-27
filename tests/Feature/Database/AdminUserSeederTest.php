<?php

namespace Tests\Feature\Database;

use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Symfony\Component\Console\Formatter\OutputFormatter;
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
    private function seedViaConsole(): string
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
        $this->seedViaConsole();

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
        config(["security.admin.email" => ""]);

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

        // `db:seed` imprime su propia cabecera ("INFO Seeding database." y las
        // líneas RUNNING/DONE), así que la contraseña no es la segunda línea de
        // la salida: es la que va justo detrás del aviso del seeder.
        $lines = array_map("trim", explode("\n", $output));
        $headerIndex = array_key_first(array_filter(
            $lines,
            fn (string $line): bool => str_contains($line, "contraseña generada:")
        ));

        if ($headerIndex === null) {
            $this->fail("El seeder no avisó de la contraseña generada:\n".$output);
        }

        $generated = $lines[$headerIndex + 1];

        $this->assertGreaterThanOrEqual(32, strlen($generated));
        $this->assertTrue(Hash::check($generated, $user->password));
    }

    public function test_it_prints_the_generated_password_escaped_for_the_console(): void
    {
        // Str::password(symbols: true) incluye '<', '>' y '\'. Si la contraseña
        // generada se escribe en la consola sin escapar, el formatter de Symfony
        // interpreta esas secuencias como etiquetas de estilo: midiendo 20.000
        // contraseñas reales del pool, 409 (2%) salen corruptas. Como solo se
        // imprimen una vez, la cuenta nace inutilizable.
        $formatter = new OutputFormatter;

        foreach ([
            'K%bVZl0%93-BgXmY<&)eVT4QTKajPW~',
            'q?y#8g~sXZxmkVTD7w.1uGp^_qa68|X',
            '<info>Abcd1234</info>',
            'a\<b>c',
        ] as $password) {
            $printed = $formatter->format("<options=bold>".OutputFormatter::escape($password)."</>");

            $this->assertSame($password, $printed);
        }

        // Y el seeder usa exactamente ese escape: la prueba de arriba sería inútil
        // si el código volviera a interpolar la contraseña cruda.
        $seeder = file_get_contents(base_path("database/seeders/AdminUserSeeder.php"));

        $this->assertStringContainsString("OutputFormatter::escape(\$password)", $seeder);
        $this->assertStringNotContainsString("<options=bold>{\$password}</>", $seeder);
    }

    public function test_it_refuses_a_weak_password_in_production(): void
    {
        $this->app["env"] = "production";

        config(["security.admin.password" => "admin123"]);

        $this->expectException(RuntimeException::class);

        // `Artisan::call` no vale aquí: con env=production la consola responde a
        // su propio aviso "APPLICATION IN PRODUCTION" y, al no haber terminal,
        // cancela el comando ("Command cancelled") sin llegar al seeder. La
        // excepción debe propagarse llamando al seeder a pelo.
        $this->seedDirectly();
    }

    public function test_it_does_not_duplicate_the_admin_nor_reset_its_password(): void
    {
        $this->seedViaConsole();

        $original = User::where("email", "admin@local.test")->value("password");

        // Volver a sembrar (migrate:refresh --seed, un deploy...) no debe cambiar
        // una contraseña que el administrador ya pudo cambiar a mano.
        config(["security.admin.password" => "otra-contraseña-distinta-1234"]);
        Artisan::call("db:seed", ["--class" => AdminUserSeeder::class]);

        $this->assertSame(1, User::where("email", "admin@local.test")->count());
        $this->assertSame($original, User::where("email", "admin@local.test")->value("password"));
    }
}
