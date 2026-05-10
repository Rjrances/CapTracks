<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'academic_term_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreignId('academic_term_id')
                    ->nullable()
                    ->after('school_year')
                    ->constrained('academic_terms')
                    ->nullOnDelete();
            });
        }

        if (Schema::hasColumn('users', 'semester')) {
            DB::table('users')->orderBy('id')->chunk(200, function ($rows) {
                foreach ($rows as $row) {
                    $sem = (string) ($row->semester ?? '');
                    if ($sem === '' || $sem === 'Unknown') {
                        continue;
                    }
                    $tid = DB::table('academic_terms')->where('semester', $sem)->value('id');
                    if ($tid) {
                        DB::table('users')
                            ->where('id', $row->id)
                            ->whereNull('academic_term_id')
                            ->update(['academic_term_id' => $tid]);
                    }
                }
            });
        }

        $this->deleteDuplicateUsers();

        foreach ([
            'users_faculty_id_semester_unique',
            'users_email_semester_unique',
        ] as $indexName) {
            if ($this->usersIndexExists($indexName)) {
                Schema::table('users', function (Blueprint $table) use ($indexName) {
                    $table->dropUnique($indexName);
                });
            }
        }

        if (! $this->usersIndexExists('users_faculty_id_academic_term_unique')) {
            Schema::table('users', function (Blueprint $table) {
                $table->unique(['faculty_id', 'academic_term_id'], 'users_faculty_id_academic_term_unique');
            });
        }

        if (! $this->usersIndexExists('users_email_academic_term_unique')) {
            Schema::table('users', function (Blueprint $table) {
                $table->unique(['email', 'academic_term_id'], 'users_email_academic_term_unique');
            });
        }

        if (Schema::hasColumn('users', 'semester')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('semester');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('users', 'semester')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('semester', 50)->nullable()->after('school_year');
            });

            DB::table('users')->whereNotNull('academic_term_id')->orderBy('id')->chunk(200, function ($rows) {
                foreach ($rows as $row) {
                    $label = DB::table('academic_terms')->where('id', $row->academic_term_id)->value('semester');
                    if ($label) {
                        DB::table('users')->where('id', $row->id)->update(['semester' => $label]);
                    }
                }
            });
        }

        if ($this->usersIndexExists('users_email_academic_term_unique')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropUnique('users_email_academic_term_unique');
            });
        }

        if ($this->usersIndexExists('users_faculty_id_academic_term_unique')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropUnique('users_faculty_id_academic_term_unique');
            });
        }

        foreach ([
            ['faculty_id', 'semester', 'users_faculty_id_semester_unique'],
            ['email', 'semester', 'users_email_semester_unique'],
        ] as [$colA, $colB, $name]) {
            if (! $this->usersIndexExists($name) && Schema::hasColumn('users', 'semester')) {
                Schema::table('users', function (Blueprint $table) use ($colA, $colB, $name) {
                    $table->unique([$colA, $colB], $name);
                });
            }
        }

        if (Schema::hasColumn('users', 'academic_term_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropConstrainedForeignId('academic_term_id');
            });
        }
    }

    private function deleteDuplicateUsers(): void
    {
        DB::statement('
            DELETE u1 FROM users u1
            INNER JOIN users u2
                ON u1.faculty_id = u2.faculty_id
                AND u1.academic_term_id <=> u2.academic_term_id
                AND u1.id > u2.id
        ');
    }

    private function usersIndexExists(string $indexName): bool
    {
        $match = DB::selectOne(
            'SELECT COUNT(*) AS c FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ?',
            ['users', $indexName]
        );

        return isset($match->c) && (int) $match->c > 0;
    }
};
