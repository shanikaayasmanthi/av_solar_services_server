<?php

namespace App\Http\Controllers;

use App\Models\MainPanelWork;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;


class MainPanelWorkController extends Controller
{
    public function saveServiceMainPanelWork($serviceId, $mainPanelWorkData)
    {

        try {
            if ($serviceId == null || $serviceId < 1) {
                return false;
            }

            $mainPanelWorkColumnData = [
                "service_id" => $serviceId,
                "on_grid_valtage" => $mainPanelWorkData->onlineGridVoltage->value ?? '0',
                "on_grid_valtage_comments" => $mainPanelWorkData->onlineGridVoltage->comment ?? null,
                "off_grid_valtage" => $mainPanelWorkData->offlineGridVoltage->value ?? '0',
                "off_grid_valtage_comments" => $mainPanelWorkData->offlineGridVoltage->comment ?? '0',
                "invertor_service_fan_time" => $mainPanelWorkData->invertorServiceFanTime->checked,
                "invertor_service_fan_time_comments" => $mainPanelWorkData->invertorServiceFanTime->comment ?? null,
                "breaker_service" => $mainPanelWorkData->breakerService->checked,
                "breaker_service_comments" => $mainPanelWorkData->breakerService->comment ?? null,
                "DC_surge_arrestors" => $mainPanelWorkData->dcSurgeArrestors->checked,
                "DC_surge_arrestors_comments" => $mainPanelWorkData->dcSurgeArrestors->comment ?? null,
                "AC_surge_arrestors" => $mainPanelWorkData->acSurgeArrestors->checked,
                "AC_surge_arrestors_comments" => $mainPanelWorkData->acSurgeArrestors->comment ?? null,
                "invertor_connection_MC4_condition" => $mainPanelWorkData->invertorConnection->checked,
                "invertor_connection_MC4_condition_comments" => $mainPanelWorkData->invertorConnection->comment ?? null,
                "low_valtage_range" => $mainPanelWorkData->lowVoltageRange->value ?? '0',
                "low_valtage_range_comments" => $mainPanelWorkData->lowVoltageRange->comment ?? null,
                "high_valtage_range" => $mainPanelWorkData->highVoltageRange->value ?? '0',
                "high_valtage_range_comments" => $mainPanelWorkData->highVoltageRange->comment ?? null,
                "low_freaquence_range" => $mainPanelWorkData->lowFrequencyRange->value ?? '0',
                "low_freaquence_range_comments" => $mainPanelWorkData->lowFrequencyRange->comment ?? null,
                "high_freaquence_range" => $mainPanelWorkData->highFrequencyRange->value ?? '0',
                "high_freaquence_range_comments" => $mainPanelWorkData->highFrequencyRange->comment ?? null,
                "invertor_startup_time" => $mainPanelWorkData->invertorSetupTime->value ?? '0',
                "invertor_startup_time_comments" => $mainPanelWorkData->invertorSetupTime->comment ?? null,
                "e_today_invertor" => $mainPanelWorkData->eTodayInvertor->value ?? '0',
                "e_today_invertor_comments" => $mainPanelWorkData->eTodayInvertor->comment ?? null,
                "e_total_invertor" => $mainPanelWorkData->eTotalInvertor->value ?? '0',
                "e_total_invertor_comments" => $mainPanelWorkData->eTotalInvertor->comment ?? null,
                "power_bulb_blinking_style" => $mainPanelWorkData->powerBulbBlinkingStyle->value ?? '',
                "power_bulb_blinking_style_comments" => $mainPanelWorkData->powerBulbBlinkingStyle->comment ?? null,
                "alta_vision_sticker" => $mainPanelWorkData->serviceAVSticker->checked,
                "alta_vision_sticker_comments" => $mainPanelWorkData->serviceAVSticker->comment ?? null,
                "wifi_config_done" => $mainPanelWorkData->wifiConfig->checked,
                "wifi_config_done_comments" => $mainPanelWorkData->wifiConfig->comment ?? null,
                "router_username" => $mainPanelWorkData->routerUsername->value ?? null,
                "router_username_comments" => $mainPanelWorkData->routerUsername->comment ?? null,
                "router_password" => $mainPanelWorkData->routerPassword->value ?? null,
                "router_password_comments" => $mainPanelWorkData->routerPassword->comment ?? null,
                "router_serial_number" => $mainPanelWorkData->routerSerialNo->value ?? null,
                "router_serial_number_comments" => $mainPanelWorkData->routerSerialNo->comment ?? null,
                "took_photos" => $mainPanelWorkData->tookPhotos->checked,
                "took_photos_comments" => $mainPanelWorkData->tookPhotos->comment ?? null,

            ];

            $result = MainPanelWork::create($mainPanelWorkColumnData);
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
