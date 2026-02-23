<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lineups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('video_id')->constrained('videos')->cascadeOnDelete();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->enum('team_type', ['local', 'rival']);
            $table->string('formation', 20)->nullable(); // e.g. "4-3-1"
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['video_id', 'team_type']);
            $table->index(['video_id', 'team_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lineups');
    }
};
