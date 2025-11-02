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
        Schema::table('users', function (Blueprint $table) {
            $table->string('alias')->nullable()->after('name')->unique();
        });

        Schema::table('collection_management', function (Blueprint $table) {
            $table->foreign('owner_id')->references('id')->on('users')->onDelete('cascade');
        });

        Schema::table('deck_management', function (Blueprint $table) {
            $table->foreign('deck_owner_id')->references('id')->on('users')->onDelete('cascade');
        });        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('alias');
        });

        Schema::table('collection_management', function (Blueprint $table) {
            $table->dropForeign(['owner_id']);
        });

        Schema::table('deck_management', function (Blueprint $table) {
            $table->dropForeign(['deck_owner_id']);
        });
    }
};
