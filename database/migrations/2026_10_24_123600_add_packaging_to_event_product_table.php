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
        Schema::table('event_product', function (Blueprint $table) {
            $table->string('purchase_unit')->nullable()->after('stock_planned');
            $table->integer('purchase_quantity')->nullable()->after('purchase_unit');
            $table->integer('servings_per_unit')->nullable()->after('purchase_quantity');
            $table->string('serving_unit')->nullable()->after('servings_per_unit');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('event_product', function (Blueprint $table) {
            $table->dropColumn(['purchase_unit', 'purchase_quantity', 'servings_per_unit', 'serving_unit']);
        });
    }
};
