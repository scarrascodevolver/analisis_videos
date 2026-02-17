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
        Schema::create('rival_teams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->onDelete('cascade');
            $table->string('name'); // "Old Navy RC"
            $table->string('code')->nullable(); // "ONRC"
            $table->string('city')->nullable(); // "San Isidro"
            $table->text('notes')->nullable();
            $table->timestamps();

            // Unique constraint: same name cannot exist twice in same organization
            $table->unique(['organization_id', 'name']);

            // Index for searches
            $table->index('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rival_teams');
    }
};
