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
            // Starting value is a lazy fix of a problem where result is "merged" with a battle id
            $table->id()->primary()
                ->autoIncrement()->startingValue(10);
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('co_battle_id')->nullable()
                ->comment('teammates battle id, if there is one');

            $table->smallInteger('result')->nullable();
            $table->smallInteger('type')->default(0);
            $table->smallInteger('mission_id')->default(0);
            $table->json('reagents')->default('[]')->comment('is set for regular missions, seeded by the server before a battle begins');
            $table->json('collected_reagents')->default('[]');
            $table->smallInteger('exp_bonus')->default(0);
            $table->smallInteger('random_seed')->default(0);
            $table->json('awards')->default('[]');

            $table->smallInteger('ban_type')->default(0);
            $table->string('ban_note')->default('');

            $table->smallInteger('total_turns')->default(0);
            $table->integer('total_damage_to_player')->default(0);
            $table->integer('total_damage_to_boss')->default(0);

            $table->json('used_items')->default('[]');
            $table->json('total_used_items')->default('[]');

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
