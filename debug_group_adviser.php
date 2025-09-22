<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Group;

$group = Group::find(2);
echo "Group: " . $group->name . PHP_EOL;
echo "Group faculty_id: " . $group->faculty_id . PHP_EOL;
echo "Group adviser: " . ($group->adviser ? $group->adviser->name : 'No adviser') . PHP_EOL;
if ($group->adviser) {
    echo "Adviser user ID: " . $group->adviser->id . PHP_EOL;
    echo "Adviser faculty_id: " . $group->adviser->faculty_id . PHP_EOL;
}
