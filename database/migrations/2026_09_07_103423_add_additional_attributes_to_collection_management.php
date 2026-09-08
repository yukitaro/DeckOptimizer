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
        Schema::table('collection_management', function (Blueprint $table) {
            $table->string('type')->default('collection')->index()->after('description'); // collection, deck, binder, wishlist
            $table->string('game_type')->default('mtg')->index()->after('type'); // mtg, pokemon, lorcana
            $table->boolean('is_favorite')->default(false)->index()->after('visibility');
            $table->boolean('include_in_inventory')->default(true)->index()->after('is_favorite');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('collection_management', function (Blueprint $table) {
            $table->dropColumn('is_favorite');
            $table->dropColumn('include_in_inventory');
            $table->dropColumn('type');
            $table->dropColumn('game_type');
        });
    }
};
