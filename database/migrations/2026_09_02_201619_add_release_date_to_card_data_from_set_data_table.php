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
        Schema::table('card_data_from_set_data', function (Blueprint $table) {
            $table->date('release_date')->nullable()->after('set_code')->index();
        });
    }

    public function down(): void
    {
        Schema::table('card_data_from_set_data', function (Blueprint $table) {
            $table->dropIndex(['release_date']);
            $table->dropColumn('release_date');
        });
    }
};
