<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schools', function (Blueprint $table) {
            $table->id();
            $table->char('npsn', 8)->unique();
            $table->string('name', 255);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('name');
        });

        Schema::create('pickup_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('division_id')->constrained('divisions')->restrictOnDelete();
            $table->string('name', 150);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['division_id', 'is_active']);
        });

        Schema::create('competition_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('division_id')->constrained('divisions')->restrictOnDelete();
            $table->string('name', 150);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['division_id', 'is_active']);
        });

        Schema::create('incoming_letters', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('division_id')->constrained('divisions')->restrictOnDelete();
            $table->string('letter_number', 100);
            $table->string('origin_agency', 255);
            $table->date('letter_date');
            $table->date('received_date');
            $table->text('subject');
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['division_id', 'letter_date', 'received_date']);
            $table->index(['division_id', 'letter_number']);
            $table->index(['division_id', 'letter_number', 'letter_date', 'origin_agency'], 'incoming_letters_duplicate_advisory_index');
            $table->index('deleted_at');
        });

        Schema::create('outgoing_letters', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('division_id')->constrained('divisions')->restrictOnDelete();
            $table->unsignedSmallInteger('letter_year');
            $table->string('letter_number', 100);
            $table->date('letter_date');
            $table->date('disseminated_date');
            $table->text('subject');
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['division_id', 'letter_year', 'letter_number'], 'outgoing_letters_division_year_number_unique');
            $table->index(['division_id', 'letter_date', 'disseminated_date']);
            $table->index('deleted_at');
        });

        Schema::create('piagams', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('division_id')->constrained('divisions')->restrictOnDelete();
            $table->string('student_name', 150);
            $table->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $table->foreignId('competition_category_id')->constrained('competition_categories')->restrictOnDelete();
            $table->year('competition_year');
            $table->foreignId('pickup_category_id')->nullable()->constrained('pickup_categories')->restrictOnDelete();
            $table->string('recipient_name', 150)->nullable();
            $table->string('recipient_position', 100)->nullable();
            $table->string('recipient_unit', 150)->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['division_id', 'school_id']);
            $table->index(['student_name', 'competition_year']);
        });

        Schema::create('attachments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('division_id')->constrained('divisions')->restrictOnDelete();
            $table->string('attachable_type', 191);
            $table->unsignedBigInteger('attachable_id');
            $table->string('file_path', 500)->nullable();
            $table->string('original_name', 255);
            $table->unsignedBigInteger('file_size_bytes')->default(0);
            $table->string('mime_type', 100)->default('application/octet-stream');
            $table->char('file_hash', 64)->nullable();
            $table->boolean('is_missing')->default(false);
            $table->enum('migration_status', [
                'migrated',
                'missing_drive_file',
                'inaccessible_drive_file',
                'download_failed',
                'validation_rejected',
                'no_attachment_expected',
            ])->default('migrated');
            $table->string('missing_reason', 255)->nullable();
            $table->string('legacy_drive_file_id', 100)->nullable();
            $table->text('legacy_drive_url')->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['attachable_type', 'attachable_id']);
            $table->index('division_id');
            $table->index(['is_missing', 'migration_status']);
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('username', 100);
            $table->string('role', 50);
            $table->foreignId('division_id')->nullable()->constrained('divisions')->nullOnDelete();
            $table->string('event_code', 50);
            $table->string('auditable_type', 191)->nullable();
            $table->unsignedBigInteger('auditable_id')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45);
            $table->string('user_agent', 255)->nullable();
            $table->string('description', 500);
            $table->timestamp('created_at')->useCurrent();

            $table->index(['event_code', 'created_at']);
            $table->index('user_id');
            $table->index('division_id');
            $table->index(['auditable_type', 'auditable_id']);
        });

        Schema::create('idempotency_keys', function (Blueprint $table) {
            $table->id();
            $table->string('key', 64);
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('division_id')->nullable()->constrained('divisions')->nullOnDelete();
            $table->char('request_hash', 64);
            $table->unsignedSmallInteger('response_code');
            $table->mediumText('response_body');
            $table->timestamp('created_at')->useCurrent();
            $table->dateTime('expires_at');

            $table->unique(['key', 'user_id']);
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('idempotency_keys');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('settings');
        Schema::dropIfExists('attachments');
        Schema::dropIfExists('piagams');
        Schema::dropIfExists('outgoing_letters');
        Schema::dropIfExists('incoming_letters');
        Schema::dropIfExists('competition_categories');
        Schema::dropIfExists('pickup_categories');
        Schema::dropIfExists('schools');
    }
};
