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
        Schema::create('wormix_characters_data', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')
                ->constrained('users')->cascadeOnDelete();
            $table->bigInteger('profile_id');
            $table->smallInteger('type')->default(0);

            $table->string("name")->default("");
            $table->smallInteger('armor')->default(1);
            $table->smallInteger('attack')->default(1);

            $table->smallInteger('level')->default(1);
            $table->smallInteger('experience')->default(0);

            $table->smallInteger('race')->default(2);
            $table->smallInteger('skin')->default(0);
            $table->smallInteger('hat')->default(0);
            $table->smallInteger('artifact')->default(0); // new

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wormix_characters_data');
    }
};
