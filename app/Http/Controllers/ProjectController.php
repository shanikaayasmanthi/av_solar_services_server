<?php

namespace App\Http\Controllers;

use App\Traits\HttpResponses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Exception;

use App\Models\Project;

use function PHPSTORM_META\map;

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

    //get project details 
    public function getprojectDetails(Request $request)
{
    try {
        $request->validate([
            'project_id' => "required|exists:projects,id"
        ]);

        $project = Project::with([
            "onGrid", "offGridHybrid.battery", "solarPanel", "invertor"
        ])->where("id", $request->project_id)->first();

        if (!$project) {
            return $this->error("", "Unauthorized", 401);
        }

        unset($project["customer_id"]);
        unset($project["project_name"]);
        unset($project["project_address"]);
        unset($project["neatest_town"]);
        unset($project["longitude"]);
        unset($project["lattitude"]);
        unset($project["location"]);
        unset($project["created_at"]);
        unset($project["updated_at"]);

        // Solar Panels
        $solarPanels = $project->solarPanel->map(function ($panel) {
            return [
                "solar_panel_model" => $panel->solar_panel_model,
                "solar_panel_type" => $panel->panel_type,
                "panel_model_code" => $panel->panel_model_code,
                "panel_wattage" => $panel->wattage_of_pannel,
                "no_of_panels" => $panel->no_of_panels,
            ];
        });

        unset($project["solarPanel"]);

        // Invertors
        $invertors = $project->invertor->map(function ($invertor) {
            return [
                "invertor_model_no" => $invertor->invertor_model_no,
                "invertor_check_code" => $invertor->invertor_check_code,
                "invertor_serial_no" => $invertor->invertor_serial_no,
                "brand" => $invertor->brand,
                "invertor_capacity" => $invertor->invertor_capacity,
            ];
        });
        unset($project["invertor"]);

        // OnGrid / OffGrid
        // $ongrid = null;
        // $offgrid = null;
        // $battery = null;

        if ($project->type == "ongrid") {
            $ongrid = $project->onGrid;
            unset($project["onGrid"]);
            unset($project["offGridHybrid"]);
            return $this->success([
                "project"=>$project,
                "on_grid"=>$ongrid,
                "solar_panel"=>$solarPanels,
                "invertor"=>$invertors
            ]);
        } elseif ($project->type == "offgrid") {
            $offgrid = $project->offGridHybrid;
            $battery = $offgrid?->battery;
            unset($project["onGrid"]);
            unset($project["offGridHybrid"]);
            unset($offgrid["battery"]);
            return $this->success([
                "project"=>$project,
                "off_grid_hybrid"=>$$offgrid,
                "solar_panel"=>$solarPanels,
                "invertor"=>$invertors,
                "battery"=>$battery,
            ]);
        }

    
    } catch (ValidationException $e) {
        return $this->error([], "No such project", 404);
    } catch (Exception $e) {
        return $this->error([], "Error occurred", 500);
    }
}


  public function getLocation($id)
    {
        try {
            // Added: Validate the route parameter $id to ensure it exists in the projects table
            validator(['id' => $id], [
                'id' => 'required|exists:projects,id'
            ])->validate();

            // Original logic: Fetch the project using the validated id
            $project = Project::find($id);

            if ($project) {
                return response()->json([
                    'lattitude' => $project->lattitude,
                    'longitude' => $project->longitude,
                ]);
            }

            return response()->json(['error' => 'Project not found'], 404);
        } catch (ValidationException $e) {
            // Added: Handle validation errors by returning a 404 with the error message
            return response()->json(['error' => 'Invalid or missing project ID'], 404);
        } catch (Exception $e) {
            // Added: Handle unexpected errors with a generic 500 response
            return response()->json(['error' => 'Error occurred'], 500);
        }
    }
}
