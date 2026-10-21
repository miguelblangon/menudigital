<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * La columna se creó como `is_available` pero todo el código de aplicación
 * (controladores, formularios, tests) la llama `available`; el modelo hacía de
 * puente con un accessor/mutator que además enmascaraba escritas incorrectas.
 * Se renombra la columna para que haya un único nombre real y verificable.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table("products", function (Blueprint $table) {
            $table->renameColumn("is_available", "available");
        });
    }

    public function down(): void
    {
        Schema::table("products", function (Blueprint $table) {
            $table->renameColumn("available", "is_available");
        });
    }
};
