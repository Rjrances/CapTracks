<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ChairpersonDashboardController extends Controller
{
    public function index()
    {
        // Load chairperson-specific data
        return view('dashboards.chairperson');
    }
}
