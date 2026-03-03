<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tournament_registrations', function (Blueprint $table) {
            $table->foreignId('division_id')
                ->nullable()
                ->after('tournament_id')
                ->constrained('tournament_divisions')
                ->nullOnDelete();
        });

        Schema::table('video_org_shares', function (Blueprint $table) {
            $table->foreignId('division_id')
                ->nullable()
                ->after('target_category_id')
                ->constrained('tournament_divisions')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tournament_registrations', function (Blueprint $table) {
            $table->dropForeign(['division_id']);
            $table->dropColumn('division_id');
        });
        Schema::table('video_org_shares', function (Blueprint $table) {
            $table->dropForeign(['division_id']);
            $table->dropColumn('division_id');
        });
    }
};
