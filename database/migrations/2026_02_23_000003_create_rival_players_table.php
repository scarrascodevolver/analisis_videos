<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rival_players', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rival_team_id')->constrained('rival_teams')->cascadeOnDelete();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->string('name', 100);
            $table->tinyInteger('shirt_number')->unsigned()->nullable();
            $table->tinyInteger('usual_position')->unsigned()->nullable(); // 1-15
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['rival_team_id', 'organization_id']);
        });

        // Add FK for rival_player_id in lineup_players now that rival_players table exists
        Schema::table('lineup_players', function (Blueprint $table) {
            $table->foreign('rival_player_id')->references('id')->on('rival_players')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('lineup_players', function (Blueprint $table) {
            $table->dropForeign(['rival_player_id']);
        });

        Schema::dropIfExists('rival_players');
    }
};
