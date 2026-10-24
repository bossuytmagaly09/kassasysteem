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
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('event_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->string('payment_method')->nullable()->after('status');
            $table->string('card_uid')->nullable()->after('payment_method');
            $table->integer('vakjes')->nullable()->after('card_uid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('event_id');
            $table->dropColumn(['payment_method', 'card_uid', 'vakjes']);
        });
    }
};
