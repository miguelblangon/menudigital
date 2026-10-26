# Criterio *mobile-first*

El proyecto se gestiona **desde el teléfono**: el administrador da de alta productos en la barra del café y el cliente escanea el QR con su móvil. Por eso el móvil no es una variante a comprobar al final, sino el punto de partida de cada interfaz. Requisito original: `especificaciones.md` («totalmente responsive y especialmente adaptado a resoluciones móviles», y «la parte de la gestión también se hará de forma responsiva pensando en móviles»).

## Reglas del proyecto

1. **Mobile-first con Tailwind.** La base (sin prefijo) es el móvil; `sm:` (≥640px), `md:` (≥768px) y `lg:` (≥1024px) añaden, nunca quitan. Prohibido el `max-*` para maquetar.
2. **Un solo layout de panel.** Todas las pantallas privadas comparten `layouts/admin.blade.php`: sidebar fijo en escritorio (`lg:`) y *drawer* con *overlay* en móvil. No hay una navegación para el CRUD y otra para el resto.
3. **Objetivos táctiles ≥ 44 px.** Botones y campos con `py-3`; los controles pequeños de escritorio se redimensionan en móvil, no se dejan igual.
4. **`text-base` (16 px) en todos los `<input>`, `<select>` y `<textarea>`.** Por debajo de 16 px iOS **hace zoom al enfocar** el campo y descoloca el formulario entero: es el fallo de usabilidad más frecuente en un panel móvil.
5. **Nada de offsets mágicos** (`top-[57px]`). Todo lo pegajoso viaja en un único bloque `sticky`: si el contenido crece, el offset sigue siendo correcto.
6. **Anchos fluidos, `max-w-*` como techo.** Un contenedor con ancho fijo en píxeles desborda antes de los 375 px.

## Qué se corrigió en esta revisión

| Problema real | Síntoma en el móvil | Corrección |
|---|---|---|
| El SVG del QR se emite con ancho absoluto (`width="464px"`) | Scroll horizontal en toda la página `/admin/qr` desde 375 px | contenedor `data-qr` con `max-w-[17rem] sm:max-w-[22rem]` + regla `[data-qr] svg { width:100%; height:auto }` en `resources/css/app.css`. Sigue siendo vector, así que al imprimir no pierde nitidez |
| La barra de categorías se clavaba con `top-[57px]` | Al crecer el header (3 botones en ≤375 px) la barra se solapaba con los productos | `<header>` **único** `sticky` que contiene el título y la barra de categorías (`menu-header.blade.php`); `menu-categories.blade.php` se incluye dentro |
| Faltaban *safe area insets* | En iPhone con muesca el header pegado al borde superior y el último producto bajo la barra de gestos | `viewport-fit=cover` + `pt-[env(safe-area-inset-top)]` en los headers y utilidad `pb-safe` (`calc(1.5rem + env(safe-area-inset-bottom))`) |
| El modal de alérgenos se pintaba antes de arrancar Alpine | Flash de un *overlay* a pantalla completa sobre la carta | `x-cloak` en el *bottom sheet* + regla `[x-cloak] { display: none !important }` (el bundle de Vite es un módulo diferido) |
| `max-h-[80vh]` en el filtro | `vh` usa la altura de la barra de direcciones sin plegar: la fila «Limpiar/Aplicar» quedaba fuera | `max-h-[85dvh]` (altura dinámica real) |
| Alérgenos en `grid-cols-2` | Nombres como «Frutos de cáscara» partidos en dos líneas apretadas | `grid-cols-1 sm:grid-cols-2` |
| Botones del filtro en fila horizontal | Botones estrechísimos en 375 px | `flex-col sm:flex-row` |
| El sidebar no tenía QR, Perfil ni Salir | **No se podía cerrar sesión ni tocar el perfil desde el móvil** | zona de cuenta en `layouts/admin.blade.php` con el nombre, Perfil y el formulario de logout |
| Dashboard, QR y Perfil usaban la navegación de Breeze | Dos navegaciones distintas, con menú inconsistente según la pantalla | las tres vistas migradas a `@extends('layouts.admin')` |

## Carta pública

- **Cabecera pegajosa única**: título del local, botón de filtros y barra de categorías. La barra se desliza en horizontal (`overflow-x-auto no-scrollbar snap-x`) con `whitespace-nowrap` en cada chip: en móvil una rejilla de 12 categorías ocuparía media pantalla antes de ver un producto.
- **`no-scrollbar`**: la barra de scroll de escritorio resta altura a los chips y no aporta nada en táctil.
- **Una columna** de productos hasta `sm:`, con tarjetas `menu-product-card.blade.php` (nombre, precio, descripción y chips de alérgenos).
- **Filtro de alérgenos** como *bottom sheet*: una columna en móvil y dos a partir de `sm:`, con `role="dialog"`, `aria-modal` y `aria-pressed` en cada chip.
- **Sin barras laterales ni *hover***: en táctil no existe `:hover`, así que toda la información relevante está visible, no revelada al pasar el ratón.

## Panel

- *Drawer*: `#sidebar` fijo a la izquierda en `lg:` y deslizable con `#menu-toggle` + `#sidebar-overlay` en móvil (`transform -translate-x-full`). El botón declara `aria-controls` y `aria-expanded`.
- `h-screen overflow-y-auto` en el *aside*: con `min-h-screen` el *drawer* se estiraba y el logout quedaba fuera del área deslizable.
- **Tabla en escritorio, tarjetas en móvil**: los listados usan `hidden md:table` + tarjetas `md:hidden`. Una tabla de 6 columnas (imagen, nombre, categoría, precio, alérgenos, acciones) en 375 px es ilegible; en cambio la tabla sigue siendo lo más rápido de escanear en un monitor.
- Formularios: una columna en móvil, `sm:grid-cols-2` para precio/categoría y `sm:grid-cols-2 lg:grid-cols-3` para los checkboxes de alérgenos. Botones `w-full sm:w-auto` apilados, para que «Guardar» ocupe el ancho del pulgar.
