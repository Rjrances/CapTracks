<?php
namespace App\Http\Controllers;
use App\Models\AcademicTerm;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class AcademicTermController extends Controller
{
    public function index()
    {
        $academicTerms = AcademicTerm::orderBy('school_year', 'desc')
            ->orderBy('semester')
            ->get();
        return view('chairperson.academic-terms.index', compact('academicTerms'));
    }
    public function create()
    {
        return view('chairperson.academic-terms.create');
    }
    public function store(Request $request)
    {
        $request->validate([
            'school_year' => 'required|string|max:255',
            'semester' => [
                'required',
                'in:First Semester,Second Semester,Summer',
                function ($attribute, $value, $fail) use ($request) {
                    $fullSemester = trim((string) $request->school_year) . ' ' . $value;
                    $exists = AcademicTerm::where('semester', $fullSemester)->exists();
                    if ($exists) {
                        $fail('This school year and semester combination already exists.');
                    }
                },
            ],
        ]);
        if ($request->has('is_active') && $request->is_active) {
            AcademicTerm::where('is_active', true)->update(['is_active' => false]);
        }
        AcademicTerm::create([
            'school_year' => $request->school_year,
            'semester' => $request->school_year . ' ' . $request->semester,
            'is_active' => $request->has('is_active') ? $request->is_active : false,
            'is_archived' => false
        ]);
        return redirect()->route('chairperson.academic-terms.index')
            ->with('success', 'Academic term created successfully.');
    }
    public function show(AcademicTerm $academicTerm)
    {
        return view('chairperson.academic-terms.show', compact('academicTerm'));
    }
    public function edit(AcademicTerm $academicTerm)
    {
        return view('chairperson.academic-terms.edit', compact('academicTerm'));
    }
    public function update(Request $request, AcademicTerm $academicTerm)
    {
        $request->validate([
            'school_year' => 'required|string|max:255',
            'semester' => [
                'required',
                'in:First Semester,Second Semester,Summer',
                function ($attribute, $value, $fail) use ($request, $academicTerm) {
                    $fullSemester = trim((string) $request->school_year) . ' ' . $value;
                    $exists = AcademicTerm::where('semester', $fullSemester)
                        ->where('id', '!=', $academicTerm->id)
                        ->exists();
                    if ($exists) {
                        $fail('This school year and semester combination already exists.');
                    }
                },
            ],
        ]);

        $wantsActive = $request->has('is_active') && $request->is_active;
        if (!$wantsActive && $academicTerm->is_active) {
            $hasOtherActiveTerm = AcademicTerm::where('id', '!=', $academicTerm->id)
                ->where('is_active', true)
                ->exists();
            if (!$hasOtherActiveTerm) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'At least one academic term must remain active.');
            }
        }

        if ($request->has('is_active') && $request->is_active) {
            AcademicTerm::where('id', '!=', $academicTerm->id)
                ->where('is_active', true)
                ->update(['is_active' => false]);
        }
        $academicTerm->update([
            'school_year' => $request->school_year,
            'semester' => $request->school_year . ' ' . $request->semester,
            'is_active' => $request->has('is_active') ? $request->is_active : false,
            'is_archived' => $request->has('is_archived') ? $request->is_archived : false
        ]);
        return redirect()->route('chairperson.academic-terms.index')
            ->with('success', 'Academic term updated successfully.');
    }
    public function destroy(AcademicTerm $academicTerm)
    {
        if ($academicTerm->is_active) {
            return redirect()->route('chairperson.academic-terms.index')
                ->with('error', 'Cannot delete the active academic term.');
        }
        $academicTerm->delete();
        return redirect()->route('chairperson.academic-terms.index')
            ->with('success', 'Academic term deleted successfully.');
    }
    public function toggleActive(AcademicTerm $academicTerm)
    {
        if ($academicTerm->is_archived) {
            return redirect()->route('chairperson.academic-terms.index')
                ->with('error', 'Cannot activate an archived academic term.');
        }

        if ($academicTerm->is_active) {
            $hasOtherActiveTerm = AcademicTerm::where('id', '!=', $academicTerm->id)
                ->where('is_active', true)
                ->exists();
            if (!$hasOtherActiveTerm) {
                return redirect()->route('chairperson.academic-terms.index')
                    ->with('error', 'At least one academic term must remain active.');
            }
        }

        DB::transaction(function () use ($academicTerm) {
            AcademicTerm::where('id', '!=', $academicTerm->id)
                ->update(['is_active' => false]);
            $academicTerm->update(['is_active' => !$academicTerm->is_active]);
        });
        $status = $academicTerm->fresh()->is_active ? 'activated' : 'deactivated';
        return redirect()->route('chairperson.academic-terms.index')
            ->with('success', "Academic term {$status} successfully.");
    }
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
