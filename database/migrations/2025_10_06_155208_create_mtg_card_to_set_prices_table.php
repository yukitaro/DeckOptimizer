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
        Schema::create('mtg_card_to_set_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('card_metadata_id')->constrained('card_metadata')->onDelete('cascade');
            $table->foreignId('card_data_id')->constrained('card_data_from_set_data')->onDelete('cascade');
            $table->foreignId('magic_set_data_id')->constrained('magic_set_data')->onDelete('cascade');
            $table->string('currency')->default('usd');
            $table->decimal('price', 8, 2)->default('0')->nullable();
            $table->boolean('is_foil')->default(false);
            $table->date('price_date');
            $table->string('source')->default('scryfall');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mtg_card_to_set_prices');
    }
};
