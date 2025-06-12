<?php

namespace App\Http\Controllers;

use App\Traits\HttpResponses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Exception;

use App\Models\Project;

class ProjectController extends Controller
{
    use HttpResponses;

    public function getCustomer(Request $request){
        try{
            $request ->validate([
            "project_id"=>"required|exists:projects,id"
        ]);
        // log::info($request->project_id);

        $project = Project::with(['customer.customerPhoneNo'])->where('id',$request ->project_id)->firstOrFail();

        if($project){
            $customer = $project->customer;
            if($customer){
                $phonenumbers = $customer->customerPhoneNo->pluck('phone_no')->toArray()??[];
                $customer = $customer->toArray();
                unset($customer['customer_phone_no']);
                 return $this->success([
                    'customer'=>$customer,
                    'phone_numbers'=>$phonenumbers
                 ]);

            }else{
                return $this->error('','No such customer',404);
            }
        }else{
            return $this->error('','No such project',404);
        }
        }catch(ValidationException $e){
           return $this->error('','',401);
        }catch(Exception $e){
           return $this->error('','',500);
        }
        


    }

    public function getLocation($id)
    {
        $project = Project::find($id);

        if ($project) {
            return response()->json([
                
                'lattitude' => $project->lattitude, 
                'longitude' => $project->longitude,
            ]);
        }

        return response()->json(['error' => 'Project not found'], 404);
    }
}
