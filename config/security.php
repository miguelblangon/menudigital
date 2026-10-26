<?php

/*
 * Cabeceras de seguridad. Se saca de Env para poder desplegar la
 * Content-Security-Policy en dos pasos: primero en modo informe (el navegador
 * anota en la consola lo que rompería, sin bloquear) y, revisado el panel y la
 * carta, ya en firme.
 */

return [
    "csp_report_only" => filter_var(env("CSP_REPORT_ONLY", false), FILTER_VALIDATE_BOOLEAN),

    /*
     * Credenciales con las que `AdminUserSeeder` crea la única cuenta del
     * sistema. Estaban escritas a mano en database/seeders/AdminUserSeeder.php:
     * ese fichero está versionado, así que la contraseña quedaba legible para
     * siempre en el historial de git. Aquí sólo hay claves; el valor real viaja
     * en el .env (ignorado) y en producción se exige explícito.
     *
     * ADMIN_PASSWORD vacío ⇒ el seeder genera uno fuerte y lo imprime una vez.
     */
    "admin" => [
        "name" => (string) env("ADMIN_NAME", "Administrador"),
        "email" => (string) env("ADMIN_EMAIL", ""),
        "password" => (string) env("ADMIN_PASSWORD", ""),

        /*
         * Política del seeder. `enforce_strong_password` se activa solo cuando
         * el entorno es producción, pero se puede forzar en local con
         * ADMIN_REQUIRE_STRONG_PASSWORD=true para probar el despliegue sin
         * cambiar APP_ENV.
         */
        "enforce_strong_password" => filter_var(
            env("ADMIN_REQUIRE_STRONG_PASSWORD", env("APP_ENV", "production") === "production"),
            FILTER_VALIDATE_BOOLEAN
        ),
        "min_length" => (int) env("ADMIN_PASSWORD_MIN_LENGTH", 16),
    ],
];
