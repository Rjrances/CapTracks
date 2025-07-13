<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Offering;
use App\Models\User;
use App\Models\Schedule;
use App\Imports\StudentsImport;
use Maatwebsite\Excel\Facades\Excel;

class ChairpersonController extends Controller
{
    // Chairperson dashboard
    public function index()
    {
        return view('chairperson.dashboard');
    }

    // ========== OFFERINGS ==========

    // Show list of offerings
    public function indexOfferings()
    {
        $offerings = Offering::all();
        return view('chairperson.offerings.index', compact('offerings'));
    }

    // Show create offering form
    public function createOffering()
    {
        return view('chairperson.offerings.create');
    }

    // Show edit form for an offering
    public function editOffering($id)
    {
        $offering = Offering::findOrFail($id);
        return view('chairperson.offerings.edit', compact('offering'));
    }

    // Store a new offering
    public function storeOffering(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        Offering::create([
            'title' => $request->title,
            'description' => $request->description,
        ]);

        return redirect()->route('chairperson.offerings.index')
                         ->with('success', 'Offering added successfully.');
    }

    // Update an existing offering
    public function updateOffering(Request $request, $id)
    {
        $offering = Offering::findOrFail($id);

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $offering->update([
            'title' => $request->title,
            'description' => $request->description,
        ]);

        return redirect()->route('chairperson.offerings.index')
                         ->with('success', 'Offering updated successfully.');
    }

    // Delete an offering
    public function deleteOffering($id)
    {
        $offering = Offering::findOrFail($id);
        $offering->delete();

        return redirect()->route('chairperson.offerings.index')
                         ->with('success', 'Offering deleted successfully.');
    }

    // ========== TEACHERS ==========

    public function teachers()
    {
        $teachers = User::whereIn('role', ['adviser', 'panelist'])->get();
        return view('chairperson.teachers.index', compact('teachers'));
    }

    // ========== SCHEDULES ==========

    public function schedules()
    {
        $schedules = Schedule::with('offering')->get(); // eager loading offering
        return view('chairperson.schedules.index', compact('schedules'));
    }

    // ========== STUDENT EXCEL IMPORT ==========

    public function uploadStudentList(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:2048',
        ]);

        try {
            Excel::import(new StudentsImport, $request->file('file'));
            return redirect()->back()->with('success', 'Student list imported successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['file' => 'Import failed: ' . $e->getMessage()]);
        }
    }
}
