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
            $table->integer('purchase_price')->nullable()->after('purchase_quantity')->comment('Purchase price per unit in cents');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('event_product', function (Blueprint $table) {
            $table->dropColumn('purchase_price');
        });
    }
};
