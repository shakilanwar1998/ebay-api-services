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
        Schema::table('credentials', function (Blueprint $table) {
            $table->dateTime('rf_token_valid_till')->nullable();
            $table->dateTime('access_token_valid_till')->nullable();
            $table->dateTime('app_token_valid_till')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('credentials', function (Blueprint $table) {
            $table->dropColumn('access_token_valid_till');
            $table->dropColumn('app_token_valid_till');
            $table->dropColumn('rf_token_valid_till');
        });
    }
};
