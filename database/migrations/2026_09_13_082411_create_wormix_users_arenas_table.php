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
        Schema::create('wormix_users_arenas', function (Blueprint $table) {
            $table->foreignId('user_id')->primary()
                ->constrained('users')->cascadeOnDelete();

            $table->integer('battle_tokens')->unsigned()->default(10);
            $table->integer('wager_tokens')->unsigned()->default(1);
            $table->integer('boss_tokens')->unsigned()->default(1);
            $table->integer('heroic_tokens')->unsigned()->default(1);

            $table->integer('solo_mission_id')->unsigned()->default(0);
            $table->integer('coop_mission_id')->unsigned()->default(0);

            $table->foreignId('current_battle_id')->nullable()
                ->constrained('wormix_users_battles')->nullOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wormix_users_arenas');
    }
};
