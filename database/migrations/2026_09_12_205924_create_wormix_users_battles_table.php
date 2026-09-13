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
        Schema::create('wormix_users_battles', function (Blueprint $table) {
            $table->id()->primary()->autoIncrement();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('co_battle_id')->comment('teammates battle id, if there is one');

            $table->string('type');
            $table->smallInteger('mission_id');
            $table->json('reagents')->default('[]')->comment('is set for regular missions, seeded by the server before a battle begins');
            $table->json('collected_reagents')->default('[]');
            $table->smallInteger('exp_bonus');
            $table->smallInteger('random_seed');
            $table->json('awards');

            $table->smallInteger('ban_type');
            $table->string('ban_note');

            $table->smallInteger('total_turns');
            $table->integer('total_damage_to_player');
            $table->integer('total_damage_to_boss');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wormix_users_battles');
    }
};
