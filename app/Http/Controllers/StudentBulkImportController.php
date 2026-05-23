<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\StudentBulkImporter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class StudentBulkImportController extends Controller
{
    public function run(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:5120',
        ]);

        $file = $request->file('csv_file');

        if (! $file || ! $file->isValid()) {
            return back()->with('import_error', 'File upload failed — please try again.');
        }

        $absolutePath = $file->getRealPath();

        try {
            $result = app(StudentBulkImporter::class)->import($absolutePath);
        } catch (\Throwable $e) {
            Log::warning('StudentBulkImport controller failed', ['error' => $e->getMessage()]);
            return back()->with('import_error', 'Import failed: ' . $e->getMessage());
        }

        return back()->with('import_result', $result);
    }
}
