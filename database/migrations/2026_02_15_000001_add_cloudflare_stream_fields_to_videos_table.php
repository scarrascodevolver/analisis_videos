<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            $table->string('cloudflare_uid', 100)->nullable()->after('file_path');
            $table->string('cloudflare_playback_url', 500)->nullable()->after('cloudflare_uid');
            $table->string('cloudflare_thumbnail', 500)->nullable()->after('cloudflare_playback_url');
            // pendingupload | queued | inprogress | ready | error
            $table->string('cloudflare_status', 30)->nullable()->after('cloudflare_thumbnail');
        });
    }

    public function down(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            $table->dropColumn([
                'cloudflare_uid',
                'cloudflare_playback_url',
                'cloudflare_thumbnail',
                'cloudflare_status',
            ]);
        });
    }
};
