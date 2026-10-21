<nav class="sticky top-[57px] z-40 bg-white border-b border-stone-100">
    <div class="max-w-lg mx-auto">
        <div class="flex flex-wrap px-4 py-2 gap-2 justify-center">
            <button 
                @click="filterByCategory(null)"
                :class="activeCategory === null ? 'bg-stone-800 text-white' : 'bg-stone-100 text-stone-600 hover:bg-stone-200'"
                class="px-4 py-2 rounded-full text-sm font-medium whitespace-nowrap transition-colors"
            >
                Todos
            </button>
            <template x-for="category in categories" :key="category.id">
                <button 
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