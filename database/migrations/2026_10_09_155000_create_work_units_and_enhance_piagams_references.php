<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('work_units')) {
            Schema::create('work_units', function (Blueprint $table) {
                $table->id();
                $table->string('name', 150);
                $table->string('code', 50)->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index('name');
                $table->index('is_active');
            });
        }

        Schema::table('piagams', function (Blueprint $table) {
            if (! Schema::hasColumn('piagams', 'work_unit_id')) {
                $table->foreignId('work_unit_id')->nullable()->after('school_origin')->constrained('work_units')->nullOnDelete();
            }
            if (! Schema::hasColumn('piagams', 'npsn')) {
                $table->string('npsn', 20)->nullable()->after('work_unit_id');
            }
            if (! Schema::hasColumn('piagams', 'pickup_category_name')) {
                $table->string('pickup_category_name', 150)->nullable()->after('pickup_category_id');
            }
            if (! Schema::hasColumn('piagams', 'competition_category_name')) {
                $table->string('competition_category_name', 150)->nullable()->after('competition_category_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('piagams', function (Blueprint $table) {
            if (Schema::hasColumn('piagams', 'work_unit_id')) {
                $table->dropForeign(['work_unit_id']);
                $table->dropColumn('work_unit_id');
            }
            if (Schema::hasColumn('piagams', 'npsn')) {
                $table->dropColumn('npsn');
            }
            if (Schema::hasColumn('piagams', 'pickup_category_name')) {
                $table->dropColumn('pickup_category_name');
            }
            if (Schema::hasColumn('piagams', 'competition_category_name')) {
                $table->dropColumn('competition_category_name');
            }
        });

        Schema::dropIfExists('work_units');
    }
};
