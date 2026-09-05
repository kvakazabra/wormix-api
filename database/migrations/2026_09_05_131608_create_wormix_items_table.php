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
        Schema::create('wormix_items', function (Blueprint $table) {
            $table->bigInteger('id')->unsigned()->primary();

            $table->string('name')->nullable();

            $table->boolean('hide_in_shop')->default(0);

            $table->integer('price')->unsigned()->default(0);
            $table->integer('real_price')->unsigned()->default(0);

            $table->integer('duration')->unsigned()->nullable();

            $table->integer('required_scenario')->unsigned()->default(0);
            $table->integer('required_level')->unsigned()->default(0);
            $table->integer('required_rating')->unsigned()->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wormix_items');
    }
};
