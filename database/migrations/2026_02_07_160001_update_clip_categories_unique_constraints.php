<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Update unique constraints to handle the 3 scopes.
     * MySQL requires special handling because indexes used by foreign keys can't be dropped directly.
     */
    public function up(): void
    {
        // First, create a simple index on organization_id so the FK can use it
        // This allows us to drop the composite unique indexes
        Schema::table('clip_categories', function (Blueprint $table) {
            $table->index('organization_id', 'clip_categories_org_id_index');
        });

        // Now we can safely drop the unique constraints
        Schema::table('clip_categories', function (Blueprint $table) {
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
            // Restore old constraints
            $table->unique(['organization_id', 'slug'], 'clip_categories_organization_id_slug_unique');
            $table->unique(['organization_id', 'hotkey'], 'clip_categories_organization_id_hotkey_unique');

            // Drop the simple index we created
            $table->dropIndex('clip_categories_org_id_index');
        });
    }
};
