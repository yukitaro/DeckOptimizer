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
        Schema::create('magic_set_data', function (Blueprint $table) {
            $table->id()->primary();
            $table->string('set_name');
            $table->string('official_set_code');
            $table->string('release_date');
            $table->string('total_cards')->nullable();
            $table->timestamps();
        });

        Schema::create('card_data_from_set_data', function (Blueprint $table) {
            $table->id()->primary();
            $table->string('name');
            $table->index('name');
            $table->foreignId('magic_set_data_id')->constrained('magic_set_data')->onDelete('cascade');
            $table->string('set_name');
            $table->string('number_in_set');
            $table->string('card_uuid');
            $table->string('card_multiverse_id')->nullable();
            $table->string('type');
            $table->string('types');
            $table->string('keywords')->nullable();
            $table->string('colors');
            $table->string('colorIdentities');
            $table->string('mana_cost')->nullable();
            $table->integer('mana_value')->default(0);
            $table->mediumText('printings');
            $table->string('rarity')->nullable();
            $table->mediumText('text')->nullable();
            $table->string('power')->nullable();
            $table->string('toughness')->nullable();
            $table->string('image_url')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('card_data_from_set_data');
        Schema::dropIfExists('magic_set_data');
    }
};
