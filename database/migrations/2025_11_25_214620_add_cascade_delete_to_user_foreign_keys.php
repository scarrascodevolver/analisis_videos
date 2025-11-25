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
        // 1. video_assignments - CASCADE
        Schema::table('video_assignments', function (Blueprint $table) {
            $table->dropForeign(['assigned_by']);
            $table->dropForeign(['assigned_to']);
        });

        Schema::table('video_assignments', function (Blueprint $table) {
            $table->foreign('assigned_by')
                  ->references('id')->on('users')
                  ->onDelete('cascade');

            $table->foreign('assigned_to')
                  ->references('id')->on('users')
                  ->onDelete('cascade');
        });

        // 2. videos - SET NULL
        Schema::table('videos', function (Blueprint $table) {
            $table->dropForeign(['uploaded_by']);
        });

        // Hacer nullable la columna
        DB::statement('ALTER TABLE videos MODIFY uploaded_by BIGINT UNSIGNED NULL');

        Schema::table('videos', function (Blueprint $table) {
            $table->foreign('uploaded_by')
                  ->references('id')->on('users')
                  ->onDelete('set null');
        });

        // 3. video_comments - CASCADE
        Schema::table('video_comments', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        Schema::table('video_comments', function (Blueprint $table) {
            $table->foreign('user_id')
                  ->references('id')->on('users')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revertir video_assignments
        Schema::table('video_assignments', function (Blueprint $table) {
            $table->dropForeign(['assigned_by']);
            $table->dropForeign(['assigned_to']);
        });

        Schema::table('video_assignments', function (Blueprint $table) {
            $table->foreign('assigned_by')->references('id')->on('users');
            $table->foreign('assigned_to')->references('id')->on('users');
        });

        // Revertir videos
        Schema::table('videos', function (Blueprint $table) {
            $table->dropForeign(['uploaded_by']);
        });

        DB::statement('ALTER TABLE videos MODIFY uploaded_by BIGINT UNSIGNED NOT NULL');

        Schema::table('videos', function (Blueprint $table) {
            $table->foreign('uploaded_by')->references('id')->on('users');
        });

        // Revertir video_comments
        Schema::table('video_comments', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        Schema::table('video_comments', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users');
        });
    }
};
