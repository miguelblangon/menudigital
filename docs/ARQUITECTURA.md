# Arquitectura

Único punto de entrada del código: qué hay, dónde y por qué. Requisitos de origen: `especificaciones.md` (raíz). Criterio de móvil: [MOVIL.md](MOVIL.md). Qué está verificado: [PRUEBAS.md](PRUEBAS.md).

## Stack

| Capa | Elección | Notas |
|---|---|---|
| Framework | Laravel **13.35.0** | `bootstrap/app.php` (configuración estilo L11+, sin `app/Http/Kernel.php`) |
| Lenguaje | PHP **8.5.11** | usa GD para rasterizar el QR (no hay `imagick` en este host) |
| Base de datos | SQLite en `database/database.sqlite` | `DB_CONNECTION=sqlite`; sesiones, colas y caché también en BD |
| Vistas | Blade | `@extends` en el panel; componentes `x-…` sólo en lo que trae Breeze |
| CSS | Tailwind **3.4.19** vía PostCSS | `tailwind.config.js` + `postcss.config.js` (no el plugin v4) |
| JS | Alpine.js 3 dentro del bundle de Vite | **ni un `<script>` inline**: es lo que permite una CSP cerrada |
| Tipografías | `@fontsource` (Figtree, Inter, Playfair Display) | autohospedadas, **sin CDNs** |
| QR | `endroid/qr-code` v6 + rasterizado manual con GD | v6 no produce PNG sin `imagick` |
| Autenticación | Laravel Breeze | un único administrador; no hay registro público |
| Calidad | Pint, PHPStan (larastan, nivel 5), PHPUnit | ver [PRUEBAS.md](PRUEBAS.md) |

## Modelo de datos

```
categories ──1:N──> products <──N:M──> allergens
```

| Tabla | Columnas | Migración |
|---|---|---|
| `categories` | `id`, `name`, `slug` **unique**, `order` (default 0), `timestamps` | `2026_10_12_210038_create_categories_table.php` |
| `products` | `id`, `category_id` FK **onDelete cascade**, `name`, `description` nullable, `price decimal(8,2)`, `image` nullable, `available` bool default true, `timestamps` | `2026_10_12_210041_create_products_table.php` + renombrado en `2026_10_21_100000_…` |
| `allergens` | `id`, `name`, `timestamps` | `2026_10_15_173225_create_allergens_table.php` |
| `allergen_product` | `allergen_id` + `product_id` FK cascade, **unique (allergen_id, product_id)** | `20261017_120000_create_allergen_product_table.php` |

Tres detalles con historia, anotados para que no vuelvan:

1. **`available`, no `is_available`.** La columna nació como `is_available` y el código usaba `available` enmascarado por un accesor/mutador: los toggles no guardaban. La migración `2026_10_21_100000_rename_is_available_to_available_on_products_table.php` unificó el nombre y el accesor se eliminó. Así un typo falla en los tests en vez de quedarse en el valor por defecto.
2. **`allergen_product` sustituyó a `product_alergen`** (tabla antigua huérfana, borrada en `2026_10_17_120001_drop_product_alergen_table.php`). El nombre actual respeta la convención de Eloquent para `belongsToMany`, así que la relación no necesita argumentos.
3. **La FK de `products` es `cascade`**, pero `CategoryController::destroy()` **bloquea el borrado de una categoría con productos** para que el cascade no sea la vía normal de pérdida de datos.

Modelos: `Product` (`$fillable` explícito, `casts` de `price`→float y `available`→bool, scope `available()`), `Category` (`products()`), `Allergen` (`products()`).

## Rutas (43)

```bash
php artisan route:list --except-vendor
```

| Grupo | Rutas | Acceso |
|---|---|---|
| Carta pública | `GET /` → `MenuController@index` | invitado |
| Panel CRUD | `Route::resource` de `admin/products`, `admin/categories`, `admin/allergens` (7 cada una) | `auth` |
| Panel: filtros | `GET admin/products/filters` → `ProductController@filters` (fragmento del listado) | `auth` |
| QR | `admin/qr`, `admin/qr/generate`, `admin/qr/download` | `auth` |
| Breeze | `login`, `logout`, `forgot-password`, `reset-password`, `confirm-password`, `verify-email`, `profile`, `dashboard` | mixto |
| Salud | `GET /up` (definido en `bootstrap/app.php`) | público |

**No hay API ni registro público**: no existe `routes/api.php` ni rutas de registro. El `Route::middleware('auth')` del grupo `admin` es la única puerta del negocio.

## Carta pública (`MenuController`)

Los filtros se aplican **dos veces a propósito**:

- **En el servidor** (`index()`): los enlaces profundos `/?category=cafes&allergens[]=1&max_price=6` funcionan sin JavaScript y al compartir el QR. Categoría inexistente → `404`.
- **En el cliente** (Alpine, `resources/js/app.js`): al pulsar una categoría o un alérgeno no hay recarga, requisito explícito de `especificaciones.md`.

El estado inicial se hidrata desde PHP en un único `<script type="application/json" id="menu-data">`: un bloque no ejecutable que la CSP no bloquea. Motivo técnico: `@json()` de Blade parte el argumento con `explode(",")` (`Illuminate\View\Compilers\Concerns\CompilesJson`), así que un array literal con varias claves se trunca y la vista no compila; el controlador compone `$menuState` y la vista sólo hace `@json($menuState)`.

Exclusión de alérgenos: `whereDoesntHave("allergens", … whereIn("allergens.id", …))`. Es `whereDoesntHave`, no `doesntHave`: con `doesntHave` se excluirían los productos **con cualquier alérgeno**, no los que contienen alguno de los seleccionados.

Orden de la carta: primero `categories.order` (subconsulta escalar, sin `join`, para respetar el orden manual del comercio) y después `name`.

## Panel de administración

| Controlador | Particularidades |
|---|---|
| `Admin\ProductController` | validación con `Rule::exists` de categoría y de `allergens.*`, imagen `image|max:2048`, subida al disco `public`, `allergens()->sync()` y borrado de la imagen anterior al reemplazarla. El checkbox va precedido de `<input type="hidden" name="available" value="0">`: desmarcado significa realmente *no disponible*. El listado filtra por `search` (LIKE sobre `name`) y `category` (id): `filterState()` es **común** a `index()` y a `filters()`, así que la primera pantalla y el refresco async coinciden. Un `category` inexistente no es error: se ignora y deja el listado completo. La ruta `filters` se declara **antes** del `Route::resource`, si no `admin/products/{product}` se la queda y el *route model binding* responde 404 con `"filters"` como id |
| `Admin\CategoryController` | `slug` único con sufijo numérico ante colisión (`cafe-2`), `withCount("products")` en el listado, `destroy()` devuelve flash de error si la categoría tiene productos |
| `Admin\AllergenController` | nombre único, `detach()` explícito al borrar |
| `Admin\QrController` | `index()` (SVG embebido), `generate()` (`image/svg+xml`), `download()` (PNG adjunto a 600 px) |

`App\Services\QrCodeService` es el único punto de acceso al generador: en v6 `QrCode` es `final readonly` y se construye con argumentos nombrados; el PNG se rasteriza a mano con GD recorriendo la matriz de módulos.

## JavaScript: sin un solo script inline

`resources/js/app.js` concentra todo, con reglas delegadas en lugar de atributos `onclick`:

- `form[data-confirm]` → `confirm()` antes de borrar.
- `#menu-toggle` / `#sidebar` / `#sidebar-overlay` → *drawer* del panel.
- `[data-print]` → `window.print()` en la página del QR.
- `[data-logout]` → submit del formulario de cierre de sesión.
- `[data-product-filters]` → `Alpine.data('productFilters')`: el buscador y el selector de categoría del listado de productos.

### Filtro async del listado de productos

El componente `productFilters` no filtra en memoria: **filtra el servidor** y
sustituye el contenido de `[data-product-list]` con `innerHTML`.

- **Por qué HTML y no JSON:** el listado tiene dos maquetas (tarjetas en móvil,
  tabla en `sm:`) y formularios de borrado con `@csrf`. Renderizar eso en
  plantillas `x-for` al otro lado del cable sería duplicar el markup y su
  escapado. Respondiendo el partial Blade, lo inyectado sigue escapado por
  Blade y la confirmación de borrado sigue funcionando por delegación
  (`form[data-confirm]`), no por atributos inline.
- **Sin directivas de Alpine dentro del partial:** el contenedor se reemplaza con
  `innerHTML` y Alpine no recorre nodos recién añadidos. Si hubiera un `x-for`
  ahí, quedaría inerte tras el primer refresco.
- **El `<form method="GET">` es la fuente de verdad:** los valores se leen con
  `new FormData(form)`, no con una copia en el estado. Con JavaScript desactivado
  ese mismo formulario filtra recargando; con JavaScript activo Alpine intercepta
  el `submit`. El botón es el mismo en los dos casos y no lleva `x-cloak`: Alpine
  quita ese atributo al inicializar, así que ocultarlo así lo dejaría visible
  justo cuando JavaScript **sí** funciona.
- **Nunca se busca desde `$el`, siempre desde el nodo del componente:** en Alpine
  `$el` es el elemento sobre el que se evalúa la expresión. Como `@input`,
  `@change` y `@submit.prevent` cuelgan del `<form>` y de los campos, `this.$el`
  dentro de un método es el campo, no la raíz del `x-data` (comprobado con
  Alpine 3.17 sobre el HTML real del listado). `scope()` sube con
  `closest('[data-product-filters]')` y desde ahí se localizan el formulario, el
  listado y `#product-search`. Sin ese ancla el `querySelector` devolvía `null` y
  `new FormData(null)` reventaba con «FormData constructor: Argument 1 is not an
  object»: ni una petición salía al servidor.
- **Carreras:** teclear dispara varios refrescoes. Cada petición cancela a la
  anterior con `AbortController` y se numera con `requestId`; gana siempre la
  última, y sólo esa apaga el indicador de carga.
- **La URL refleja lo que hay en pantalla** (`history.replaceState`, no
  `pushState`): recargar, volver atrás o compartir el enlace devuelve el mismo
  listado, porque `index()` aplica la misma query.

Es el mismo patrón que la carta pública (servidor para enlaces profundos,
cliente para no recargar), así que el filtro del panel también se verifica sin
JavaScript: ver [PRUEBAS.md](PRUEBAS.md).

Esto no es estética: es lo que permite `script-src 'self' 'unsafe-eval'` **sin `'unsafe-inline'`**. Un `<script>` inyectado en el nombre de un producto (XSS) no llega a ejecutarse. `'unsafe-eval'` es obligatorio porque Alpine compila sus expresiones con `new Function()`.

## Seguridad

`App\Http\Middleware\SecurityHeaders`, registrado en `bootstrap/app.php` con `$middleware->web(append: …)` y **además** en `$exceptions->respond(...)`: las respuestas que nacen fuera del pipeline (404, 302 de invitado, 419 de CSRF, 500) nunca pasan por `handle()`.

| Cabecera | Valor | Para qué |
|---|---|---|
| `X-Frame-Options` | `DENY` | clickjacking |
| `X-Content-Type-Options` | `nosniff` | que el navegador no interprete un PNG subido como HTML |
| `Referrer-Policy` | `strict-origin-when-cross-origin` | no filtrar rutas del negocio a terceros |
| `Permissions-Policy` | `camera=(), microphone=(), geolocation=()` | la app no usa ninguno de esos permisos |
| `Content-Security-Policy` | `default-src 'self'; script-src 'self' 'unsafe-eval'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self'; connect-src 'self'; form-action 'self'; frame-ancestors 'none'; base-uri 'self'; object-src 'none'` | corta de raíz un XSS |
| `Strict-Transport-Security` | `max-age=31536000; includeSubDomains` | sólo se declara si la petición es `secure()` |
| `Cache-Control` | `no-store, max-age=0` | sólo en `admin/*`, `dashboard`, `profile*`, `login`, `password` |

Dos trampas ya resueltas: hay que hacer `headers->remove("Cache-Control")` antes de `set()` porque Symfony fusiona directivas (acababa en `max-age=0, no-store, private`); y `style-src 'unsafe-inline'` sigue abierto porque los componentes de Breeze usan `style=""` en *dropdowns* y modales — los estilos no ejecutan código y ninguna vista mete contenido del usuario dentro de un `style`.

Con `CSP_REPORT_ONLY=true` la política sólo informa: sirve para ver en la consola qué rompería antes de aplicarla en firme (`config/security.php`).

Fuerza bruta: `LoginRequest::ensureIsNotRateLimited()` limita a **5 intentos** con la clave `email|ip` (`Str::transliterate(Str::lower($email).'|'.$ip)`).

### Pendiente de seguridad

- **Segundo factor (2FA): no implementado.** `especificaciones.md` lo pide («autenticación con doble factor»); no hay TOTP, ni códigos de recuperación, ni columna que los almacene. Es el hueco funcional más claro del proyecto.
- `config/cors.php` está publicado con `allowed_origins => ['*']`. Hoy es inerte (no hay rutas `api/*` y `HandleCors` sólo actúa sobre esas rutas), pero conviene estrecharlo o eliminarlo si se añade una API.
- Credenciales por defecto en los *seeds* (`admin@menudigital.com` / `admin123`): cambiarlas antes de publicar. Ver [DESARROLLO.md](DESARROLLO.md).

## Vistas

- `layouts/admin.blade.php`: **única** navegación del panel (sidebar en escritorio, *drawer* en móvil, zona de cuenta con Perfil y Salir). `dashboard`, `profile/edit` y `admin/qr` usan este layout; `layouts/app.blade.php` y `layouts/navigation.blade.php` quedan para las vistas de Breeze que ya no se enlazan desde el panel.
- `layouts/guest.blade.php`: login y recuperación.
- `menu.blade.php` + `components/menu-*.blade.php`: carta pública (header, barra de categorías, tarjeta de producto, filtro de alérgenos).
- `admin/{products,categories,allergens}/{index,create,edit}.blade.php`: CRUD (9 vistas).
- `admin/products/partials/{list,table}.blade.php`: el listado filtrable, en partial y no dentro de `index.blade.php` porque es **el mismo fragmento** que devuelve `ProductController@filters` cuando Alpine lo pide. `list` trae las tarjetas (móvil) e incluye `table` (escritorio): 11 vistas de CRUD en total.

Los listados del panel combinan `hidden md:table` (tabla en escritorio) con tarjetas `md:hidden`, porque el panel se gestiona desde el móvil. El criterio completo está en [MOVIL.md](MOVIL.md).


