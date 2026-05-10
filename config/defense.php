<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Coordinator-selectable defense panel slots
    |--------------------------------------------------------------------------
    |
    | Total faculty slots assigned as Chair + Member + additional Panelists
    | (the group's adviser and offering coordinator are added separately).
    | Minimum 2; maximum 10.
    |
    */
    'panel_slots' => max(2, min(10, (int) env('DEFENSE_PANEL_SLOTS', 4))),
];
