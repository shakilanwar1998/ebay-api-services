<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('aspects', function (Blueprint $table) {
            $table->boolean('required')->default(false)->after('values');
            $table->string('data_type')->default('STRING')->after('required');
            $table->string('mode')->default('FREE_TEXT')->after('data_type');
            $table->string('usage')->default('RECOMMENDED')->after('mode');
        });
    }

    public function down(): void
    {
        Schema::table('aspects', function (Blueprint $table) {
            $table->dropColumn(['required', 'data_type', 'mode', 'usage']);
        });
    }
}; 