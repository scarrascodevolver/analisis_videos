<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('video_clips', function (Blueprint $table) {
            // Per-analyst clip visibility: false = solo el creador, true = visible a toda la org
            $table->boolean('is_shared')->default(false)->after('is_highlight');

            // Índice para filtrar clips por analista eficientemente
            $table->index(['video_id', 'created_by'], 'video_clips_video_creator_idx');
        });
    }

    public function down(): void
    {
        Schema::table('video_clips', function (Blueprint $table) {
            $table->dropIndex('video_clips_video_creator_idx');
            $table->dropColumn('is_shared');
        });
    }
};
