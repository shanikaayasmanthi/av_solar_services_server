<?php

namespace App\Http\Controllers;

use App\Models\ServiceTechniciant;
use Illuminate\Http\Request;

class ServiceTechniciantController extends Controller
{
    public function saveServiceTechnicians($serviceId, $technicians)
{
    try {
        foreach ($technicians as $technician) {
            $data = [
                "service_id" => $serviceId,
                "techniciant_name" => $technician
            ];
            $result = ServiceTechniciant::create($data);

            if (!$result) {
                throw new \Exception("Failed to save technician: " . $technician);
            }
        }

        // If all inserts are done successfully:
        return true;

    } catch (\Exception $e) {
        throw $e;  // throw to parent
    }
}

}
