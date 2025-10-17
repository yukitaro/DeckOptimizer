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
        Schema::table('scryfall_imports', function (Blueprint $table) {
            $table->integer('record_count')->nullable()->after('filename');
            $table->timestamp('imported_at')->nullable()->after('record_count');
            $table->string('status')->default('pending')->after('imported_at');
            $table->text('error')->nullable()->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('scryfall_imports', function (Blueprint $table) {
            $table->dropColumn([
                'record_count',
                'imported_at',
                'status',
                'error',
            ]);
        });
    }

};
