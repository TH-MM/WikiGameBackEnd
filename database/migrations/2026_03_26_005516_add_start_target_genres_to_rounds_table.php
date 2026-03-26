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
        Schema::table('rounds', function (Blueprint $table) {
            $table->string('start_genre')->nullable()->after('language');
            $table->string('target_genre')->nullable()->after('start_genre');
            $table->dropColumn('genre');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rounds', function (Blueprint $table) {
            $table->string('genre')->nullable()->after('language');
            $table->dropColumn('start_genre');
            $table->dropColumn('target_genre');
        });
    }
};
