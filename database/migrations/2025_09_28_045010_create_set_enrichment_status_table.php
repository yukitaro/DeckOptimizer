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
        Schema::create('set_enrichment_status', function (Blueprint $table) {
            $table->id();
            $table->string('set_code')->unique();
            $table->timestamp('enriched_at')->nullable();
            $table->string('logic_version')->nullable(); // e.g. "v1", "v2", "2025-09-27"
            $table->json('flags')->nullable(); // optional: track which enrichment steps were applied
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('set_enrichment_status');
    }
};
