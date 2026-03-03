<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tournament_divisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->unsignedSmallInteger('order')->default(0);
            $table->timestamps();

            $table->unique(['tournament_id', 'name'], 'tdiv_tournament_name_unique');
            $table->index('tournament_id', 'tdiv_tournament_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tournament_divisions');
    }
};
