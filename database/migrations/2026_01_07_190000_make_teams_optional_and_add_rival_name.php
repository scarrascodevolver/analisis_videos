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
            // Agregar campo de texto para nombre del rival
            $table->string('rival_team_name')->nullable()->after('rival_team_id');
        });

        // Hacer analyzed_team_id nullable (si no lo es)
        // Usamos statement directo porque change() puede tener problemas con FKs
        if (Schema::hasColumn('videos', 'analyzed_team_id')) {
            Schema::table('videos', function (Blueprint $table) {
                $table->unsignedBigInteger('analyzed_team_id')->nullable()->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            $table->dropColumn('rival_team_name');
        });
    }
};
