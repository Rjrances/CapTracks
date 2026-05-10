<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if ($this->studentsIndexExists('students_email_academic_term_unique')) {
            Schema::table('students', function (Blueprint $table) {
                $table->dropUnique('students_email_academic_term_unique');
            });
        }

        if (Schema::hasColumn('students', 'academic_term_id')) {
            Schema::table('students', function (Blueprint $table) {
                $table->dropConstrainedForeignId('academic_term_id');
            });
        }

        if (! $this->studentsIndexExists('students_email_school_year_semester_unique')) {
            Schema::table('students', function (Blueprint $table) {
                $table->unique(['email', 'school_year', 'semester'], 'students_email_school_year_semester_unique');
            });
        }
    }

    public function down(): void
    {
        if ($this->studentsIndexExists('students_email_school_year_semester_unique')) {
            Schema::table('students', function (Blueprint $table) {
                $table->dropUnique('students_email_school_year_semester_unique');
            });
        }

        Schema::table('students', function (Blueprint $table) {
            $table->foreignId('academic_term_id')
                ->nullable()
                ->after('school_year')
                ->constrained('academic_terms')
                ->nullOnDelete();
        });

        DB::table('students')->orderBy('student_id')->chunk(200, function ($rows) {
            foreach ($rows as $row) {
                $sy = (string) ($row->school_year ?? '');
                $slot = (string) ($row->semester ?? '');
                if ($sy === '' || $slot === '') {
                    continue;
                }
                $tid = DB::table('academic_terms')
                    ->where('school_year', $sy)
                    ->get()
                    ->first(function ($t) use ($slot) {
                        $clean = trim((string) preg_replace('/^\d{4}-\d{4}\s+/', '', (string) $t->semester));

                        return strcasecmp($clean, $slot) === 0;
                    });

                if ($tid) {
                    DB::table('students')
                        ->where('student_id', $row->student_id)
                        ->update(['academic_term_id' => $tid->id]);
                }
            }
        });

        if (! $this->studentsIndexExists('students_email_academic_term_unique')) {
            Schema::table('students', function (Blueprint $table) {
                $table->unique(['email', 'academic_term_id'], 'students_email_academic_term_unique');
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
