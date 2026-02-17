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
        Schema::table('videos', function (Blueprint $table) {
            // Add FK to rival_teams table
            // Place it right before rival_team_name for logical ordering
            $table->foreignId('rival_team_id')
                ->nullable()
                ->after('analyzed_team_name')
                ->constrained('rival_teams')
                ->onDelete('set null');

            // rival_team_name already exists, keep for fallback compatibility
            // If rival_team_id exists, use that; otherwise use rival_team_name
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            $table->dropForeign(['rival_team_id']);
            $table->dropColumn('rival_team_id');
        });
    }
};
