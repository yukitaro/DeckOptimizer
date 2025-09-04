<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('collections', function (Blueprint $table) {
            $table->id()->primary();
            $table->string('collection_name');
            $table->string('password');
        });

        Schema::create('cards_in_collection', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('card_id');
            $table->foreignId('collection_id');
            $table->integer('count_owned')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('collections');
        Schema::dropIfExists('cards_in_collection');
    }
};
