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
        Schema::create('wormix_reagents', function (Blueprint $table) {
            $table->bigInteger('id')
                ->primary()->unique('reagent_idx');
            $table->string('name')->nullable();
            $table->bigInteger('price')->default(100);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wormix_reagents');
    }
};
