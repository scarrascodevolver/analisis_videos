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
        Schema::table('videos', function (Blueprint $table) {
            $table->enum('visibility_type', ['public', 'forwards', 'backs', 'specific'])
                ->default('public')
                ->after('status')
                ->comment('Visibility level: public=all team, forwards=positions 1-8, backs=positions 9-15, specific=assigned players only');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            $table->dropColumn('visibility_type');
        });
    }
};
