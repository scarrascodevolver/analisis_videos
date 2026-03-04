<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('video_clips', function (Blueprint $table) {
            $table->unsignedSmallInteger('sort_order')->default(0)->after('end_time');
        });
    }

    public function down(): void
    {
        Schema::table('video_clips', function (Blueprint $table) {
            $table->dropColumn('sort_order');
        });
    }
};
