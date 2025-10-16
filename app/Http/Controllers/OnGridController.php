<?php

namespace App\Http\Controllers;
use App\Models\OnGrid;
use Illuminate\Http\Request;

class OnGridController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'on_grid_project_id' => 'nullable|string',
            'electricity_bill_name' => 'required|string|max:255',
            'wifi_username' => 'nullable|string|max:255',
             'wifi_password' => 'nullable|string|max:255',
            'harmonic_meter' => 'required|string|max:255',
            'remarks' => 'nullable|string'
        ]);

        $onGrid = OnGrid::updateOrCreate(
            ['project_id' => $validated['project_id']],
            $validated
        );

        return response()->json([
            'message' => 'Ongrid project details saved successfully.',
            'data' => $onGrid
        ], 200);
    }
}
