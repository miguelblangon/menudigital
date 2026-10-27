# Estado del proyecto

Trazabilidad de cada requisito de `especificaciones.md` contra el código real y el
test que lo fija. Todo lo afirmado aquí está verificado con
`php artisan route:list`, `php artisan test` y lectura de los ficheros citados.

Estado del 2026-10-27: **123 tests · 499 aserciones · 0 fallos**, Pint sin incidencias
y PHPStan (larastan, nivel 5) sin errores. 43 rutas registradas.

Leyenda: ✅ verificado con test · ✅(v) verificado por lectura del código · 🟡 hecho con aviso · ⏳ pendiente.

## Landing page (carta pública, `/`)

| Requisito | Estado | Dónde | Test |
|---|---|---|---|
| Contiene productos, categorías y alérgenos | ✅ | `MenuController@index`, `resources/views/menu.blade.php` | `MenuControllerTest` (9) |
| Productos mostrados de forma responsive y adaptada a móvil | ✅ | `components/menu-product-card.blade.php`, ver [MOVIL.md](MOVIL.md) | `ResponsiveLayoutTest` |
| Un producto pertenece a una categoría | ✅ | `Product::category()` (`belongsTo`), FK `category_id` | `ProductControllerTest` |
| Un producto puede tener varios alérgenos | ✅ | `Product::allergens()` (`belongsToMany`), tabla `allergen_product` | `ProductControllerTest` (sync) |
| Filtrar por alérgenos excluyendo los productos que los llevan, sin recargar | ✅ | filtro servidor en `MenuController` (`whereDoesntHave`) + Alpine en `resources/js/app.js` | `MenuControllerTest` |
| Filtrar por categoría al pulsar, sin recargar | ✅ | chips de `components/menu-categories.blade.php` + `filterByCategory()` en Alpine | `MenuControllerTest` |

Matiz importante: el filtro funciona **también sin JavaScript**. El servidor aplica
`?category=…&allergens[]=…&max_price=…`, así que el enlace del QR o una carta
compartida llegan ya filtradas y el estado inicial de Alpine coincide con lo
devuelto. Un fallo de red en el local no deja la carta inservible.

## Gestión (`/admin`, autenticada)

| Requisito | Estado | Dónde | Test |
|---|---|---|---|
| CRUD de productos | ✅ | `Admin\ProductController` + `Route::resource` en `routes/web.php` | `ProductControllerTest` (28) |
| Filtrar el listado del panel por nombre y por categoría, sin recargar | ✅ | `filterState()`/`filteredProducts()` en `Admin\ProductController`; ruta `admin/products/filters`; partial `admin/products/partials/list.blade.php` y Alpine `productFilters()` en `resources/js/app.js` | `ProductControllerTest` (13 de sus 28) |
| CRUD de categorías | ✅ | `Admin\CategoryController` (slug único con sufijo, `withCount`) | `CategoryControllerTest` (9) |
| CRUD de alérgenos | ✅ | `Admin\AllergenController` (nombre único, `detach()` al borrar) | `AllergenControllerTest` (13) |
| Enlazar categoría ↔ producto para que filtre | ✅ | `category_id` con `Rule::exists`; `MenuController` ordena por `categories.order` | `ProductControllerTest` |
| Asignar alérgenos ↔ producto para que filtre | ✅ | `allergens()->sync()` en `store`/`update` | `ProductControllerTest` |
| Gestión responsive, pensada para móvil | ✅ | `layouts/admin.blade.php` (drawer) + tarjetas en móvil y tabla en `sm:` | `ResponsiveLayoutTest` (9) |
| QR de la URL de la landing | ✅ | `Admin\QrController` (`index`, `generate`, `download`) + `App\Services\QrCodeService` | `QrControllerTest` (5) |

La especificación pedía «CRUD de Qr». No hay CRUD: el QR **no se guarda en base de
datos**, se genera contra `url('/')` en cada petición (SVG en línea y PNG
rasterizado con GD). No hay nada que editar ni borrar, así que las tres acciones
(`index`/`generate`/`download`) cubren el requisito.

## Seguridad

| Requisito | Estado | Dónde | Test |
|---|---|---|---|
| Sólo el administrador accede | ✅ | `Route::middleware([auth])` sobre los tres `Route::resource` de `/admin` y sobre `admin/products/filters` | `AdminAccessTest` (4): las siete acciones de cada CRUD más el fragmento de filtros, sin sesión, redirigen a `/login` y no escriben en BD |
| No hay registro público | ✅(v) | `route:list` muestra 43 rutas y **ninguna `/register`**; no existe `RegisterController` | — |
| Un único usuario, no pensado para múltiples usuarios | ✅(v) | `AdminUserSeeder` crea `admin@menudigital.com`; no hay gestión de usuarios | `ProfileTest` (5) |
| Bloqueo por intentos fallidos (fuerza bruta) | ✅ | `LoginRequest::ensureIsNotRateLimited()`: 5 intentos por clave `email|ip` | `LoginThrottleTest` (3) |
| Auditoría de amenazas más comunes | ✅ | [PROGRESO-AUDITORIA.md](PROGRESO-AUDITORIA.md), bloque a bloque | `SecurityHeadersTest` (11) |
| Cabeceras de seguridad (CSP, X-Frame-Options, nosniff, HSTS…) | ✅ | `app/Http/Middleware/SecurityHeaders.php`, `config/security.php` | `SecurityHeadersTest` |
| Contraseñas seguras y rehash | ✅(v) | driver resuelto: `bcrypt` con 12 rondas y `rehash_on_login` (valores por defecto del framework: `config/hashing.php` **no está publicado**) | `PasswordUpdateTest` (2) |
| **Doble factor (2FA) en la autenticación** | ⏳ **PENDIENTE** | no existe: sin TOTP, sin columnas de factores. Breeze trae verificación de email (`verify-email`), que **no es 2FA** | — |

## Lo que queda pendiente

1. **2FA**: es el único requisito funcional de `especificaciones.md` sin
   implementar. Exige decisión de producto (TOTP con app de autenticador, con
   flujos de recuperación de códigos, frente a una alternativa más ligera). Por la
   regla de «preguntar antes de cambiar el comportamiento de producto» no se ha
   hecho a iniciativa propia.
2. **Seeders sin test** (`DatabaseSeeder`, `CategorySeeder`, `AlergenSeeder`,
   `ProductSeeder`): un cambio de esquema los rompe en silencio. `AdminUserSeeder`
   sí está cubierto, por `AdminUserSeederTest` (7 tests · 21 aserciones).
3. **Datos sembrados sin acentos** (`Alergen*`, `Cafe`, `Descafeinado`,
   `Sin Lactosa`) mientras la interfaz los muestra con acento. El filtro de
   alérgenos compara nombres en minúscula, así que renombrar `Gluten` → `Glúten`
   desincroniza el filtro del cliente con los nombres ya guardados en los productos.
4. **`config/cors.php` publicado con `allowed_origins => ['*']`**: hoy es inerte
   (no hay rutas `api/*`), pero es un riesgo latente si se añade una API.
5. **Un solo idioma**: los textos propios están en español y `__(…)` sólo aparece
   en las vistas de Breeze; no hay ficheros de idioma del proyecto.

## Deuda conocida y por qué no se ha tocado

- `endroid/qr-code` v6 necesita `imagick` para su `PngWriter`; este host sólo tiene
  `gd`, así que el PNG se rasteriza a mano en `QrCodeService::matrixToPng()`.
  Funciona y está testeado, pero si algún día hay Imagick, sustituirlo es un cambio
  de una línea.
- Las rutas `admin/*/show` existen por ser `Route::resource`, pero no hay vista
  dedicada enlazada en la navegación. Inofensivo; se pueden quitar si se prefiere
  una superficie menor.
- `tests/Feature/ExampleTest.php` y `tests/Unit/ExampleTest.php` son los dos test
  que trae Laravel por defecto (2 de los 123).
- Existe `database/seeders/MenuSeeder.php` **huérfano**: no está en la lista de
  `DatabaseSeeder` y ningún otro seeder lo invoca (`grep -rn MenuSeeder database/`
  sólo devuelve su propia declaración). Los datos de demostración los siembran
  `CategorySeeder`, `AlergenSeeder` y `ProductSeeder`. Detalle en [DESARROLLO.md](DESARROLLO.md).

## Cómo volver a verificar todo esto

```bash
php artisan route:list --except-vendor     # 43 rutas, ninguna /register
php artisan test                           # 123 passing · 499 aserciones
vendor/bin/pint --test                     # 0 incidencias de estilo
vendor/bin/phpstan analyse --no-progress   # 0 errores (nivel 5)
grep -rn "tooManyAttempts" app/Http        # umbral de fuerza bruta = 5 intentos
```

