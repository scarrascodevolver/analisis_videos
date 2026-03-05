<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shared_clips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('video_clip_id')->constrained('video_clips')->onDelete('cascade');
            $table->foreignId('video_id')->constrained('videos')->onDelete('cascade');
            $table->foreignId('shared_by_user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('shared_with_user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('from_organization_id')->constrained('organizations')->onDelete('cascade');
            $table->foreignId('to_organization_id')->constrained('organizations')->onDelete('cascade');
            $table->text('message')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index('shared_with_user_id');
            $table->index(['shared_with_user_id', 'read_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shared_clips');
    }
};
