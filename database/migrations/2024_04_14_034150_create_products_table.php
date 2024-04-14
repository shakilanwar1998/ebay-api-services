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
        Schema::create('products', function (Blueprint $table) {
            $table->id();

            $table->string('sku')->nullable();
            $table->string('listing_id')->nullable();
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->integer('category_id')->nullable();
            $table->integer('local_category_name')->nullable();
            $table->decimal('price')->default(0);
            $table->integer('stock')->default(0);
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->json('images')->nullable();
            $table->string('condition')->nullable();
            $table->json('shipping_details')->nullable();
            $table->index('sku');
            $table->timestamps();
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
