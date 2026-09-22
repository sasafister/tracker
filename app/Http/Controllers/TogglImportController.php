<?php

namespace App\Http\Controllers;

use App\Services\TogglCsvImporter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class TogglImportController extends Controller
{
    /**
     * Toggl writes times in the exporting user's own timezone and says
     * nothing about it in the file, so the browser sends its zone along.
     */
    public function store(Request $request, TogglCsvImporter $importer): JsonResponse
    {
        $data = $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:10240'],
            'timezone' => ['required', 'timezone:all'],
        ]);

        try {
            $result = $importer->import(
                $request->user(),
                $data['file']->getRealPath(),
                $data['timezone'],
            );
        } catch (RuntimeException $error) {
            return response()->json([
                'message' => $error->getMessage(),
            ], 422);
        }

        return response()->json($result);
    }
}
