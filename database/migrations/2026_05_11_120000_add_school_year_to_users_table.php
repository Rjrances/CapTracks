<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'school_year')) {
                $table->string('school_year', 20)->nullable()->after('department');
            }
        });

        if (Schema::hasColumn('users', 'school_year')) {
            DB::table('users')
                ->whereNull('school_year')
                ->whereNotNull('semester')
                ->orderBy('id')
                ->chunk(200, function ($rows) {
                    foreach ($rows as $row) {
                        $sem = (string) ($row->semester ?? '');
                        if (preg_match('/^(\d{4}-\d{4})\s+/', $sem, $m)) {
                            DB::table('users')
                                ->where('id', $row->id)
                                ->update(['school_year' => $m[1]]);
                        }
                    }
                });
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'school_year')) {
                $table->dropColumn('school_year');
            }
        });
    }
};
