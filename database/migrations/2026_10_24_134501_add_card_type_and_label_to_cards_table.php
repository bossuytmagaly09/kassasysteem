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
        Schema::table('cards', function (Blueprint $table) {
            $table->string('card_type')->nullable()->default('badge')->after('uid')->comment('badge, bankkaart, kaart, tag, etc.');
            $table->string('label')->nullable()->after('card_type')->comment('Optional description / nickname');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cards', function (Blueprint $table) {
            $table->dropColumn(['card_type', 'label']);
        });
    }
};
