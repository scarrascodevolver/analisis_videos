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
        Schema::table('organizations', function (Blueprint $table) {
            // Regional settings
            $table->string('timezone', 50)->default('UTC')->after('invitation_code');

            // Compression strategy settings
            $table->enum('compression_strategy', ['immediate', 'nocturnal', 'hybrid'])
                ->default('hybrid')
                ->after('timezone');
            $table->tinyInteger('compression_start_hour')->unsigned()->default(3)->after('compression_strategy');
            $table->tinyInteger('compression_end_hour')->unsigned()->default(7)->after('compression_start_hour');
            $table->integer('compression_hybrid_threshold')->unsigned()->default(500)->after('compression_end_hour')
                ->comment('MB threshold for hybrid strategy');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn([
                'timezone',
                'compression_strategy',
                'compression_start_hour',
                'compression_end_hour',
                'compression_hybrid_threshold',
            ]);
        });
    }
};
