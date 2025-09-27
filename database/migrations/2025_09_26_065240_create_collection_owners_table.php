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
/*         Schema::table('collections', function (Blueprint $table) {
            //$table->text('description')->nullable();
            //$table->unsignedBigInteger('owner_id')->nullable();//->constrained('users'); // future-proofing
            $table->timestamps();
        });
 */
        Schema::create('sets_in_collection', function (Blueprint $table) {
            $table->id();
            $table->foreignId('collection_id')->constrained('collections')->onDelete('cascade');
            $table->foreignId('set_id')->constrained('magic_set_data');
            $table->timestamps();
        });

        Schema::create('collected_cards', function (Blueprint $table) {
            $table->id();
            $table->boolean('is_foil')->default(false);
            $table->foreignId('set_in_collection_id')->constrained('sets_in_collection')->onDelete('cascade');
            $table->foreignId('card_data_id')->constrained('card_data_from_set_data');
            $table->integer('card_count')->default(0);
            $table->string('condition')->nullable();
            $table->string('printing_variant')->nullable();
            $table->integer('purchase_price')->nullable();
            $table->string('storage_location')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('collections');
        Schema::dropIfExists('sets_in_collection');
        Schema::dropIfExists('collected_cards');
    }
};
