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
        Schema::create('backup_runs', function (Blueprint $table) {
            $table->id();
            $table->string('backup_id', 64)->unique();
            $table->enum('type', ['full', 'database', 'files'])->default('full');
            $table->enum('trigger', ['scheduled', 'manual'])->default('scheduled');
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', [
                'pending',
                'preparing',
                'archiving',
                'encrypting',
                'uploading',
                'verifying',
                'completed',
                'failed',
            ])->default('pending');
            $table->dateTime('started_at');
            $table->dateTime('completed_at')->nullable();
            $table->unsignedBigInteger('archive_size_bytes')->nullable();
            $table->unsignedInteger('files_count')->nullable();
            $table->char('sha256_checksum', 64)->nullable();
            $table->string('remote_storage_provider', 32)->default('google_drive');
            $table->string('remote_file_id', 255)->nullable();
            $table->string('error_code', 64)->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index('status', 'idx_backup_runs_status');
            $table->index('created_at', 'idx_backup_runs_created');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('backup_runs');
    }
};
