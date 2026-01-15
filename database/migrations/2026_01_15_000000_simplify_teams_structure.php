<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Simplifica la estructura de equipos:
     * - Elimina la tabla teams (ya no se necesita con multi-tenant)
     * - El equipo analizado siempre es la organización
     * - El rival es solo texto libre
     */
    public function up(): void
    {
        // 1. Agregar columna analyzed_team_name a videos (si no existe)
        if (!Schema::hasColumn('videos', 'analyzed_team_name')) {
            Schema::table('videos', function (Blueprint $table) {
                $table->string('analyzed_team_name')->nullable()->after('rival_team_name');
            });
        }

        // 2. Migrar datos: copiar nombre del equipo analizado desde teams
        $videos = DB::table('videos')
            ->whereNotNull('analyzed_team_id')
            ->whereNull('analyzed_team_name')
            ->get();

        foreach ($videos as $video) {
            $team = DB::table('teams')->find($video->analyzed_team_id);
            if ($team) {
                DB::table('videos')
                    ->where('id', $video->id)
                    ->update(['analyzed_team_name' => $team->name]);
            }
        }

        // 3. Para videos sin equipo analizado, usar el nombre de la organización
        $videosWithoutTeam = DB::table('videos')
            ->whereNull('analyzed_team_name')
            ->orWhere('analyzed_team_name', '')
            ->get();

        foreach ($videosWithoutTeam as $video) {
            $org = DB::table('organizations')->find($video->organization_id);
            if ($org) {
                DB::table('videos')
                    ->where('id', $video->id)
                    ->update(['analyzed_team_name' => $org->name]);
            }
        }

        // 4. Migrar rival_team_id a rival_team_name si existe
        $videosWithRivalId = DB::table('videos')
            ->whereNotNull('rival_team_id')
            ->where(function($q) {
                $q->whereNull('rival_team_name')
                  ->orWhere('rival_team_name', '');
            })
            ->get();

        foreach ($videosWithRivalId as $video) {
            $team = DB::table('teams')->find($video->rival_team_id);
            if ($team) {
                DB::table('videos')
                    ->where('id', $video->id)
                    ->update(['rival_team_name' => $team->name]);
            }
        }

        // 5. Eliminar foreign keys y columnas de videos
        Schema::table('videos', function (Blueprint $table) {
            // Eliminar FK si existe
            $foreignKeys = $this->getForeignKeys('videos');

            if (in_array('videos_analyzed_team_id_foreign', $foreignKeys)) {
                $table->dropForeign(['analyzed_team_id']);
            }
            if (in_array('videos_rival_team_id_foreign', $foreignKeys)) {
                $table->dropForeign(['rival_team_id']);
            }
        });

        // Eliminar columnas
        Schema::table('videos', function (Blueprint $table) {
            if (Schema::hasColumn('videos', 'analyzed_team_id')) {
                $table->dropColumn('analyzed_team_id');
            }
            if (Schema::hasColumn('videos', 'rival_team_id')) {
                $table->dropColumn('rival_team_id');
            }
        });

        // 6. Eliminar tabla teams
        Schema::dropIfExists('teams');

        echo "✅ Migración completada:\n";
        echo "   - Columna analyzed_team_name agregada a videos\n";
        echo "   - Datos migrados desde teams\n";
        echo "   - Columnas analyzed_team_id y rival_team_id eliminadas\n";
        echo "   - Tabla teams eliminada\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Recrear tabla teams
        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained('organizations')->onDelete('cascade');
            $table->string('name');
            $table->string('abbreviation', 10)->nullable();
            $table->boolean('is_own_team')->default(false);
            $table->timestamps();

            $table->unique(['organization_id', 'name'], 'teams_org_name_unique');
        });

        // Agregar columnas FK a videos
        Schema::table('videos', function (Blueprint $table) {
            $table->foreignId('analyzed_team_id')->nullable()->after('uploaded_by');
            $table->foreignId('rival_team_id')->nullable()->after('analyzed_team_id');
        });

        echo "⚠️ Rollback parcial completado.\n";
        echo "   - Tabla teams recreada (vacía)\n";
        echo "   - Columnas FK agregadas a videos (sin datos)\n";
        echo "   - Los datos de equipos NO se pueden restaurar automáticamente.\n";
    }

    /**
     * Get foreign key names for a table
     */
    private function getForeignKeys(string $table): array
    {
        $foreignKeys = [];

        try {
            $results = DB::select("
                SELECT CONSTRAINT_NAME
                FROM information_schema.TABLE_CONSTRAINTS
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = ?
                AND CONSTRAINT_TYPE = 'FOREIGN KEY'
            ", [$table]);

            foreach ($results as $result) {
                $foreignKeys[] = $result->CONSTRAINT_NAME;
            }
        } catch (\Exception $e) {
            // Si falla, intentar nombres estándar de Laravel
        }

        return $foreignKeys;
    }
};
