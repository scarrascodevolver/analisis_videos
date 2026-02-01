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
        Schema::table('video_assignments', function (Blueprint $table) {
            $table->dropColumn(['status', 'due_date', 'completed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('video_assignments', function (Blueprint $table) {
            $table->date('due_date')->nullable();
            $table->enum('status', ['assigned', 'in_progress', 'completed', 'overdue'])->default('assigned');
            $table->timestamp('completed_at')->nullable();
        });
    }
};
