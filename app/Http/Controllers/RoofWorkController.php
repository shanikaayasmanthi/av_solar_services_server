<?php

namespace App\Http\Controllers;


use App\Models\RoofWork;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;



class RoofWorkController extends Controller
{
    public function saveServiceRoofWorkData($serviceId, $roofWorkData)
    {
        try {
            if ($serviceId == null || $serviceId < 1) {
                return false;
            }

            // Log::info("roof".(array)$roofWorkData);
            $roofWorkColumnData = [
                "service_id" => $serviceId,
                "cloudness_reading" => $roofWorkData->cloudiness->value ?? 0,
                "cloudness_reading_comments" => $roofWorkData->cloudiness->comment ?? null,
                "panel_service" => $roofWorkData->panelService->checked,
                "panel_service_comments" => $roofWorkData->panelService->comment ?? null,
                "structure_service" => $roofWorkData->structureService->checked,
                "structure_service_comments" => $roofWorkData->structureService->comment ?? null,
                "nut_bolt_condition" => $roofWorkData->nutsBolts->checked,
                "nut_bolt_condition_comments" => $roofWorkData->nutsBolts->comment ?? null,
                "shadow" => $roofWorkData->shadow->checked,
                "shadow_comments" => $roofWorkData->shadow->comment ?? null,
                "panel_MC4_condition" => $roofWorkData->panelMp4->checked,
                "panel_MC4_condition_comments" => $roofWorkData->panelMp4->comment ?? null,
                "took_photos" => $roofWorkData->photos->checked,
                "took_photos_comments" => $roofWorkData->photos->comment ?? null,
            ];

            $result = RoofWork::create($roofWorkColumnData);
            if ($result === false) {
                return false;
            } else {
                return true;
            }
        } catch (\Exception $e) {
            throw $e;
        }
    }


    public function getRoofWorkDetailsByServiceId(Request $request)
{
    try {
        $request->validate([
            'service_id' => 'required|integer|exists:services,id'
        ]);

        $roofWork = RoofWork::where('service_id', $request->service_id)->first();

      if (!$roofWork) {
    return response()->json([
        'status' => 'no_data',
        'message' => 'Roof work details not found for this service',
        'data' => null
    ], 200); 
    }

    return response()->json([
        'status' => 'success',
        'data' => [
            'cloudness' => [
                'reading' => $roofWork->cloudness_reading,
                'comments' => $roofWork->cloudness_reading_comments
            ],
            'panel_service' => [
                'checked' => (bool)$roofWork->panel_service,
                'comments' => $roofWork->panel_service_comments
            ],
            'structure_service' => [
                'checked' => (bool)$roofWork->structure_service,
                'comments' => $roofWork->structure_service_comments
            ],
            'nut_bolt_condition' => [
                'checked' => (bool)$roofWork->nut_bolt_condition,
                'comments' => $roofWork->nut_bolt_condition_comments
            ],
            'shadow' => [
                'checked' => (bool)$roofWork->shadow,
                'comments' => $roofWork->shadow_comments
            ],
            'panel_MC4_condition' => [
                'checked' => (bool)$roofWork->panel_MC4_condition,
                'comments' => $roofWork->panel_MC4_condition_comments
            ],
            'took_photos' => [
                'checked' => (bool)$roofWork->took_photos,
                'comments' => $roofWork->took_photos_comments
            ]
        ]
    ]);

} catch (ValidationException $e) {
    return response()->json([
        'status' => 'error',
        'message' => 'Validation error',
        'errors' => $e->errors()
    ], 400);
} catch (Exception $e) {
    Log::error("Error fetching roof work details: " . $e->getMessage());
    return response()->json([
        'status' => 'error',
        'message' => 'Internal server error'
    ], 500);
}
}
}
