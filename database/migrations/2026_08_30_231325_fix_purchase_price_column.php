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
            Schema::table('collected_cards', function (Blueprint $table) {
                // Step 1: temporarily make it a string and nullable
                $table->string('purchase_price')->nullable()->change();
            });

            // Step 2: clean bad data
            DB::table('collected_cards')
                ->where('purchase_price', '')
                ->orWhereNull('purchase_price')
                ->update(['purchase_price' => null]);

            DB::statement("UPDATE collected_cards SET purchase_price = TRIM(purchase_price)");

            // Step 3: convert to decimal
            Schema::table('collected_cards', function (Blueprint $table) {
                $table->decimal('purchase_price', 10, 2)->nullable()->change();
            });
        }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('collected_cards', function (Blueprint $table) {
            $table->float('purchase_price')->nullable()->change();
        });
    }
};
