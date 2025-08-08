<?php

namespace App\Http\Controllers;

use App\Models\AcademicTerm;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AcademicTermController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $academicTerms = AcademicTerm::orderBy('school_year', 'desc')
            ->orderBy('semester')
            ->get();
        
        return view('chairperson.academic-terms.index', compact('academicTerms'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('chairperson.academic-terms.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'school_year' => 'required|string|max:255',
            'semester' => 'required|in:First Semester,Second Semester,Summer',
        ]);

        // If this term is being set as active, deactivate all other terms
        if ($request->has('is_active') && $request->is_active) {
            AcademicTerm::where('is_active', true)->update(['is_active' => false]);
        }

        AcademicTerm::create([
            'school_year' => $request->school_year,
            'semester' => $request->semester,
            'is_active' => $request->has('is_active') ? $request->is_active : false,
            'is_archived' => false
        ]);

        return redirect()->route('chairperson.academic-terms.index')
            ->with('success', 'Academic term created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(AcademicTerm $academicTerm)
    {
        return view('chairperson.academic-terms.show', compact('academicTerm'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(AcademicTerm $academicTerm)
    {
        return view('chairperson.academic-terms.edit', compact('academicTerm'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, AcademicTerm $academicTerm)
    {
        $request->validate([
            'school_year' => 'required|string|max:255',
            'semester' => 'required|in:First Semester,Second Semester,Summer',
        ]);

        // If this term is being set as active, deactivate all other terms
        if ($request->has('is_active') && $request->is_active) {
            AcademicTerm::where('id', '!=', $academicTerm->id)
                ->where('is_active', true)
                ->update(['is_active' => false]);
        }

        $academicTerm->update([
            'school_year' => $request->school_year,
            'semester' => $request->semester,
            'is_active' => $request->has('is_active') ? $request->is_active : false,
            'is_archived' => $request->has('is_archived') ? $request->is_archived : false
        ]);

        return redirect()->route('chairperson.academic-terms.index')
            ->with('success', 'Academic term updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(AcademicTerm $academicTerm)
    {
        // Check if this is the active term
        if ($academicTerm->is_active) {
            return redirect()->route('chairperson.academic-terms.index')
                ->with('error', 'Cannot delete the active academic term.');
        }

        $academicTerm->delete();

        return redirect()->route('chairperson.academic-terms.index')
            ->with('success', 'Academic term deleted successfully.');
    }

    /**
     * Toggle the active status of an academic term
     */
    public function toggleActive(AcademicTerm $academicTerm)
    {
        if ($academicTerm->is_archived) {
            return redirect()->route('chairperson.academic-terms.index')
                ->with('error', 'Cannot activate an archived academic term.');
        }

        DB::transaction(function () use ($academicTerm) {
            // Deactivate all other terms
            AcademicTerm::where('id', '!=', $academicTerm->id)
                ->update(['is_active' => false]);
            
            // Activate this term
            $academicTerm->update(['is_active' => !$academicTerm->is_active]);
        });

        $status = $academicTerm->fresh()->is_active ? 'activated' : 'deactivated';
        return redirect()->route('chairperson.academic-terms.index')
            ->with('success', "Academic term {$status} successfully.");
    }

    /**
     * Toggle the archived status of an academic term
     */
    public function toggleArchived(AcademicTerm $academicTerm)
    {
        if ($academicTerm->is_active) {
            return redirect()->route('chairperson.academic-terms.index')
                ->with('error', 'Cannot archive the active academic term.');
        }

        $academicTerm->update(['is_archived' => !$academicTerm->is_archived]);

        $status = $academicTerm->fresh()->is_archived ? 'archived' : 'unarchived';
        return redirect()->route('chairperson.academic-terms.index')
            ->with('success', "Academic term {$status} successfully.");
    }
}
