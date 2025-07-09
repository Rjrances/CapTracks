<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CoordinatorDashboardController extends Controller
{
    public function index()
    {
        // Load coordinator-specific data
        return view('dashboards.coordinator');
    }
}
