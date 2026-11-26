<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('document_types')) {
            Schema::create('document_types', function (Blueprint $table) {
                $table->id();
                $table->foreignId('division_id')->nullable()->constrained('divisions')->nullOnDelete();
                $table->string('name', 150);
                $table->string('code', 50)->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes();

                $table->index('name');
                $table->index('is_active');
                $table->index('deleted_at');
            });

            // Seed initial default document types for the agency
            $defaultId = DB::table('document_types')->insertGetId([
                'division_id' => null,
                'name' => 'Piagam Penghargaan',
                'code' => 'PIAGAM',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        Schema::table('piagams', function (Blueprint $table) {
            if (! Schema::hasColumn('piagams', 'document_type_id')) {
                $table->foreignId('document_type_id')->nullable()->after('competition_category_name')->constrained('document_types')->nullOnDelete();
            }
            if (! Schema::hasColumn('piagams', 'document_type_name')) {
                $table->string('document_type_name', 150)->nullable()->after('document_type_id');
            }
        });

        // Ensure all existing piagam records have default document type name
        if (isset($defaultId)) {
            DB::table('piagams')->whereNull('document_type_name')->update([
                'document_type_id' => $defaultId,
                'document_type_name' => 'Piagam Penghargaan',
            ]);
        } else {
            $existingDefault = DB::table('document_types')->where('name', 'Piagam Penghargaan')->first();
            if ($existingDefault) {
                DB::table('piagams')->whereNull('document_type_name')->update([
                    'document_type_id' => $existingDefault->id,
                    'document_type_name' => $existingDefault->name,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('piagams', function (Blueprint $table) {
            if (Schema::hasColumn('piagams', 'document_type_id')) {
                $table->dropForeign(['document_type_id']);
                $table->dropColumn('document_type_id');
            }
            if (Schema::hasColumn('piagams', 'document_type_name')) {
                $table->dropColumn('document_type_name');
            }
        });

        Schema::dropIfExists('document_types');
    }
};
