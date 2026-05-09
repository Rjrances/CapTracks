<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('students', 'year') && !Schema::hasColumn('students', 'year_level')) {
            DB::statement('ALTER TABLE students CHANGE year year_level VARCHAR(50) NULL');
        }

        Schema::table('students', function (Blueprint $table) {
            if (!Schema::hasColumn('students', 'school_year')) {
                $table->string('school_year', 20)->nullable()->after('course');
            }
        });

        if (Schema::hasColumn('students', 'school_year')) {
            DB::table('students')
                ->whereNull('school_year')
                ->whereNotNull('semester')
                ->orderBy('student_id')
                ->chunk(200, function ($rows) {
                    foreach ($rows as $row) {
                        $sem = (string) ($row->semester ?? '');
                        if (preg_match('/^(\d{4}-\d{4})\s+/', $sem, $m)) {
                            DB::table('students')
                                ->where('student_id', $row->student_id)
                                ->update(['school_year' => $m[1]]);
                        }
                    }
                });
        }
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            if (Schema::hasColumn('students', 'school_year')) {
                $table->dropColumn('school_year');
            }
        });

        if (Schema::hasColumn('students', 'year_level') && !Schema::hasColumn('students', 'year')) {
            DB::statement('ALTER TABLE students CHANGE year_level year VARCHAR(50) NULL');
        }
    }
};
