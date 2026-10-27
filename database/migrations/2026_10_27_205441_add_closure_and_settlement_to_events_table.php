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
        Schema::table('events', function (Blueprint $table) {
            $table->boolean('is_closed')->default(false)->after('is_active');
            $table->dateTime('closed_at')->nullable()->after('is_closed');
            $table->boolean('poef_settled')->default(false)->after('closed_at');
            $table->dateTime('poef_settled_at')->nullable()->after('poef_settled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['is_closed', 'closed_at', 'poef_settled', 'poef_settled_at']);
        });
    }
};
