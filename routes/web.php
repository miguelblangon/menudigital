<?php

use App\Http\Controllers\Admin\AllergenController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\QrController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\ProfileController;
use App\Models\Allergen;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Facades\Route;

// Ruta pública de la carta
Route::get("/", [MenuController::class, "index"])->name("menu");

// Dashboard (requiere autenticación)
Route::get('/dashboard', function () {
    $productsCount = Product::count();
    $categoriesCount = Category::count();
    $allergensCount = Allergen::count();
    $recentProducts = Product::with('category')->latest()->take(5)->get();

    return view('dashboard', [
        'productsCount' => $productsCount,
        'categoriesCount' => $categoriesCount,
        'allergensCount' => $allergensCount,
        'recentProducts' => $recentProducts,
    ]);
})->middleware(['auth'])->name('dashboard');

// Rutas de administración (requieren autenticación)
Route::prefix("admin")->name("admin.")->middleware(['auth'])->group(function () {
    Route::resource("products", ProductController::class);
    Route::resource("categories", CategoryController::class);
    Route::resource("allergens", AllergenController::class);

    // QR Code routes
    Route::get("qr", [QrController::class, "index"])->name("qr.index");
    Route::get("qr/generate", [QrController::class, "generate"])->name("qr.generate");
    Route::get("qr/download", [QrController::class, "download"])->name("qr.download");
});

// Rutas de perfil (requieren autenticación)
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
