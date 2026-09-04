<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('list_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('list_id')->constrained('lists')->cascadeOnDelete();

            // Polymorphic reference: card, sealed_product, list, etc.
            $table->string('item_type')->index();
            $table->unsignedBigInteger('item_id')->index();

            // Quantity is generic (cards, packs, boxes, etc.)
            $table->integer('quantity')->default(1);

            // Arbitrary metadata (JSON) for finishes, variants, conditions, target prices, roles, etc.
            $table->json('metadata')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('list_items');
    }
};