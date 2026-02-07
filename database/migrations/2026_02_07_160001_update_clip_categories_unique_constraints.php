<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Update unique constraints to handle the 3 scopes:
     * - organization: unique per org (current behavior)
     * - user: unique per user
     * - video: unique per video
     *
     * Note: MySQL doesn't support partial/conditional unique indexes natively,
     * and composite indexes with nullable columns don't work as expected.
     * We'll handle uniqueness validation in the application layer (controller).
     */
    public function up(): void
    {
        Schema::table('clip_categories', function (Blueprint $table) {
            // Drop old unique constraints that don't consider scope
            $table->dropUnique('clip_categories_organization_id_slug_unique');
            $table->dropUnique('clip_categories_organization_id_hotkey_unique');
        });

        // Uniqueness is now enforced at application level in ClipCategoryController
        // using slugExists() and checkHotkeyExists() methods that check by scope.
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clip_categories', function (Blueprint $table) {
            // Restore old constraints (only works if all data is organization-scoped)
            $table->unique(['organization_id', 'slug'], 'clip_categories_organization_id_slug_unique');
            $table->unique(['organization_id', 'hotkey'], 'clip_categories_organization_id_hotkey_unique');
        });
    }
};
