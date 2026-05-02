<?php

namespace App\Services;

use App\Imports\StudentsImport;
use App\Models\Offering;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Validators\ValidationException;

class StudentImportService
{
    public const MODE_CHAIRPERSON = 'chairperson';

    public const MODE_COORDINATOR = 'coordinator';

    public function importFromRequest(Request $request, string $mode = self::MODE_CHAIRPERSON): RedirectResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:csv|max:10240',
        ], [
            'file.required' => 'Please select a file to upload.',
            'file.file' => 'The uploaded file is invalid.',
            'file.mimes' => 'Please upload a CSV file (.csv).',
            'file.max' => 'File size must not exceed 10MB.',
        ]);

        if ($mode === self::MODE_COORDINATOR) {
            $user = $request->user();
            $offeringId = $request->get('offering_id');
            if ($offeringId) {
                $offering = Offering::find($offeringId);
                if (!$offering || (int) $offering->faculty_id !== (int) $user->faculty_id) {
                    return back()->with('error', 'You can only import for offerings you coordinate.');
                }
            }
        }

        try {
            $file = $request->file('file');
            $fileName = $file->getClientOriginalName();
            if ($file->getSize() === 0) {
                return back()->with('error', 'Import failed: The uploaded file is empty. Please check your file and try again.');
            }

            $offeringId = $request->get('offering_id');
            $import = new StudentsImport($offeringId);
            Excel::import($import, $file);

            if ($offeringId) {
                try {
                    $offering = Offering::find($offeringId);
                    if ($offering) {
                        $recentStudents = Student::where('created_at', '>=', now()->subMinutes(2))->get();
                        if ($recentStudents->count() > 0) {
                            foreach ($recentStudents as $student) {
                                $student->enrollInOffering($offering);
                            }
                        }
                    }
                } catch (\Exception $e) {
                    Log::error('Fallback enrollment failed: ' . $e->getMessage());
                }
            }

            $successMessage = "Students imported successfully from '{$fileName}'!";

            if ($request->has('offering_id')) {
                $oid = $request->get('offering_id');
                $offering = Offering::find($oid);
                $enrollmentMessage = $offering
                    ? " Students have been automatically enrolled in {$offering->subject_code}."
                    : '';

                if ($mode === self::MODE_CHAIRPERSON) {
                    return redirect()->route('chairperson.offerings.show', $oid)
                        ->with('success', $successMessage . $enrollmentMessage);
                }

                return redirect()->route('coordinator.classlist.index')
                    ->with('success', $successMessage . $enrollmentMessage);
            }

            if ($mode === self::MODE_COORDINATOR) {
                return redirect()->route('coordinator.classlist.index')->with('success', $successMessage);
            }

            return back()->with('success', $successMessage);
        } catch (ValidationException $e) {
            Log::error('Student import validation failed: ' . $e->getMessage());
            $errorMessage = "Import failed due to validation errors:\n";
            $allErrors = [];
            foreach ($e->failures() as $failure) {
                foreach ($failure->errors() as $error) {
                    $allErrors[] = $error;
                }
            }
            $errorMessage .= '• ' . implode("\n• ", $allErrors);

            return back()->with('error', $errorMessage);
        } catch (\Exception $e) {
            Log::error('Student import failed: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            $errorMessage = 'Import failed: ' . $e->getMessage();
            if (str_contains(strtolower($e->getMessage()), 'duplicate entry')) {
                $errorMessage = 'Import failed: Some student IDs or emails already exist in the system. Please check for duplicates.';
            } elseif (str_contains(strtolower($e->getMessage()), 'syntax error')) {
                $errorMessage = "Import failed: The file format is invalid. Please ensure it's a valid Excel or CSV file.";
            } elseif (str_contains(strtolower($e->getMessage()), 'permission denied')) {
                $errorMessage = 'Import failed: Permission denied. Please check file permissions.';
            } elseif (str_contains(strtolower($e->getMessage()), 'could not find driver')) {
                $errorMessage = 'Import failed: Database connection issue. Please try again.';
            } elseif (str_contains(strtolower($e->getMessage()), 'memory limit')) {
                $errorMessage = 'Import failed: File is too large. Please try with a smaller file or contact administrator.';
            }

            return back()->with('error', $errorMessage);
        }
    }
}
