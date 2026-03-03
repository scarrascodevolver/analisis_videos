<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tournament_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')->constrained()->cascadeOnDelete();
            $table->foreignId('club_organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->enum('status', ['active', 'withdrawn'])->default('active');
            $table->timestamp('registered_at')->useCurrent();
            $table->timestamp('withdrawn_at')->nullable();
            $table->timestamps();

            $table->unique(['tournament_id', 'club_organization_id'], 'treg_tournament_club_unique');
            $table->index(['club_organization_id', 'status'], 'treg_club_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tournament_registrations');
    }
};
