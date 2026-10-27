# Pruebas y puertas de calidad

Estado de la suite: **123 tests · 499 aserciones · ~4,7 s · 0 fallos**.

```bash
php artisan test                                            # todo
php artisan test --filter=Responsive                        # un grupo
php artisan test tests/Feature/Admin/ProductControllerTest.php
composer test                                               # config:clear + artisan test
vendor/bin/pint --test                                      # estilo (preset `laravel`)
vendor/bin/phpstan analyse --no-progress --memory-limit=1G  # nivel 5 · 0 errores
```

## Entorno de las pruebas

`phpunit.xml` aísla la ejecución del entorno de desarrollo:

| Variable | Valor | Motivo |
|---|---|---|
| `DB_CONNECTION` / `DB_DATABASE` | `sqlite` / `:memory:` | la base real (`database/database.sqlite`) no se toca nunca |
| `CACHE_STORE`, `SESSION_DRIVER` | `array` | sin dependencias externas (redis/memcached) |
| `BCRYPT_ROUNDS` | `4` | los tests de autenticación hashean muchas veces; las 12 rondas del `.env` multiplicarían el tiempo |
| `QUEUE_CONNECTION` | `sync` | los eventos de mail se ejecutan en línea y se pueden afirmar |
| `MAIL_MAILER` | `array` | el reset de contraseña se verifica sin servidor SMTP |

Todos los tests que tocan base de datos usan `RefreshDatabase` (una transacción por test).

## Inventario

| Fichero | Tests | Qué fija |
|---|---:|---|
| `Feature/Admin/AdminAccessTest.php` | 4 | **puerta del panel sin sesión**: las siete acciones de cada CRUD (incluidas `store`, `update` y `destroy`) y el fragmento `admin/products/filters` redirigen a `/login` y ningún intento de escritura llega a la base de datos |
| `Feature/Admin/ProductControllerTest.php` | 28 | CRUD completo, validaciones (`price` numérico, `category_id` existente, alérgeno inexistente rechazado), subida y reemplazo de imagen (la antigua se borra del disco) y borrado del fichero al eliminar el producto. Además el **filtro del listado**: `search` por nombre (insensible a mayúsculas en ASCII, `trim`, vacío sin efecto, sin resultados), `category` por id (categoría inexistente = sin filtro), ambos a la vez, `200` en el fragmento `admin/products/filters` con el mismo subconjunto de filas que la página completa, y que el formulario de la vista expone los dos campos con sus valores recuperados |
| `Feature/Admin/AllergenControllerTest.php` | 13 | CRUD, nombre único (en `store` y en `update`, permitiendo mantener el propio), tabla intermedia `allergen_product`, relación navegable desde los dos lados y `detach()` de los productos al borrar un alérgeno |
| `Feature/Admin/CategoryControllerTest.php` | 9 | CRUD, nombre único, `slug` con sufijo cuando dos nombres colisionan y **bloqueo del borrado** de una categoría con productos |
| `Feature/Admin/QrControllerTest.php` | 5 | invitado → `/login`; `/admin/qr` embebe `<svg`; `/generate` devuelve `image/svg+xml`; `/download` devuelve un PNG real (se comprueban los bytes mágicos `\x89PNG\r\n\x1a\n`, no el `Content-Type` a pelo) |
| `Feature/MenuControllerTest.php` | 9 | carta pública: producto no disponible oculto, filtro por `slug`, exclusión por alérgeno (`whereDoesntHave`), ids de alérgeno inexistentes ignorados, `max_price`, orden por `categories.order` + nombre, 404 en `slug` desconocido, acceso sin autenticar y que el estado de Alpine viaja como JSON parseable sin poder cerrar el bloque `</script>` |
| `Feature/SecurityHeadersTest.php` | 11 | cabeceras de endurecimiento en carta y panel, `no-store` sólo en rutas privadas, cabeceras presentes también en 404/302, CSP sin orígenes de terceros, `script-src` sin `'unsafe-inline'`, HSTS sólo en HTTPS y que **ninguna vista** contiene manejadores inline (`onclick=`, `onload=`) ni CDN de tipografías |
| `Feature/ResponsiveLayoutTest.php` | 11 | adaptabilidad a móvil (se detaila abajo) |
| `Feature/Auth/LoginThrottleTest.php` | 3 | bloqueo tras 5 intentos, contador que se limpia al acertar y clave `email\|ip` (un atacante no bloquea la cuenta de otro usuario desde otra IP) |
| `Feature/Auth/{Authentication,EmailVerification,PasswordConfirmation,PasswordReset,PasswordUpdate}Test.php` | 16 | lo que trae Breeze: login, verificación, confirmación, reset y cambio de contraseña |
| `Feature/ProfileTest.php` | 5 | perfil: nombre/email, verificación al cambiar el email, contraseña y borrado de cuenta |
| `Feature/Database/AdminUserSeederTest.php` | 7 | el administrador viene del entorno (`ADMIN_EMAIL`/`ADMIN_PASSWORD`), nunca de un valor escrito en el seeder: rechaza email vacío o placeholder, rechaza la contraseña débil heredada (`admin123`) cuando la política es estricta, genera una fuerte si no hay ninguna y **no pisa** la contraseña de un admin ya creado |
| `Feature/ExampleTest.php`, `Unit/ExampleTest.php` | 2 | humo (200 en `/`); se mantienen como test de cordura del arranque |

## `ResponsiveLayoutTest.php` — qué fija cada caso

La adaptabilidad a móvil no se puede afirmar con un test de HTTP corriente (es CSS), así que este fichero **afirma el contrato**: el HTML y la hoja de estilos contienen las reglas que hacen que la interfaz funcione en 375 px. Si alguien quita una de esas reglas, el test revienta a propósito.

| Test | Contrato |
|---|---|
| `test_the_menu_declares_the_mobile_viewport_with_safe_areas` | `width=device-width, initial-scale=1, viewport-fit=cover` en la carta |
| `test_every_admin_screen_declares_the_mobile_viewport` | el viewport móvil está en **todas** las vistas del panel, no sólo en una |
| `test_the_admin_sidebar_reaches_every_section_and_can_log_out` | el *sidebar* enlaza Dashboard, Productos, Categorías, Alérgenos, QR, Perfil y **logout**: desde el móvil no puede haber secciones inalcanzables |
| `test_the_qr_svg_is_wrapped_because_endroid_hardcodes_its_width` | el `<svg>` del QR está dentro de `[data-qr]`: `endroid/qr-code` escribe `width="464px"` y sin contenedor la página desborda en horizontal |
| `test_the_category_bar_is_part_of_the_sticky_header_not_a_second_offset` | no hay un segundo `sticky` con offset mágico (`top-[57px]`) |
| `test_the_category_bar_wraps_into_rows_instead_of_hiding_categories` | la barra de categorías hace `flex-wrap` y no `overflow-hidden`: con muchas categorías se parten en filas antes de dejar categorías inalcanzables |
| `test_the_allergen_sheet_is_cloaked_until_alpine_starts` | el *bottom sheet* lleva `x-cloak` (Alpine arranca diferido y el *overlay* se vería un instante por encima de la carta) |
| `test_the_mobile_drawer_is_declared_accessibly` | `#menu-toggle` declara `aria-controls` y `aria-expanded`, y existen `#sidebar` y `#sidebar-overlay` |
| `test_the_stylesheets_carry_the_mobile_specific_rules` | `app.css` contiene `pb-safe`, `env(safe-area-inset-bottom`, `[x-cloak]`, `no-scrollbar`, `[data-qr] svg` y `@media print` |
| `test_the_sticky_headers_reserve_the_top_safe_area` | los dos `<header>` pegajosos aplican `pt-[env(safe-area-inset-top)]` |
| `test_no_view_uses_the_breeze_app_layout` | **una sola navegación**: ninguna vista vuelve a `<x-app-layout>`, para que el panel no vuelva a tener dos navegaciones conviviendo (la de Breeze y el *drawer* con el que se gestiona desde el móvil) |

## Puertas de calidad

- **Pint** (`pint.json`): preset `laravel` con `single_quote: false` — el código PHP usa comillas dobles de forma consistente, así que desactivar la regla cuesta menos que reescribir el histórico. `vendor/bin/pint` corrige; `vendor/bin/pint --test` sólo informa.
- **PHPStan** (`phpstan.neon`): nivel 5 con `larastan`, sobre `app`, `routes` y `database/factories`. Pasar a nivel 6 obligaría a añadir docblocks genéricos en los `return` de los controladores: es deuda asumida, no un olvido.

## Lo que **no** está cubierto

1. **No hay pruebas de navegador ni visuales** (no hay Playwright/Puppeteer en `package.json`). La adaptabilidad móvil se verifica con las aserciones de contrato anteriores **más** revisión manual en un teléfono real; la altura efectiva y los solapamientos finos quedan fuera.
2. **2FA**: no existe, así que tampoco hay tests (ver [ESTADO.md](ESTADO.md)).
3. **Rendimiento**: sin *benchmarks*. La carta lanza 3 consultas (`categories`, `allergens` y `products` con `with()`) y no usa caché.
4. **Entrega de imágenes**: se prueba que la ruta se guarda y que el fichero viejo se borra, pero no que la imagen se sirva. Eso exige `php artisan storage:link` (ver [DESARROLLO.md](DESARROLLO.md)).

