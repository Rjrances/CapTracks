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
    public function index()
    {
        return view('chairperson.dashboard');
    }

    // ======= OFFERINGS =======

    public function indexOfferings()
    {
        $offerings = Offering::all();
        return view('chairperson.offerings.index', compact('offerings'));
    }

    public function createOffering()
    {
        return view('chairperson.offerings.create');
    }

    public function editOffering($id)
    {
        $offering = Offering::findOrFail($id);
        return view('chairperson.offerings.edit', compact('offering'));
    }

    public function storeOffering(Request $request)
    {
         logger($request->all());
        $request->validate([
            'subject_title' => 'required|string|max:255',
            'subject_code' => 'required|string|max:100',
            'teacher_name'  => 'required|string|max:255',
        ]);

        Offering::create([
            'subject_title' => $request->subject_title,
            'subject_code'  => $request->subject_code,
            'teacher_name'  => $request->teacher_name,
        ]);

        return redirect()->route('chairperson.offerings.index')->with('success', 'Offering added successfully.');
    }

    public function updateOffering(Request $request, $id)
    {
        $request->validate([
            'subject_title' => 'required|string|max:255',
            'subject_code'  => 'required|string|max:100',
            'teacher_name'  => 'required|string|max:255',
        ]);

        $offering = Offering::findOrFail($id);

        $offering->update([
            'subject_title' => $request->subject_title,
            'subject_code'  => $request->subject_code,
            'teacher_name'  => $request->teacher_name,
        ]);

        return redirect()->route('chairperson.offerings.index')->with('success', 'Offering updated successfully.');
    }

    // ======= TEACHERS =======

    public function teachers()
    {
        $teachers = User::whereIn('role', ['adviser', 'panelist'])->get();
        return view('chairperson.teachers.index', compact('teachers'));
    }

    // ======= SCHEDULES =======

    public function schedules()
    {
        $schedules = Schedule::with('offering')->get();
        return view('chairperson.schedules.index', compact('schedules'));
    }

    // ======= STUDENT IMPORT =======

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
