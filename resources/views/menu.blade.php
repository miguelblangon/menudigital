<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cafetería - Carta Digital</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
</head>
<body class="bg-stone-50 min-h-screen" x-data="menuApp()">
    @include('components.menu-header')
    @include('components.menu-categories')

    <!-- Products Grid -->
    <main class="max-w-lg mx-auto px-4 py-6">
        <div class="grid gap-4">
            <template x-for="product in filteredProducts" :key="product.id">
                @include('components.menu-product-card')
            </template>

            <!-- Empty State -->
            <template x-if="filteredProducts.length === 0">
                <div class="text-center py-12">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mx-auto text-stone-300 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p class="text-stone-500">No hay productos que coincidan con los filtros</p>
                </div>
            </template>
        </div>
    </main>

    @include('components.menu-allergen-filter')

    <script>
        function menuApp() {
            return {
                categories: @json($categories),
                products: @json($products),
                activeCategory: null,
                showAllergenFilter: false,
                selectedAllergens: [],
                
                allergenOptions: @json($allergens->map(fn($a) => ['value' => strtolower($a->name), 'label' => $a->name])),
                
                get filteredProducts() {
                    let filtered = this.products;
                    
                    if (this.activeCategory) {
                        const category = this.categories.find(c => c.slug === this.activeCategory);
                        if (category) {
                            filtered = filtered.filter(p => p.category_id === category.id);
                        }
                    }
                    
                    if (this.selectedAllergens.length > 0) {
                        filtered = filtered.filter(product => {
                            return !product.allergens.some(allergen => 
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
                }
            };
        }
    </script>
</body>
</html>