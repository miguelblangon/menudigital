# MenúDigital

Carta digital para un restaurante: el comercio gestiona productos, categorías y
alérgenos desde un panel privado, y los clientes escanean un código QR de la mesa
para ver la carta en su móvil.

**No hay registro público.** Una única cuenta de administrador gestiona todo; el
visitante sólo lee la carta. Todo el entorno (carta y panel) está pensado para
usarse desde el teléfono: la especificación lo pide y el panel se maneja entero
con una mano.

## Stack

| Capa | Qué se usa |
|---|---|
| Backend | Laravel 13.35 · PHP 8.5.11 · SQLite (`database/database.sqlite`) |
| Front | Blade + Tailwind CSS 3.4 · Alpine.js · Vite |
| Autenticación | Laravel Breeze (sesión) · bloqueo por intentos fallidos |
| QR | `endroid/qr-code` v6 (SVG en línea + PNG rasterizado con GD) |
| Calidad | PHPUnit (feature tests) · Pint · PHPStan/larastan nivel 5 |

Tipografías, Alpine y favicon se sirven **desde el propio origen**: no hay CDNs,
y eso permite una `Content-Security-Policy` cerrada (`script-src 'self'`).

## Puesta en marcha

```bash
composer setup            # install + .env + key:generate + migrate + npm install + build
php artisan migrate:fresh --seed
composer dev              # server + queue + vite en un solo proceso
```

En producción: `npm run build` y `php artisan migrate --force`.

Credenciales sembradas en entorno de desarrollo (`database/seeders/AdminUserSeeder.php`):
`admin@menudigital.com` / `admin123`. **Cambiarlas antes de publicar.**

## Rutas

| Ruta | Acceso | Qué es |
|---|---|---|
| `/` | público | La carta, con filtros de categoría y de alérgenos |
| `/dashboard` | auth | Resumen del negocio |
| `/admin/products` | auth | CRUD de productos (imagen, precio, disponibilidad, alérgenos), con buscador por nombre y filtro por categoría |
| `/admin/categories` | auth | CRUD de categorías (slug automático, orden manual) |
| `/admin/allergens` | auth | CRUD de alérgenos |
| `/admin/qr` | auth | Código QR de la carta: ver, descargar PNG, imprimir |
| `/login`, `/logout` | guest/auth | Acceso del administrador (no existe `/register`) |

## Verificación

```bash
composer test                                                # 123 passing · 499 aserciones
vendor/bin/pint --test                                       # 0 incidencias de estilo
vendor/bin/phpstan analyse --no-progress --memory-limit=1G   # 0 errores (nivel 5)
```

## Documentación

Toda está en [`docs/`](docs/README.md):

- [`ARQUITECTURA.md`](docs/ARQUITECTURA.md) — modelo de datos, rutas, controladores y la carta con/sin JavaScript.
- [`MOVIL.md`](docs/MOVIL.md) — el criterio *mobile-first* y qué regla CSS o de marcado lo sostiene.
- [`PRUEBAS.md`](docs/PRUEBAS.md) — qué fija cada test y las puertas de calidad.
- [`DESARROLLO.md`](docs/DESARROLLO.md) — instalación, base de datos, *seeders* y despliegue.
- [`ESTADO.md`](docs/ESTADO.md) — trazabilidad requisito → implementación, con lo pendiente.
- [`PROGRESO-AUDITORIA.md`](docs/PROGRESO-AUDITORIA.md) — bitácora de la auditoría de seguridad.

Los requisitos originales viven en [`especificaciones.md`](especificaciones.md).
