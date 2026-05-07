<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('name_prefix', 20)->nullable()->after('name');
            $table->string('first_name', 100)->nullable()->after('name_prefix');
            $table->string('middle_name', 100)->nullable()->after('first_name');
            $table->string('last_name', 100)->nullable()->after('middle_name');
            $table->string('suffix', 20)->nullable()->after('last_name');
        });

        $students = DB::table('students')
            ->select('student_id', 'name')
            ->orderBy('student_id')
            ->get();

        foreach ($students as $student) {
            $name = trim((string) $student->name);
            if ($name === '') {
                continue;
            }

            $parts = preg_split('/\s+/', $name) ?: [];
            if (count($parts) === 1) {
                $firstName = $parts[0];
                $lastName = $parts[0];
                $middleName = null;
            } else {
                $firstName = array_shift($parts);
                $lastName = array_pop($parts);
                $middleName = !empty($parts) ? implode(' ', $parts) : null;
            }

            DB::table('students')
                ->where('student_id', $student->student_id)
                ->update([
                    'first_name' => $firstName ?: null,
                    'middle_name' => $middleName ?: null,
                    'last_name' => $lastName ?: null,
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn([
                'name_prefix',
                'first_name',
                'middle_name',
                'last_name',
                'suffix',
            ]);
        });
    }
};
