{{--
    Barra de categorías de la carta.

    Ya no es `sticky` por su cuenta (antes `sticky top-[57px]`): vive dentro del
    <header> pegajoso, así que se pega arriba sin ningún offset que haya que
    mantener a mano.

    Los chips envuelven en filas (`flex flex-wrap`): todas las categorías se ven
    sin deslizar nada, y si mañana hay 20 categorías se generan 6 filas en vez de
    esconderlas detrás de un carrusel. A propósito NO se usan `flex-nowrap`,
    `overflow-x-auto` ni `shrink-0`: en una pantalla táctil no hay barra de scroll
    que avise de que a la derecha quedan más categorías, así que el carrusel se
    leía como "sólo hay estas 5".

    Coste asumido: el bloque pegajoso crece hacia abajo con el número de
    categorías. Con la carta sembrada (8 + "Todos") son 2-3 filas en un móvil de
    375px; si el catálogo se disparase, la decisión a tomar sería despegar
    (`sticky`) esta barra y dejarla fluir con la página, no volver al scroll
    horizontal.
--}}
<nav class="border-t border-stone-100" aria-label="Categorías de la carta">
    <div class="max-w-lg mx-auto">
        <div class="flex flex-wrap content-center px-4 py-2 gap-2 sm:justify-center">
            <button
                type="button"
                @click="filterByCategory(null)"
                :class="activeCategory === null ? 'bg-stone-800 text-white' : 'bg-stone-100 text-stone-600 hover:bg-stone-200'"
                class="px-4 py-2 rounded-full text-sm font-medium whitespace-nowrap transition-colors"
            >
                Todos
            </button>
            <template x-for="category in categories" :key="category.id">
                <button
                    type="button"
                    @click="filterByCategory(category.slug)"
                    :class="activeCategory === category.slug ? 'bg-stone-800 text-white' : 'bg-stone-100 text-stone-600 hover:bg-stone-200'"
                    class="px-4 py-2 rounded-full text-sm font-medium whitespace-nowrap transition-colors"
                >
                    <span x-text="category.name"></span>
                </button>
            </template>
        </div>
    </div>
</nav>

