<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cards', function (Blueprint $table) {
            $table->foreignId('poef_id')->nullable()->after('id')->constrained()->nullOnDelete();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('poef_id')->nullable()->after('card_id')->constrained()->nullOnDelete();
            $table->timestamp('settled_at')->nullable()->after('status');
        });

        // Update default status for new orders to 'open' instead of 'paid'
        Schema::table('orders', function (Blueprint $table) {
            $table->string('status')->default('open')->change();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('status')->default('paid')->change();
            $table->dropForeign(['poef_id']);
            $table->dropColumn(['poef_id', 'settled_at']);
        });

        Schema::table('cards', function (Blueprint $table) {
            $table->dropForeign(['poef_id']);
            $table->dropColumn('poef_id');
        });
    }
};
