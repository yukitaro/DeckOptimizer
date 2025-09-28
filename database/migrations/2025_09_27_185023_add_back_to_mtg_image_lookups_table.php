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
        Schema::table('mtg_image_lookups', function (Blueprint $table) {
            $table->string('canonical_image_url_back')->nullable()->default(null)->after('canonical_image_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mtg_image_lookups', function (Blueprint $table) {
            //
        });
    }
};
