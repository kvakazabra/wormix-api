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
        Schema::create('wormix_users_cookies', function (Blueprint $table) {
            $table->foreignId('user_id')->primary()->
            constrained('users')->cascadeOnDelete();

            $table->json('cookies')->default('[]');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wormix_users_cookies');
    }
};
