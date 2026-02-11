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
        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lobby_id')->constrained('lobbies')->cascadeOnDelete();
            $table->unsignedInteger('number');
            $table->string('name')->nullable();
            $table->unsignedInteger('max_players')->nullable();
            $table->timestamps();

            $table->unique(['lobby_id', 'number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teams');
    }
};
