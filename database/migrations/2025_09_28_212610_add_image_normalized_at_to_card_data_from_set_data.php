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
        Schema::table('card_data_from_set_data', function (Blueprint $table) {
            $table->timestamp('image_normalized_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('card_data_from_set_data', function (Blueprint $table) {
            $table->dropColumn('image_normalized_at');
        });
    }
};
