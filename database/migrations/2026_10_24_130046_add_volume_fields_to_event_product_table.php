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
            $table->string('package_volume')->nullable()->after('purchase_unit')->comment('Volume of purchase package, e.g. 1.5L, 50L, 33cl');
            $table->string('serving_volume')->nullable()->after('serving_unit')->comment('Volume per glass/serving, e.g. 25cl, 20cl, 4cl');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('event_product', function (Blueprint $table) {
            $table->dropColumn(['package_volume', 'serving_volume']);
        });
    }
};
