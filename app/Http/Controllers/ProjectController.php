<?php

namespace App\Http\Controllers;

use App\Models\OffGridHybrid;
use App\Models\OnGrid;
use App\Traits\HttpResponses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Exception;
use Illuminate\Support\Str;

use App\Models\Project;
use App\Models\User;

use function PHPSTORM_META\map;

class ProjectController extends Controller
{
    use HttpResponses;

    public function getCustomer(Request $request)
    {
        try {
            $request->validate([
                "project_id" => "required|exists:projects,id"
            ]);
            // log::info($request->project_id);

            $project = Project::with(['customer.customerPhoneNo'])->where('id', $request->project_id)->firstOrFail();

            if ($project) {
                $customer = $project->customer;
                if ($customer) {
                    $phonenumbers = $customer->customerPhoneNo->pluck('phone_no')->toArray() ?? [];
                    $customer = $customer->toArray();
                    unset($customer['customer_phone_no']);
                    return $this->success([
                        'customer' => $customer,
                        'phone_numbers' => $phonenumbers
                    ]);
                } else {
                    return $this->error('', 'No such customer', 404);
                }
            } else {
                return $this->error('', 'No such project', 404);
            }
        } catch (ValidationException $e) {
            return $this->error('', '', 401);
        } catch (Exception $e) {
            return $this->error('', '', 500);
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
                "onGrid",
                "offGridHybrid.battery",
                "solarPanel",
                "invertor"
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
            $solarPanels = $project->solarPanel->filter(function ($panel) {
                return $panel && $panel->is_current;
            })->map(function ($panel) {
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
                    "project" => $project,
                    "on_grid" => $ongrid,
                    "solar_panel" => $solarPanels,
                    "invertor" => $invertors
                ]);
            } elseif ($project->type == "offgrid") {
                $offgrid = $project->offGridHybrid;
                // log::info($offgrid);
                log::info($offgrid->battery);
                $battery = $offgrid?->battery;
                unset($project["onGrid"]);
                unset($project["offGridHybrid"]);
                unset($offgrid["battery"]);
                return $this->success([
                    "project" => $project,
                    "off_grid_hybrid" => $offgrid,
                    "solar_panel" => $solarPanels,
                    "invertor" => $invertors,
                    "battery" => $battery,
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
            // Validate the ID
            validator(['id' => $id], [
                'id' => 'required|exists:projects,id'
            ], [
                'id.required' => 'Project ID is required',
                'id.exists' => 'The specified project does not exist'
            ])->validate();

            // Find the project
            $project = Project::find($id);

            // Return success response with location data
            return $this->success([
                'lattitude' => $project->lattitude,
                'longitude' => $project->longitude,
            ]);
        } catch (ValidationException $e) {
            // Return validation error response
            return response()->json([
                'success' => false,
                'code' => 'validation_error',
                'error' => 'Invalid project ID',
                'details' => $e->errors()
            ], 404);
        } catch (Exception $e) {
            // Return server error response
            return response()->json([
                'success' => false,
                'code' => 'server_error',
                'error' => 'Server error',
                'details' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function openProject(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,user_id',
            'type' => 'required|in:on_grid,off_grid&hybrid',
            'project_name' => 'nullable|string',
            'project_address' => 'required|string',
            'nearest_town' => 'required|string',
            'no_of_panels' => 'required|integer|min:1',
            'system_capacity' => 'numeric|min:0.1',
            'service_years_in_agreement' => 'required|integer|min:1',
            'service_rounds_in_agreement' => 'required|integer|min:1',
            'project_no' => 'required|integer',
            // 'project_installation_date' => 'required|date',
            // 'longitude' => 'nullable|numeric',
            // 'lattitude' => 'nullable|numeric',
            // 'location' => 'nullable|string',
            // 'remarks' => 'nullable|string',
        ]);

        if($request->type== 'on_grid'){
            $project_type = 'ongrid';
        }else if($request->type == 'off_grid&hybrid'){
            $project_type = 'offgrid';
        }
        $result = $project = Project::create([
            'customer_id' => $request->customer_id,
            'type' => $project_type,
            'project_name' => $request->project_name,
            'project_address' => $request->project_address,
            'neatest_town' => $request->nearest_town,
            'no_of_panels' => $request->no_of_panels,
            'panel_capacity' => $request->system_capacity,
            'service_years_in_agreement' => $request->service_years_in_agreement,
            'service_rounds_in_agreement' => $request->service_rounds_in_agreement,
            // 'project_installation_date' => $request->project_installation_date,
            // 'system_on' => now(), // or set it explicitly
            // 'longitude' => $request->longitude,
            // 'lattitude' => $request->lattitude,
            // 'location' => $request->location,
            // 'remarks' => $request->remarks,
        ]);

        if(!$result) {
            return $this->error('', 'Failed to create project', 500);
        }

        switch ($project_type) {
            case 'ongrid':
                $on_grid = new OnGrid();
                $on_grid->project_id = $result->id;
                $on_grid->on_grid_project_id = $request->project_no;
                $ongrid_result =$on_grid->save();
                if(!$ongrid_result) {
                    return $this->error('', 'Failed to create OnGrid project', 500);
                }
                break;

            case 'offgrid':
                $off_grid = new OffGridHybrid();
                $off_grid->project_id = $result->id;
                $off_grid->off_grid_hybrid_project_id = $request->project_no;
                $off_grid_result = $off_grid->save();
                if(!$off_grid_result) {
                    return $this->error('', 'Failed to create OffGrid project', 500);
                }
                break;

            default:
                return $this->error('', 'Invalid project type', 400);
        }

        return $this->success([
            'message' => 'Project created successfully.',
            'project' => $project
        ]);
    }


    public function getAllProjects(Request $request)
    {
        try {

            $query = Project::with(['onGrid', 'offGridHybrid']);

            $type = Str::lower($request->input('type'));
            $searchTerm =  $request->input('query', '');


            // if (!empty($searchTerm)) {
            //     $query->where('project_name', 'like', '%' . $searchTerm . '%');
            // }
            if (!empty($searchTerm)) {
                $query->where(function ($q) use ($searchTerm) {
                    // Search in project_name and project_address on the projects table
                    $q->where('project_name', 'like', '%' . $searchTerm . '%')
                      ->orWhere('project_address', 'like', '%' . $searchTerm . '%');

                    // Search project_no in the related onGrid table
                    $q->orWhereHas('onGrid', function ($onGridQuery) use ($searchTerm) {
                        $onGridQuery->where('on_grid_project_id', 'like', '%' . $searchTerm . '%');
                    });

                    // Search project_no in the related offGridHybrid table
                    $q->orWhereHas('offGridHybrid', function ($offGridQuery) use ($searchTerm) {
                        $offGridQuery->where('off_grid_hybrid_project_id', 'like', '%' . $searchTerm . '%');
                    });
                });
            }

            if($type==''){
                $projects = $query->orderBy('created_at', 'asc')->paginate(6);

                
            }else{
                $projects = $query->where('type', $type)
                    ->orderBy('created_at', 'desc')
                    ->paginate(6);
            }

            
            return $this->success([
                'projects' => $projects
            ]);
        }catch (ValidationException $e) {
            return $this->error('', 'Validation Error', 404);
        }catch (Exception $e) {
            return $this->error('', 'Error occurred', 500);
        }
    }

    public function getProjectById(Request $request) {
        try{
            $request->validate([
                'id' => 'required|exists:projects,id'
            ]);

            
        }catch (ValidationException $e) {
            return $this->error('', 'No such project', 404);
        } catch (Exception $e) {
            return $this->error('', 'Error occurred', 500);
        }
    }

    //get total project count
    public function getProjectCount(){
        try{
            $count = Project::count();
            return $this->success([
                'project_count' => $count
            ]);
        }catch (Exception $e) {
            return $this->error('', 'Error occurred', 500);
        }
    }

    //get customer data for web
    public function getCustomerData(Request $request){
        try{

            $request->validate([
            'project_id' => 'required|exists:projects,id'
        ]);
            log::info($request->input('project_id'));
            $project = Project::with(['customer.customerPhoneNo'])->where('id', $request->input('project_id'))->first();

            $userEmail = User::where('id', $project->customer->user_id)->value('email');

            $customer = $project->customer;
            $phoneNumbers = $project->customer->customerPhoneNo->pluck('phone_no')->toArray() ?? [];
            unset($customer->customer_phone_no);
            // $customer = $customer->toArray();
            $customer->email = $userEmail;
            $responseData = [
                'customer' => $project->customer,
                'phone_numbers' => $phoneNumbers,
                
            ];

            return $this->success($responseData);

        }catch (ValidationException $e) {
            return $this->error('', 'Validation Error', 404);
        } catch (Exception $e) {
            return $this->error('', 'Error occurred', 500);
        }
    }

    //get project data for web
    public function getprojectData(Request $request){
        try{
            $request->validate([
                'project_id' => 'required|exists:projects,id'
            ]);

            $project = Project::with(['onGrid','OffGridHybrid'])->where('id', $request->input('project_id'))->first();

            if(!$project){
                return $this->error('', 'No such project', 404);
            }

            if($project->type == 'ongrid'){
                $onGrid = $project->onGrid;
                unset($project->onGrid);
                return $this->success([
                    'project' => $project,
                    'on_grid' => $onGrid
                ]);
            }else{
                $offGridHybrid = $project->offGridHybrid;
                unset($project->offGridHybrid);
                return $this->success([
                    'project' => $project,
                    'off_grid_hybrid' => $offGridHybrid
                ]);
            }

        }catch (ValidationException $e) {
            return $this->error('', 'No such project', 404);
        } catch (Exception $e) {
            return $this->error('', 'Error occurred', 500);
        }
    }
    
// Get project location and capacity(web)
public function getProjectLocationAndCapacity(Request $request)
{
    try {
        $request->validate([
            'project_id' => "required|exists:projects,id"
        ]);

        $project = Project::select(
            'longitude',
            'lattitude',
            'panel_capacity'
        )->where("id", $request->project_id)->first();

        if (!$project) {
            return $this->error("", "Project not found", 404);
        }

        return $this->success([
            "longitude" => $project->longitude,
            "latitude" => $project->lattitude,
            "system_capacity" => $project->panel_capacity
        ]);

    } catch (ValidationException $e) {
        return $this->error([], "Validation error", 400);
    } catch (Exception $e) {
        return $this->error([], "Error occurred", 500);
    }
}

    // Get project location and capacity
    public function getProjectLocationAndCapacityApi(Request $request)
    {
        try {
            $request->validate([
                'project_id' => "required|exists:projects,id"
            ]);

            $project = Project::select(
                'longitude',
                'lattitude',
                'panel_capacity'
            )->where("id", $request->project_id)->first();

            if (!$project) {
                return $this->error("", "Project not found", 404);
            }

            return $this->success([
                "longitude" => $project->longitude,
                "latitude" => $project->lattitude,
                "system_capacity" => $project->panel_capacity
            ]);

        } catch (ValidationException $e) {
            return $this->error([], "Validation error", 400);
        } catch (Exception $e) {
            return $this->error([], "Error occurred", 500);
        }
    }
}
