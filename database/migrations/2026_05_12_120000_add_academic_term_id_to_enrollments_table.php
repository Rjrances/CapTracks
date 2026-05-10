<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('enrollments', 'academic_term_id')) {
            Schema::table('enrollments', function (Blueprint $table) {
                $table->foreignId('academic_term_id')
                    ->nullable()
                    ->after('student_id')
                    ->constrained('academic_terms')
                    ->nullOnDelete();
            });
        }

        if (Schema::hasColumn('enrollments', 'semester')) {
            DB::table('enrollments')->orderBy('id')->chunk(200, function ($rows) {
                foreach ($rows as $row) {
                    $sem = (string) ($row->semester ?? '');
                    if ($sem === '') {
                        continue;
                    }
                    $tid = DB::table('academic_terms')->where('semester', $sem)->value('id');
                    if ($tid) {
                        DB::table('enrollments')
                            ->where('id', $row->id)
                            ->whereNull('academic_term_id')
                            ->update(['academic_term_id' => $tid]);
                    }
                }
            });

            DB::statement('
                UPDATE enrollments e
                INNER JOIN students s ON s.student_id = e.student_id
                SET e.academic_term_id = s.academic_term_id
                WHERE e.academic_term_id IS NULL AND s.academic_term_id IS NOT NULL
            ');
        }

        DB::table('enrollments')->whereNull('academic_term_id')->delete();

        $this->deleteDuplicateEnrollments();

        foreach (['enrollments_student_id_semester_unique'] as $indexName) {
            if ($this->enrollmentsIndexExists($indexName)) {
                Schema::table('enrollments', function (Blueprint $table) use ($indexName) {
                    $table->dropUnique($indexName);
                });
            }
        }

        if (! $this->enrollmentsIndexExists('enrollments_student_id_academic_term_unique')) {
            Schema::table('enrollments', function (Blueprint $table) {
                $table->unique(['student_id', 'academic_term_id'], 'enrollments_student_id_academic_term_unique');
            });
        }

        if (Schema::hasColumn('enrollments', 'semester')) {
            Schema::table('enrollments', function (Blueprint $table) {
                $table->dropColumn('semester');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('enrollments', 'semester')) {
            Schema::table('enrollments', function (Blueprint $table) {
                $table->string('semester')->nullable()->after('student_id');
            });

            DB::statement('
                UPDATE enrollments e
                INNER JOIN academic_terms t ON t.id = e.academic_term_id
                SET e.semester = t.semester
                WHERE e.academic_term_id IS NOT NULL
            ');
        }

        if ($this->enrollmentsIndexExists('enrollments_student_id_academic_term_unique')) {
            Schema::table('enrollments', function (Blueprint $table) {
                $table->dropUnique('enrollments_student_id_academic_term_unique');
            });
        }

        if (! $this->enrollmentsIndexExists('enrollments_student_id_semester_unique') && Schema::hasColumn('enrollments', 'semester')) {
            Schema::table('enrollments', function (Blueprint $table) {
                $table->unique(['student_id', 'semester'], 'enrollments_student_id_semester_unique');
            });
        }

        if (Schema::hasColumn('enrollments', 'academic_term_id')) {
            Schema::table('enrollments', function (Blueprint $table) {
                $table->dropConstrainedForeignId('academic_term_id');
            });
        }
    }

    private function deleteDuplicateEnrollments(): void
    {
        DB::statement('
            DELETE e1 FROM enrollments e1
            INNER JOIN enrollments e2
                ON e1.student_id = e2.student_id
                AND e1.academic_term_id = e2.academic_term_id
                AND e1.id > e2.id
        ');
    }

    private function enrollmentsIndexExists(string $indexName): bool
    {
        $match = DB::selectOne(
            'SELECT COUNT(*) AS c FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ?',
            ['enrollments', $indexName]
        );

        return isset($match->c) && (int) $match->c > 0;
    }
};
