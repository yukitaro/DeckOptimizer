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
        Schema::create('mtg_json_import_candidates', function (Blueprint $table) {
            $table->id();
            $table->string('set_code')->index();
            $table->string('set_name')->nullable();
            $table->date('release_date')->nullable();
            $table->integer('total_cards')->nullable();
            $table->integer('metadata_count')->nullable();
            $table->integer('normalized_count')->nullable();
            $table->integer('image_count')->nullable();
            $table->float('metadata_pct')->nullable();
            $table->float('normalization_pct')->nullable();
            $table->float('image_pct')->nullable();
            $table->bigInteger('json_file_size_bytes')->nullable();
            $table->date('json_file_date')->nullable();
            $table->boolean('imported_into_database')->default(false);
            $table->boolean('ready_for_import')->default(false);
            $table->timestamps();
        });

        Schema::table('magic_set_data', function (Blueprint $table) {
            $table->boolean('imported_from_mtgjson')->nullable()->default(false);
            $table->date('date_of_json_used_for_import')->nullable()->default(null);
        });
    }

    public function down()
    {
        Schema::dropIfExists('mtg_json_import_candidates');
        Schema::table('magic_set_data', function (Blueprint $table) {
            $table->dropColumn('imported_from_mtgjson');
            $table->dropColumn('date_of_json_used_for_import');
        });
    }
};
