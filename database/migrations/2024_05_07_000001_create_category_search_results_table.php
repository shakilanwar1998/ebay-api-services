<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('category_search_results', function (Blueprint $table) {
            $table->id();
            $table->string('title')->index();
            $table->json('suggestions');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('category_search_results');
    }
};