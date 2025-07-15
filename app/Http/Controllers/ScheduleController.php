<?php

namespace App\Http\Controllers;

use App\Models\Schedule;
use App\Models\Student;
use App\Models\Offering;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $schedules = Schedule::with(['student', 'offering'])->orderBy('date', 'desc')->get();
        return view('chairperson.schedules.index', compact('schedules'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $students = Student::all();
        $offerings = Offering::all();
        return view('chairperson.schedules.create', compact('students', 'offerings'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'offering_id' => 'nullable|exists:offerings,id',
            'type' => 'required|in:proposal,final',
            'date' => 'required|date',
            'time' => 'required',
            'room' => 'required|string|max:255',
            'remarks' => 'nullable|string',
        ]);
        Schedule::create($validated);
        return redirect()->route('schedules.index')->with('success', 'Schedule created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $schedule = Schedule::findOrFail($id);
        $students = Student::all();
        $offerings = Offering::all();
        return view('chairperson.schedules.edit', compact('schedule', 'students', 'offerings'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $schedule = Schedule::findOrFail($id);
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'offering_id' => 'nullable|exists:offerings,id',
            'type' => 'required|in:proposal,final',
            'date' => 'required|date',
            'time' => 'required',
            'room' => 'required|string|max:255',
            'remarks' => 'nullable|string',
        ]);
        $schedule->update($validated);
        return redirect()->route('schedules.index')->with('success', 'Schedule updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $schedule = Schedule::findOrFail($id);
        $schedule->delete();
        return redirect()->route('schedules.index')->with('success', 'Schedule deleted successfully.');
    }
}
