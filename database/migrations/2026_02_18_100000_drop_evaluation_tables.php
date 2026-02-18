<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('player_evaluations');
        Schema::dropIfExists('evaluation_periods');
    }

    public function down(): void
    {
        // No restore - evaluations system removed intentionally
    }
};
