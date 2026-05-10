<?php

namespace Database\Seeders;

use App\Models\AcademicTerm;
use App\Models\Group;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DefenseDatasetSeeder extends Seeder
{
    private const TARGET_GROUP_COUNT = 32;

    public function run(): void
    {
        $activeTerm = AcademicTerm::query()->where('is_active', true)->first();
        if (! $activeTerm) {
            $this->command?->error('No active academic term found. Set one active term first.');

            return;
        }

        $facultyPool = User::query()
            ->whereIn('role', ['chairperson', 'coordinator', 'teacher', 'adviser', 'panelist'])
            ->where('academic_term_id', $activeTerm->id)
            ->get(['id', 'faculty_id', 'name']);

        if ($facultyPool->count() < 3) {
            $this->command?->error('Need at least 3 faculty users in the active term to build panel assignments.');

            return;
        }

        $this->stripLegacyBracketPrefixedGroupNames();

        $this->deletePreviouslySeededDefenseGroups($activeTerm);
        $this->ensureDefenseDatasetGroups($activeTerm, $facultyPool);

        $datasetNames = array_column($this->screenshotGroupBlueprints(), 'name');

        $groups = Group::query()
            ->with(['members', 'offering'])
            ->where('academic_term_id', $activeTerm->id)
            ->whereHas('members')
            ->whereNotNull('offering_id')
            ->whereIn('name', $datasetNames)
            ->orderBy('id')
            ->take(self::TARGET_GROUP_COUNT)
            ->get();

        if ($groups->isEmpty()) {
            $this->command?->warn('No eligible groups found for defense dataset seeding.');

            return;
        }

        DB::transaction(function () use ($groups, $facultyPool, $activeTerm) {
            // Intentionally no schedules/ratings here — groups and adviser assignments only.
        });

        $this->command?->info('Defense dataset ready for '.$groups->count().' groups with roster-aligned members.');
    }

    /** Older seeds prefixed group names with a bracket tag; strip so listings stay clean. */
    private function stripLegacyBracketPrefixedGroupNames(): void
    {
        Group::query()
            ->whereRaw("name REGEXP '^\\\\[[^\\\\]]+\\\\] '")
            ->chunkById(100, function ($groups): void {
                foreach ($groups as $group) {
                    $clean = preg_replace('/^\[[^\]]+\]\s+/u', '', $group->name);
                    if ($clean !== '' && $clean !== $group->name) {
                        $group->update(['name' => $clean]);
                    }
                }
            });
    }

    private function deletePreviouslySeededDefenseGroups(AcademicTerm $activeTerm): void
    {
        $datasetNames = array_column($this->screenshotGroupBlueprints(), 'name');

        $groupIds = Group::query()
            ->where('academic_term_id', $activeTerm->id)
            ->whereIn('name', $datasetNames)
            ->pluck('id');

        if ($groupIds->isEmpty()) {
            return;
        }

        $scheduleIds = DB::table('defense_schedules')->whereIn('group_id', $groupIds)->pluck('id');
        if ($scheduleIds->isNotEmpty()) {
            DB::table('defense_evaluation_summaries')->whereIn('defense_schedule_id', $scheduleIds)->delete();
            DB::table('rating_sheets')->whereIn('defense_schedule_id', $scheduleIds)->delete();
            DB::table('defense_panels')->whereIn('defense_schedule_id', $scheduleIds)->delete();
            DB::table('defense_schedules')->whereIn('id', $scheduleIds)->delete();
        }

        DB::table('group_members')->whereIn('group_id', $groupIds)->delete();
        Group::query()->whereIn('id', $groupIds)->delete();
    }

    private function ensureDefenseDatasetGroups(AcademicTerm $activeTerm, Collection $facultyPool): void
    {
        $offering = DB::table('offerings')
            ->where('academic_term_id', $activeTerm->id)
            ->orderBy('id')
            ->first();

        if (! $offering) {
            $this->command?->error('No offering found in active term. Create at least one offering first.');

            return;
        }

        $adviserFacultyIds = $facultyPool
            ->pluck('faculty_id')
            ->filter()
            ->map(fn ($id) => (string) $id)
            ->values();

        if ($adviserFacultyIds->isEmpty()) {
            $this->command?->error('No faculty_id values available to assign as group advisers.');

            return;
        }

        $ungroupedStudentIds = Student::query()
            ->whereNotIn('student_id', function ($query) {
                $query->select('student_id')->from('group_members');
            })
            ->orderBy('student_id')
            ->pluck('student_id')
            ->values();

        if ($ungroupedStudentIds->count() < 3) {
            $this->command?->warn('Not enough ungrouped students to fill many roster slots from imports.');
        }

        $groupBlueprints = $this->screenshotGroupBlueprints();

        $nextStudentNumber = (int) Student::query()
            ->selectRaw('MAX(CAST(student_id AS UNSIGNED)) as max_id')
            ->value('max_id');
        if ($nextStudentNumber < 2026000000) {
            $nextStudentNumber = 2026000000;
        }

        foreach (array_slice($groupBlueprints, 0, self::TARGET_GROUP_COUNT) as $blueprint) {
            $adviserFacultyId = $this->resolveAdviserFacultyId($blueprint['adviser_token'], $facultyPool)
                ?? $adviserFacultyIds->first();
            if (! $adviserFacultyId) {
                continue;
            }

            $group = Group::query()->create([
                'name' => $blueprint['name'],
                'description' => null,
                'faculty_id' => $adviserFacultyId,
                'academic_term_id' => $activeTerm->id,
                'offering_id' => $offering->id,
            ]);

            $memberStudentIds = [];
            foreach ($blueprint['members'] as $memberName) {
                $student = Student::query()->where('name', $memberName)->first();
                if (! $student) {
                    $nextStudentNumber++;
                    $generatedStudentId = (string) $nextStudentNumber;
                    $student = Student::query()->create([
                        'student_id' => $generatedStudentId,
                        'name' => $memberName,
                        'email' => strtolower(str_replace(' ', '.', preg_replace('/[^A-Za-z0-9 ]/', '', $memberName))).'@seed.local',
                        'course' => 'BS Information Technology',
                        'school_year' => $activeTerm->school_year,
                        'semester' => '1',
                    ]);
                }
                $memberStudentIds[] = (string) $student->student_id;
            }

            $memberStudentIds = array_values(array_unique($memberStudentIds));
            $memberStudentIds = array_slice($memberStudentIds, 0, 3);
            if (count($memberStudentIds) < 2) {
                continue;
            }

            foreach ($memberStudentIds as $memberIndex => $studentId) {
                DB::table('group_members')->insert([
                    'group_id' => $group->id,
                    'student_id' => $studentId,
                    'role' => $memberIndex === 0 ? 'leader' : 'member',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    private function resolveAdviserFacultyId(?string $token, Collection $facultyPool): ?string
    {
        if (! $token) {
            return null;
        }

        // Match roster spellings to imported faculty names (e.g. teachers CSV uses "Bandalan").
        $needleVariants = match ($token) {
            'Badalan' => ['Badalan', 'Bandalan'],
            default => [$token],
        };

        foreach ($needleVariants as $needle) {
            $match = $facultyPool->first(function ($faculty) use ($needle) {
                return stripos((string) $faculty->name, (string) $needle) !== false;
            });
            if ($match) {
                return (string) $match->faculty_id;
            }
        }

        return null;
    }

    private function screenshotGroupBlueprints(): array
    {
        return [
            // Adviser: Tejana
            ['name' => 'AI-dk', 'adviser_token' => 'Tejana', 'members' => ['Catado, Mary Amiel Riva', 'Plaza, Anthony James', 'Roche, Marylle']],
            ['name' => 'Mewkathon', 'adviser_token' => 'Tejana', 'members' => ['Torres, Jose Leto', 'Aroma, Kent Francois', 'Olbedencia, Philip James']],
            ['name' => 'gitpush', 'adviser_token' => 'Tejana', 'members' => ['Enopia, Marivic', 'Delantar, Ma. Teresa', 'Bestal, John William']],
            ['name' => 'J++', 'adviser_token' => 'Tejana', 'members' => ['Quijano, James', 'Tan, Jhedver']],
            // Adviser: Bayocot
            ['name' => 'Vagabond', 'adviser_token' => 'Bayocot', 'members' => ['Valencia, Christian Chil', 'Montealto, Anthony Jay', 'Pogoy, Rafhael']],
            ['name' => 'Sonnet4', 'adviser_token' => 'Bayocot', 'members' => ['Aguspina, Jabez', 'Evangelista, Rextine', 'Ravelo, Joshua']],
            ['name' => 'Gladiolus', 'adviser_token' => 'Bayocot', 'members' => ['Cornel, Kyla Alianna', 'Abella, May Fatima', 'Deiparine, Patricia']],
            ['name' => 'Grex VaMenTa', 'adviser_token' => 'Bayocot', 'members' => ['Mendoza, Ryu', 'Tan, Robien Lee', 'Valencia, Percival Louis']],
            ['name' => 'Alot', 'adviser_token' => 'Bayocot', 'members' => ['Maturan, Ryle', 'Tabarno, Ryan', 'Pamaos, Jorene']],
            ['name' => 'Wu-an Clan', 'adviser_token' => 'Bayocot', 'members' => ['Gonzales, Melvic John', 'Enad, Joshua']],
            ['name' => 'Honey Badger', 'adviser_token' => 'Bayocot', 'members' => ['Cinco, Kharl Edward', 'Lastimosa, Althea Marie']],
            // Adviser: Gadiane
            ['name' => 'Trinode', 'adviser_token' => 'Gadiane', 'members' => ['Antiquina, Nino Clint', 'Canete, Luigi', 'Obaob, Neil Joshua']],
            ['name' => "Catamco's Group", 'adviser_token' => 'Gadiane', 'members' => ['Catamco, Larry', 'Gabriel, Paulo', 'Villafuerte, Elijah']],
            ['name' => 'Fantastik Four', 'adviser_token' => 'Gadiane', 'members' => ['Lactuan, Dave Dominic', 'Panong, Kristian Cesar II', 'Suba, Jamal Robert']],
            // Adviser: Patalita
            ['name' => "Otter's Creek", 'adviser_token' => 'Patalita', 'members' => ['Baclayo, Lhea-Lhyn', 'Ong, Franka Isabelle', 'Andrino, Kim']],
            ['name' => 'Sting', 'adviser_token' => 'Patalita', 'members' => ['Caballero, Mel Kevin', 'Gimenez, Earl Reynan', 'Mansing, Jamil']],
            ['name' => 'Teambangan', 'adviser_token' => 'Patalita', 'members' => ['Burgos, Joseph', 'Tabada, Alica Lynn']],
            ['name' => 'Cobra Rise', 'adviser_token' => 'Patalita', 'members' => ['Deiparine, Renzo Miguel', 'Bonghanoy, Kyle', 'Sy, Christian Javidil']],
            ['name' => 'Bisag Unsa', 'adviser_token' => 'Patalita', 'members' => ['Ortiz, Sean Michael', 'Rances, Rainer Josh']],
            // Adviser: Badalan
            ['name' => 'GEWGabyte', 'adviser_token' => 'Badalan', 'members' => ['Enerez, Daveryle', 'Campos, Neville Henry']],
            // Adviser: Petralba
            ['name' => 'Bonk', 'adviser_token' => 'Petralba', 'members' => ['Lingcopines, Kyla Mari', 'Yangan, Bonn Azioele', 'Nipay, Nina Glen']],
            ['name' => 'KuanMoCode', 'adviser_token' => 'Petralba', 'members' => ['Carigo, Karen', 'Garcia, Alliyana Rose', 'Nacorda, Johanne']],
            ['name' => 'aHack', 'adviser_token' => 'Petralba', 'members' => ['Lagahid, Kaye Marie', 'Sembrano, Donna Elizabeth', 'Cano, Julien Veniz']],
            // Adviser: Homecillo
            ['name' => 'Sentinels', 'adviser_token' => 'Homecillo', 'members' => ['Pagador, Zandale', 'Bartolaba, Vinz Khyl James', 'Layam, Aaron John']],
            ['name' => 'CodeKada', 'adviser_token' => 'Homecillo', 'members' => ['Belgera, Jessa Marie', 'Olivo, Rena']],
            ['name' => 'Code Geeks', 'adviser_token' => 'Homecillo', 'members' => ['Lydzustre, Francis', 'Guarino, Venz Henry', 'Patena, Rhobert']],
            ['name' => 'BarLleGon', 'adviser_token' => 'Homecillo', 'members' => ['Bartulin, Geo Vince', 'Llevado, Bernie Leen', 'Sagon, Julian Andrei']],
            ['name' => 'CokalionHub', 'adviser_token' => 'Homecillo', 'members' => ['Cabanero, Ravi', 'Nabalan, Jude Ralph', 'Alas, Steven James']],
            // Adviser: Rubia
            ['name' => 'DeepMinds', 'adviser_token' => 'Rubia', 'members' => ['Reyes III, Ramon Reynel', 'Ylanan, Ymir Onil']],
            ['name' => 'Kanang Kuan', 'adviser_token' => 'Rubia', 'members' => ['Hista, John Argie', 'Patatag, John Paul', 'Monte de Ramos, Harvy']],
            ['name' => 'Akaton', 'adviser_token' => 'Rubia', 'members' => ['Cañete, John Maynard', 'De los Reyes, Joshua Caleb', 'Fernandez, Sean Archer']],
            ['name' => 'Hackemon', 'adviser_token' => 'Rubia', 'members' => ['Doquila, Queen Angel', 'Pugosa, Krishna Rhabe', 'Alberca, Chendy']],
        ];
    }

    // Note: schedule/panel/rating generation intentionally omitted here.
}
