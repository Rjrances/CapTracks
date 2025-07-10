<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Offering;
use App\Models\User;
use App\Models\Schedule;

class ChairpersonController extends Controller
{
    public function index() 
    {
        return view('chairperson.dashboard');
    }

    // OFFERINGS
    public function offerings()
    {
        $offerings = Offering::all();
        return view('chairperson.offerings.index', compact('offerings'));
    }

    public function storeOffering(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        Offering::create($request->only('title', 'description'));

        return back()->with('success', 'Offering added successfully.');
    }

    public function updateOffering(Request $request, $id)
    {
        $offering = Offering::findOrFail($id);

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $offering->update($request->only('title', 'description'));

        return back()->with('success', 'Offering updated successfully.');
    }

    public function deleteOffering($id)
    {
        $offering = Offering::findOrFail($id);
        $offering->delete();

        return back()->with('success', 'Offering deleted successfully.');
    }

    // TEACHERS
    public function teachers()
    {
        $teachers = User::whereIn('role', ['adviser', 'panelist'])->get();
        return view('chairperson.teachers.index', compact('teachers'));
    }

    // SCHEDULES
    public function schedules()
    {
        $schedules = Schedule::all();
        return view('chairperson.schedules.index', compact('schedules'));
    }
}
