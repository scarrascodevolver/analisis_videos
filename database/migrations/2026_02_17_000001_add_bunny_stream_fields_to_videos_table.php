<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            $table->string('bunny_video_id', 100)->nullable()->after('cloudflare_status');
            $table->string('bunny_hls_url', 500)->nullable()->after('bunny_video_id');
            $table->string('bunny_thumbnail', 500)->nullable()->after('bunny_hls_url');
            $table->string('bunny_status', 30)->nullable()->after('bunny_thumbnail');
        });
    }

    public function down(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            $table->dropColumn(['bunny_video_id', 'bunny_hls_url', 'bunny_thumbnail', 'bunny_status']);
        });
    }
};
