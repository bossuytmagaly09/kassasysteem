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
        Schema::create('cashier_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('kassa_id')->default('1');
            $table->string('cashier_name');
            $table->unsignedBigInteger('hangaar_user_id')->nullable();
            $table->string('card_uid')->nullable();
            $table->string('auth_method')->default('scanner'); // 'scanner' or 'pin'
            $table->dateTime('logged_in_at');
            $table->dateTime('logged_out_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cashier_sessions');
    }
};
