<?php

namespace App\Services;

use App\Imports\FacultyImport;
use App\Models\AcademicTerm;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Validators\ValidationException;

class FacultyImportService
{
    public function importFromRequest(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:csv|max:10240',
        ], [
            'file.required' => 'Please select a file to upload.',
            'file.file' => 'The uploaded file is invalid.',
            'file.mimes' => 'Please upload a CSV file (.csv).',
            'file.max' => 'File size must not exceed 10MB.',
        ]);

        try {
            Log::info('Starting faculty import...');
            $file = $request->file('file');
            $fileName = $file->getClientOriginalName();
            $fileSize = number_format($file->getSize() / 1024, 2);

            if ($file->getSize() === 0) {
                return back()->with('error', 'Import failed: The uploaded file is empty. Please check your file and try again.');
            }

            Log::info("Importing file: {$fileName} (Size: {$fileSize} KB)");
            $activeTerm = AcademicTerm::where('is_active', true)->first();
            $semester = $activeTerm ? $activeTerm->semester : null;
            $import = new FacultyImport($semester);
            Excel::import($import, $file);
            Log::info('Faculty import completed successfully');

            $createdCount = $import->getCreatedCount();
            $updatedCount = $import->getUpdatedCount();
            $existingChangedCount = $import->getExistingFacultyChangedCount();
            $existingUnchangedCount = $import->getExistingFacultyUnchangedCount();
            if ($createdCount === 0 && $updatedCount === 0) {
                return back()->with('error', 'Import failed: no faculty rows could be processed. Check that your file has data rows and required columns.');
            }

            $summaryParts = [];
            if ($createdCount > 0) {
                $summaryParts[] = $createdCount === 1 ? '1 new faculty member added' : "{$createdCount} new faculty members added";
            }
            if ($updatedCount > 0) {
                if ($existingChangedCount > 0 && $existingUnchangedCount > 0) {
                    $summaryParts[] = "{$existingChangedCount} existing faculty member(s) had information or role updated; {$existingUnchangedCount} already matched the file with no changes.";
                } elseif ($existingChangedCount > 0) {
                    $summaryParts[] = $existingChangedCount === 1
                        ? '1 existing faculty member had information or role updated.'
                        : "{$existingChangedCount} existing faculty members had information or role updated.";
                } else {
                    $summaryParts[] = $updatedCount === 1
                        ? '1 existing faculty member matched the import with no field or role changes.'
                        : "{$updatedCount} existing faculty members matched the import with no field or role changes.";
                }
            }
            $successMessage = "Faculty import completed from '{$fileName}'";
            $successMessage .= !empty($summaryParts) ? ': ' . implode('; ', $summaryParts) . '.' : '.';

            return redirect()->route('chairperson.teachers.index')->with('success', $successMessage);
        } catch (ValidationException $e) {
            Log::error('Faculty import validation failed: ' . $e->getMessage());
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
            Log::error('Faculty import failed: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            $errorMessage = 'Import failed: ' . $e->getMessage();
            if (str_contains(strtolower($e->getMessage()), 'duplicate entry')) {
                $errorMessage = 'Import failed: Some faculty IDs or emails already exist in the system. Please check for duplicates.';
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
