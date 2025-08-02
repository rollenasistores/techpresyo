<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name', 500);
            $table->string('slug', 500)->unique();
            $table->string('model', 200)->nullable();
            $table->string('sku', 100)->nullable();
            $table->foreignId('brand_id')->constrained('brands');
            $table->foreignId('category_id')->constrained('categories');
            $table->text('description')->nullable();
            $table->json('specifications')->nullable();
            $table->json('images')->nullable();
            $table->enum('status', ['active', 'discontinued', 'coming_soon'])->default('active');
            $table->timestamps();

            $table->index(['brand_id', 'category_id']);
            $table->index('status');
            $table->fulltext(['name', 'model', 'description']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
