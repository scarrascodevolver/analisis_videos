<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tournaments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('season')->nullable(); // ej: "2026", "2025/2026"
            $table->timestamps();

            $table->unique(['organization_id', 'name', 'season']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tournaments');
    }
};
