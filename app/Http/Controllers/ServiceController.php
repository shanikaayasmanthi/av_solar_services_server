<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\Supervisor;
use App\Traits\HttpResponses;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ServiceController extends Controller
{
    use HttpResponses;

    public function getSupervisorAllServices(Request $request){
        try{
            $request->validate([
                'user_id'=>'required|exists:supervisors,user_id'
            ]);

            $services = Service::where('supervisor_id', $request->user_id)
                   ->whereNull('service_time')
                   ->get();

        } catch(ValidationException $e){
            return $this->error('','Unauthorized access',401);
        } catch (Exception $e){
            return $this->error('',$e,500);
        }
    }
}
