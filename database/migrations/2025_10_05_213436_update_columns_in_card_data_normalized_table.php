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
        Schema::table('card_data_normalized', function (Blueprint $table) {
            $table->dropColumn([
                'name',
                'type',
                'colors',
                'mana_cost',
                'rarity',
                'text',
                'power',
                'toughness',
            ]);
        });

        Schema::table('card_data_normalized', function (Blueprint $table) {
            $table->string('normalized_name');
        });


    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('card_data_normalized', function (Blueprint $table) {
            $table->string('name');
            $table->string('type');
            $table->string('colors');
            $table->string('mana_cost')->nullable();
            $table->string('rarity')->nullable();
            $table->mediumText('text')->nullable();
            $table->string('power')->nullable();
            $table->string('toughness')->nullable();
        });

        Schema::table('card_data_normalized', function (Blueprint $table) {
            $table->dropColumn([
                'normalized_name',
            ]);
        });
    }
};
