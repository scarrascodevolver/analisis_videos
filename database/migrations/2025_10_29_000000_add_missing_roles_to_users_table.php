<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // MySQL requires ALTER TABLE to modify ENUM values
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('analista', 'jugador', 'entrenador', 'director_tecnico', 'director_club', 'staff', 'scout', 'aficionado') NOT NULL DEFAULT 'jugador'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to original ENUM values
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('analista', 'jugador', 'entrenador', 'director_tecnico', 'scout', 'aficionado') NOT NULL DEFAULT 'jugador'");
    }
};
