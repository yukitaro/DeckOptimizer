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
        Schema::create('mtg_image_lookups', function (Blueprint $table) {
            $table->id();
            $table->string('card_uuid')->unique();
            $table->string('original_image_url')->nullable()->default(null);
            $table->string('canonical_image_url')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mtg_image_lookups');
    }
};
