<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('piagams', function (Blueprint $table) {
            $table->string('student_name', 255)->change();
            $table->unsignedBigInteger('school_id')->nullable()->change();
            $table->unsignedBigInteger('competition_category_id')->nullable()->change();
            $table->year('competition_year')->nullable()->change();
            $table->string('school_origin', 255)->nullable()->after('school_id');
            $table->text('piagam_description')->nullable()->after('student_name');
        });
    }

    public function down(): void
    {
        Schema::table('piagams', function (Blueprint $table) {
            $table->dropColumn(['school_origin', 'piagam_description']);
            $table->string('student_name', 150)->change();
            $table->unsignedBigInteger('school_id')->nullable(false)->change();
            $table->unsignedBigInteger('competition_category_id')->nullable(false)->change();
            $table->year('competition_year')->nullable(false)->change();
        });
    }
};
