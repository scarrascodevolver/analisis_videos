<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('video_org_shares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('video_id')->constrained()->cascadeOnDelete();
            $table->foreignId('source_organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('target_organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('target_category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->foreignId('shared_by')->constrained('users')->cascadeOnDelete();
            $table->enum('status', ['active', 'revoked'])->default('active');
            $table->text('notes')->nullable();
            $table->timestamp('shared_at')->useCurrent();
            $table->timestamp('revoked_at')->nullable();
            $table->foreignId('revoked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['video_id', 'target_organization_id'], 'vshare_video_target_unique');
            $table->index(['target_organization_id', 'target_category_id', 'status'], 'vshare_target_cat_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('video_org_shares');
    }
};
