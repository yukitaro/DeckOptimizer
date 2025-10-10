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
        Schema::table('mtg_image_lookups', function (Blueprint $table) {
            $table->json('scryfall_image_uris')->nullable()->after('canonical_image_url_back');
            $table->boolean('hydrated_via_command')->default(false)->after('scryfall_image_uris');
        });
    }

    public function down()
    {
        Schema::table('mtg_image_lookups', function (Blueprint $table) {
            $table->dropColumn('scryfall_image_uris');
        });
    }
};
