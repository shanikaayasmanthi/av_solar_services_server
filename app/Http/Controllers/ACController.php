<?php

namespace App\Http\Controllers;

use App\Models\AC;
use Illuminate\Http\Request;

class ACController extends Controller
{
    public function saveServiceACData($serviceId, $acData)
    {
        try {
            if ($serviceId == null || $serviceId < 1) {
                return false;
            }

            $acColumnData = [
                'service_id' => $serviceId,
                'OC_valtage_L1_N' => $acData->OCVoltage[0] ?? 0.0,
                'OC_valtage_L2_N' => $acData->OCVoltage[1] ?? 0.0,
                'OC_valtage_L3_N' => $acData->OCVoltage[2] ?? 0.0,
                'OC_valtage_L1_L2' => $acData->OCVoltage[3] ?? 0.0,
                'OC_valtage_L1_L3' => $acData->OCVoltage[4] ?? 0.0,
                'OC_valtage_L2_L3' => $acData->OCVoltage[5] ?? 0.0,
                'OC_valtage_N_E' => $acData->OCVoltage[6] ?? 0.0,
                'load_valtage_L1_N' => $acData->LoadVoltage[0] ?? 0.0,
                'load_valtage_L2_N' => $acData->LoadVoltage[1] ?? 0.0,
                'load_valtage_L3_N' => $acData->LoadVoltage[2] ?? 0.0,
                'load_valtage_L1_L2' => $acData->LoadVoltage[3] ?? 0.0,
                'load_valtage_L1_L3' => $acData->LoadVoltage[4] ?? 0.0,
                'load_valtage_L2_L3' => $acData->LoadVoltage[5] ?? 0.0,
                'load_valtage_N_E' => $acData->LoadVoltage[6] ?? 0.0,
                'load_current_L1_N' => $acData->LoadCurrent[0] ?? 0.0,
                'load_current_L2_N' => $acData->LoadCurrent[1] ?? 0.0,
                'load_current_L3_N' => $acData->LoadCurrent[2] ?? 0.0,
            ];

            $result = AC::create($acColumnData);
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
