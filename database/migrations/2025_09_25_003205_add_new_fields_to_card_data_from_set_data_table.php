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
            $table->foreignId('card_metadata_id')->nullable()->constrained('card_metadata');
            $table->string('set_code')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('card_data_from_set_data', function (Blueprint $table) {
            $table->dropForeign(['card_metadata_id']);
            $table->dropColumn(['card_metadata_id', 'set_code']);
        });
    }
};
