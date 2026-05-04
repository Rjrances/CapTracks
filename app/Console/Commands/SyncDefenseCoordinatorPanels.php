<?php

namespace App\Console\Commands;

use App\Models\AcademicTerm;
use App\Models\DefensePanel;
use App\Models\DefenseSchedule;
use App\Models\Offering;
use App\Models\User;
use Illuminate\Console\Command;

class SyncDefenseCoordinatorPanels extends Command
{
    protected $signature = 'captracks:sync-defense-coordinators
                            {--fix-cap402 : Align CS-CAP-402 (offer_code 12002) coordinator when duplicated as CAP I coordinator}
                            {--dry-run : Show changes without saving}';

    protected $description = 'Updates defense_panels coordinator rows to match each schedule\'s group offering coordinator (offerings.faculty_id).';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        if ($this->option('fix-cap402')) {
            $this->fixCap402OfferingCoordinator($dryRun);
        }

        $updated = 0;
        DefenseSchedule::query()
            ->with(['group.offering'])
            ->chunkById(50, function ($schedules) use ($dryRun, &$updated) {
                foreach ($schedules as $schedule) {
                    $offering = $schedule->group?->offering;
                    if (!$offering || !$offering->faculty_id) {
                        continue;
                    }

                    $coordinatorUser = User::query()
                        ->where('faculty_id', $offering->faculty_id)
                        ->orderBy('id')
                        ->first();

                    if (!$coordinatorUser) {
                        $this->warn("No user found for offering {$offering->id} faculty_id {$offering->faculty_id} (schedule {$schedule->id})");
                        continue;
                    }

                    $panel = DefensePanel::query()
                        ->where('defense_schedule_id', $schedule->id)
                        ->where('role', 'coordinator')
                        ->first();

                    if (!$panel) {
                        if (!$dryRun) {
                            DefensePanel::create([
                                'defense_schedule_id' => $schedule->id,
                                'faculty_id' => $coordinatorUser->id,
                                'role' => 'coordinator',
                                'status' => 'accepted',
                                'responded_at' => now(),
                            ]);
                        }
                        $this->line("Schedule {$schedule->id}: created coordinator panel → {$coordinatorUser->name} (user {$coordinatorUser->id})");
                        $updated++;
                        continue;
                    }

                    if ((int) $panel->faculty_id === (int) $coordinatorUser->id) {
                        continue;
                    }

                    $this->line("Schedule {$schedule->id}: coordinator {$panel->faculty_id} → {$coordinatorUser->id} ({$coordinatorUser->name})");
                    if (!$dryRun) {
                        $panel->update([
                            'faculty_id' => $coordinatorUser->id,
                            'status' => 'accepted',
                            'responded_at' => now(),
                        ]);
                    }
                    $updated++;
                }
            });

        $this->info($dryRun ? "Dry run complete. {$updated} row(s) would change." : "Done. {$updated} coordinator panel(s) updated.");

        return self::SUCCESS;
    }

    /**
     * When CAP II (12002) shares the same offerings.faculty_id as CAP I (12000) for the active term,
     * assign CAP II to the other coordinator user (faculty_id 10004) when present.
     */
    private function fixCap402OfferingCoordinator(bool $dryRun): void
    {
        $activeTerm = AcademicTerm::where('is_active', true)->first();
        if (!$activeTerm) {
            $this->warn('No active term; skipping --fix-cap402.');
            return;
        }

        $cap1 = Offering::query()
            ->where('academic_term_id', $activeTerm->id)
            ->where('offer_code', '12000')
            ->first();

        $cap2 = Offering::query()
            ->where('academic_term_id', $activeTerm->id)
            ->where('offer_code', '12002')
            ->first();

        if (!$cap1 || !$cap2) {
            $this->warn('Could not find offerings 12000 / 12002 for active term; skipping CAP II fix.');
            return;
        }

        if ((string) $cap1->faculty_id !== (string) $cap2->faculty_id) {
            $this->info('CAP I and CAP II already have different coordinators; no offering update.');
            return;
        }

        $michael = User::query()
            ->where('faculty_id', '10004')
            ->orderBy('id')
            ->first();

        if (!$michael) {
            $this->warn('No user with faculty_id 10004 found; cannot auto-split CAP II coordinator.');
            return;
        }

        $this->info("Setting CAP II (offering id {$cap2->id}) coordinator to {$michael->faculty_id} ({$michael->name}).");
        if (!$dryRun) {
            $cap2->update(['faculty_id' => $michael->faculty_id]);
        }
    }
}
