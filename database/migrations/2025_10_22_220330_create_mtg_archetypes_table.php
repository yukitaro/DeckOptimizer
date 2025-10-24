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
        Schema::create('mtg_archetypes', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('format')->nullable(); // e.g. "Pauper", "Modern"
            $table->text('description')->nullable();
            $table->text('criteria')->nullable(); // DSL or JSON
            $table->timestamps();
        });

        Schema::table('deck_management', function (Blueprint $table) {
            $table->foreignId('archetype_id')->nullable()->constrained('mtg_archetypes')->after('archetype');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mtg_archetypes');

        Schema::table('deck_management', function (Blueprint $table) {
            $table->dropForeign(['archetype_id']);
            $table->dropColumn('archetype_id');
        });
    }
};
