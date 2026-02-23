<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lineup_players', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lineup_id')->constrained('lineups')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('rival_player_id')->nullable(); // FK added after rival_players table
            $table->string('player_name', 100)->nullable(); // fallback display name
            $table->tinyInteger('shirt_number')->unsigned()->nullable(); // 1-23
            $table->tinyInteger('position_number')->unsigned()->nullable(); // 1-15 (null = bench only)
            $table->enum('status', ['starter', 'substitute', 'unavailable'])->default('starter');
            $table->smallInteger('substitution_minute')->unsigned()->nullable();
            $table->timestamps();
            $table->index('lineup_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lineup_players');
    }
};
