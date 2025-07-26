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
        Schema::table('products', function (Blueprint $table) {
            $columnsToDrop = [
                'sku',
                'listing_id',
                'category_id',
                'local_category_name',
                'price',
                'stock',
                'condition',
                'shipping_details',
                'postal_code',
                'specifications',
                'exceptions',
                'created_at', // will be re-added below
                'updated_at', // will be re-added below
            ];
            foreach ($columnsToDrop as $column) {
                if (Schema::hasColumn('products', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('sku')->nullable();
            $table->string('listing_id')->nullable();
            $table->integer('category_id')->nullable();
            $table->integer('local_category_name')->nullable();
            $table->decimal('price')->default(0);
            $table->integer('stock')->default(0);
            $table->string('condition')->nullable();
            $table->json('shipping_details')->nullable();
            $table->string('postal_code')->nullable();
            $table->json('specifications')->nullable();
            $table->json('exceptions')->nullable();
        });
    }
}; 