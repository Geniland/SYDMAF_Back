<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('produits', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->string('name'); // Nom du produit
            $table->text('description'); // Description du produit
            $table->decimal('price', 10, 2); // Prix avec 2 décimales
            $table->string('image_path'); // Chemin de l'image
            $table->timestamps(); // Champs created_at et updated_at
            $table->unsignedBigInteger('categories_id')->nullable(); // Clé étrangère vers catégories
            $table->foreign('categories_id')->references('id')->on('categories')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('produits');
    }
};
