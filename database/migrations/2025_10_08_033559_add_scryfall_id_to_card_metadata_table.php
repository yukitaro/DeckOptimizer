<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('card_metadata', function (Blueprint $table) {
            $table->string('scryfall_id', 36)
                ->storedAs("JSON_UNQUOTE(JSON_EXTRACT(identifiers, '$.scryfallId'))")
                ->nullable();

            $table->index('scryfall_id', 'idx_scryfall_id');
        });
    }

    public function down()
    {
        Schema::table('card_metadata', function (Blueprint $table) {
            $table->dropIndex('idx_scryfall_id');
            $table->dropColumn('scryfall_id');
        });
    }
};
