<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('students', 'academic_term_id')) {
            Schema::table('students', function (Blueprint $table) {
                $table->foreignId('academic_term_id')
                    ->nullable()
                    ->after('school_year')
                    ->constrained('academic_terms')
                    ->nullOnDelete();
            });
        }

        if (Schema::hasColumn('students', 'semester')) {
            DB::table('students')->orderBy('student_id')->chunk(200, function ($rows) {
                foreach ($rows as $row) {
                    $sem = (string) ($row->semester ?? '');
                    if ($sem === '') {
                        continue;
                    }
                    $termId = DB::table('academic_terms')->where('semester', $sem)->value('id');
                    if ($termId) {
                        DB::table('students')
                            ->where('student_id', $row->student_id)
                            ->whereNull('academic_term_id')
                            ->update(['academic_term_id' => $termId]);
                    }
                }
            });
        }

        foreach ([
            'students_student_id_semester_unique',
            'students_email_semester_unique',
        ] as $indexName) {
            if ($this->studentsIndexExists($indexName)) {
                Schema::table('students', function (Blueprint $table) use ($indexName) {
                    $table->dropUnique($indexName);
                });
            }
        }

        if (! $this->studentsIndexExists('students_email_academic_term_unique')) {
            Schema::table('students', function (Blueprint $table) {
                $table->unique(['email', 'academic_term_id'], 'students_email_academic_term_unique');
            });
        }

        if (Schema::hasColumn('students', 'semester')) {
            Schema::table('students', function (Blueprint $table) {
                $table->dropColumn('semester');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('students', 'semester')) {
            Schema::table('students', function (Blueprint $table) {
                $table->string('semester', 50)->nullable()->after('school_year');
            });

            DB::table('students')->whereNotNull('academic_term_id')->orderBy('student_id')->chunk(200, function ($rows) {
                foreach ($rows as $row) {
                    $label = DB::table('academic_terms')->where('id', $row->academic_term_id)->value('semester');
                    if ($label) {
                        DB::table('students')
                            ->where('student_id', $row->student_id)
                            ->update(['semester' => $label]);
                    }
                }
            });
        }

        if ($this->studentsIndexExists('students_email_academic_term_unique')) {
            Schema::table('students', function (Blueprint $table) {
                $table->dropUnique('students_email_academic_term_unique');
            });
        }

        foreach ([
            ['student_id', 'semester', 'students_student_id_semester_unique'],
            ['email', 'semester', 'students_email_semester_unique'],
        ] as [$colA, $colB, $name]) {
            if (! $this->studentsIndexExists($name) && Schema::hasColumn('students', 'semester')) {
                Schema::table('students', function (Blueprint $table) use ($colA, $colB, $name) {
                    $table->unique([$colA, $colB], $name);
                });
            }
        }

        if (Schema::hasColumn('students', 'academic_term_id')) {
            Schema::table('students', function (Blueprint $table) {
                $table->dropConstrainedForeignId('academic_term_id');
            });
        }
    }

    private function studentsIndexExists(string $indexName): bool
    {
        $match = DB::selectOne(
            'SELECT COUNT(*) AS c FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ?',
            ['students', $indexName]
        );

        return isset($match->c) && (int) $match->c > 0;
    }
};
