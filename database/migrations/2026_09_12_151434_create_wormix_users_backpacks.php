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
        Schema::create('wormix_users_backpacks', function (Blueprint $table) {
            $table->foreignId('owner_id')->primary()
                ->constrained('users')->cascadeOnDelete();

            $table->smallInteger('current_configuration')->default(0);
            $table->json('configurations')->default('[[4,2,1]]');
            $table->json('hotkeys')->default('[]');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wormix_users_backpacks');
    }
};
