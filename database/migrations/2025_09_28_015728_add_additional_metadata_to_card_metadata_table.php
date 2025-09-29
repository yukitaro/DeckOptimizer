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
        Schema::table('card_metadata', function (Blueprint $table) {
            $table->json('purchaseUrls')->nullable();
            $table->json('identifiers')->nullable();
            $table->json('normalized_attributes')->nullable();
            $table->string('normalized_name')->nullable()->index();
            $table->string('borderColor')->nullable()->default('');
            $table->boolean('isFullArt')->nullable()->default(false);
            $table->string('frameVersion')->nullable()->default('');
            $table->boolean('hasFoil')->nullable()->default(false);
            $table->boolean('hasNonFoil')->nullable()->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('card_metadata', function (Blueprint $table) {
            $table->dropColumn('frameEffects');
            $table->dropColumn('borderColor');
            $table->dropColumn('isFullArt');
            $table->dropColumn('frameVersion');
            $table->dropColumn('hasFoil');
            $table->dropColumn('hasNonFoil');
            $table->dropColumn('purchaseUrls');
            $table->dropColumn('identifiers');
            $table->dropColumn('normalized_attributes');
            $table->dropColumn('normalized_name');
        });
    }
};
