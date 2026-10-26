# Desarrollo

Cómo arrancar el proyecto en una máquina nueva, qué comandos se usan y qué hay que
tocar antes de publicar. Ver [PRUEBAS.md](PRUEBAS.md) para la suite y
[ARQUITECTURA.md](ARQUITECTURA.md) para el código.

## Requisitos

| Requisito | Versión / detalle |
|---|---|
| PHP | **8.5.11** (el proyecto está probado sobre esta versión) |
| Extensiones PHP | `pdo_sqlite`, `sqlite3`, **`gd`** (rasteriza el QR), `mbstring`, `openssl`, `fileinfo`. **`imagick` no hace falta**: el `PngWriter` de `endroid/qr-code` v6 depende de Imagick y aquí el PNG se rasteriza a mano con GD |
| Composer | 2.x |
| Node / npm | Node 20+ (Vite 8) |
| Base de datos | SQLite (`database/database.sqlite`), sin servidor externo |

## Instalación

```bash
composer setup          # install + .env + key:generate + migrate + npm install + npm run build
touch database/database.sqlite
php artisan migrate:fresh --seed
php artisan storage:link          # imprescindible para ver las imágenes subidas
```

`composer setup` está definido en `composer.json` y hace, en orden: `composer install`,
copiar `.env.example` a `.env` si no existe, `key:generate`, `migrate --force`,
`npm install --ignore-scripts` y `npm run build`.

### `storage:link` no es opcional

Las imágenes se guardan en el disco `public` (`$request->file("image")->store("products", "public")`),
es decir en `storage/app/public/products/`. Las vistas las piden por
`asset("storage/…")`, que sólo funciona si existe el enlace `public/storage` →
`storage/app/public`. Sin ese enlace el panel sube la imagen correctamente pero la
carta muestra el *placeholder*.

## Base de datos

```bash
php artisan migrate          # 10 migraciones
php artisan migrate:fresh --seed
php artisan migrate:status
```

| Migración | Qué hace |
|---|---|
| `0001_01_01_000000_create_users_table` | usuarios, sesiones y *password reset tokens* |
| `0001_01_01_000001_create_cache_table` | caché en BD (`CACHE_STORE=database`) |
| `0001_01_01_000002_create_jobs_table` | colas en BD |
| `2026_10_12_210038_create_categories_table` | `name`, `slug` unique, `order` |
| `2026_10_12_210041_create_products_table` | producto con FK a categoría (`cascade`) |
| `2026_10_15_173225_create_allergens_table` | alérgenos |
| `2026_10_15_174049_create_product_alergen_table` | tabla intermedia antigua (obsoleta) |
| `20261017_120000_create_allergen_product_table` | intermedia correcta para `belongsToMany` |
| `2026_10_17_120001_drop_product_alergen_table` | borra la intermedia antigua |
| `2026_10_21_100000_rename_is_available_to_available_on_products_table` | unifica el nombre de la columna de disponibilidad |

## Seeders y credenciales de desarrollo

`php artisan db:seed` ejecuta `DatabaseSeeder`, que llama a:

1. `AdminUserSeeder` — crea el administrador (idempotente: no duplica si el email ya existe).
2. `CategorySeeder` — categorías de ejemplo con `order`.
3. `AlergenSeeder` — alérgenos (el nombre del fichero mantiene el typo histórico `Alergen`).
4. `ProductSeeder` — productos de ejemplo con alérgenos asociados.

**Credenciales que siembra el seeder:**

```
email:    admin@menudigital.com
password: admin123
```

> ⚠️ **Cambiarlas antes de publicar.** `especificaciones.md` dice que sólo hay un
> administrador y que no existe registro público, así que esa contraseña es la
> única puerta al negocio.

Cómo cambiarla de forma segura (hash bcrypt con las 12 rondas del `.env`):

```bash
php artisan tinker
>>> \App\Models\User::where('email','admin@menudigital.com')
        ->update(['password' => \Hash::make('LA-CONTRASENA-NUEVA')]);
```

**Nota:** existe un `MenuSeeder` que **no** está en la lista de `DatabaseSeeder`
(quedó sustituido por `CategorySeeder` + `AlergenSeeder` + `ProductSeeder`). Se
puede ejecutar suelto con `php artisan db:seed --class=MenuSeeder`, pero no es el
flujo normal y puede duplicar datos.

## Comandos diarios

```bash
composer dev                  # artisan serve + queue + vite en un solo proceso
npm run dev                   # sólo Vite
npm run build                 # bundle de producción → public/build
php artisan test              # 103 tests
composer test                 # config:clear + artisan test
vendor/bin/pint               # corrige el estilo
vendor/bin/pint --test        # sólo informa
vendor/bin/phpstan analyse --no-progress --memory-limit=1G
php artisan route:list --except-vendor
```

Tailwind se compila con **PostCSS** (`postcss.config.js` → `tailwindcss` +
`autoprefixer`), no con el plugin de Vite de v4. Si se añade una clase
arbitraria nueva (`pt-[env(…)]`) hay que recargar el build para que exista.


## Antes de publicar (producción)

El `.env` de desarrollo trae valores que **no** deben salir a internet:

```
APP_ENV=local        →  production
APP_DEBUG=true       →  false          (un 500 filtraría la traza y el .env)
APP_NAME=Laravel     →  el nombre real del local (aparece en el título de la carta)
APP_URL=http://localhost:8000  →  el dominio público
```

`APP_URL` no es decorativo: **el código QR se genera con `url("/")`**
(`Admin\QrController`), así que el valor impreso en las mesas sale de ahí. Si se
deja en `localhost:8000`, el QR escaneado en el local no lleva a ningún sitio.

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan storage:link
npm run build
```

Sesiones, colas y caché van a la base de datos (`SESSION_DRIVER=database`,
`CACHE_STORE=database`): con SQLite en producción hay que vigilar el crecimiento
de las tablas `sessions` y `jobs` y limpiarlas (`php artisan model:prune` o un
cron).

HTTPS: `SecurityHeaders` sólo declara `Strict-Transport-Security` y
`upgrade-insecure-requests` cuando la petición es `secure()`, así que el
certificado no es opcional si se quiere HSTS. Detrás de un proxy (nginx,
Cloudflare) hay que configurar las *proxies* de confianza para que Laravel vea
la petición como HTTPS real.

Dos variables propias del proyecto:

| Variable | Efecto |
|---|---|
| `CSP_REPORT_ONLY=true` | la Content-Security-Policy pasa a `Content-Security-Policy-Report-Only`: informa sin bloquear. Sirve la primera semana de despliegue para ver si alguna vista rompe la política (`config/security.php`) |
| `DB_CONNECTION=sqlite` | pasar a `mysql`/`pgsql` obliga a revisar la migración de renombrado de columna, que usa `RENAME COLUMN` |

## Verificación en el teléfono

Antes de dar por bueno un cambio de interfaz, abrir en un teléfono real (no sólo
el simulador del navegador):

1. `/` desde el QR → la carta debe caber sin desplazamiento horizontal.
2. Enfocar un `<input>` del panel con iOS: no debe hacer zoom automático.
3. Abrir el *drawer* (`☰`) y cerrar sesión desde él.
4. El filtro de alérgenos debe aparecer como *bottom sheet* y sus botones han de
   quedar visibles con la barra de direcciones del navegador desplegada.

Los detalles que sostienen estos cuatro puntos están en [MOVIL.md](MOVIL.md).
