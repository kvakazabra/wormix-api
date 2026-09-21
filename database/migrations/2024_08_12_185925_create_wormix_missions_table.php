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
        Schema::create('wormix_missions', function (Blueprint $table) {
            $table->bigInteger('mission_id')
                ->primary()->unique('mission_idx');
            $table->integer('required_level')->default(1);
            $table->json('awards');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wormix_missions');
    }
};
