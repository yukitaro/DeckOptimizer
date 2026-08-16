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
        Schema::create('site_features', function (Blueprint $table) {
            $table->id();
            $table->string('site_mode'); // e.g. mtg, one-piece, operations
            $table->string('feature_name');
            $table->string('slug'); // machine-friendly id
            $table->string('description')->nullable();
            $table->boolean('is_enabled')->default(true);
            $table->boolean('is_global')->default(false);
            $table->integer('sort_order')->default(0);
            $table->json('meta')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('deprecated_at')->nullable();
            $table->timestamps();

            $table->unique(['site_mode', 'feature_name']);
            $table->unique(['site_mode', 'slug']);
            $table->index('site_mode');
        });

        Schema::table('deck_optimizer_issues', function (Blueprint $table) {
            $table->foreignId('site_feature_id')->nullable()->constrained('site_features')->onDelete('set null')->after('id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('deck_optimizer_issues', function (Blueprint $table) {
            $table->dropForeign(['site_feature_id']);
            $table->dropColumn('site_feature_id');
        });
        Schema::dropIfExists('site_features');
    }
};
