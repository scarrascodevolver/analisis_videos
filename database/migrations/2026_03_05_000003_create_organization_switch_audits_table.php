<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organization_switch_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('from_organization_id')->nullable()->constrained('organizations')->nullOnDelete();
            $table->foreignId('to_organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('source_url', 2048)->nullable();
            $table->string('switch_reason', 100)->default('manual');
            $table->timestamp('switched_at')->useCurrent();
            $table->index(['user_id', 'switched_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_switch_audits');
    }
};

