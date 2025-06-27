<?php

namespace App\Http\Controllers;

use App\Models\DC;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DCController extends Controller
{
    public function saveServiceDCData($serviceId, $dcData)
    {

        try {
            if ($serviceId == null || $serviceId < 1) {
                return false;
            }

            // Log::info("from dc",(array)$dcData);

            $dcColumnData = [
                'service_id' => $serviceId,
                "OC_voltage_string_1" => $dcData->OCVoltage[0] ?? 0.0,
                "OC_voltage_string_2" => $dcData->OCVoltage[1] ?? 0.0,
                "OC_voltage_string_3" => $dcData->OCVoltage[2] ?? 0.0,
                "OC_voltage_string_4" => $dcData->OCVoltage[3] ?? 0.0,
                "OC_voltage_string_5" => $dcData->OCVoltage[4] ?? 0.0,
                "OC_voltage_string_6" => $dcData->OCVoltage[5] ?? 0.0,
                "OC_voltage_string_7" => $dcData->OCVoltage[6] ?? 0.0,
                "OC_voltage_string_8" => $dcData->OCVoltage[7] ?? 0.0,
                "load_voltage_string_1" => $dcData->LoadVoltage[0] ?? 0.0,
                "load_voltage_string_2" => $dcData->LoadVoltage[1] ?? 0.0,
                "load_voltage_string_3" => $dcData->LoadVoltage[2] ?? 0.0,
                "load_voltage_string_4" => $dcData->LoadVoltage[3] ?? 0.0,
                "load_voltage_string_5" => $dcData->LoadVoltage[4] ?? 0.0,
                "load_voltage_string_6" => $dcData->LoadVoltage[5] ?? 0.0,
                "load_voltage_string_7" => $dcData->LoadVoltage[6] ?? 0.0,
                "load_voltage_string_8" => $dcData->LoadVoltage[7] ?? 0.0,
                "load_current_string_1" => $dcData->LoadCurrent[0] ?? 0.0,
                "load_current_string_2" => $dcData->LoadCurrent[1] ?? 0.0,
                "load_current_string_3" => $dcData->LoadCurrent[2] ?? 0.0,
                "load_current_string_4" => $dcData->LoadCurrent[3] ?? 0.0,
                "load_current_string_5" => $dcData->LoadCurrent[4] ?? 0.0,
                "load_current_string_6" => $dcData->LoadCurrent[5] ?? 0.0,
                "load_current_string_7" => $dcData->LoadCurrent[6] ?? 0.0,
                "load_current_string_8" => $dcData->LoadCurrent[7] ?? 0.0,
            ];

            $result = DC::create($dcColumnData);
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
