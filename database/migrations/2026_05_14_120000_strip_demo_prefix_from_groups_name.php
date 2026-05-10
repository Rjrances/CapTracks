<?php

use App\Models\Group;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Older demo seeds stored "[DEMO] " inside groups.name; strip it so UI matches current seeds.
     */
    public function up(): void
    {
        Group::query()
            ->where('name', 'like', '[DEMO] %')
            ->chunkById(100, function ($groups): void {
                foreach ($groups as $group) {
                    $clean = preg_replace('/^\[DEMO\]\s+/u', '', $group->name);
                    if ($clean !== '' && $clean !== $group->name) {
                        $group->update(['name' => $clean]);
                    }
                }
            });
    }

    /**
     * Reverse is not supported — original prefixed names were not retained.
     */
    public function down(): void
    {
        //
    }
};
