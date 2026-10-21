<?php

/*
 * Cabeceras de seguridad. Se saca de Env para poder desplegar la
 * Content-Security-Policy en dos pasos: primero en modo informe (el navegador
 * anota en la consola lo que rompería, sin bloquear) y, revisado el panel y la
 * carta, ya en firme.
 */

return [
    "csp_report_only" => filter_var(env("CSP_REPORT_ONLY", false), FILTER_VALIDATE_BOOLEAN),
];
