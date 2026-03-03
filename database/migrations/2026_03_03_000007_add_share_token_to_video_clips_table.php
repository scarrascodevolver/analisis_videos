<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('video_clips', function (Blueprint $table) {
            $table->string('share_token', 64)->nullable()->unique()->after('is_shared');
        });
    }

    public function down(): void
    {
        Schema::table('video_clips', function (Blueprint $table) {
            $table->dropColumn('share_token');
        });
    }
};
