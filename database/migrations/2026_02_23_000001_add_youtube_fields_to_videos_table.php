<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            $table->boolean('is_youtube_video')->default(false)->after('bunny_mp4_url');
            $table->string('youtube_url', 500)->nullable()->after('is_youtube_video');
            $table->string('youtube_video_id', 20)->nullable()->after('youtube_url');
        });
    }

    public function down(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            $table->dropColumn(['is_youtube_video', 'youtube_url', 'youtube_video_id']);
        });
    }
};
