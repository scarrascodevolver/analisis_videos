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
        Schema::create('video_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('video_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained();
            $table->foreignId('parent_id')->nullable()->constrained('video_comments')->onDelete('cascade');
            $table->text('comment');
            $table->integer('timestamp_seconds');
            $table->enum('category', ['tecnico', 'tactico', 'fisico', 'mental'])->default('tecnico');
            $table->enum('priority', ['baja', 'media', 'alta', 'critica'])->default('media');
            $table->enum('status', ['pendiente', 'en_revision', 'completado'])->default('pendiente');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('video_comments');
    }
};
