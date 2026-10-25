<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->decimal('punch_value', 8, 2)->nullable()->after('vak_price');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->string('kind')->default('consumption')->after('name');
            $table->integer('punch_cost')->nullable()->after('price');
            $table->integer('punches')->nullable()->after('punch_cost');
            $table->integer('sort')->default(0)->after('is_active');
        });

        Schema::table('poefs', function (Blueprint $table) {
            $table->unsignedBigInteger('hangaar_user_id')->nullable()->after('external_id')->index();
            $table->unsignedBigInteger('drank_user_id')->nullable()->after('hangaar_user_id')->index();
            $table->string('poef_name')->nullable()->after('name');
            $table->boolean('has_poef')->default(true)->after('discount_percentage');
        });

        Schema::table('cards', function (Blueprint $table) {
            $table->string('uid')->nullable()->change();
            $table->string('card_hash', 64)->nullable()->after('uid')->index();
            $table->unsignedBigInteger('hangaar_user_id')->nullable()->after('poef_id')->index();
            $table->unsignedBigInteger('drank_user_id')->nullable()->after('hangaar_user_id')->index();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->string('uuid', 36)->nullable()->after('id')->index();
            $table->string('card_hash', 64)->nullable()->after('card_uid')->index();
            $table->string('device', 50)->nullable()->after('card_hash');
            $table->string('beheer_status', 20)->default('pending')->after('status')->index();
            $table->unsignedBigInteger('beheer_sale_id')->nullable()->after('beheer_status');
            $table->text('beheer_error')->nullable()->after('beheer_sale_id');
            $table->timestamp('synced_at')->nullable()->after('settled_at');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['uuid', 'card_hash', 'device', 'beheer_status', 'beheer_sale_id', 'beheer_error', 'synced_at']);
        });

        Schema::table('cards', function (Blueprint $table) {
            $table->dropColumn(['card_hash', 'hangaar_user_id', 'drank_user_id']);
        });

        Schema::table('poefs', function (Blueprint $table) {
            $table->dropColumn(['hangaar_user_id', 'drank_user_id', 'poef_name', 'has_poef']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['kind', 'punch_cost', 'punches', 'sort']);
        });

        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('punch_value');
        });
    }
};
