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
        Schema::table('deck_management', function (Blueprint $table) {
            $table->string('archetype')->nullable();
            $table->string('format')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('deck_management', function (Blueprint $table) {
            $table->dropColumn('archetype');
            $table->dropColumn('format');
        });
    }
};
