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
}
