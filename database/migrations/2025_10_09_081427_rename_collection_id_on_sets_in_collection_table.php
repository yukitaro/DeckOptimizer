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
        Schema::table('collection', function (Blueprint $table) {
             Schema::table('sets_in_collection', function (Blueprint $table) {
            $table->renameColumn('collection_id', 'collection_management_id');
        });
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('collection', function (Blueprint $table) {
             $table->renameColumn('collection_management_id', 'collection_id');
        });
    }
};
