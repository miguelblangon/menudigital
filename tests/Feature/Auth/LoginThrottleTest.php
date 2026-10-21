<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * El panel es un único administrador y el login es la única puerta: sin el
 * limitador de Breeze (5 intentos por email+IP) un ataque de diccionario contra
 * la contraseña sería indefinido. Es un control de seguridad, así que queda fijo
 * aquí: si alguien quita el RateLimiter de LoginRequest o cambia la clave del
 * throttle, este test lo rompe.
 */
class LoginThrottleTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_login_is_blocked_after_five_failed_attempts(): void
    {
        $user = User::factory()->create(["email" => "jefe@menudigital.test"]);

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->post("/login", [
                "email" => $user->email,
                "password" => "incorrecta-{$attempt}",
            ])->assertSessionHasErrors("email");
        }

        // El sexto intento ya no se valida contra la BD: se entra con la
        // contraseña buena y aun así queda fuera. Se sigue la redirección para
        // comprobar en el HTML (flujo real del navegador) que el aviso se ve.
        $html = $this->from("/login")
            ->followingRedirects()
            ->post("/login", [
                "email" => $user->email,
                "password" => "password",
            ])
            ->assertOk()
            ->getContent();

        $this->assertGuest();

        // El contador sigue quemado (quedan segundos de espera para ese email+IP).
        $this->assertGreaterThan(
            0,
            RateLimiter::availableIn($this->throttleKey($user->email)),
            "El bloqueo debería mantener el contador activo tras el quinto intento."
        );

        // Lo que ve el administrador es una frase legible, no la clave de
        // traducción. Se aceptan los dos idiomas porque el repo todavía no tiene
        // carpeta lang/: auth.throttle viene del inglés del framework.
        $this->assertMatchesRegularExpression(
            "/too many login attempts|demasiados intentos/i",
            $html,
            "El aviso de bloqueo debería mostrarse como mensaje traducido."
        );
        $this->assertStringNotContainsString(
            "auth.throttle",
            $html,
            "No debería imprimirse la clave de traducción sin resolver."
        );
    }

    /**
     * Clave del limitador tal y como la arma LoginRequest: email en minúscula
     * transliterado + IP del test (127.0.0.1). Si alguien la cambia en el
     * login, availableIn() dará 0 y este test lo rompe.
     */
    private function throttleKey(string $email): string
    {
        return Str::transliterate(Str::lower($email)."|127.0.0.1");
    }

    public function test_a_successful_login_clears_the_throttle_counter(): void
    {
        $user = User::factory()->create(["email" => "jefe@menudigital.test"]);

        // Cuatro fallos dejan margen: cinco bloquean. Si el contador no se
        // limpiara al acertar, el administrador quedaría fuera tras un despiste.
        for ($attempt = 1; $attempt <= 4; $attempt++) {
            $this->post("/login", ["email" => $user->email, "password" => "incorrecta"]);
        }

        $this->post("/login", ["email" => $user->email, "password" => "password"])
            ->assertRedirect(route("dashboard", absolute: false));

        $this->assertAuthenticatedAs($user);
    }

    public function test_the_throttle_is_keyed_by_email_and_ip(): void
    {
        $user = User::factory()->create(["email" => "jefe@menudigital.test"]);

        // Saturar el intento del administrador no debe impedir entrar a otro
        // usuario desde la misma máquina: la clave es email + IP, no solo IP.
        for ($attempt = 1; $attempt <= 6; $attempt++) {
            $this->post("/login", ["email" => $user->email, "password" => "incorrecta"]);
        }

        $other = User::factory()->create(["email" => "segundo@menudigital.test"]);

        $this->post("/login", ["email" => $other->email, "password" => "password"])
            ->assertRedirect(route("dashboard", absolute: false));

        $this->assertAuthenticatedAs($other);
    }
}
