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
        // Check which indexes exist before trying to drop them
        $indexes = collect(DB::select("SHOW INDEX FROM clip_categories"))
            ->pluck('Key_name')
            ->unique()
            ->toArray();

        // First, create a simple index on organization_id so the FK can use it (if not exists)
        if (!in_array('clip_categories_org_id_index', $indexes)) {
            Schema::table('clip_categories', function (Blueprint $table) {
                $table->index('organization_id', 'clip_categories_org_id_index');
            });
        }

        // Now we can safely drop the unique constraints (if they exist)
        Schema::table('clip_categories', function (Blueprint $table) use ($indexes) {
            if (in_array('clip_categories_organization_id_slug_unique', $indexes)) {
                $table->dropUnique('clip_categories_organization_id_slug_unique');
            }
            if (in_array('clip_categories_organization_id_hotkey_unique', $indexes)) {
                $table->dropUnique('clip_categories_organization_id_hotkey_unique');
            }
        });

        // Uniqueness is now enforced at application level in ClipCategoryController
        // using slugExists() and checkHotkeyExists() methods that check by scope.
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $indexes = collect(DB::select("SHOW INDEX FROM clip_categories"))
            ->pluck('Key_name')
            ->unique()
            ->toArray();

        Schema::table('clip_categories', function (Blueprint $table) use ($indexes) {
            // Restore old constraints if not exist
            if (!in_array('clip_categories_organization_id_slug_unique', $indexes)) {
                $table->unique(['organization_id', 'slug'], 'clip_categories_organization_id_slug_unique');
            }
            if (!in_array('clip_categories_organization_id_hotkey_unique', $indexes)) {
                $table->unique(['organization_id', 'hotkey'], 'clip_categories_organization_id_hotkey_unique');
            }

            // Drop the simple index we created if exists
            if (in_array('clip_categories_org_id_index', $indexes)) {
                $table->dropIndex('clip_categories_org_id_index');
            }
        });
    }
};
