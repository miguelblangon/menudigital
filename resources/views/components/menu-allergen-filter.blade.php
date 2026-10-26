{{--
    Bottom sheet de filtros.

    x-cloak + la regla [x-cloak] de app.css: Alpine arranca diferida (el bundle
    es un módulo), así que sin esto este overlay a pantalla completa se pinta
    durante un instante por encima de la carta.

    max-h-[85dvh] en lugar de 80vh: en móvil `vh` usa la altura de la barra de
    direcciones sin plegar, y el sheet terminaba cortado o con la fila de
    acciones fuera de la zona útil. `dvh` sigue la altura visible real.
    pb-safe deja sitio a la barra de gestos del iPhone.
--}}
<div x-show="showAllergenFilter" x-cloak class="fixed inset-0 z-50 flex items-end justify-center bg-black/50 backdrop-blur-sm" x-transition.opacity>
    <div
        role="dialog"
        aria-modal="true"
        aria-label="Filtrar la carta por alérgenos"
        class="bg-white w-full max-w-lg rounded-t-3xl p-4 sm:p-6 pb-safe max-h-[85dvh] overflow-y-auto"
        x-transition:enter="transition ease-out duration-300 transform translate-y-full"
        x-transition:enter-end="transform translate-y-0"
    >
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-xl font-bold text-stone-800 font-display">Filtrar por alérgenos</h2>
            <button type="button" @click="showAllergenFilter = false" aria-label="Cerrar filtros" class="p-2 hover:bg-stone-100 rounded-full transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-stone-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <p class="text-sm text-stone-600 mb-4">Selecciona los alérgenos que quieres excluir:</p>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <template x-for="allergen in allergenOptions" :key="allergen.value">
                <button 
                    type="button"
                    @click="toggleAllergen(allergen.value)"
                    :aria-pressed="selectedAllergens.includes(allergen.value)"
                    :class="selectedAllergens.includes(allergen.value) ? 'bg-red-100 text-red-800 border-red-300' : 'bg-white text-stone-700 border-stone-200 hover:bg-stone-50'"
                    class="flex items-center gap-2 px-4 py-3 rounded-xl border-2 transition-colors"
                >
                    <div :class="selectedAllergens.includes(allergen.value) ? 'bg-red-500' : 'bg-stone-200'" class="w-5 h-5 rounded-full flex items-center justify-center">
                        <svg x-show="selectedAllergens.includes(allergen.value)" xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <span class="text-sm font-medium" x-text="allergen.label"></span>
                </button>
            </template>
        </div>

        <!-- Actions -->
        <div class="flex flex-col sm:flex-row gap-3 mt-6">
            <button 
                type="button"
                @click="selectedAllergens = []"
                class="flex-1 px-4 py-3 bg-stone-100 text-stone-700 rounded-xl font-medium hover:bg-stone-200 transition-colors"
            >
                Limpiar
            </button>
            <button 
                type="button"
                @click="showAllergenFilter = false"
                class="flex-1 px-4 py-3 bg-stone-800 text-white rounded-xl font-medium hover:bg-stone-900 transition-colors"
            >
                Aplicar filtros
            </button>
        </div>
    </div>
</div>