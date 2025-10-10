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
        Schema::table('collection_management', function (Blueprint $table) {
            if (Schema::hasColumn('collection_management', 'sets_in_collection_id')) {
                $table->dropColumn('sets_in_collection_id');
            }   
        });

        Schema::table('sets_in_collection', function (Blueprint $table) {
            // First drop the existing foreign keys
            //$table->dropForeign(['set_id']);
            //$table->dropForeign(['collection_id']);

            // Then re-add it with ON DELETE CASCADE
            $table->foreign('set_id')
                  ->references('id')->on('magic_set_data')
                  ->onDelete('cascade');
            $table->foreign('collection_id')
                  ->references('id')->on('collection_management')
                  ->onDelete('cascade');                  
        });        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('collection_management', function (Blueprint $table) {
            $table->integer('sets_in_collection_id')->unsigned()->nullable();
        });

        Schema::table('sets_in_collection', function (Blueprint $table) {
            $table->dropForeign(['set_id']);
            $table->dropForeign(['collection_id']);

            // Re-add without cascade (if needed)
            $table->foreign('set_id')
                  ->references('id')->on('magic_set_data');
            $table->foreign('collection_id')
                  ->references('id')->on('collections');                  
        });        
    }
};
