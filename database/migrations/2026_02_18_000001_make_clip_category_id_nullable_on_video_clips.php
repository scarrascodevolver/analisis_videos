<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('video_clips', function (Blueprint $table) {
            // Drop existing FK constraint
            $table->dropForeign(['clip_category_id']);

            // Make nullable and re-add FK with nullOnDelete
            $table->foreignId('clip_category_id')->nullable()->change();
            $table->foreign('clip_category_id')->references('id')->on('clip_categories')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('video_clips', function (Blueprint $table) {
            $table->dropForeign(['clip_category_id']);
            $table->foreignId('clip_category_id')->nullable(false)->change();
            $table->foreign('clip_category_id')->references('id')->on('clip_categories')->cascadeOnDelete();
        });
    }
};
