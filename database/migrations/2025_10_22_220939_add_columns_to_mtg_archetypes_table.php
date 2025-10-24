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
        Schema::table('mtg_archetypes', function (Blueprint $table) {
            $table->string('archetype_source_site')->nullable()->default('mtgdecks');
            $table->string('archetype_source_site_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mtg_archetypes', function (Blueprint $table) {
            $table->dropColumn('archetype_source_site');
            $table->dropColumn('archetype_source_site_id');
        });
    }
};
