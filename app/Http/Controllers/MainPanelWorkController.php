<?php

namespace App\Http\Controllers;

use App\Models\MainPanelWork;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;


class MainPanelWorkController extends Controller
{
// public function saveServiceMainPanelWork($serviceId, $mainPanelWorkData)
// {
//     try {

//         $data = json_decode(json_encode($mainPanelWorkData), true);


//         \Log::info("Incoming main panel data:", (array) $data);
//         if ($serviceId == null || $serviceId < 1) {
//             return false;
//         }

//         $mainPanelWorkColumnData = [
//             "service_id" => $serviceId,
//               "on_grid_valtage" => $mainPanelWorkData['onlineGridVoltage']['value'] ?? 0,
//             "on_grid_valtage_comments" => $mainPanelWorkData['onlineGridVoltage']['comment'] ?? null,
//             "off_grid_valtage" => $mainPanelWorkData['offlineGridVoltage']['value'] ?? 0,
//             "off_grid_valtage_comments" => $mainPanelWorkData['offlineGridVoltage']['comment'] ?? null,
//             "invertor_service_fan_time" => $mainPanelWorkData['invertorServiceFanTime']['checked'] ?? 0,
//             "invertor_service_fan_time_comments" => $mainPanelWorkData['invertorServiceFanTime']['comment'] ?? null,

//             "breaker_service" => $mainPanelWorkData['breakerService']['checked'] ?? 0,
//             "breaker_service_comments" => $mainPanelWorkData['breakerService']['comment'] ?? null,

//             "DC_surge_arrestors" => $mainPanelWorkData['dcSurgeArrestors']['checked'] ?? 0,
//             "DC_surge_arrestors_comments" => $mainPanelWorkData['dcSurgeArrestors']['comment'] ?? null,

//             "AC_surge_arrestors" => $mainPanelWorkData['acSurgeArrestors']['checked'] ?? 0,
//             "AC_surge_arrestors_comments" => $mainPanelWorkData['acSurgeArrestors']['comment'] ?? null,

//             "invertor_connection_MC4_condition" => $mainPanelWorkData['invertorConnection']['checked'] ?? 0,
//             "invertor_connection_MC4_condition_comments" => $mainPanelWorkData['invertorConnection']['comment'] ?? null,

//             "low_valtage_range" => $mainPanelWorkData['lowVoltageRange']['value'] ?? 0,
//             "low_valtage_range_comments" => $mainPanelWorkData['lowVoltageRange']['comment'] ?? null,

//             "high_valtage_range" => $mainPanelWorkData['highVoltageRange']['value'] ?? 0,
//             "high_valtage_range_comments" => $mainPanelWorkData['highVoltageRange']['comment'] ?? null,

//             "low_freaquence_range" => $mainPanelWorkData['lowFrequencyRange']['value'] ?? 0,
//             "low_freaquence_range_comments" => $mainPanelWorkData['lowFrequencyRange']['comment'] ?? null,

//             "high_freaquence_range" => $mainPanelWorkData['highFrequencyRange']['value'] ?? 0,
//             "high_freaquence_range_comments" => $mainPanelWorkData['highFrequencyRange']['comment'] ?? null,
            
//             // Correct field name mapping
//             "invertor_startup_time" => $mainPanelWorkData['invertorSetupTime']['value'] ?? 0,
//             "invertor_startup_time_comments" => $mainPanelWorkData['invertorSetupTime']['comment'] ?? null,

//             "e_today_invertor" => $mainPanelWorkData['eTodayInvertor']['value'] ?? 0,
//             "e_today_invertor_comments" => $mainPanelWorkData['eTodayInvertor']['comment'] ?? null,

//             "e_total_invertor" => $mainPanelWorkData['eTotalInvertor']['value'] ?? 0,
//             "e_total_invertor_comments" => $mainPanelWorkData['eTotalInvertor']['comment'] ?? null,

//             // "power_bulb_blinking_style" => $mainPanelWorkData['powerBulbBlinkingStyle']['value'] ?? '',
//             // "power_bulb_blinking_style_comments" => $mainPanelWorkData['powerBulbBlinkingStyle']['comment'] ?? null,
//             "power_bulb_blinking_style" => isset($mainPanelWorkData['powerBulbBlinkingStyle']['value']) 
//     ? (string) $mainPanelWorkData['powerBulbBlinkingStyle']['value'] 
//     : null,
// "power_bulb_blinking_style_comments" => $mainPanelWorkData['powerBulbBlinkingStyle']['comment'] ?? null,


//             // Correct field name mapping
//             "alta_vision_sticker" => $mainPanelWorkData['serviceAVSticker']['checked'] ? 1 : 0,
//             "alta_vision_sticker_comments" => $mainPanelWorkData['serviceAVSticker']['comment'] ?? null,

//             "wifi_config_done" => $mainPanelWorkData['wifiConfig']['checked'] ? 1 : 0,
//             "wifi_config_done_comments" => $mainPanelWorkData['wifiConfig']['comment'] ?? null,

//             "router_username" => $mainPanelWorkData['routerUsername']['value'] ?? null,
//             "router_username_comments" => $mainPanelWorkData['routerUsername']['comment'] ?? null,

//             "router_password" => $mainPanelWorkData['routerPassword']['value'] ?? null,
//             "router_password_comments" => $mainPanelWorkData['routerPassword']['comment'] ?? null,

//             "router_serial_number" => $mainPanelWorkData['routerSerialNo']['value'] ?? null,
//             "router_serial_number_comments" => $mainPanelWorkData['routerSerialNo']['comment'] ?? null,

//             "took_photos" => $mainPanelWorkData['tookPhotos']['checked'] ? 1 : 0,
//             "took_photos_comments" => $mainPanelWorkData['tookPhotos']['comment'] ?? null,
//         ];

//         \Log::info("Processed main panel data:", $mainPanelWorkColumnData);
        
//         $result = MainPanelWork::create($mainPanelWorkColumnData);
        
//         \Log::info("Save result:", ['result' => $result]);
        
//         return $result ? true : false;
//     } catch (\Exception $e) {
//         \Log::error("MainPanelWork save error: " . $e->getMessage(), [
//             'trace' => $e->getTraceAsString()
//         ]);
//         throw $e;
//     }
// }

public function saveServiceMainPanelWork($serviceId, $mainPanelWorkData)
{
    try {
        if (empty($serviceId) || $serviceId < 1) {
            \Log::warning("Invalid serviceId passed to saveServiceMainPanelWork", [
                'serviceId' => $serviceId
            ]);
            return false;
        }

        // Normalize stdClass -> array
        $data = json_decode(json_encode($mainPanelWorkData), true) ?? [];

        \Log::info("Incoming main panel data:", $data);

        $mainPanelWorkColumnData = [
            "service_id" => $serviceId,

            "on_grid_valtage" => $data['onlineGridVoltage']['value'] ?? 0,
            "on_grid_valtage_comments" => $data['onlineGridVoltage']['comment'] ?? null,

            "off_grid_valtage" => $data['offlineGridVoltage']['value'] ?? 0,
            "off_grid_valtage_comments" => $data['offlineGridVoltage']['comment'] ?? null,

            "invertor_service_fan_time" => isset($data['invertorServiceFanTime']['checked']) ? (int)$data['invertorServiceFanTime']['checked'] : 0,
            "invertor_service_fan_time_comments" => $data['invertorServiceFanTime']['comment'] ?? null,

            "breaker_service" => isset($data['breakerService']['checked']) ? (int)$data['breakerService']['checked'] : 0,
            "breaker_service_comments" => $data['breakerService']['comment'] ?? null,

            "DC_surge_arrestors" => isset($data['dcSurgeArrestors']['checked']) ? (int)$data['dcSurgeArrestors']['checked'] : 0,
            "DC_surge_arrestors_comments" => $data['dcSurgeArrestors']['comment'] ?? null,

            "AC_surge_arrestors" => isset($data['acSurgeArrestors']['checked']) ? (int)$data['acSurgeArrestors']['checked'] : 0,
            "AC_surge_arrestors_comments" => $data['acSurgeArrestors']['comment'] ?? null,

            "invertor_connection_MC4_condition" => isset($data['invertorConnection']['checked']) ? (int)$data['invertorConnection']['checked'] : 0,
            "invertor_connection_MC4_condition_comments" => $data['invertorConnection']['comment'] ?? null,

            "low_valtage_range" => $data['lowVoltageRange']['value'] ?? 0,
            "low_valtage_range_comments" => $data['lowVoltageRange']['comment'] ?? null,

            "high_valtage_range" => $data['highVoltageRange']['value'] ?? 0,
            "high_valtage_range_comments" => $data['highVoltageRange']['comment'] ?? null,

            "low_freaquence_range" => $data['lowFrequencyRange']['value'] ?? 0,
            "low_freaquence_range_comments" => $data['lowFrequencyRange']['comment'] ?? null,

            "high_freaquence_range" => $data['highFrequencyRange']['value'] ?? 0,
            "high_freaquence_range_comments" => $data['highFrequencyRange']['comment'] ?? null,
            
            "invertor_startup_time" => $data['invertorSetupTime']['value'] ?? 0,
            "invertor_startup_time_comments" => $data['invertorSetupTime']['comment'] ?? null,

            "e_today_invertor" => $data['eTodayInvertor']['value'] ?? 0,
            "e_today_invertor_comments" => $data['eTodayInvertor']['comment'] ?? null,

            "e_total_invertor" => $data['eTotalInvertor']['value'] ?? 0,
            "e_total_invertor_comments" => $data['eTotalInvertor']['comment'] ?? null,

            // ✅ safely saved as string
            "power_bulb_blinking_style" => isset($data['powerBulbBlinkingStyle']['value'])
                ? (string) $data['powerBulbBlinkingStyle']['value']
                : null,
            "power_bulb_blinking_style_comments" => $data['powerBulbBlinkingStyle']['comment'] ?? null,

            "alta_vision_sticker" => isset($data['serviceAVSticker']['checked']) ? (int)$data['serviceAVSticker']['checked'] : 0,
            "alta_vision_sticker_comments" => $data['serviceAVSticker']['comment'] ?? null,

            "wifi_config_done" => isset($data['wifiConfig']['checked']) ? (int)$data['wifiConfig']['checked'] : 0,
            "wifi_config_done_comments" => $data['wifiConfig']['comment'] ?? null,

            "router_username" => $data['routerUsername']['value'] ?? null,
            "router_username_comments" => $data['routerUsername']['comment'] ?? null,

            "router_password" => $data['routerPassword']['value'] ?? null,
            "router_password_comments" => $data['routerPassword']['comment'] ?? null,

            "router_serial_number" => $data['routerSerialNo']['value'] ?? null,
            "router_serial_number_comments" => $data['routerSerialNo']['comment'] ?? null,

            "took_photos" => isset($data['tookPhotos']['checked']) ? (int)$data['tookPhotos']['checked'] : 0,
            "took_photos_comments" => $data['tookPhotos']['comment'] ?? null,
        ];

        \Log::info("Processed main panel data:", $mainPanelWorkColumnData);

        // Use updateOrCreate to prevent duplicates
        $result = MainPanelWork::updateOrCreate(
            ['service_id' => $serviceId],
            $mainPanelWorkColumnData
        );

        \Log::info("Save result:", ['result' => $result]);

        return (bool) $result;
    } catch (\Exception $e) {
        \Log::error("MainPanelWork save error: " . $e->getMessage(), [
            'trace' => $e->getTraceAsString()
        ]);
        return false;
    }
}


 public function getMainPanelWorkDetails(Request $request)
{
    try {
        $request->validate([
            'service_id' => 'required|integer|exists:services,id'
        ]);

        $mainPanelWork = MainPanelWork::where('service_id', $request->service_id)->first();

       if (!$mainPanelWork) {
            return response()->json([
                'status' => 'no_data',
                'message' => 'Main panel work details not found',
                'data' => null
            ], 200); 
        }


        return response()->json([
            'status' => 'success',
            'data' => [
                'on_grid_voltage' => [
                    'reading' => $mainPanelWork->on_grid_valtage,
                    'comments' => $mainPanelWork->on_grid_valtage_comments
                ],
                'off_grid_voltage' => [
                    'reading' => $mainPanelWork->off_grid_valtage,
                    'comments' => $mainPanelWork->off_grid_valtage_comments
                ],
                'invertor_fan_time' => [
                    'reading' => $mainPanelWork->invertor_service_fan_time,
                    'comments' => $mainPanelWork->invertor_service_fan_time_comments
                ],
                'breaker_service' => [
                    'checked' => (bool)$mainPanelWork->breaker_service,
                    'comments' => $mainPanelWork->breaker_service_comments
                ],
                'dc_surge_arrestors' => [
                    'status' => $mainPanelWork->DC_surge_arrestors,
                    'comments' => $mainPanelWork->DC_surge_arrestors_comments
                ],
                'ac_surge_arrestors' => [
                    'status' => $mainPanelWork->AC_surge_arrestors,
                    'comments' => $mainPanelWork->AC_surge_arrestors_comments
                ],
                'invertor_mc4_condition' => [
                    'status' => $mainPanelWork->invertor_connection_MC4_condition,
                    'comments' => $mainPanelWork->invertor_connection_MC4_condition_comments
                ],
                'low_voltage_range' => [
                    'value' => $mainPanelWork->low_valtage_range,
                    'comments' => $mainPanelWork->low_valtage_range_comments
                ],
                'high_voltage_range' => [
                    'value' => $mainPanelWork->high_valtage_range,
                    'comments' => $mainPanelWork->high_valtage_range_comments
                ],
                'low_frequency_range' => [
                    'value' => $mainPanelWork->low_freaquence_range,
                    'comments' => $mainPanelWork->low_freaquence_range_comments
                ],
                'high_frequency_range' => [
                    'value' => $mainPanelWork->high_freaquence_range,
                    'comments' => $mainPanelWork->high_freaquence_range_comments
                ],
                'invertor_startup_time' => [
                    'value' => $mainPanelWork->invertor_startup_time,
                    'comments' => $mainPanelWork->invertor_startup_time_comments
                ],
                'e_today' => [
                    'value' => $mainPanelWork->e_today_invertor,
                    'comments' => $mainPanelWork->e_today_invertor_comments
                ],
                'e_total' => [
                    'value' => $mainPanelWork->e_total_invertor,
                    'comments' => $mainPanelWork->e_total_invertor_comments
                ],
                'power_bulb_blinking_style' => [
                    'description' => $mainPanelWork->power_bulb_blinking_style,
                    'comments' => $mainPanelWork->power_bulb_blinking_style_comments
                ],
                'alta_vision_sticker' => [
                    'available' => (bool)$mainPanelWork->alta_vision_sticker,
                    'comments' => $mainPanelWork->alta_vision_sticker_comments
                ],
                'wifi_config_done' => [
                    'done' => (bool)$mainPanelWork->wifi_config_done,
                    'comments' => $mainPanelWork->wifi_config_done_comments
                ],
                'router_credentials' => [
                    'username' => $mainPanelWork->router_username,
                    'username_comments' => $mainPanelWork->router_username_comments,
                    'password' => $mainPanelWork->router_password,
                    'password_comments' => $mainPanelWork->router_password_comments,
                    'serial_number' => $mainPanelWork->router_serial_number,
                    'serial_number_comments' => $mainPanelWork->router_serial_number_comments
                ],
                'took_photos' => [
                    'status' => (bool)$mainPanelWork->took_photos,
                    'comments' => $mainPanelWork->took_photos_comments
                ],
                'images' => $mainPanelWork->images
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
            'message' => 'Server error',
            'error' => $e->getMessage()
        ], 500);
    }
}

}
