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
        Schema::create('mtg_deck_board_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('deck_id')->constrained('deck_management')->onDelete('cascade');
            $table->string('board_type'); // 'main', 'side', 'commander', 'companion', etc.
            $table->string('label')->nullable(); // Optional: 'Main Deck', 'Sideboard', 'Commander'
            $table->integer('num_cards')->default(0); // Track number of cards in this group
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mtg_deck_board_groups');
        Schema::table('cards_in_deck', function (Blueprint $table) {
            $table->dropForeign(['mtg_deck_board_group_id']);
            $table->dropColumn('mtg_deck_board_group_id');
        });
    }
};
