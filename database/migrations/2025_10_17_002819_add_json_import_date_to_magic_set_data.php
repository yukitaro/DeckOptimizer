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
        Schema::table('magic_set_data', function (Blueprint $table) {
            $table->date('date_of_json_used_for_import')->nullable()->after('release_date');
        });
    }

    public function down()
    {
        Schema::table('magic_set_data', function (Blueprint $table) {
            $table->dropColumn('date_of_json_used_for_import');
        });
    }
};
