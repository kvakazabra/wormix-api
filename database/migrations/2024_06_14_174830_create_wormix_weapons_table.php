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
        Schema::create('wormix_weapons', function (Blueprint $table) {
            $table->bigInteger('id')->unsigned()->primary();

            $table->string('name')->nullable();
            $table->string('description')->nullable(); // new
            $table->string('hint')->nullable(); // new
            $table->string('note')->nullable(); // new

            $table->boolean('is_starter')->default(0);
            $table->boolean('hide_in_shop')->default(0);
            $table->boolean('boss_weapon')->default(0); // new
            $table->boolean('temporal')->default(0); // new

            $table->integer('price')->unsigned()->default(0);
            $table->integer('real_price')->unsigned()->default(0);
            $table->integer('sell_price')->unsigned()->default(0);

            $table->boolean('infinite')->default(false);
            $table->boolean('is_complex')->default(false);
            $table->smallInteger('max_shots')->default(-1); // new, one_day removed

            $table->integer('required_friends')->unsigned()->default(0);
            $table->integer('required_level')->unsigned()->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wormix_weapons');
    }
};
