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
        Schema::create('videos', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('file_path');
            $table->string('file_name');
            $table->integer('file_size');
            $table->string('mime_type');
            $table->integer('duration')->nullable();
            $table->foreignId('uploaded_by')->constrained('users');
            $table->foreignId('analyzed_team_id')->constrained('teams');
            $table->foreignId('rival_team_id')->nullable()->constrained('teams');
            $table->foreignId('category_id')->constrained('categories');
            $table->date('match_date');
            $table->enum('status', ['pending', 'processing', 'completed', 'archived'])->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('videos');
    }
};
