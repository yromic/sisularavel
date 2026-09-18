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
        Schema::create('divisions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('code', 20)->unique();
            $table->string('name', 150);
            $table->string('status', 20)->default('active')->index();
            $table->string('legacy_drive_folder_id', 100)->nullable();
            $table->foreignId('created_by_user_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('deleted_at');
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('username', 50)->unique();
            $table->string('name', 150);
            $table->string('email', 150)->nullable();
            $table->string('password');
            $table->string('role', 50)->default('operator');
            $table->foreignId('division_id')->nullable()->constrained('divisions')->restrictOnDelete();
            $table->boolean('is_active')->default(true);
            $table->boolean('must_reset_password')->default(false);
            $table->char('password_reset_token', 64)->nullable();
            $table->timestamp('password_reset_expires_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->rememberToken();
            $table->unique('email');
            $table->index(['division_id', 'role', 'is_active']);
            $table->index(['password_reset_token', 'password_reset_expires_at']);
            $table->index('deleted_at');
        });

        Schema::table('divisions', function (Blueprint $table) {
            $table->foreign('created_by_user_id')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('divisions', function (Blueprint $table) {
            $table->dropForeign(['created_by_user_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['division_id']);
        });

        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('users');
        Schema::dropIfExists('divisions');
    }
};
