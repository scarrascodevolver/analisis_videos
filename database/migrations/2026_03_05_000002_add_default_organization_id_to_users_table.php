<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('default_organization_id')
                ->nullable()
                ->after('is_org_manager')
                ->constrained('organizations')
                ->nullOnDelete();
        });

        // Backfill default organization from existing pivot state.
        $userIds = DB::table('users')->pluck('id');

        foreach ($userIds as $userId) {
            $defaultOrgId = DB::table('organization_user')
                ->where('user_id', $userId)
                ->where('is_current', true)
                ->value('organization_id');

            if (! $defaultOrgId) {
                $defaultOrgId = DB::table('organization_user')
                    ->where('user_id', $userId)
                    ->orderByDesc('created_at')
                    ->value('organization_id');
            }

            if ($defaultOrgId) {
                DB::table('users')
                    ->where('id', $userId)
                    ->update(['default_organization_id' => $defaultOrgId]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('default_organization_id');
        });
    }
};

