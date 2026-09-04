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
        Schema::table('deck_optimizer_issues', function (Blueprint $table) {
            $table->foreignId('issue_type_id')->constrained('enum_values')->cascadeOnDelete()->after('id');
            $table->dropColumn('issue_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('deck_optimizer_issues', function (Blueprint $table) {
            $table->dropForeign(['issue_type_id']);
            $table->dropColumn('issue_type_id');
            $table->string('issue_type')->after('id');
        });
    }
};
