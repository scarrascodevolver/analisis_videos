<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clip_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name', 50);
            $table->string('slug', 50);
            $table->string('color', 7)->default('#007bff');
            $table->string('icon', 50)->nullable();
            $table->char('hotkey', 1)->nullable();
            $table->unsignedTinyInteger('lead_seconds')->default(5);
            $table->unsignedTinyInteger('lag_seconds')->default(3);
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();

            $table->unique(['organization_id', 'slug']);
            $table->unique(['organization_id', 'hotkey']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clip_categories');
    }
};
