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
        Schema::create('deck_owner', function (Blueprint $table) {
            $table->id()->primary();
            $table->string('owner_login')->nullable();
            $table->string('free_text');
            $table->foreignId('deck_id');
            $table->timestamps();
        });

        Schema::create('deck_management', function (Blueprint $table) {
            $table->id()->primary();
            $table->foreignId('deck_owner_id');
            $table->foreignId('cards_in_deck_id');
            $table->string('deck_name');
            $table->string('description');
            $table->string('external_link')->nullable()->default('');
            $table->integer('num_cards')->nullable()->default(0);
            $table->timestamps();
        });

        Schema::create('cards_in_deck', function (Blueprint $table) {
            $table->id()->primary();
            $table->string('card_name');
            $table->integer('card_count');
            $table->string('image_url')->nullable()->default('');
            $table->foreignId('deck_management_id');
            $table->foreignId('card_data_normalized_id');
        });        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deck_management');
    }
};
