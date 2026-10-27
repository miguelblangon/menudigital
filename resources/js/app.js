

import Alpine from 'alpinejs';

/**
 * Todo el JavaScript de la aplicación vive en este bundle, que Vite sirve desde
 * el mismo origen. Las vistas no llevan ni <script> inline ni CDNs de terceros,
 * y por eso la Content-Security-Policy puede declararse con script-src 'self'
 * sin 'unsafe-inline': un HTML inyectado (XSS) en la carta o en el panel no
 * llegaría a ejecutarse.
 *
 * Los datos que el servidor pasa a Alpine viajan en un bloque
 * <script type="application/json">: no es ejecutable, así que la CSP no lo
 * bloquea y no hace falta firmarlo con nonces.
 */

// ----------------------------------------------------------------- carta pública
const menuData = document.getElementById('menu-data');

if (menuData !== null) {
    const state = JSON.parse(menuData.textContent);

    Alpine.data('menuApp', () => ({
        categories: state.categories,
        products: state.products,
        // Hidratado desde el servidor: los enlaces del QR (?category=...&allergens[]=...)
        // llegan ya filtrados y el estado inicial debe coincidir.
        activeCategory: state.activeCategory,
        showAllergenFilter: false,
        selectedAllergens: state.selectedAllergenValues,
        allergenOptions: state.allergenOptions,

        get filteredProducts() {
            let filtered = this.products;

            if (this.activeCategory) {
                const category = this.categories.find(
                    (candidate) => candidate.slug === this.activeCategory
                );

                if (category) {
                    filtered = filtered.filter((product) => product.category_id === category.id);
                }
            }

            if (this.selectedAllergens.length > 0) {
                filtered = filtered.filter((product) => {
                    return !product.allergens.some((allergen) =>
                        this.selectedAllergens.includes(allergen.name.toLowerCase())
                    );
                });
            }

            return filtered;
        },

        filterByCategory(slug) {
            this.activeCategory = slug;
        },

        toggleAllergen(allergen) {
            const index = this.selectedAllergens.indexOf(allergen);

            if (index > -1) {
                this.selectedAllergens.splice(index, 1);
            } else {
                this.selectedAllergens.push(allergen);
            }
        },
    }));
}

// ------------------------------------------------------------ panel: borrados
// Antes cada botón llevaba onclick="return confirm(...)". Un único listener
// delegado sobre el submit del <form data-confirm> hace lo mismo sin script
// inline y con la misma protección contra borrados accidentales.
document.addEventListener(
    'submit',
    (event) => {
        const form = event.target.closest('form[data-confirm]');

        if (form !== null && !window.confirm(form.dataset.confirm)) {
            event.preventDefault();
        }
    },
    true
);

// ------------------------------------------------------------ panel: sidebar
// El panel se gestiona desde el móvil, así que el drawer es la navegación
// principal. Además de abrir/cerrar:
//  - se bloquea el scroll del fondo mientras está abierto (si no, arrastrar
//    sobre el drawer mueve la página y parece que la app "se rompe");
//  - Escape lo cierra, que es lo que espera quien usa un teclado bluetooth;
//  - se mantiene aria-expanded/aria-hidden coherente con lo que hay en pantalla.
const menuToggle = document.getElementById('menu-toggle');
const sidebar = document.getElementById('sidebar');
const overlay = document.getElementById('sidebar-overlay');

if (menuToggle !== null && sidebar !== null && overlay !== null) {
    const isDrawerOpen = () => ! sidebar.classList.contains('-translate-x-full');

    const setDrawer = (open) => {
        sidebar.classList.toggle('-translate-x-full', ! open);
        overlay.classList.toggle('hidden', ! open);
        document.body.classList.toggle('overflow-hidden', open);
        menuToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        menuToggle.setAttribute('aria-label', open ? 'Cerrar el menú de navegación' : 'Abrir el menú de navegación');

        if (! open) {
            document.removeEventListener('keydown', onKeydown);
        }
    };

    const onKeydown = (event) => {
        if (event.key === 'Escape') {
            setDrawer(false);
            menuToggle.focus();
        }
    };

    menuToggle.addEventListener('click', () => {
        const open = ! isDrawerOpen();

        setDrawer(open);

        if (open) {
            document.addEventListener('keydown', onKeydown);
        }
    });

    overlay.addEventListener('click', () => setDrawer(false));
}

// ------------------------------------------------------------ panel: QR
// window.print() a secas imprimiría el sidebar, el header y los botones junto
// con el código. data-print-block marca el único bloque que debe salir en papel
// (las reglas @media print de app.css hacen el resto).
const printButton = document.querySelector('[data-print]');

if (printButton !== null) {
    printButton.addEventListener('click', () => window.print());
}

// ------------------------------------------------------------ logout (Breeze)
document.querySelectorAll('[data-logout]').forEach((link) => {
    link.addEventListener('click', (event) => {
        event.preventDefault();

        link.closest('form').submit();
    });
});

// ------------------------------------------------ panel: filtros de productos
//
// El listado del panel se filtra sin recargar. Dos decisiones que se sostienen
// la una a la otra:
//
//  1. Filtra el servidor. `GET /admin/products/filters` devuelve el mismo partial
//     Blade que pintó la primera pantalla, con `admin/products/index` aplicando
//     los mismos filtros en su propio render. Así el markup de las tarjetas y de
//     la tabla (con su escapado `{{ }}` y sus `@csrf` de los borrados) no se
//     duplica en plantillas de Alpine al otro lado del cable, y el `innerHTML`
//     que se inyecta sigue siendo HTML escapado por Blade: un nombre de producto
//     inyectado (XSS) no abre camino ni aquí ni en el render inicial.
//  2. El formulario es la fuente de verdad. Los valores se leen del DOM con
//     `new FormData(form)`, no de una copia en el estado, porque con JavaScript
//     desactivado ese mismo `<form method="GET">` es el que filtra.
//
// Dos refrescoes cruzados es lo normal tecleando: cada petición nueva cancela a
// la anterior con AbortController y además se numera, para que una respuesta
// tardía no pise a la última ni apague el indicador de carga de su sucesora.
const productFilterRoot = document.querySelector('[data-product-filters]');

if (productFilterRoot !== null) {
    Alpine.data('productFilters', () => ({
        loading: false,
        error: null,
        hasFilters: false,
        requestId: 0,
        controller: null,

        init() {
            // Hidrata desde el formulario: la primera pantalla ya puede llegar
            // filtrada por query (?search=…&category=…) y el botón "Limpiar"
            // debe verse desde el principio.
            this.hasFilters = this.hasValues(this.params());
        },

        // `$el` en Alpine NO es la raíz del `x-data`: es el elemento sobre el que
        // Alpine está evaluando la expresión. Como `@input`, `@change` y
        // `@submit.prevent` cuelgan del <form> y de los campos, dentro de apply()
        // `this.$el` era el campo o el propio formulario, y un `querySelector`
        // desde ahí no encontraba nada: `new FormData(null)` reventaba con
        // "FormData constructor: Argument 1 is not an object" antes de pedir
        // nada al servidor. Todo se busca siempre desde el nodo del componente.
        scope() {
            return this.$el.closest("[data-product-filters]") ?? this.$el;
        },

        form() {
            return this.scope().querySelector("[data-filter-form]");
        },

        list() {
            return this.scope().querySelector("[data-product-list]");
        },

        // `URLSearchParams.size` es demasiado reciente como para fiarse; la
        // comparación de la query ya dice lo mismo y va en cualquier navegador.
        hasValues(params) {
            return params.toString() !== "";
        },

        params() {
            const form = this.form();

            if (form === null) {
                return new URLSearchParams();
            }

            const params = new URLSearchParams(new FormData(form));

            // Un campo vacío no viaja: "?search=&category=" ensucia la URL y el
            // servidor lo trataría igual que una ausencia.
            for (const [key, value] of [...params.entries()]) {
                if (value === "") {
                    params.delete(key);
                }
            }

            return params;
        },

        apply() {
            this.requestId += 1;

            const requestId = this.requestId;

            if (this.controller !== null) {
                this.controller.abort();
            }

            this.controller = new AbortController();

            const params = this.params();
            const query = params.toString();
            const endpoint = this.scope().dataset.endpoint;

            if (typeof endpoint !== "string" || endpoint === "") {
                this.error = "El listado no tiene endpoint de filtros configurado.";

                return;
            }

            this.loading = true;
            this.error = null;
            this.hasFilters = this.hasValues(params);

            fetch(query === "" ? endpoint : `${endpoint}?${query}`, {
                headers: {
                    "Accept": "text/html",
                    "X-Requested-With": "XMLHttpRequest",
                },
                credentials: "same-origin",
                signal: this.controller.signal,
            })
                .then((response) => {
                    if (! response.ok) {
                        throw new Error(`respuesta ${response.status}`);
                    }

                    return response.text();
                })
                .then((html) => {
                    if (requestId !== this.requestId) {
                        return;
                    }

                    // innerHTML y no x-html: el fragmento no trae ni una directiva
                    // de Alpine, así que no hay árbol que volver a inicializar.
                    const list = this.list();

                    if (list === null) {
                        throw new Error("falta el contenedor del listado");
                    }

                    list.innerHTML = html;
                    this.syncUrl(params);
                })
                .catch((error) => {
                    if (error.name === "AbortError" || requestId !== this.requestId) {
                        return;
                    }

                    // No se toca el listado: lo que hay en pantalla sigue siendo
                    // válido; sólo se avisa de que el refresco no llegó.
                    this.error = "No se pudo actualizar el listado: lo que ves puede estar desactualizado.";
                })
                .finally(() => {
                    if (requestId === this.requestId) {
                        this.loading = false;
                    }
                });
        },

        // La URL refleja lo que hay en pantalla, así que recargar, volver atrás o
        // compartir el enlace devuelve exactamente el mismo listado (el servidor
        // aplica la misma query en index()). replaceState, no pushState: una letra
        // tecleada no debe dejar 20 entradas en el histórico.
        syncUrl(params) {
            const query = params.toString();
            const base = this.scope().dataset.listUrl ?? window.location.pathname;

            window.history.replaceState({}, "", query === "" ? base : `${base}?${query}`);
        },

        clear() {
            const form = this.form();

            if (form !== null) {
                form.querySelectorAll("[data-filter-field]").forEach((field) => {
                    field.value = "";
                });
            }

            this.apply();

            const search = this.scope().querySelector("#product-search");

            if (search !== null) {
                search.focus();
            }
        },
    }));
}

window.Alpine = Alpine;

Alpine.start();

