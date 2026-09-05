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
        Schema::create('wormix_crafted_equipment', function (Blueprint $table) {
            $table->bigInteger('family_id')->unsigned()->primary();

            $table->string('name')->nullable();

            $table->boolean('hide_in_shop')->default(true);
            $table->boolean('hide_in_craft')->default(false);

            $table->integer('duration')->unsigned()->nullable();

            $table->json('craft_cost');
            $table->json('remake_cost');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wormix_crafted_equipment');
    }
};
