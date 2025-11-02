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
        Schema::table('deck_management', function (Blueprint $table) {
            $table->string('visibility')->default('private')->after('deck_owner_id');
        });

        Schema::table('collection_management', function (Blueprint $table) {
            $table->string('visibility')->default('private')->after('owner_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('deck_management', function (Blueprint $table) {
            $table->dropColumn('visibility');
        });

        Schema::table('collection_management', function (Blueprint $table) {
            $table->dropColumn('visibility');
        });
    }
};
