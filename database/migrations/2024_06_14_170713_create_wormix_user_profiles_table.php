<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Query\Expression;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('wormix_user_profiles', function (Blueprint $table) {
            $table->foreignId('user_id')->primary()->constrained('users')->cascadeOnDelete();

            $table->integer('money')->unsigned()->default(450)->comment('fuses');
            $table->integer('real_money')->unsigned()->default(3)->comment('rubies');

            $table->smallInteger('extra_group_slots')->default(0)->comment('bought slots for teammates');
            $table->smallInteger('rank')->unsigned()->default(20)->comment('rank');
            $table->integer('rank_points')->unsigned()->default(0)->comment('rank points');
            $table->integer('rating')->unsigned()->default(0)->comment('user rating');
            $table->integer('reaction_rate')->unsigned()->default(0)->comment('user reaction rate');
            $table->integer('race_change_timestamp')->unsigned()->default(0)->comment('timestamp');

            $table->json('reagents')->default('[]')->comment('user reagents');
            $table->json('recipes')->default('[]')->comment('user craft weapons');
            $table->json('races')->default('[2]'); // new
            $table->json('skins')->default('[]');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wormix_user_profiles');
    }
};
