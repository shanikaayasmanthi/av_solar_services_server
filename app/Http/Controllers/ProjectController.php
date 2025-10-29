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
use Illuminate\Facades\DB;
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
            'project_no' => 'required|string',
            'project_installation_date' => 'nullable|date',
             'longitude' => 'nullable|numeric',
            'lattitude' => 'nullable|numeric',
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
             'project_installation_date' => $request->project_installation_date,
            // 'system_on' => now(), // or set it explicitly
             'longitude' => $request->longitude,
             'lattitude' => $request->lattitude,
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

            $query = Project::with(['onGrid', 'offGridHybrid'])
            ->where('External/Internal', 'Internal')
            ->where('isInstalled', 1);

            $type = Str::lower($request->input('type'));
            $searchTerm =  $request->input('query', '');
            $isHold = $request->input('is_hold');
           

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

            if(!is_null($isHold)){
                if($isHold){
                    $query->where('is_hold', (int)$isHold);
                }else {
                    $query->where('is_hold', 0);
                }
            }

            if($type==''){
                $projects = $query->orderBy('created_at', 'asc')->paginate(8);

                
            }else{
                $projects = $query->where('type', $type)
                    ->orderBy('created_at', 'desc')
                    ->paginate(8);
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

    public function getHoldProjectCount(){
        try{
            $count = Project::where('is_hold', true)->count();
            return $this->success([
                'hold_project_count' => $count
            ]);
        }catch (Exception $e) {
            return $this->error('', 'Error occurred', 500);
        }
    }

    //get customer data for web
public function getCustomerData(Request $request) {
    try {
        $request->validate([
            'project_id' => 'required|exists:projects,id'
        ]);

        $project = Project::with(['customer.customerPhoneNo', 'customer.user'])
            ->where('id', $request->input('project_id'))
            ->first();

        $customer = $project->customer;
        $phoneNumbers = $customer->customerPhoneNo->pluck('phone_no')->toArray() ?? [];

        $responseData = [
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'email' => $customer->user->email, // directly from relation
                'address' => $customer->address,
            ],
            'phone_numbers' => $phoneNumbers,
        ];

        return $this->success($responseData);

    } catch (ValidationException $e) {
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

            $project = Project::with(['onGrid','OffGridHybrid'])
            ->where('id', $request->input('project_id'))
            //->where('External/Internal', 'Internal') //filter internal only
            ->first();

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
   
// Get non-installed projects
public function getNonInstalledProjects(Request $request)
{
    try {
        
        $validated = $request->validate([
            'project_id' => 'nullable|exists:projects,id'
        ]);

        $query = Project::query()
            ->with(['customer', 'onGrid', 'offGridHybrid'])
            ->where('isInstalled', false);

        if ($request->has('project_id')) {
            $query->where('id', $request->project_id);
        }

        $projects = $query->get()->map(function ($project) {
            try {
                $offGridId = $project->offGridHybrid->off_grid_hybrid_project_id ?? null;
                $onGridId = $project->onGrid->on_grid_project_id ?? null;

                return [
                    'project_id' => $project->id,
                    'on_grid_project_id' => $onGridId,
                    'off_grid_hybrid_project_id' => $offGridId,
                    'project_no' => $project->type === 'offgrid' ? $offGridId : $onGridId, // ← This is the added line
                    'project_name' => $project->project_name,
                    'customer_name' => $project->customer->name ?? 'Unknown',
                    'nearest_town' => $project->neatest_town,
                    'type' => $project->type,
                    'capacity' => $project->panel_capacity,
                    'total_panels' => $project->no_of_panels
                ];
            } catch (\Exception $e) {
                \Log::error("Error processing project {$project->id}: " . $e->getMessage());
                return null;
            }
        })->filter(); 

        return response()->json([
            'status' => 'success',
            'data' => [
                'projects' => $projects->values()
            ]
        ]);

    } catch (\Exception $e) {
        \Log::error('API Error: ' . $e->getMessage());
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage(),
            'data' => ''
        ], 500);
    }
}

public function updateInstallationDetails(Request $request, $project_id)
{
    try {
        $validated = $request->validate([
            'longitude' => 'nullable|numeric',
            'latitude' => 'nullable|numeric',
            'installation_date' => 'nullable|date',
            'system_on_date' => 'nullable|date',
            'remarks' => 'nullable|string',
            'is_installed' => 'nullable|boolean',
        ]);

        $project = Project::findOrFail($project_id);

        $updateData = [
            'longitude' => $validated['longitude'] ?? $project->longitude,
            'lattitude' => $validated['latitude'] ?? $project->lattitude,
            'project_installation_date' => $validated['installation_date'] ?? $project->project_installation_date,
            'system_on' => $validated['system_on_date'] ?? $project->system_on,
            'remarks' => $validated['remarks'] ?? $project->remarks,
            'isInstalled' => $validated['is_installed'] ?? $project->isInstalled,
        ];

        $project->update($updateData);

        return response()->json([
            'status' => 'success',
            'message' => 'Installation details updated successfully',
            'data' => $project
        ]);

    } catch (ValidationException $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Validation error',
            'errors' => $e->errors()
        ], 422);
    } catch (Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Server error',
            'error' => $e->getMessage()
        ], 500);
    }
}

public function getPendingInstallationDetails($project_id)
{
    try {
       
        $project = Project::where('id', $project_id)
                        ->where('isInstalled', false)
                        ->firstOrFail();

        return response()->json([
            'status' => 'success',
            'data' => [
                'longitude' => $project->longitude,
                'latitude' => $project->lattitude, 
                'installation_date' => $project->project_installation_date,
                'system_on_date' => $project->system_on,
                'remarks' => $project->remarks,
                'is_installed' => $project->isInstalled,

            ]
        ]);

    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Project not found or already installed'
        ], 404);
    } catch (\Exception $e) {
        \Log::error("Failed to fetch installation details: " . $e->getMessage());
        return response()->json([
            'status' => 'error',
            'message' => 'Failed to fetch installation details'
        ], 500);
    }
}

public function getExternalProjects(Request $request)
{
    try {
        \Log::info('External projects request received', [
            'type' => $request->input('type'),
            'query' => $request->input('query'),
            'is_hold' => $request->input('is_hold')
        ]);

        $query = Project::with(['onGrid', 'offGridHybrid', 'customer'])
            ->where('External/Internal', 'External')
            ->where('isInstalled', 1);

        $type = Str::lower($request->input('type'));
        $searchTerm = $request->input('query', '');
        $isHold = $request->input('is_hold');

        // Search functionality
        if (!empty($searchTerm)) {
            \Log::info('Searching for: ' . $searchTerm);
            $query->where(function ($q) use ($searchTerm) {
                // Search in project fields
                $q->where('project_name', 'like', '%' . $searchTerm . '%')
                  ->orWhere('project_address', 'like', '%' . $searchTerm . '%')
                  ->orWhere('company_name', 'like', '%' . $searchTerm . '%')
                  ->orWhere('neatest_town', 'like', '%' . $searchTerm . '%');

                // Search project_no in the related onGrid table
                $q->orWhereHas('onGrid', function ($onGridQuery) use ($searchTerm) {
                    $onGridQuery->where('on_grid_project_id', 'like', '%' . $searchTerm . '%');
                });

                // Search project_no in the related offGridHybrid table
                $q->orWhereHas('offGridHybrid', function ($offGridQuery) use ($searchTerm) {
                    $offGridQuery->where('off_grid_hybrid_project_id', 'like', '%' . $searchTerm . '%');
                });

                // Search in customer relationship - FIXED: Remove email search since it's not in customers table
                $q->orWhereHas('customer', function ($customerQuery) use ($searchTerm) {
                    $customerQuery->where('name', 'like', '%' . $searchTerm . '%');
                    // Remove the email search since it's not in the customers table
                    // ->orWhere('email', 'like', '%' . $searchTerm . '%');
                });

                // If you need to search by email, you need to join with the users table
                $q->orWhereHas('customer.user', function ($userQuery) use ($searchTerm) {
                    $userQuery->where('email', 'like', '%' . $searchTerm . '%');
                });
            });
        }

        if (!is_null($isHold)) {
            $query->where('is_hold', (int) $isHold);
        }

        // Filter by type
        if ($type && in_array($type, ['ongrid', 'offgrid', 'hybrid'])) {
            $query->where('type', $type);
        }

        \Log::info('About to execute pagination query');
        $projects = $query->orderBy('created_at', 'desc')->paginate(8);
        \Log::info('Pagination query executed successfully');

        // Transform the response to include required fields
        \Log::info('Starting data transformation');
        $transformedProjects = $projects->getCollection()->map(function ($project) {
            $projectNo = null;
            
            if ($project->type === 'ongrid' && $project->onGrid) {
                $projectNo = $project->onGrid->on_grid_project_id;
            } elseif (($project->type === 'offgrid' || $project->type === 'hybrid') && $project->offGridHybrid) {
                $projectNo = $project->offGridHybrid->off_grid_hybrid_project_id;
            }

            return [
                'id' => $project->id,
                'project_no' => $projectNo,
                'project_name' => $project->project_name,
                'company_name' => $project->company_name,
                'nearest_project' => $project->neatest_town,
                'type' => $project->type,
                'is_hold' => $project->is_hold,
                'project_address' => $project->project_address,
                'installation_date' => $project->project_installation_date,
                'customer' => $project->customer ? [
                    'name' => $project->customer->name,
                    'email' => $project->customer->user->email // Get email from user relationship
                ] : null,
                'on_grid_details' => $project->onGrid,
                'off_grid_details' => $project->offGridHybrid
            ];
        });

        // Replace the original collection with transformed data
        $projects->setCollection($transformedProjects);
        \Log::info('Data transformation completed successfully');

        return $this->success([
            'projects' => $projects
        ]);

    } catch (ValidationException $e) {
        \Log::error('Validation error in getExternalProjects: ' . $e->getMessage());
        return $this->error('', 'Validation Error', 404);
    } catch (Exception $e) {
        \Log::error('Error in getExternalProjects: ' . $e->getMessage());
        \Log::error('Stack trace: ' . $e->getTraceAsString());
        return $this->error('', 'Error occurred: ' . $e->getMessage(), 500);
    }
}

    // Helper methods for success/error responses (assuming they exist in your base controller)
    protected function success($data, $message = 'Success', $code = 200)
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], $code);
    }

    protected function error($data, $message = 'Error', $code = 400)
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data' => $data
        ], $code);
    }

    public function openExternalProject(Request $request)
{
    $request->validate([
        'customer_id' => 'nullable|exists:customers,user_id', // external may not need customer
        'type' => 'required|in:on_grid,off_grid&hybrid',
        'project_name' => 'nullable|string',
        'project_address' => 'required|string',
        'nearest_town' => 'nullable|string',
        'no_of_panels' => 'nullable|integer|min:1',
        'system_capacity' => 'nullable|numeric|min:0.1',
        'service_years_in_agreement' => 'nullable|integer|min:1',
        'service_rounds_in_agreement' => 'nullable|integer|min:1',
        'project_no' => 'required|string',
        'longitude' => 'nullable|numeric',
        'lattitude' => 'nullable|numeric',
        'installation_completed' => 'boolean',
        'project_installation_date' => 'nullable|date',
        'system_turned_on' => 'boolean',
        'system_on_date' => 'nullable|date',
        
        // external-specific
        'company_name' => 'nullable|string',

    ]);

    if ($request->type == 'on_grid') {
        $project_type = 'ongrid';
    } else if ($request->type == 'off_grid&hybrid') {
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
        'longitude' => $request->longitude,
        'lattitude' => $request->lattitude,

        // External/Internal column
        'External/Internal' => $request->company_name ? 'External' : 'Internal',

        // Company name
        'company_name' => $request->company_name,

        // Installation Completed → isInstalled
        'isInstalled' => $request->installation_completed ?? false,
        'project_installation_date' => $request->installation_completed ? $request->project_installation_date : null,

        // System On mapping
        'system_on' => $request->system_turned_on ? $request->system_on_date : null,
    ]);

    if (!$result) {
        return $this->error('', 'Failed to create project', 500);
    }

    switch ($project_type) {
        case 'ongrid':
            $on_grid = new OnGrid();
            $on_grid->project_id = $result->id;
            $on_grid->on_grid_project_id = $request->project_no;
            $ongrid_result = $on_grid->save();
            if (!$ongrid_result) {
                return $this->error('', 'Failed to create OnGrid project', 500);
            }
            break;

        case 'offgrid':
            $off_grid = new OffGridHybrid();
            $off_grid->project_id = $result->id;
            $off_grid->off_grid_hybrid_project_id = $request->project_no;
            $off_grid_result = $off_grid->save();
            if (!$off_grid_result) {
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

public function updateProjectData(Request $request)
{
    try {
        $request->validate([
            'project_id' => 'required|exists:projects,id',
            'project' => 'nullable|array',
            'on_grid' => 'nullable|array',
            'off_grid_hybrid' => 'nullable|array',
        ]);

        $project = Project::findOrFail($request->input('project_id'));
        
        // Update project data if provided
        if ($request->has('project')) {
            $projectData = $request->input('project');
            
            $project->update([
                'project_installation_date' => $projectData['project_installation_date'] ?? null,
                'system_on' => $projectData['system_on'] ?? null,
                'remarks' => $projectData['remarks'] ?? null,
                'service_rounds_in_agreement' => $projectData['service_rounds_in_agreement'] ?? 0,
                'service_years_in_agreement' => $projectData['service_years_in_agreement'] ?? 0,
                'project_address' => $projectData['project_address'] ?? null,
                'longitude' => $projectData['longitude'] ?? null,
                'lattitude' => $projectData['lattitude'] ?? null,
                'neatest_town' => $projectData['neatest_town'] ?? null,
                'no_of_panels' => $projectData['no_of_panels'] ?? null,
            ]);
        }

        // Update on_grid data if provided
        if ($request->has('on_grid') && $project->type == 'ongrid') {
            $onGridData = $request->input('on_grid');
            
            if ($project->onGrid) {
                $project->onGrid->update([
                    'electricity_bill_name' => $onGridData['electricity_bill_name'] ?? null,
                    'harmonic_meter' => $onGridData['harmonic_meter'] ?? null,
                    'wifi_username' => $onGridData['wifi_username'] ?? null,
                    'wifi_password' => $onGridData['wifi_password'] ?? null,
                    'remarks' => $onGridData['remarks'] ?? null,
                ]);
            }
        }

        // Update off_grid_hybrid data if provided
        if ($request->has('off_grid_hybrid') && ($project->type == 'offgrid' || $project->type == 'hybrid')) {
            $offGridData = $request->input('off_grid_hybrid');
            
            if ($project->offGridHybrid) {
                $project->offGridHybrid->update([
                    'connection_type' => $offGridData['connection_type'] ?? null,
                    'remarks' => $offGridData['remarks'] ?? null,
                    'wifi_username' => $offGridData['wifi_username'] ?? null,
                    'wifi_password' => $offGridData['wifi_password'] ?? null,
                ]);
            }
        }

        // Reload the updated relationships
        $project->load('onGrid', 'offGridHybrid');

        return $this->success([
            'project' => $project,
            'on_grid' => $project->onGrid,
            'off_grid_hybrid' => $project->offGridHybrid
        ], 'Project updated successfully');

    } catch (ValidationException $e) {
        return $this->error($e->errors(), 'Validation failed', 422);
    } catch (Exception $e) {
        return $this->error($e->getMessage(), 'Error occurred', 500);
    }
}


public function holdProject($id, Request $request) {
    $project = Project::findOrFail($id); 
    
    // Validate the request data
    $validated = $request->validate([
        'remarks' => 'nullable|string|max:500'
    ]);
    
    $project->is_hold = 1;
    $project->remarks = $validated['remarks'] ?? null; // Save the remarks
    $project->save();

    return $this->success(['message' => 'Project put on hold']);
}

public function releaseProject($id, Request $request) {
    $project = Project::findOrFail($id);
    
    // Validate the request data
    $validated = $request->validate([
        'remarks' => 'nullable|string|max:500'
    ]);
    
    $project->is_hold = 0;
    $project->remarks = $validated['remarks'] ?? null; // Save the remarks
    $project->save();

    return $this->success(['message' => 'Project released']);
}

// For Internal Hold Projects
public function getHoldProjects(Request $request)
{
    try {
        $query = Project::with(['onGrid', 'offGridHybrid'])
            ->where('External/Internal', 'Internal')
            ->where('is_hold', 1);

        $type = Str::lower($request->input('type'));
        $searchTerm = $request->input('query', '');

        if (!empty($searchTerm)) {
            $query->where(function ($q) use ($searchTerm) {
                $q->where('project_name', 'like', '%' . $searchTerm . '%')
                  ->orWhere('project_address', 'like', '%' . $searchTerm . '%')
                  ->orWhereHas('onGrid', fn($onGrid) => 
                      $onGrid->where('on_grid_project_id', 'like', '%' . $searchTerm . '%'))
                  ->orWhereHas('offGridHybrid', fn($offGrid) => 
                      $offGrid->where('off_grid_hybrid_project_id', 'like', '%' . $searchTerm . '%'));
            });
        }

        if ($type && in_array($type, ['ongrid', 'offgrid', 'hybrid'])) {
            $query->where('type', $type);
        }

        $projects = $query->orderBy('created_at', 'desc')->paginate(8);

        return $this->success([
            'projects' => $projects
        ]);
    } catch (Exception $e) {
        return $this->error('', 'Error occurred: ' . $e->getMessage(), 500);
    }
}


// For External Hold Projects
public function getHoldExternalProjects(Request $request)
{
    try {
        $query = Project::with(['onGrid', 'offGridHybrid', 'customer'])
            ->where('External/Internal', 'External')
            ->where('is_hold', 1);

        $type = Str::lower($request->input('type'));
        $searchTerm = $request->input('query', '');

        if (!empty($searchTerm)) {
            $query->where(function ($q) use ($searchTerm) {
                $q->where('project_name', 'like', '%' . $searchTerm . '%')
                  ->orWhere('project_address', 'like', '%' . $searchTerm . '%')
                  ->orWhere('company_name', 'like', '%' . $searchTerm . '%')
                  ->orWhere('neatest_town', 'like', '%' . $searchTerm . '%')
                  ->orWhereHas('onGrid', fn($onGrid) => 
                      $onGrid->where('on_grid_project_id', 'like', '%' . $searchTerm . '%'))
                  ->orWhereHas('offGridHybrid', fn($offGrid) => 
                      $offGrid->where('off_grid_hybrid_project_id', 'like', '%' . $searchTerm . '%'))
                  ->orWhereHas('customer', fn($customer) => 
                      $customer->where('name', 'like', '%' . $searchTerm . '%')
                               ->orWhere('email', 'like', '%' . $searchTerm . '%'));
            });
        }

        if ($type && in_array($type, ['ongrid', 'offgrid', 'hybrid'])) {
            $query->where('type', $type);
        }

        $projects = $query->orderBy('created_at', 'desc')->paginate(8);

        // transform for external (keep same format)
        $transformed = $projects->getCollection()->map(function ($project) {
            $projectNo = null;
            if ($project->type === 'ongrid' && $project->onGrid) {
                $projectNo = $project->onGrid->on_grid_project_id;
            } elseif (($project->type === 'offgrid' || $project->type === 'hybrid') && $project->offGridHybrid) {
                $projectNo = $project->offGridHybrid->off_grid_hybrid_project_id;
            }

            return [
                'id' => $project->id,
                'project_no' => $projectNo,
                'project_name' => $project->project_name,
                'company_name' => $project->company_name,
                'nearest_project' => $project->neatest_town,
                'type' => $project->type,
                'is_hold' => $project->is_hold,
                'project_address' => $project->project_address,
                'installation_date' => $project->project_installation_date,
                'customer' => $project->customer ? [
                    'name' => $project->customer->name,
                    'email' => $project->customer->email
                ] : null,
                'on_grid_details' => $project->onGrid,
                'off_grid_details' => $project->offGridHybrid
            ];
        });

        $projects->setCollection($transformed);

        return $this->success([
            'projects' => $projects
        ]);
    } catch (Exception $e) {
        return $this->error('', 'Error occurred: ' . $e->getMessage(), 500);
    }
}

}
