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
        // Add missing indexes for sets_in_collections (currently has no indexes!)
        Schema::table('sets_in_collections', function (Blueprint $table) {
            $table->index(['collection_id', 'collected_cards_from_sets_id'], 'sets_collections_composite_idx');
            $table->index('collection_id', 'sets_collections_collection_idx');
        });

        // Add missing indexes for collected_cards_from_sets (currently has no indexes!)
        Schema::table('collected_cards_from_sets', function (Blueprint $table) {
            $table->index('sets_in_collection_id', 'collected_cards_sets_idx');
            $table->index('card_data_from_set_data_id', 'collected_cards_card_idx');
            // Composite index for duplicate detection in CollectedCardService
            $table->index(['sets_in_collection_id', 'card_data_from_set_data_id'], 'collected_cards_composite_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sets_in_collections', function (Blueprint $table) {
            $table->dropIndex('sets_collections_composite_idx');
            $table->dropIndex('sets_collections_collection_idx');
        });

        Schema::table('collected_cards_from_sets', function (Blueprint $table) {
            $table->dropIndex('collected_cards_sets_idx');
            $table->dropIndex('collected_cards_card_idx');
            $table->dropIndex('collected_cards_composite_idx');
        });
    }
};