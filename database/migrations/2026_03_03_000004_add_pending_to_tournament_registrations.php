<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL requires this approach to modify an ENUM column
        DB::statement("ALTER TABLE tournament_registrations MODIFY COLUMN status ENUM('pending','active','withdrawn','rejected') NOT NULL DEFAULT 'pending'");

        Schema::table('tournament_registrations', function (Blueprint $table) {
            $table->timestamp('rejected_at')->nullable()->after('withdrawn_at');
        });
    }

    public function down(): void
    {
        Schema::table('tournament_registrations', function (Blueprint $table) {
            $table->dropColumn('rejected_at');
        });
        DB::statement("ALTER TABLE tournament_registrations MODIFY COLUMN status ENUM('active','withdrawn') NOT NULL DEFAULT 'active'");
    }
};
