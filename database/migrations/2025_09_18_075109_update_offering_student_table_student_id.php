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
        Schema::table('offering_student', function (Blueprint $table) {
            // Keep migration re-runnable even if the column/foreign key was already updated.
            if (Schema::hasColumn('offering_student', 'student_id')) {
                try {
                    $table->dropForeign(['student_id']);
                } catch (\Throwable $e) {
                    // Foreign key may already be dropped in some environments.
                }

                try {
                    $table->dropForeign(['offering_id']);
                } catch (\Throwable $e) {
                    // Foreign key may already be dropped in some environments.
                }

                try {
                    $table->dropUnique('offering_student_offering_id_student_id_unique');
                } catch (\Throwable $e) {
                    // Unique key may not exist yet.
                }

                try {
                    $table->dropUnique('offering_student_student_unique');
                } catch (\Throwable $e) {
                    // Unique key may not exist yet.
                }

                $table->dropColumn('student_id');
            }
        });

        Schema::table('offering_student', function (Blueprint $table) {
            if (! Schema::hasColumn('offering_student', 'student_id')) {
                $table->string('student_id', 20)->after('offering_id');
            }
        });

        Schema::table('offering_student', function (Blueprint $table) {
            try {
                $table->foreign('student_id')->references('student_id')->on('students')->onDelete('cascade');
            } catch (\Throwable $e) {
                // Foreign key may already exist.
            }

            try {
                $table->foreign('offering_id')->references('id')->on('offerings')->onDelete('cascade');
            } catch (\Throwable $e) {
                // Foreign key may already exist.
            }

            try {
                $table->unique(['offering_id', 'student_id'], 'offering_student_offering_id_student_id_unique');
            } catch (\Throwable $e) {
                // Unique key may already exist.
            }

            try {
                $table->unique('student_id', 'offering_student_student_unique');
            } catch (\Throwable $e) {
                // Unique key may already exist.
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('offering_student', function (Blueprint $table) {
            try {
                $table->dropForeign(['student_id']);
            } catch (\Throwable $e) {
                // Foreign key may already be dropped.
            }

            try {
                $table->dropForeign(['offering_id']);
            } catch (\Throwable $e) {
                // Foreign key may already be dropped.
            }

            try {
                $table->dropUnique('offering_student_offering_id_student_id_unique');
            } catch (\Throwable $e) {
                // Unique key may already be dropped.
            }

            try {
                $table->dropUnique('offering_student_student_unique');
            } catch (\Throwable $e) {
                // Unique key may already be dropped.
            }

            if (Schema::hasColumn('offering_student', 'student_id')) {
                $table->dropColumn('student_id');
            }
        });

        Schema::table('offering_student', function (Blueprint $table) {
            if (! Schema::hasColumn('offering_student', 'student_id')) {
                $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            }

            try {
                $table->foreign('offering_id')->references('id')->on('offerings')->onDelete('cascade');
            } catch (\Throwable $e) {
                // Foreign key may already exist.
            }

            try {
                $table->unique(['offering_id', 'student_id'], 'offering_student_offering_id_student_id_unique');
            } catch (\Throwable $e) {
                // Unique key may already exist.
            }

            try {
                $table->unique('student_id', 'offering_student_student_unique');
            } catch (\Throwable $e) {
                // Unique key may already exist.
            }
        });
    }
};