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
            $table->foreignId('card_data_from_set_data_id')->nullable()->constrained('card_data_from_set_data');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('card_metadata', function (Blueprint $table) {
            $table->dropForeign(['card_data_from_set_data_id']);
            $table->dropColumn(['card_data_from_set_data_id']);
        });
    }
};
