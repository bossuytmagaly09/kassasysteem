<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('poefs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('external_id')->unique()->comment('drankdb.users.id');
            $table->string('name');
            $table->integer('discount_percentage')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('poefs');
    }
};
