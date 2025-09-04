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
        Schema::create('magic_sets', function (Blueprint $table) {
            $table->increments('id')->primary();
            $table->string('set_name');
            $table->string('official_set_id');
            $table->string('published_year');
            $table->string('total_cards')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('card_data', function (Blueprint $table) {
            $table->id()->primary();
            $table->string('name');
            $table->string('set_name');
            $table->string('official_set_id');
            $table->string('card_id');
            $table->string('card_uuid');
            $table->string('card_multiverse_id');
            $table->string('type');
            $table->string('colors');
            $table->string('mana_cost');
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
        Schema::dropIfExists('magic_sets');
        Schema::dropIfExists('cards');
    }
};
