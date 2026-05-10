<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Coordinator-selectable defense panel slots (invited faculty)
    |--------------------------------------------------------------------------
    |
    | Maximum rows submitted as Chair + Member + optional Panelists (the group's
    | adviser and offering coordinator are added separately). Minimum submitted
    | is 2 (Chair + Member only). Config value is max total invited slots (2–10).
    |
    */
    'panel_slots' => max(2, min(10, (int) env('DEFENSE_PANEL_SLOTS', 4))),
];
