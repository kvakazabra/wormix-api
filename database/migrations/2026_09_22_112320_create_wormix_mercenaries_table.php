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
        Schema::create('wormix_mercenaries', function (Blueprint $table) {
            $table->id();
            $table->smallInteger('level')->default(30);
            $table->smallInteger('required_level');
            $table->string('name')->default("");
            $table->smallInteger('attack');
            $table->smallInteger('armor');
            $table->smallInteger('race')->default(2);
            $table->smallInteger('skin')->default(0);
            $table->integer('hat')->default(0);
            $table->integer('artifact')->default(0);
            $table->integer('price');
            $table->integer('real_price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wormix_mercenaries');
    }
};
