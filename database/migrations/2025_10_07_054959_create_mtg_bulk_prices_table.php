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
        Schema::create('mtg_bulk_prices', function (Blueprint $table) {
            $table->string('scryfall_id')->primary(); // unique per printing
            $table->string('oracle_id')->index();     // groups printings
            $table->string('name');                   // card name
            $table->string('set_name');               // canonical set name
            $table->string('collector_number');       // collector number
            $table->decimal('usd', 8, 2)->nullable(); // non-foil price
            $table->decimal('usd_foil', 8, 2)->nullable(); // foil price
            $table->string('rarity')->nullable();     // optional
            $table->date('released_at')->nullable();  // release date
            $table->text('image_uri')->nullable();    // for frontend preview
            $table->date('price_date');               // import date
            $table->timestamps();                     // created_at / updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mtg_bulk_prices');
    }
};
