<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('clip_categories', function (Blueprint $table) {
            // Add scope column: organization (plantillas), user (personales), video (del XML)
            $table->enum('scope', ['organization', 'user', 'video'])
                ->default('organization')
                ->after('organization_id');

            // Add user_id for personal categories (scope=user)
            $table->foreignId('user_id')
                ->nullable()
                ->after('scope')
                ->constrained()
                ->cascadeOnDelete();

            // Add video_id for video-specific categories (scope=video)
            $table->foreignId('video_id')
                ->nullable()
                ->after('user_id')
                ->constrained()
                ->cascadeOnDelete();

            // Add index for efficient queries
            $table->index(['scope', 'organization_id']);
            $table->index(['scope', 'user_id']);
            $table->index(['scope', 'video_id']);
        });

        // Update existing categories to have scope='organization' (already default, but explicit)
        DB::table('clip_categories')->update(['scope' => 'organization']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clip_categories', function (Blueprint $table) {
            // Drop foreign keys first
            $table->dropForeign(['user_id']);
            $table->dropForeign(['video_id']);

            // Drop indexes
            $table->dropIndex(['scope', 'organization_id']);
            $table->dropIndex(['scope', 'user_id']);
            $table->dropIndex(['scope', 'video_id']);

            // Drop columns
            $table->dropColumn(['scope', 'user_id', 'video_id']);
        });
    }
};
