<?php

namespace App\Http\Controllers;

use App\Models\OutdoorWork;
use Illuminate\Http\Request;

class OutdoorWorkController extends Controller
{
    public function saveServiceOutDoorWork($serviceId, $outDoorWorkData)
    {
        try {
            if ($serviceId == null || $serviceId < 1) {
                return false;
            }

            $outDoorWorkColumnData = [
                "service_id" => $serviceId,
                "CEB_import_reading" => $outDoorWorkData->cebImport->value ?? '0',
                "CEB_import_reading_comments" => $outDoorWorkData->cebImport->comment ?? null,
                "CEB_export_reading" => $outDoorWorkData->cebExport->value ?? '0',
                "CEB_export_reading_comments" => $outDoorWorkData->cebExport->comment ?? null,
                "round_resistence" => $outDoorWorkData->groundResistance->value ?? '0',
                "round_resistence_comments" => $outDoorWorkData->groundResistance->comment ?? null,
                "earthing_rod_connection" => $outDoorWorkData->earthRod->checked,
                "earthing_rod_connection_comments" => $outDoorWorkData->earthRod->comment ?? null,
            ];

            $result = OutdoorWork::create($outDoorWorkColumnData);
            if ($result === false) {
                return false;
            } else {
                return true;
            }
        } catch (\Exception $e) {
            throw $e;
        }
    }

        public function getOutdoorWorkDetails(Request $request)
    {
        try {
            $request->validate([
                'service_id' => 'required|integer|exists:services,id'
            ]);

            $outdoorWork = OutdoorWork::where('service_id', $request->service_id)->first();

            if (!$outdoorWork) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Outdoor work details not found'
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'data' => [
                    'CEB_import' => [
                        'reading' => $outdoorWork->CEB_import_reading,
                        'comments' => $outdoorWork->CEB_import_reading_comments
                    ],
                    'CEB_export' => [
                        'reading' => $outdoorWork->CEB_export_reading,
                        'comments' => $outdoorWork->CEB_export_reading_comments
                    ],
                    'ground_resistance' => [
                        'reading' => $outdoorWork->round_resistence,
                        'comments' => $outdoorWork->round_resistence_comments
                    ],
                    'earthing_rod' => [
                        'checked' => (bool)$outdoorWork->earthing_rod_connection,
                        'comments' => $outdoorWork->earthing_rod_connection_comments
                    ]
                ]
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Server error'
            ], 500);
        }
    }
}
