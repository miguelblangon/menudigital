

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

window.Alpine = Alpine;

Alpine.start();

