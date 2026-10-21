<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            if (! Schema::hasColumn('schools', 'deleted_at')) {
                $table->softDeletes()->after('is_active');
                $table->index('deleted_at');
            }
        });

        Schema::table('competition_categories', function (Blueprint $table) {
            if (! Schema::hasColumn('competition_categories', 'deleted_at')) {
                $table->softDeletes()->after('is_active');
                $table->index('deleted_at');
            }
        });

        Schema::table('pickup_categories', function (Blueprint $table) {
            if (! Schema::hasColumn('pickup_categories', 'deleted_at')) {
                $table->softDeletes()->after('is_active');
                $table->index('deleted_at');
            }
        });

        Schema::table('work_units', function (Blueprint $table) {
            if (! Schema::hasColumn('work_units', 'deleted_at')) {
                $table->softDeletes()->after('is_active');
                $table->index('deleted_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('work_units', function (Blueprint $table) {
            if (Schema::hasColumn('work_units', 'deleted_at')) {
                $table->dropIndex(['deleted_at']);
                $table->dropSoftDeletes();
            }
        });

        Schema::table('pickup_categories', function (Blueprint $table) {
            if (Schema::hasColumn('pickup_categories', 'deleted_at')) {
                $table->dropIndex(['deleted_at']);
                $table->dropSoftDeletes();
            }
        });

        Schema::table('competition_categories', function (Blueprint $table) {
            if (Schema::hasColumn('competition_categories', 'deleted_at')) {
                $table->dropIndex(['deleted_at']);
                $table->dropSoftDeletes();
            }
        });

        Schema::table('schools', function (Blueprint $table) {
            if (Schema::hasColumn('schools', 'deleted_at')) {
                $table->dropIndex(['deleted_at']);
                $table->dropSoftDeletes();
            }
        });
    }
};
