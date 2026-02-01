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
        // Modify the enum to include 'general'
        DB::statement("ALTER TABLE video_comments MODIFY COLUMN category ENUM('tecnico', 'tactico', 'fisico', 'mental', 'general') DEFAULT 'tecnico'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove 'general' from the enum
        DB::statement("ALTER TABLE video_comments MODIFY COLUMN category ENUM('tecnico', 'tactico', 'fisico', 'mental') DEFAULT 'tecnico'");
    }
};
