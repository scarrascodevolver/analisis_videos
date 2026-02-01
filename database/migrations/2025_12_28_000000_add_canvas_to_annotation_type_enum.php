<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Modificar enum para agregar 'canvas'
        DB::statement("ALTER TABLE video_annotations MODIFY annotation_type ENUM('arrow', 'circle', 'line', 'text', 'rectangle', 'free_draw', 'canvas') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE video_annotations MODIFY annotation_type ENUM('arrow', 'circle', 'line', 'text', 'rectangle', 'free_draw') NOT NULL");
    }
};
