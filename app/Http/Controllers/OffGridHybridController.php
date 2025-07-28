<?php

namespace App\Http\Controllers;
use App\Models\OffGridHybrid;
use Illuminate\Http\Request;

class OffGridHybridController extends Controller
{
    public function store(Request $request)
{
    $validated = $request->validate([
        'project_id' => 'required|exists:projects,id',
        'off_grid_hybrid_project_id' => 'required|string', 
        'connection_type' => 'required|string|max:50',
        'wifi_username' => 'nullable|string|max:255',
        'wifi_passowrd' => 'nullable|string|max:255',
        'remarks' => 'nullable|string'
    ]);

    $offgrid = OffGridHybrid::updateOrCreate(
        ['project_id' => $validated['project_id']],
        $validated
    );

    return response()->json([
        'message' => 'Offgrid project details saved successfully.',
        'data' => $offgrid
    ], 200);
}
}
