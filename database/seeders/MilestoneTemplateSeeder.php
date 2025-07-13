<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MilestoneTemplate;
use App\Models\MilestoneTask;

class MilestoneTemplateSeeder extends Seeder
{
    public function run()
    {
        $proposal = MilestoneTemplate::create([
            'name' => 'Proposal',
            'description' => 'Initial project proposal submission',
        ]);

        $proposal->tasks()->createMany([
            ['name' => 'Research Topic', 'description' => 'Identify and research topic', 'order' => 1],
            ['name' => 'Proposal Draft', 'description' => 'Write proposal draft', 'order' => 2],
            ['name' => 'Submit Proposal', 'description' => 'Submit proposal document', 'order' => 3],
        ]);

        $finalDefense = MilestoneTemplate::create([
            'name' => 'Final Defense',
            'description' => 'Final project presentation and defense',
        ]);

        $finalDefense->tasks()->createMany([
            ['name' => 'Prepare Presentation', 'description' => 'Create slides and demo', 'order' => 1],
            ['name' => 'Rehearse Defense', 'description' => 'Practice defense presentation', 'order' => 2],
            ['name' => 'Final Submission', 'description' => 'Submit final documents', 'order' => 3],
        ]);
    }
}
