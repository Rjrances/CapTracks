<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('students', 'semester')) {
            Schema::table('students', function (Blueprint $table) {
                $table->string('semester', 50)->nullable()->after('school_year');
            });
        }

        DB::table('students')->orderBy('student_id')->chunk(200, function ($rows) {
            foreach ($rows as $row) {
                if (! $row->academic_term_id) {
                    continue;
                }
                $label = DB::table('academic_terms')->where('id', $row->academic_term_id)->value('semester');
                if (! $label) {
                    continue;
                }
                $slot = trim((string) preg_replace('/^\d{4}-\d{4}\s+/', '', (string) $label));
                if ($slot !== '') {
                    DB::table('students')->where('student_id', $row->student_id)->update(['semester' => $slot]);
                }
            }
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('students', 'semester')) {
            Schema::table('students', function (Blueprint $table) {
                $table->dropColumn('semester');
            });
        }
    }
};
