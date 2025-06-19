<?php

namespace App\Http\Controllers;

use App\Models\ServiceTechniciant;
use Illuminate\Http\Request;

class ServiceTechniciantController extends Controller
{
    public function saveServiceTechnicians($serviceId,$technicians){
        foreach($technicians as $technician){
            $data = [
                "service_id"=>$serviceId,
                "techniciant_name"=>$technician
            ];
            $result = ServiceTechniciant::create($data);

            if($result){
                return true;
            }else{
                return false;
            }
        }
    }
}
