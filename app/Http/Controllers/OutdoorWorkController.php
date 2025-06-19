<?php

namespace App\Http\Controllers;

use App\Models\OutdoorWork;
use Illuminate\Http\Request;

class OutdoorWorkController extends Controller
{
    public function saveServiceOutDoorWork($serviceId,$outDoorWorkData){
        if($serviceId == null || $serviceId <1){
            return false;
        }

        $outDoorWorkColumnData = [
            "service_id"=>$serviceId,
            "CEB_import_reading"=>$outDoorWorkData->cebImport->value??'0',
            "CEB_import_reading_comments"=>$outDoorWorkData->cebImport->comment??null,
            "CEB_export_reading"=>$outDoorWorkData->cebExport->value??'0',
            "CEB_export_reading_comments"=>$outDoorWorkData->cebExport->comment??null,
            "round_resistence"=>$outDoorWorkData->groundResistance->value??'0',
            "round_resistence_comments"=>$outDoorWorkData->groundResistance->comment??null,
            "earthing_rod_connection"=>$outDoorWorkData->earthRod->checked,
            "earthing_rod_connection_comments"=>$outDoorWorkData->earthRod->comment??null,
        ];

        $result = OutdoorWork::create($outDoorWorkColumnData);
        if ($result === false) {
            return false;
        }else{
            return true;
        }
    }
}
