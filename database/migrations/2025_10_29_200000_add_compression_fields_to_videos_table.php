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
        Schema::table('videos', function (Blueprint $table) {
            // Processing status tracking
            $table->enum('processing_status', ['pending', 'processing', 'completed', 'failed'])
                  ->default('pending')
                  ->after('status')
                  ->comment('Video compression status');

            // File size information
            $table->bigInteger('original_file_size')
                  ->nullable()
                  ->after('file_size')
                  ->comment('Original file size before compression');

            $table->bigInteger('compressed_file_size')
                  ->nullable()
                  ->after('original_file_size')
                  ->comment('Compressed file size after processing');

            // Original file path (before compression)
            $table->string('original_file_path', 500)
                  ->nullable()
                  ->after('file_path')
                  ->comment('Original file path before compression');

            // Compression ratio (percentage saved)
            $table->decimal('compression_ratio', 5, 2)
                  ->nullable()
                  ->after('compressed_file_size')
                  ->comment('Compression ratio percentage');

            // Processing timestamps
            $table->timestamp('processing_started_at')
                  ->nullable()
                  ->after('updated_at')
                  ->comment('When compression started');

            $table->timestamp('processing_completed_at')
                  ->nullable()
                  ->after('processing_started_at')
                  ->comment('When compression completed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            $table->dropColumn([
                'processing_status',
                'original_file_size',
                'compressed_file_size',
                'original_file_path',
                'compression_ratio',
                'processing_started_at',
                'processing_completed_at',
            ]);
        });
    }
};
