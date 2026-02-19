<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->string('bunny_library_id')->nullable()->after('slug');
            $table->string('bunny_api_key')->nullable()->after('bunny_library_id');
            $table->string('bunny_cdn_hostname')->nullable()->after('bunny_api_key');
        });
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn(['bunny_library_id', 'bunny_api_key', 'bunny_cdn_hostname']);
        });
    }
};
