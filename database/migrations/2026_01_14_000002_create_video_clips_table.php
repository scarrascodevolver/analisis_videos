<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('video_clips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('video_id')->constrained()->cascadeOnDelete();
            $table->foreignId('clip_category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users');
            $table->decimal('start_time', 10, 2);
            $table->decimal('end_time', 10, 2);
            $table->string('title', 100)->nullable();
            $table->text('notes')->nullable();
            $table->json('players')->nullable();
            $table->json('tags')->nullable();
            $table->unsignedTinyInteger('rating')->nullable();
            $table->boolean('is_highlight')->default(false);
            $table->timestamps();

            $table->index(['video_id', 'clip_category_id']);
            $table->index(['video_id', 'start_time']);
            $table->index(['organization_id', 'clip_category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('video_clips');
    }
};
