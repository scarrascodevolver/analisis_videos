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
        // 1. VIDEOS - Agregar organization_id
        Schema::table('videos', function (Blueprint $table) {
            $table->foreignId('organization_id')
                ->nullable()
                ->after('id')
                ->constrained('organizations')
                ->onDelete('cascade');
            $table->index('organization_id');
        });

        // 2. TEAMS - Agregar organization_id y actualizar unique constraint
        Schema::table('teams', function (Blueprint $table) {
            $table->foreignId('organization_id')
                ->nullable()
                ->after('id')
                ->constrained('organizations')
                ->onDelete('cascade');
            $table->index('organization_id');
        });

        // Quitar unique de name y crear unique compuesto
        Schema::table('teams', function (Blueprint $table) {
            $table->dropUnique(['name']);
            $table->unique(['organization_id', 'name'], 'teams_org_name_unique');
        });

        // 3. CATEGORIES - Agregar organization_id y actualizar unique constraint
        Schema::table('categories', function (Blueprint $table) {
            $table->foreignId('organization_id')
                ->nullable()
                ->after('id')
                ->constrained('organizations')
                ->onDelete('cascade');
            $table->index('organization_id');
        });

        // Quitar unique de name y crear unique compuesto
        Schema::table('categories', function (Blueprint $table) {
            $table->dropUnique(['name']);
            $table->unique(['organization_id', 'name'], 'categories_org_name_unique');
        });

        // 4. PLAYER_EVALUATIONS - Agregar organization_id
        Schema::table('player_evaluations', function (Blueprint $table) {
            $table->foreignId('organization_id')
                ->nullable()
                ->after('id')
                ->constrained('organizations')
                ->onDelete('cascade');
            $table->index('organization_id');
        });

        // 5. EVALUATION_PERIODS - Agregar organization_id
        Schema::table('evaluation_periods', function (Blueprint $table) {
            $table->foreignId('organization_id')
                ->nullable()
                ->after('id')
                ->constrained('organizations')
                ->onDelete('cascade');
            $table->index('organization_id');
        });

        // 6. SETTINGS - Agregar organization_id y actualizar unique constraint
        Schema::table('settings', function (Blueprint $table) {
            $table->foreignId('organization_id')
                ->nullable()
                ->after('id')
                ->constrained('organizations')
                ->onDelete('cascade');
            $table->index('organization_id');
        });

        // Quitar unique de key y crear unique compuesto
        Schema::table('settings', function (Blueprint $table) {
            $table->dropUnique(['key']);
            $table->unique(['organization_id', 'key'], 'settings_org_key_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revertir SETTINGS
        Schema::table('settings', function (Blueprint $table) {
            $table->dropUnique('settings_org_key_unique');
            $table->unique('key');
            $table->dropForeign(['organization_id']);
            $table->dropIndex(['organization_id']);
            $table->dropColumn('organization_id');
        });

        // Revertir EVALUATION_PERIODS
        Schema::table('evaluation_periods', function (Blueprint $table) {
            $table->dropForeign(['organization_id']);
            $table->dropIndex(['organization_id']);
            $table->dropColumn('organization_id');
        });

        // Revertir PLAYER_EVALUATIONS
        Schema::table('player_evaluations', function (Blueprint $table) {
            $table->dropForeign(['organization_id']);
            $table->dropIndex(['organization_id']);
            $table->dropColumn('organization_id');
        });

        // Revertir CATEGORIES
        Schema::table('categories', function (Blueprint $table) {
            $table->dropUnique('categories_org_name_unique');
            $table->unique('name');
            $table->dropForeign(['organization_id']);
            $table->dropIndex(['organization_id']);
            $table->dropColumn('organization_id');
        });

        // Revertir TEAMS
        Schema::table('teams', function (Blueprint $table) {
            $table->dropUnique('teams_org_name_unique');
            $table->unique('name');
            $table->dropForeign(['organization_id']);
            $table->dropIndex(['organization_id']);
            $table->dropColumn('organization_id');
        });

        // Revertir VIDEOS
        Schema::table('videos', function (Blueprint $table) {
            $table->dropForeign(['organization_id']);
            $table->dropIndex(['organization_id']);
            $table->dropColumn('organization_id');
        });
    }
};
