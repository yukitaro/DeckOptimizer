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
            $table->string('set_code')->nullable();
            $table->foreignId('source_printing_id')->nullable()->constrained('card_data_from_set_data');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('card_data_normalized', function (Blueprint $table) {
            $table->dropForeign(['source_printing_id']);
            $table->dropColumn(['set_code', 'source_printing_id']);
        });
    }
};
