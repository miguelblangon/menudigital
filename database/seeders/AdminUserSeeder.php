<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Alta de la única cuenta del sistema (no hay registro público).
 *
 * Las credenciales ya no están escritas en este fichero: vive en config,
 * alimentado por ADMIN_NAME / ADMIN_EMAIL / ADMIN_PASSWORD del .env. La razón
 * es que este seeder está versionado y una contraseña incrustada queda para
 * siempre en el historial de git, incluso después de borrarla.
 */
class AdminUserSeeder extends Seeder
{
    /**
     * Contraseñas que se rechazan aunque cumplan la longitud: son las que
     * cualquiera probaría primero contra /login, y eran exactamente las que el
     * seeder llevaba escritas antes (admin@menudigital.com / admin123).
     *
     * @var list<string>
     */
    private const WEAK_PASSWORDS = [
        "admin",
        "admin123",
        "password",
        "password123",
        "contrasena",
        "contraseña",
        "menudigital",
        "12345678",
    ];

    public function run(): void
    {
        $name = trim((string) config("security.admin.name"));
        $email = trim((string) config("security.admin.email"));

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException(
                "ADMIN_EMAIL no está definido o no es un email válido. Añádelo al .env antes de sembrar: ADMIN_EMAIL=tu@email.com"
            );
        }

        if ($name === "") {
            $name = "Administrador";
        }

        if (User::where("email", $email)->exists()) {
            $this->command?->getOutput()
                ->writeln("<fg=yellow>El administrador {$email} ya existe: no se ha tocado su contraseña.</>");

            return;
        }

        $password = (string) config("security.admin.password");
        $generated = $password === "";

        if ($generated) {
            $password = Str::password(length: 32, symbols: true);
        } else {
            $this->assertPasswordIsAcceptable($password, $email);
        }

        // Asignación atributo a atributo, no User::create([...]): el modelo
        // declara #[Fillable(['name','email','password'])], así que un
        // mass-assignment de 'email_verified_at' se descartaría en silencio y
        // la cuenta nacería sin verificar (Breeze bloquea el panel sin verificar).
        $user = new User;
        $user->name = $name;
        $user->email = $email;
        // Hash::make explícito: el modelo tiene el cast 'hashed', que no duplica
        // el hasheo si el valor ya viene hasheado (Hash::isHashed), pero así el
        // alta funciona igual aunque ese cast se perdiera.
        $user->password = Hash::make($password);
        $user->email_verified_at = now();
        $user->save();

        if ($generated) {
            $this->command?->getOutput()->writeln("<fg=yellow>Administrador {$email} creado con contraseña generada:</>");
            $this->command?->getOutput()->writeln("<options=bold>{$password}</>");
            $this->command?->getOutput()->writeln("<fg=yellow>Se muestra UNA sola vez. Guárdala y cámbiala si quieres con: php artisan tinker</>");
        } else {
            $this->command?->getOutput()->writeln("<fg=green>Administrador {$email} creado con la contraseña de ADMIN_PASSWORD.</>");
        }
    }

    /**
     * Valida la contraseña antes de crear la cuenta.
     *
     * La lista negra se aplica siempre, también en local: son las contraseñas
     * que primero se prueban contra /login y eran exactamente las que este
     * seeder llevaba escritas. La longitud mínima, en cambio, sólo se exige con
     * `security.admin.enforce_strong_password` (activo en producción por defecto
     * o con ADMIN_REQUIRE_STRONG_PASSWORD=true), para no castigar a quien prueba
     * en local con una contraseña corta a propósito.
     */
    private function assertPasswordIsAcceptable(string $password, string $email): void
    {
        $normalized = mb_strtolower($password);

        if (in_array($normalized, self::WEAK_PASSWORDS, true)) {
            throw new RuntimeException(
                "ADMIN_PASSWORD es una contraseña conocida (aparece en las listas que usan los ataques de fuerza bruta). ".
                "Pon una frase larga o deja ADMIN_PASSWORD vacío para que el seeder genere una."
            );
        }

        // Que la contraseña no coincida con el propio usuario: filtraría en un
        // ataque de relleno de credenciales (credential stuffing).
        if ($normalized === mb_strtolower($email) || $normalized === mb_strtolower(explode("@", $email)[0])) {
            throw new RuntimeException("ADMIN_PASSWORD no puede coincidir con el email del administrador.");
        }

        if (! config("security.admin.enforce_strong_password")) {
            return;
        }

        $minLength = (int) config("security.admin.min_length");

        if (mb_strlen($password) < $minLength) {
            throw new RuntimeException(
                "ADMIN_PASSWORD es demasiado corta para producción: mínimo {$minLength} caracteres ".
                "(actual: ".mb_strlen($password)."). Ajusta ADMIN_PASSWORD_MIN_LENGTH si quieres otro umbral."
            );
        }
    }
}

