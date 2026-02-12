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
        Schema::table('video_views', function (Blueprint $table) {
            $table->boolean('is_valid_view')->default(false)->after('completed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('video_views', function (Blueprint $table) {
            $table->dropColumn('is_valid_view');
        });
    }
};
