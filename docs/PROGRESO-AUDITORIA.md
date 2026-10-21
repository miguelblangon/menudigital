# MenúDigital — Bitácora de auditoría y correcciones

Fecha: 2026-10-21 (última actualización al cerrar la auditoría) · Stack: Laravel 13 · PHP 8.5.11 · SQLite (`database/database.sqlite`) · Blade + Vite (Tailwind, Alpine y tipografías **autohospedados, sin CDNs**)

Herramientas de control añadidas en la auditoría: `vendor/bin/pint` (`pint.json`), `vendor/bin/phpstan` con larastan (`phpstan.neon`, nivel 5) y `php artisan test`.

Referencia de requisitos: `especificaciones.md` (raíz del proyecto).

> Documento vivo: cada bloque indica **qué se detectó**, **qué se hizo**, **ficheros tocados** y **cómo verificarlo**.
> Estado de cada bloque: ✅ cerrado · 🟡 cerrado con aviso/deuda · ⏳ pendiente.

## Resumen

| # | Bloque | Estado |
|---|--------|--------|
| 1 | QR: API roto de `endroid/qr-code` v6 + falta de `imagick` | ✅ |
| 2 | Disponibilidad del producto: columna `is_available` vs `available` | ✅ |
| 3 | Alérgenos: controlador con nombre roto en rutas + CRUD inconsistente | ✅ |
| 4 | Cabeceras de seguridad HTTP (incluida Content-Security-Policy) | ✅ |
| 5 | Carta pública: filtros solo de cliente, sin degradación sin JS | ✅ |
| 6 | Categorías: borrado en cascada peligroso + colisiones de slug | ✅ |
| 7 | Suite de pruebas (feature tests) | 🟡 |
| 8 | Extensiones PHP (`ext-imagick`, `ext-iconv`) y deuda técnica | ⏳ |
| 9 | Assets sin CDNs (Alpine + tipografías) y CSP cerrada | ✅ |
| 10 | `@json` de Blade truncaba el estado inicial de Alpine | ✅ |
| 11 | Estilo (Pint) y análisis estático (PHPStan + larastan) | ✅ |
| 12 | Decisiones pendientes del usuario | ⏳ |

---

## 1 · QR: la API de `endroid/qr-code` v6 ya no es la que usaba el controlador ✅

**Síntoma real (no un detalle estético):** el `Admin\QrController` llamaba a `QrCode::create($text)->setEncoding(...)->setErrorCorrection(...)` y luego `(new PngWriter())->write($qrCode)->getDataAsString()`.
En `endroid/qr-code` v6 eso ya no existe: `QrCode` es `final readonly`, **sin `create()` ni setters**, y la matriz de módulos que devuelve el `SvgWriter` es de solo lectura. Es decir, la página `/admin/qr` reventaba.

**Qué se hizo**

- Nuevo servicio `app/Services/QrCodeService.php` (único punto de acceso al generador):
  - Construye el `QrCode` con argumentos nombrales (`data:, encoding:, errorCorrection:, size:, margin:`), como exige v6.
  - Devuelve el SVG vía `SvgWriter`, y **el PNG se rasteriza a mano con GD** recorriendo la matriz de módulos.
  - Limita el tamaño de salida (`MAX_PNG_PIXELS = 1600`) y normaliza el destino del QR a `config('app.url')`, para que el QR apunte siempre al host público configurado y no a un valor introducido por el usuario.
- Reescrito `app/Http/Controllers/Admin/QrController.php` sobre el servicio: `index()` (SVG embebido), `generate()` (`image/svg+xml`) y `download()` (PNG adjunto). Se borró el controlador antiguo duplicado/roto.

**Por qué el PNG a mano:** este host **no tiene `ext-imagick`** y el `PngWriter` de v6 depende de Imagick (no de GD). Se podría instalar `ext-imagick`, pero se prefirió no añadir binarios del sistema sin pedirlo: la rasterización con GD queda encapsulada en el servicio y es trivial sustituirla por `PngWriter` cuando Imagick esté disponible.

**Verificación**

```bash
php artisan test --filter=QrControllerTest
```

- `/admin/qr` responde 200 y embebe `<svg …>`.
- `/admin/qr/generate` → `Content-Type: image/svg+xml`, cuerpo con `<svg`.
- `/admin/qr/download` → `image/png`, `Content-Disposition: attachment; filename="menu-qr.png"` y cabecera mágica `\x89PNG\r\n\x1a\n` (no es un SVG disfrazado de PNG).
- Invitado → redirigido a `/login`.

**Ficheros:** `app/Services/QrCodeService.php`, `app/Http/Controllers/Admin/QrController.php`, `resources/views/admin/qr/index.blade.php`, `tests/Feature/Admin/QrControllerTest.php`.

---

## 2 · Disponibilidad: la base de datos decía `is_available` y el código esperaba `available` ✅

**Problema:** la columna en BD era `is_available`, el código y las vistas usaban `available`, y en medio había un accesor/mutador que enmascaraba la diferencia. Resultado: toggles de disponibilidad que no guardaban y queries que podían fallar.

**Qué se hizo**

- Migración `database/migrations/2026_10_21_100000_rename_is_available_to_available_on_products_table.php`: `renameColumn('products', 'is_available', 'available')` (SQLite ≥ 3.25 soporta `RENAME COLUMN`; la migración está probada con `migrate:fresh`).
- `app/Models/Product.php`: fuera el accesor/mutador engañoso; stays en `$casts` (`available => boolean`), scope `available()`/`scopeAvailable()` coherente y `$fillable` con `available`.
- `app/Http/Controllers/Admin/ProductController.php`: validación `'available' => ['nullable','boolean']` y normalización del checkbox de HTML (si no se envía, `false`).
- Vistas admin `products/index`, `products/create`, `products/edit`: columna y campos usando `available` (checkbox con input oculto `0` previo, para que “desmarcado” signifique realmente “no disponible”).

**Verificación**

```bash
php artisan migrate:fresh --seed
php artisan test --filter=ProductControllerTest
```

Además, `MenuController` ahora usa el scope `available()`: **un producto no disponible no aparece en la carta pública** (test `test_hides_products_that_are_not_available`).

**Ficheros:** migración citada, `app/Models/Product.php`, `app/Http/Controllers/Admin/ProductController.php`, `resources/views/admin/products/{index,create,edit}.blade.php`, `tests/Feature/Admin/ProductControllerTest.php`.

---

## 3 · Alérgenos: la ruta apuntaba a un controlador que no existía con ese nombre ✅

**Problema:** `routes/web.php` registraba `AlergenController` (falta la “a” de *allergen*), mientras el modelo es `Allergen`. Además el CRUD estaba duplicado/inconsistente (el controlador antiguo mezclaba `product_alergen`, una tabla pivote ya eliminada por `2026_10_17_120001_drop_product_alergen_table`).

**Qué se hizo**

- `app/Http/Controllers/Admin/AllergenController.php` (nombre coherente con el modelo) con CRUD completo:
  - `index` con `withCount("products")` para ver el impacto antes de borrar,
  - `store`/`update` validan `unique:allergens,name` (y `unique:...,id` al editar),
  - `destroy` hace `detach()` de la pivote `allergen_product` y luego borra (sin dejar huérfanos).
- `routes/web.php`: `use App\Http\Controllers\Admin\AllergenController;` y `Route::resource("allergens", AllergenController::class)` dentro del grupo `admin` con `auth`.
- Controlador antiguo eliminado.

**Nota de consistencia:** los *seeders* siguen llamándose `AlergenSeeder` (igual que la pivote antigua). Es cosmético y no afecta al runtime; renombrarlo toca `DatabaseSeeder`, así que se deja como deuda de bajo nivel (bloque 8).

---

## 4 · Cabeceras de seguridad HTTP ✅

**Qué se añadió** — `app/Http/Middleware/SecurityHeaders.php`, registrado en `bootstrap/app.php` (`$middleware->web(append: [...])`, o sea, en toda la capa `web`):

| Cabecera | Valor | Motivo |
|---|---|---|
| `X-Frame-Options` | `DENY` | el panel no debe poder embeberse (clickjacking) |
| `X-Content-Type-Options` | `nosniff` | evita que el navegador infiera tipos en las imágenes subidas |
| `Referrer-Policy` | `strict-origin-when-cross-origin` | no filtra rutas del negocio a terceros |
| `Permissions-Policy` | `camera=(), microphone=(), geolocation=()` | la app no usa ninguno de esos permisos |
| `Cache-Control` | `no-store, max-age=0` solo en `admin/*`, `dashboard`, `profile*` | el histórico del navegador no conserva datos del negocio; la carta pública sí puede cachearse |

| `Content-Security-Policy` | `default-src 'self'; script-src 'self' 'unsafe-eval'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self'; connect-src 'self'; form-action 'self'; frame-ancestors 'none'; base-uri 'self'; object-src 'none'` | corta de raíz un XSS: ni un `<script>` inline ni orígenes externos. `'unsafe-eval'` es obligatorio porque Alpine compila sus expresiones con `new Function()`; **no** reabre la puerta a HTML inyectado, porque sigue sin haber `'unsafe-inline'` |
| `Strict-Transport-Security` | `max-age=31536000; includeSubDomains` solo si la petición es HTTPS | prometer HSTS en una respuesta HTTP forzaría al navegador a un protocolo que el host puede no tener |

Detalles de la implementación, que no son obvios leyendo el código:

- **El valor de la CSP está en `config/security.php`**, con un interruptor `CSP_REPORT_ONLY` (`Content-Security-Policy-Report-Only`) para poder desplegarla en dos pasos: primero el navegador avisa de lo que rompería, después se aplica en firme.
- **`SecurityHeaders::apply()` es pública a propósito** y está registrada en dos sitios: en el pipeline `web` (`bootstrap/app.php`, `$middleware->web(append: ...)`) y en el *handler* de excepciones. Las respuestas que nacen fuera del pipeline (404, la 302 de invitado, el 419 de CSRF caducado, los 500) nunca pasan por `handle()`; sin ese segundo registro se quedaban sin cabeceras.
- **`Cache-Control` exige `remove()` previo** en `admin/*`, `dashboard`, `profile*`, `login` y `password`: Symfony guarda las directivas en un array interno y las fusiona, así que un `set()` a pelo acababa en `max-age=0, no-store, private`.
- En local, si existe `public/hot` se añaden a la CSP la URL del dev server de Vite y su websocket; en producción ese fichero no existe y la política queda cerrada.

**Verificación**

```bash
php artisan test --filter=SecurityHeadersTest      # cabeceras, CSP, no-store y el caso 404
php -S 127.0.0.1:8000 -t public                    # y luego:
curl -sI localhost:8000/                            # carta pública
curl -sI localhost:8000/admin/products              # invitado: 302 a /login, con cabeceras
```

---

## 5 · Carta pública: los filtros existían solo en el navegador ✅

**Problema:** el filtrado de categorías/alérgenos/precio se hacía únicamente con Alpine. Con JS desactivado (o con un enlace compartido desde el QR) la carta mostraba todo y el estado inicial del cliente no coincidía con lo renderizado.

**Qué se hizo**

- `app/Http/Controllers/MenuController.php` filtra **en el servidor** y pasa el estado al HTML:
  - `category` (slug) → si el slug no existe, **404** explícito en vez de ignorar el filtro (enlaces rotos compartidos deben ser visibles);
  - `allergens[]` → se purgan contra BD (`selectedAllergenIds()`), de modo que un id inventado no altera la consulta;
  - exclusión con `whereDoesntHave("allergens", … whereIn …)` — la forma correcta de decir “sin NINGUNO de estos alérgenos”;
  - `max_price` solo si es numérico y > 0;
  - `selectedAllergenValues` (nombres en minúscula) porque el filtro cliente trabaja con nombres y el servidor con ids: se hidratan los dos para que el estado inicial cuadre con los productos ya devueltos;
  - orden: primero `categories.order` (subconsulta correlacionada, sin `join`) y luego `name`.
- `resources/views/menu.blade.php`: bloque `<noscript>` con los chips de categoría y de alérgenos como enlaces GET (`$categoryUrl` / `$allergenUrl` conservan los demás parámetros de la query) y la lista de productos también dentro de `<noscript>`, para que la carta se pueda leer y filtrar sin JavaScript.
- Los componentes de siempre (`components/menu-categories.blade.php`, `components/menu-allergen-filter.blade.php`, `components/menu-product-card.blade.php`) siguen siendo Alpine con `@click`: **el requisito del spec de filtrar “sin recargar la página” se mantiene intacto**; los enlaces son solo la degradación sin JS.
- `app/Models/Category.php`: añadido `order` a `$fillable` (la columna existía y se usaba en `ORDER BY`, pero no se podía definir desde `create()/update()`).

**Verificación:** `php artisan test --filter=MenuControllerTest` (8 casos: disponibilidad, categoría, alérgenos, ids inexistentes, precio, orden, 404, acceso público) y probar `/` con JS desactivado en el navegador.

---

## 6 · Categorías: borrar una categoría se llevaba los productos por delante ✅

**Problema:** la FK de `products.category_id` está definida `on delete cascade`. Borrar una categoría desde el admin destruía sus productos (y sus registros de imagen) sin aviso.

**Qué se hizo**

- `Admin\CategoryController::destroy()` bloquea el borrado si `products()->count() > 0` y devuelve un mensaje explicativo (`withErrors(["category" => …])`), que el layout muestra en el bloque de errores de `resources/views/layouts/admin.blade.php`.
- `uniqueSlug()` en `store`/`update`: los slugs de categoría son la URL pública (`?category=…`), así que dos nombres que colisionen (`Café` / `Cafe`) generan `cafe` y `cafe-2` en vez de romper el enlace o provocar un UNIQUE violation.

**Verificación:** `php artisan test --filter=CategoryControllerTest` (incluye borrado bloqueado y colisión de slug).

---

## 7 · Suite de pruebas · 🟡 en verde, cobertura mejorable

```
php artisan test   →   Tests: 89 passing · Assertions: 343 · Duration ~4,5 s   (21/10/2026, al cerrar la auditoría)
vendor/bin/phpstan analyse --memory-limit=1G   →   0 errores (nivel 5, larastan)
vendor/bin/pint --test                         →   0 incidencias de estilo
```

### Qué se añadió / cambió
| Test file | Qué cubre |
|---|---|
| `tests/Feature/MenuControllerTest.php` (nuevo) | Carta pública sin autenticar, productos no disponibles ocultos, filtro por categoría, filtro por alérgenos (excluye los que llevan alguno), alérgenos inexistentes se ignoran, filtro `max_price`, orden por `categories.order` + nombre, y 404 si el slug de categoría no existe |
| `tests/Feature/Admin/QrControllerTest.php` (nuevo) | Invitado no entra al panel QR, el index embebe un SVG, `POST /qr/generate` devuelve `Content-Type: image/svg+xml`, `POST /qr/download` devuelve un PNG real (magic bytes `\x89PNG…`) con `attachment; filename="menu-qr.png"`, y la matriz cambia cuando cambia la URL |
| `tests/Feature/Admin/ProductControllerTest.php` | Cambiado de `is_available` a `available` (que es el nombre real del campo); nuevo caso de producto no disponible y nuevo caso de `allergens[]` con id inexistente |
| `tests/Feature/Admin/CategoryControllerTest.php` | Nuevo: no deja borrar una categoría con productos (`assertSessionHasErrors('category')`) y nuevo: colisión de slug (`Café` → `cafe`, y un `Cafe` nuevo → `cafe-2`) |
| `tests/Feature/Admin/AllergenControllerTest.php` (renombrado con el controlador) | CRUD de alérgenos: nombre único al crear y al editar, `withCount('products')` en el listado y `detach()` de la pivote al borrar |
| `tests/Feature/SecurityHeadersTest.php` (nuevo) | Cabeceras en carta y panel, presencia de la CSP, `no-store` solo en rutas autenticadas (la carta pública sí puede cachearse), HSTS solo en HTTPS y que **una 404 también lleva las cabeceras** (es la razón de registrar el middleware también en el handler de excepciones) |
| `tests/Feature/Auth/LoginThrottleTest.php` (nuevo) | Bloqueo tras 5 intentos fallidos — el 6º no entra **aunque la contraseña sea correcta** —, que el contador se limpia al autenticarse y que la clave del limitador es email+IP (saturar al administrador no deja fuera a otro usuario) |
| `tests/Feature/MenuControllerTest.php` (caso añadido) | `test_alpine_state_travels_as_parsable_json_and_cannot_break_out`: el bloque `#menu-data` se parsea con `json_decode` y un nombre con `</script><script>` no rompe el bloque (regresión del bloque 10) |

### Por qué se comprueba cada cosa (no es cobertura por rellenar)
- **Magic bytes del PNG**: sin `ext-imagick` el `PngWriter` de endroid v6 revienta. El test falla en el momento en que alguien vuelva a pedir PNG y el código vuelva a emitir un SVG con extensión `.png`.
- **`assertViewHas('categories' | 'activeCategory' | 'selectedAllergenValues')`** en lugar de rascar el HTML: el filtrado vive en el controlador, que es donde estaba el fallo real.
- **`allergens.*` con `Rule::exists('allergens', 'id')`**: antes un `allergens[]=999` llegaba al `sync()` y devolvía 500. El test lo fija como error de validación.
- **Borrado de categoría con productos**: antes se podía borrar y los productos huérfanos desaparecían de la carta sin dejar rastro (FK `on delete cascade`).
- **Colisión de slugs**: la carta filtra por slug, así que dos categorías con el mismo slug habrían mezclado productos.

### Deuda en pruebas
- Los *seeders* (`DatabaseSeeder`, `AdminUserSeeder`, `MenuSeeder`) no tienen test; un cambio de esquema los rompe en silencio.
- No hay test de integración que pida `/` con los datos del seeder real (los tests actuales construyen sus propios fixtures).
- Pendiente un test explícito del `<noscript>` de la carta (enlaces con `?category=` y `?allergens[]=`).

---

## 8 · Avisos y deuda técnica · 🟡⏳

1. **`ext-imagick` no está instalado** en este equipo. El PNG se rasteriza a mano con GD (`app/Services/QrCodeService.php`, `rasterizePng()`). Si algún día se instala imagick, conviene volver al `PngWriter` de la librería y borrar ~35 líneas propias. No instalar la extensión “a ciegas”.
2. **`mb_*` viene del polyfill `symfony/polyfill-mbstring`**, no de la extensión (compilada sin `--enable-mbstring`). `Str::slug()` depende del *fallback* de `iconv`. Funciona, pero si el `php.ini` del servidor cambia hay que revisar `es_ES.UTF-8` y los slugs.
3. **Los datos sembrados se escriben sin acentos** (`Alergen*`, `Cafe`, `Descafeinado`, `Sin Lactosa` en `database/seeders/MenuSeeder.php`), mientras la interfaz los muestra con acento. Los filtros por nombre de alérgeno (`strtolower($a->name)`) son sensibles a eso: si alguien reescribe un alérgeno como `Gluten` → `Glúten`, el filtro cliente/servidor deja de coincidir con los nombres ya guardados en los productos. Decisión pendiente de confirmar: unificar criterios o normalizar al filtrar.
4. **`category_id` y `product_allergen` tienen `onDelete('cascade')`** y, al mismo tiempo, el controlador bloquea el borrado si hay productos. Sugerencia: migración nueva con `onDelete('restrict')` para que la BD sea la que mande (hoy la FK permitiría lo que la app prohíbe; se puede llegar con `php artisan tinker`).
5. **`?max_price=` está implementado en `MenuController` pero no tiene control en la UI pública** (solo categoría y alérgenos, que es lo que pide `especificaciones.md`). Está ahí para enlaces de QR/fijos; si no se quiere exponer, quitarlo.
6. **`categories.order` no tiene campo en el formulario de categoría**: el seed lo deja a 0 para todas, así que hoy el orden real de la carta es alfabético. O se añade el campo al admin, o se quita la columna.
7. ~~**No hay Content-Security-Policy** y `menu.blade.php` carga Alpine desde jsDelivr y tipografías de Google Fonts~~ → **resuelto**: ver bloque 9 (assets autohospedados) y bloque 4 (CSP).
8. **Rutas de perfil/password** (`/profile`, `/password`, `/forgot-password`, `/reset-password/{token}`) siguen vivas pese a ser un panel de un único administrador. Criterio: `forgot-password` merece quedarse; `/profile` y `/register`-like no hacen falta. Requiere decisión del usuario antes de tocar rutas.
9. **`storage:link`**: `ProductController` guarda en el disco `public` (`storage/app/public/products`), así que en despliegues nuevos hay que ejecutar `php artisan storage:link`; en este repo ya existe `public/storage`.
10. **`GET /dashboard` está definido con closure en `routes/web.php`** en lugar de controlador; funciona, pero es el único sitio sin controlador explícito.

---
## 9 · Autosuficiencia de assets: la carta dependía de jsDelivr y de Google Fonts ✅

**Problema:** `menu.blade.php` cargaba Alpine desde jsDelivr y las tipografías desde Google Fonts. Tres consecuencias nada estéticas: (1) la carta pública filtraba IP y user-agent del comensal a Google en cada carga; (2) si el CDN cae o lo bloquea un cortafuegos corporativo, la carta se queda sin JavaScript; (3) **mientras existan orígenes externos y scripts inline, una CSP decente es imposible**.

**Qué se hizo**

- Alpine pasa a ser una dependencia npm (`alpinejs` en `package.json`) y se importa en `resources/js/app.js`: un único bundle servido por Vite desde el mismo origen.
- Tipografías empaquetadas con `@fontsource` (`figtree`, `inter`, `playfair-display`), importadas desde `resources/css/app.css`. Cero peticiones a `fonts.googleapis.com`.
- **Todos los manejadores inline fuera de las vistas**: los `onclick="return confirm(...)"` de los borrados del panel se sustituyen por un único listener delegado sobre `form[data-confirm]` en `app.js`; lo mismo con el sidebar, el botón de imprimir y el logout de Breeze (`data-logout`).
- El placeholder de imagen sin producto es ahora un SVG local (`public/images/no-image.svg`) en lugar de un data-URI externo.
- Con eso la CSP del bloque 4 puede declararse con `script-src 'self'` **sin `'unsafe-inline'`**, que es lo que de verdad corta un XSS.

**Verificación:** `grep -rn "jsdelivr\|googleapis\|onclick=" resources/views` (vacío) y `php artisan test --filter=SecurityHeadersTest`.

---

## 10 · `@json` de Blade partía el estado inicial de Alpine por la mitad ✅

**Síntoma:** la carta se veía pero los filtros no arrancaban con el estado del servidor (categoría/alérgenos de un enlace de QR) y `app.js` petaba al parsear.

**Causa (es un comportamiento del compilador, no un despiste nuestro):** `Illuminate\View\Compilers\Concerns\CompilesJson` compila `@json(...)` haciendo `explode(",", $args)` sobre el argumento. Un literal multilínea con varias claves —o con closures dentro, que también llevan comas— **se parte y se pierden tramos enteros**, así que el `@json($array['a'], JSON_PRETTY...)` de la vista se compilaba con el JSON truncado. Silencioso: Blade no avisa.

**Qué se hizo**

- El estado se compone en PHP en `MenuController` (`$menuState`, líneas 73-85 del controlador) y la vista solo hace `@json($menuState)`: una sola variable, una sola coma cero, y queda explícito qué viaja al cliente.
- El estado viaja en `<script type="application/json" id="menu-data">` y `app.js` hace `JSON.parse(document.getElementById('menu-data').textContent)`. Un bloque `application/json` **no es ejecutable**, así que la CSP no lo bloquea y no hace falta firmar nada con nonces.
- Blade escapa `<`, `>`, `&` y las comillas dentro de `@json`, así que un nombre de producto con `</script><script>` no puede cerrar el bloque.

**Verificación:** `php artisan test --filter=test_alpine_state_travels_as_parsable_json_and_cannot_break_out` — el test crea un producto con `</script><script>alert(1)</script>` en el nombre, pide `/`, extrae el contenido de `#menu-data`, exige que `json_decode` lo lea entero y que en el payload no aparezcan `<script>` ni `</script>` literales.

## 11 · Estilo y análisis estático: el repo no pasaba Pint ni PHPStan ✅

**Estado de partida:** `laravel/pint` y `larastan/larastan` estaban en `require-dev`, pero **no se habían ejecutado nunca** y no había fichero de configuración. `vendor/bin/pint --test` marcaba 29 ficheros.

**Qué se hizo**

- `pint.json` con el preset `laravel` y la regla `single_quote` desactivada. No es capricho: todo el repo escribe cadenas con comillas dobles, y el preset por defecto las reescribiría en los 76 ficheros, convirtiendo la auditoría en un ruido inabordable. Con `single_quote: false`, Pint deja el estilo del repo y solo corrige lo que de verdad estaba raro (líneas en blanco finales, orden de `use`, espacios en concatenación, `{` de clase mal colocado en dos migraciones).
- `phpstan.neon` con larastan, **nivel 5** y `parameters.paths` sobre `app`, `routes` y `database/factories`. Aviso práctico: en este host el worker paralelo revienta con el límite de 128 M, hay que lanzarlo con `--memory-limit=1G`.
- **Sin baseline y sin `@phpstan-ignore`**: el único error que salió se arregló en el origen.

**El error que encontró PHPStan (y era real):** `Auth\VerifyEmailController` metía `$request->user()` en `new Verified(...)`, que exige `Illuminate\Contracts\Auth\MustVerifyEmail`, y el tipo era `App\Models\User|null` por dos cosas:

1. `App\Models\User` **no declaraba el contrato** (en `app/Models/User.php` el `use Illuminate\Contracts\Auth\MustVerifyEmail;` estaba comentado y la clase no lo `implements`; los métodos funcionaban solo por el trait del `Authenticatable` base).
2. `Request::user()` es nullable, y el controlador encadenaba `$request->user()->hasVerifiedEmail()` tres veces.

Ahora `User implements MustVerifyEmail` y el controlador toma `$user` una vez, con un guard `null` → 403 (la ruta va tras `auth` + `signed`, así que el guard es defensividad, no camino normal). Nada de casts ni `@var` para callar el análisis.

**Verificación**

```bash
vendor/bin/pint --test                                  # 0 incidencias
vendor/bin/phpstan analyse --no-progress --memory-limit=1G   # 0 errores, nivel 5
```

---

## 12 · Lo que queda por decidir (no lo toco sin tu criterio) ⏳

1. **No existe carpeta `lang/`.** La interfaz está en español a mano en las vistas, pero los mensajes que genera el framework (credenciales incorrectas, validaciones, “demasiados intentos” del bloqueo) salen **en inglés** porque `APP_LOCALE=en` y no hay traducciones propias. Ruta de arreglo barata: `php artisan lang:publish`, crear `lang/es/auth.php` (y validación si hace falta) y poner `APP_LOCALE=es` en `.env` dejando `APP_FALLBACK_LOCALE=en`. El test del bloqueo acepta los dos idiomas a propósito hasta que esto se decida.
2. **Rutas de Breeze que el panel no necesita** (`/profile`, `/password`, `/email/verify*`): se han puesto tipadas y a prueba, pero siguen expuestas. `forgot-password` tiene sentido dejarlo; el resto es superficie de ataque gratuita en un panel de un único administrador.
3. **`onDelete('cascade')` en `category_id` y en la pivote de alérgenos** frente a un controlador que prohíbe borrar. Falta una migración con `onDelete('restrict')` para que mande la base de datos (hoy se puede saltar el bloqueo desde `tinker`).
4. **Acentos en los datos sembrados** (`Cafe`, `Alergen*`): los filtros por nombre comparan en minúscula, así que renombrar `Cafe` → `Café` rompe coincidencias con lo ya guardado.
5. **`categories.order` no tiene campo en el formulario** del admin: el seed lo deja a 0 y hoy el orden efectivo es alfabético. O se expone el campo, o se quita la columna.
6. **`?max_price=` funciona en el servidor pero no tiene control en la carta pública.** Está para enlaces fijos/QR; si no se quiere exponer, fuera.
7. **`ext-imagick` no está instalado**: el PNG del QR se rasteriza con GD a mano (`QrCodeService::rasterizePng()`). Si se instala la extensión, se puede volver al `PngWriter` de la librería y borrar esas ~35 líneas.
8. **En producción**: `SESSION_SECURE_COOKIE=true` (los flags de sesión ya salen de `env()` con valores por defecto sanos: `http_only=true`, `same_site=lax`), `APP_DEBUG=false`, HTTPS delante (activa HSTS y `upgrade-insecure-requests` en la CSP automáticamente) y `php artisan storage:link` en despliegues nuevos.

---

## Cierre · cómo verificar la auditoría de una vez

```bash
composer install && npm install && npm run build
php artisan migrate:fresh --seed
php artisan test                                            # 89 passing · 343 assertions
vendor/bin/pint --test                                      # 0 incidencias
vendor/bin/phpstan analyse --no-progress --memory-limit=1G  # 0 errores (nivel 5)
```

Resumen del estado: **89 pruebas en verde, Pint sin incidencias, PHPStan nivel 5 sin errores y CSP cerrada sin CDNs ni scripts inline**. Lo marcado en el bloque 12 son decisiones de producto/despliegue, no fallos conocidos.

