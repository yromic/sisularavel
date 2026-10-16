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
        Schema::table('attachments', function (Blueprint $table) {
            $table->string('google_drive_file_id', 100)->nullable()->after('legacy_drive_url');
            $table->text('google_drive_view_url')->nullable()->after('google_drive_file_id');
            $table->timestamp('google_drive_synced_at')->nullable()->after('google_drive_view_url');

            $table->index('google_drive_file_id');
            $table->index('google_drive_synced_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attachments', function (Blueprint $table) {
            $table->dropIndex(['google_drive_file_id']);
            $table->dropIndex(['google_drive_synced_at']);
            $table->dropColumn([
                'google_drive_file_id',
                'google_drive_view_url',
                'google_drive_synced_at',
            ]);
        });
    }
};
