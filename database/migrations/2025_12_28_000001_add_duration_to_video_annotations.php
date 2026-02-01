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
        Schema::table('video_annotations', function (Blueprint $table) {
            $table->integer('duration_seconds')->default(4)->after('annotation_type'); // Duración en segundos
            $table->boolean('is_permanent')->default(false)->after('duration_seconds'); // Si es permanente
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('video_annotations', function (Blueprint $table) {
            $table->dropColumn(['duration_seconds', 'is_permanent']);
        });
    }
};
