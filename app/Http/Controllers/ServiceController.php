<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Service;
use App\Models\ServiceTechniciant;
use App\Model\RoofWork;
use App\Models\OutdoorWork;
use App\Models\MainPanelWork;
use App\Traits\HttpResponses;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;

use function PHPSTORM_META\map;
use function PHPUnit\Framework\isEmpty;


class ServiceController extends Controller
{
    use HttpResponses;

    // Get all scheduled services that are not yet completed 

    public function getAllScheduledServices(Request $request)
    {
        try {

            $request->validate([]);

            $perPage = 8;
            $paginated = Service::with([
                'project.onGrid',
                'project.offGridHybrid',
                'project.customer.customerPhoneNo'
            ])
                ->where('service_done', false)
                ->paginate($perPage);

           $transformed = $paginated->getCollection()
    ->map(function ($service) {
        $project = $service->project;
        if (!$project) return null;

        $project_no = null;
        if ($project->type == 'ongrid') {
            $project_no = $project->onGrid->on_grid_project_id ?? null;
        } else if ($project->type == 'offgrid') {
            $project_no = $project->offGridHybrid->off_grid_hybrid_project_id ?? null;
        }

        $supervisor_name = null;
        if ($service->supervisor_id) {
            $supervisor = \App\Models\User::find($service->supervisor_id);
            $supervisor_name = $supervisor ? $supervisor->name : null;
        }

        $customer_name = $project->customer ? $project->customer->name : null;

        // Decide Free or Paid safely
        $serviceType = null;
        if ($service->service_round_no && $project->service_rounds_in_agreement) {
            $serviceType = $service->service_round_no <= $project->service_rounds_in_agreement
                ? 'Free'
                : 'Paid';
        }

        return [
            'project_no' => $project_no,
            'customer_name' => $customer_name,
            'service_round' => $service->service_round_no ?? null,
            'service_type' => $serviceType,
            'service_date' => $service->service_date 
                ? \Carbon\Carbon::parse($service->service_date)->format('Y-m-d')
                : null,
            'service_time' => $service->service_time ?? null,
            'supervisors' => $supervisor_name ? [$supervisor_name] : [],
        ];
    })
    ->filter()
    ->values();

            $paginated->setCollection($transformed);

            return $this->success(['services' => $paginated]);


            return $this->success(['services' => $services]);
        } catch (ValidationException $e) {
            return $this->error('', 'Unauthorized access', 401);
        } catch (Exception $e) {
            return $this->error('', $e->getMessage(), 500);
        }
    }

    //get all projects atleast one service is done
    public function getProjectsWithCompletedServices(Request $request)
    {
        try {

            $projectIds = Service::where('service_done', true)
                ->pluck('project_id')
                ->unique()
                ->toArray();

            $paginatedProjects = \App\Models\Project::with(['customer', 'onGrid', 'offGridHybrid'])
                ->whereIn('id', $projectIds)
                ->paginate(8);

            $projects = $paginatedProjects->getCollection()
                ->map(function ($project) {
                    $project_no = null;

                    if ($project->type == 'ongrid' && $project->onGrid) {
                        $project_no = $project->onGrid->on_grid_project_id;
                    } elseif ($project->type == 'offgrid' && $project->offGridHybrid) {
                        $project_no = $project->offGridHybrid->off_grid_hybrid_project_id;
                    }

                    return [
                        'project_id' => $project->id,
                        'project_no' => $project_no,
                        'customer_name' => $project->customer->name ?? null,
                        'nearest_town' => $project->neatest_town ?? null,
                        'project_name' => $project->project_name ?? null,
                    ];
                })
                ->filter()
                ->values();

            // Replace the collection with the transformed one
            $paginatedProjects->setCollection($projects);

            return $this->success([
                'projects' => $paginatedProjects
            ]);
        } catch (ValidationException $e) {
            return $this->error('', 'Unauthorized access', 401);
        } catch (Exception $e) {
            return $this->error('', $e->getMessage(), 500);
        }
    }

    //get all completed service rounds by project id
    public function getCompletedServiceRoundsByProjectId(Request $request)
    {
        try {
            $request->validate([
                'project_id' => 'required|exists:projects,id',
            ]);

            $project = Project::with('customer')->findOrFail($request->project_id);

            $services = Service::where('project_id', $request->project_id)
                ->where('service_done', true)
                ->orderBy('service_date', 'asc')
                ->get()
                ->map(function ($service) use ($project) {
                // Decide if free or paid
                $isFree = $service->service_round_no <= $project->service_rounds_in_agreement;
                $serviceType = $isFree ? 'Free' : 'Paid';
                    return [
                        'service_id' => $service->id,
                        'project_id' => $service->project_id,
                        'project_no' => $service->project->type == 'ongrid' ? $service->project->onGrid->on_grid_project_id : ($service->project->type == 'offgrid' ? $service->project->offGridHybrid->off_grid_hybrid_project_id : null),
                        'customer_name' => $service->project->customer->name ?? null,
                        'nearest_town' => $service->project->neatest_town ?? null,
                        'service_round' => $service->service_round_no,
                        'service_type' => $serviceType,
                        'service_date' => $service->service_date,
                        'service_time' => $service->service_time,
                        'remarks' => $service->remarks,
                        'service_type' => $service->service_type,
                        'supervisor_name' => $service->supervisor ? $service->supervisor->name : null,
                        'power' => $service->power,
                        'power_time' => $service->power_time,
                    ];
                });

            return $this->success([
                'project_id' => $request->project_id,
                'services' => $services,
            ]);
        } catch (ValidationException $e) {
            return $this->error('', $e->getMessage(), 401);
        } catch (Exception $e) {
            return $this->error('', $e->getMessage(), 500);
        }
    }

    //get all services for allocated to relevent supervisor
    public function getSupervisorAllServices(Request $request)
    {
        try {
            $request->validate([
                'user_id' => 'required|exists:supervisors,user_id'
            ]);

            $services = Service::with(['project.onGrid', 'project.offGridHybrid', 'project.customer.customerPhoneNo'])
                ->where('supervisor_id', $request->user_id)
                ->where('service_done', false)
                ->get()
                ->map(function ($service) {
                    $project = $service->project;
                    // log::info($project->customer);
                    $phoneNumbers = $project->customer->customerPhoneNo->pluck('phone_no')->toArray() ?? [];
                    // log::info($project);            
                    // log::info($phoneNumbers);    // $project_no = null;
                    if ($project->type == 'ongrid') {
                        $project_no = $project->onGrid->on_grid_project_id;
                        // log::info($project_no);
                    } else if ($project->type == 'offgrid') {
                        $project_no = $project->offGridHybrid->off_grid_hybrid_project_id;
                        // log::info($project_no);
                    }
                    return [
                        'service_id' => $service->id,
                        'project_id' => $service->project_id ?? null,
                        'project_no' => $project_no ?? null,
                        'project_name' => $project->project_name ?? null,
                        'project_address' => $project->project_address ?? null,
                        'customer_name' => $project->customer->name ?? null,
                        'phone' => $phoneNumbers,
                        'service_round' => $service->service_round_no ?? null,
                        'service_type' => $service->service_type ?? null,
                        'service_date' => $service->service_date ?? null,
                        'service_time' => $service->service_time ?? null,
                        // 'longitude'=>$project->longitude??null,
                        // 'lattitude'=>$project->lattitude??null,
                        // 'location'=>$project->location??null,
                    ];
                });
            // log::info($services);
            return $this->success(['Services' => $services]);
        } catch (ValidationException $e) {
            return $this->error('', 'Unauthorized access', 401);
        } catch (Exception $e) {
            return $this->error('', $e, 500);
        }
    }

    //set time for a service allocated to a supervisor
    public function setServiceTime(Request $request)
    {

        try {
            $request->validate([
                'user_id' => 'required|exists:supervisors,user_id',
                'service_id' => 'required|exists:services,id',
                'project_id' => 'required|exists:projects,id',
                'time' => 'required',
            ]);

            $service = Service::where('id', $request->service_id)->where('supervisor_id', $request->user_id)->where('project_id', $request->project_id)->first();
            log::info($service);
            if ($service) {
                $result = Service::where('id', $request->service_id)->update(['service_time' => $request->time]);
                return $this->success([
                    'result' => $result,
                ], 'Success');
            } else {
                return $this->error('', 'Error Occurred', 500);
            }
        } catch (ValidationException $e) {
            return $this->error('', 'Unauthorized access', 401);
        } catch (Exception $e) {
            return $this->error('', $e, 500);
        }
    }

    //get project id and offgrid or ongrid no from service id
    public function getProjectNo(Request $request)
    {
        try {
            $request->validate([
                'user_id' => 'required|exists:supervisors,user_id',
                'service_id' => 'required|exists:services,id'
            ]);
            // log::info($request);

            $service = Service::with(["project.onGrid", "project.offGridHybrid"])->findOrFail($request->service_id);
            // log::info($service);
            $project = $service->project;
            $project_no = null;
            if ($project->type == "ongrid" && $project->onGrid) {
                $project_no = $project->onGrid->on_grid_project_id;
            } elseif ($project->type == "offgrid" && $project->offGridHybrid) {
                $project_no = $project->offGridHybrid->off_grid_hybrid_project_id;
            }

            return $this->success([
                "project_id" => $project->id,
                "project_no" => $project_no,
                "project_name" => $project->project_name
            ]);
        } catch (ValidationException $e) {
            return $this->error('', $e, 401);
        } catch (Exception $e) {
            return $this->error('', $e, 500);
        }
    }

    // In ServiceController.php
    public function getCompletedServicesByProject(Request $request)
    {
        try {
            $request->validate([
                'project_id' => 'required|exists:projects,id'
            ]);

            $services = Service::with(['outdoorWork', 'roofWork', 'mainPanelWork', 'dc', 'ac'])
                ->where('project_id', $request->project_id)
                ->where('service_done', true)
                ->orderBy('service_date', 'desc')
                ->get()
                ->map(function ($service) {
                    return [
                        'service_round' => $service->service_round_no,
                        'service_type' => $service->service_type,
                        'service_date' => $service->service_date,
                        'service_time' => $service->service_time,
                        'remarks' => $service->remarks,
                        'outdoor_work' => $service->outdoorWork,
                        'roof_work' => $service->roofWork,
                        'main_panel_work' => $service->mainPanelWork,
                        'dc_work' => $service->dc,
                        'ac_work' => $service->ac,
                        'is_paid' => $service->service_type === 'paid', // Determine if paid service
                    ];
                });

            return $this->success(['services' => $services]);
        } catch (Exception $e) {
            return $this->error('', $e->getMessage(), 500);
        }
    }

    //save service data
    public function saveServiceDetails(Request $request)
    {
        try {
            $request->validate([
                'user_id' => 'required',
                'service_id' => 'required|exists:services,id',
                'service_data' => 'required|json'
            ]);

            $serviceData = json_decode($request->service_data);

            $service = Service::findOrFail($request->service_id);

            if ($service->supervisor_id != $request->user_id) {
                return $this->error('', 'Unauthorized', 401);
            }

            //Wrap everything inside a transaction:
            DB::beginTransaction();

            // Update Service mainData
            $mainData = $serviceData->mainData?? null;
        if ($mainData) {
            $time = !empty($mainData->time) 
                ? Carbon::today()->setTimeFromTimeString($mainData->time)->toDateTimeString() 
                : Carbon::now()->toDateTimeString();

            $service->update([
                'power' => isset($mainData->power) ? (float)$mainData->power : $service->power,
                'power_time' => $time,
                'wifi_connectivity' => !empty($mainData->wifiConnectivity),
                'capture_last_bill' => !empty($mainData->electricityBill),
            ]);

            if ($service->project) {
                $projectUpdates = [];
                
                if (isset($mainData->longitude) && !empty(trim($mainData->longitude))) {
                    $projectUpdates['longitude'] = (double)$mainData->longitude;
                }
                
                if (isset($mainData->latitude) && !empty(trim($mainData->latitude))) {
                    $projectUpdates['lattitude'] = (double)$mainData->latitude;
                }
                
                // Only update if there are changes to make
                if (!empty($projectUpdates)) {
                    $service->project->update($projectUpdates);
                }
            }
        }

        // Save DC
        if (isset($serviceData->dc)) {
            $dcController = new DCController();
            if (!$dcController->saveServiceDCData($request->service_id, $serviceData->dc)) {
                DB::rollBack();
                return $this->error('', 'Failed saving DC', 500);
            }
        }

        // Save AC
        if (isset($serviceData->ac)) {
            $acController = new ACController();
            if (!$acController->saveServiceACData($request->service_id, $serviceData->ac)) {
                DB::rollBack();
                return $this->error('', 'Failed saving AC', 500);
            }
        }

        // Save RoofWork
        if (isset($serviceData->roof_work)) {
            $roofWorkController = new RoofWorkController();
            if (!$roofWorkController->saveServiceRoofWorkData($request->service_id, $serviceData->roof_work)) {
                DB::rollBack();
                return $this->error('', 'Failed saving RoofWork', 500);
            }
        }

        // Save OutdoorWork
        if (isset($serviceData->outdoor_work)) {
            $outDoorWorkController = new OutdoorWorkController();
            if (!$outDoorWorkController->saveServiceOutDoorWork($request->service_id, $serviceData->outdoor_work)) {
                DB::rollBack();
                return $this->error('', 'Failed saving OutdoorWork', 500);
            }
        }

        // Save MainPanelWork
        if (isset($serviceData->main_panel_work)) {
            $mainPanelWorkController = new MainPanelWorkController();
            if (!$mainPanelWorkController->saveServiceMainPanelWork($request->service_id, $serviceData->main_panel_work)) {
                DB::rollBack();
                return $this->error('', 'Failed saving MainPanelWork', 500);
            }
        }

        // Save Technicians
        if (!empty($serviceData->technicians)) {
            $technicianController = new ServiceTechniciantController();
            if (!$technicianController->saveServiceTechnicians($request->service_id, $serviceData->technicians)) {
                DB::rollBack();
                return $this->error('', 'Failed saving Technicians', 500);
            }
        }

        $service->update(['service_done' => true]);

        DB::commit();
        return $this->success('', 'Service saved successfully');
    } catch (ValidationException $e) {
        return $this->error('', $e->errors(), 422);
    } catch (Exception $e) {
        DB::rollBack();
        return $this->error('', $e->getMessage(), 500);
    }
}

    public function getTechniciansByServiceId(Request $request)
    {
        try {
            $request->validate([
                'service_id' => 'required|exists:services,id'
            ]);

            $technicians = ServiceTechniciant::where('service_id', $request->service_id)
                ->pluck('techniciant_name');

            return $this->success([
                'service_id' => $request->service_id,
                'technicians' => $technicians
            ], 'Technicians fetched successfully');
        } catch (ValidationException $e) {
            return $this->error('', 'Invalid request', 422);
        } catch (Exception $e) {
            return $this->error('', $e->getMessage(), 500);
        }
    }


    //get forst and secons service done counts
    public function getServiceCounts()
    {
        try {

            $firstServiceCount = Service::where('service_round_no', 1)->where('service_done', 1)->where('service_type', 'free')->count();
            $secondServiceCount = Service::where('service_round_no', 2)->where('service_done', 1)->where('service_type', 'free')->count();

            return $this->success([
                'first_service_count' => $firstServiceCount,
                'second_service_count' => $secondServiceCount
            ]);
        } catch (ValidationException $e) {
            return $this->error('', 'Validation Error', 422);
        } catch (Exception $e) {
            return $this->error('', 'Error occurred', 500);
        }
    }

    public function getServicesSummery(Request $request)
    {

        try {
            $request->validate([
                'project_id' => 'required|exists:projects,id'
            ]);
            $services = Service::where('project_id', $request->project_id)
                ->where('service_done', true)
                ->get()
                ->map(function ($service) {
                    return [
                        'service_id' => $service->id,
                        'service_round' => $service->service_round_no,
                        'service_type' => $service->service_type,
                        'service_date' => $service->service_date,
                        'service_time' => $service->service_time,
                        'remarks' => $service->remarks,
                    ];
                });

            return $this->success([
                'services' => $services
            ]);
        } catch (ValidationException $e) {
            return $this->error('', 'Validation Error', 422);
        } catch (Exception $e) {
            return $this->error('', 'Error occurred', 500);
        }
    }

    //get next service round for shecule service
    public function getNextServiceRound(Request $request)
    {
        try {

            $request->validate([
                'project_id' => 'required|exists:projects,id'
            ]);

            $projectFreeServiceRounds = Project::where('id', $request->project_id)
                ->value('service_rounds_in_agreement');

            $serviceCount = Service::where('project_id', $request->project_id)
                ->count();

            $lastService = Service::where('project_id', $request->project_id)
                ->orderBy('service_date', 'desc')
                ->first();

                if($lastService) {
                    if ($lastService->service_done == false) {
                return $this->error('', 'Already have a service to complete', 409);
                }
            }
            if (!$serviceCount) {
                $nextServiceRound = 1;
                $serviceType = 'free';
            }else if($serviceCount== $projectFreeServiceRounds){
                $nextServiceRound = 1;
                $serviceType = 'paid';
            }else if($serviceCount < $projectFreeServiceRounds){
                $nextServiceRound = $serviceCount + 1;
                $serviceType = 'free';
            } else {
                $nextServiceRound = $serviceCount - $projectFreeServiceRounds + 1;
                $serviceType = 'paid';
            }
            // if (!$lastService) {
            //     // return $this->error('', 'No completed services found for this project', 404);
            //     $nextServiceRound = 1;
            //     $serviceType = 'free'; 
            // }
            
            // $nextServiceRound = $lastService->service_round_no + 1;

            return $this->success([
                'next_service_round' => $nextServiceRound,
                'service_type' => $serviceType,
            ]);
        } catch (ValidationException $e) {
            return $this->error('', 'Validation Error', 422);
        } catch (Exception $e) {
            return $this->error('', 'Error occurred', 500);
        }
    }

    public function scheduleNextService(Request $request)
    {
        try {
            $request->validate([
                'project_id' => 'required|exists:projects,id',
                'service_round' => 'required|integer|min:1',
                'service_type' => 'required|in:free,paid',
                'service_date' => 'required|date_format:Y-m-d',
                'supervisor_id' => 'required|exists:users,id',
            ]);

            $serviceExists = Service::where('project_id', $request->project_id)
                ->where('service_round_no', $request->service_round)
                ->exists();
            if ($serviceExists) {
                return $this->error('', 'Service for this round already exists', 409);
            }

            $lastService = Service::where('project_id', $request->project_id)
                ->where('service_done', false)
                ->orderBy('service_date', 'desc')
                ->first();

            if ($lastService) {
                return $this->error('', 'Service have to complete', 409);
            }


            $carbonDate = Carbon::parse($request->service_date);
            if ($carbonDate == false) {
                return $this->error('', 'Invalid date format', 422);
            }
            $service = new Service();
            $service->project_id = $request->project_id;
            $service->service_round_no = $request->service_round;
            $service->service_date = $carbonDate->toDateTimeString();
            $service->supervisor_id = $request->supervisor_id;
            $service->service_type = $request->service_type;
            $service->save();

            return $this->success('', 'Service scheduled successfully');
        } catch (ValidationException $e) {
            return $this->error('', 'Validation Error', 422);
        } catch (Exception $e) {
            return $this->error('', 'Failed to schedule service', 500);
        }
    }

public function getTodayServiceSummary(Request $request)
{

    try {
        $request->validate([
            'supervisor_id' => 'required|exists:supervisors,user_id'
        ]);

        $today = Carbon::today()->toDateString();

        // Get all services done today by this supervisor with project and invertor data
        $services = Service::with(['project.invertor'])
            ->where('supervisor_id', $request->supervisor_id)
            ->where('service_done', true)
            ->whereDate('service_date', $today)
            ->get();

        // Debug: Log all service types for inspection
        \Log::info('Service Types:', $services->pluck('service_type')->toArray());

        // Calculate summary values with strict type checking
        $totalServices = $services->count();
        
        $freeServices = $services->filter(function ($service) {
            return !empty($service->service_type) && 
                   strtolower(trim($service->service_type)) === 'free';
        })->count();
        
        $paidServices = $services->filter(function ($service) {
            return !empty($service->service_type) && 
                   strtolower(trim($service->service_type)) === 'paid';
        })->count();
        
        // Verify counts match expected total
        if (($freeServices + $paidServices) != $totalServices) {
            \Log::warning('Service type mismatch', [
                'total' => $totalServices,
                'free' => $freeServices,
                'paid' => $paidServices,
                'unclassified' => $totalServices - ($freeServices + $paidServices)
            ]);
        }

        // Calculate total capacity
        $totalCapacity = $services->sum(function ($service) {
            $project = $service->project;
            
            if ($project->no_of_panels && $project->panel_capacity) {
                return $project->panel_capacity * $project->no_of_panels;
            }
            
            if ($project->invertor->isNotEmpty()) {
                return $project->invertor->sum('invertor_capacity');
            }
            
            return 0;
        });

        return $this->success([
            'total_services' => $totalServices,
            'free_services' => $freeServices,
            'paid_services' => $paidServices,
            'total_capacity' => $totalCapacity . 'kw',
            'unclassified_services' => $totalServices - ($freeServices + $paidServices) // For debugging
        ], "Today's service summary fetched successfully");

    } catch (ValidationException $e) {
        return $this->error('', $e->getMessage(), 422);
    } catch (Exception $e) {
        return $this->error('', $e->getMessage(), 500);
    }
}


 // Get today's completed services for a supervisor with edit access
 
public function getTodayCompletedServices(Request $request)
{
    try {
        $request->validate([
            'supervisor_id' => 'required|exists:supervisors,user_id'
        ]);

        $today = Carbon::today()->toDateString();

        $services = Service::with([
                'project.onGrid',
                'project.offGridHybrid',
                'project.invertor'
            ])
            ->where('supervisor_id', $request->supervisor_id)
            ->where('service_done', true)
            ->whereDate('service_date', $today)
            ->get()
            ->map(function ($service) {
                $project = $service->project;
                
                // Get project number based on type
                $projectNo = null;
                if ($project->type == 'ongrid' && $project->onGrid) {
                    $projectNo = $project->onGrid->on_grid_project_id;
                } elseif ($project->type == 'offgrid' && $project->offGridHybrid) {
                    $projectNo = $project->offGridHybrid->off_grid_hybrid_project_id;
                }

                // Calculate capacity
                $capacity = 0;
                if ($project->no_of_panels && $project->panel_capacity) {
                    $capacity = $project->panel_capacity * $project->no_of_panels;
                } elseif ($project->invertor->isNotEmpty()) {
                    $capacity = $project->invertor->sum('invertor_capacity');
                }

                return [
                    'service_id' => $service->id,
                    'service_no' => $service->service_round_no,
                    'project_id' => $project->id,
                    'project_no' => $projectNo,
                    'project_name' => $project->project_name,
                    'service_type' => $service->service_type,
                    'capacity' => $capacity . 'kw',
                    'service_date' => $service->service_date,
                    'service_time' => $service->service_time
                ];
            });

        return $this->success([
            'services' => $services
        ], "Today's completed services fetched successfully");

    } catch (ValidationException $e) {
        return $this->error('', $e->getMessage(), 422);
    } catch (Exception $e) {
        return $this->error('', $e->getMessage(), 500);
    }
}

//get service details for edit

public function getServiceDetailsForEdit(Request $request)
{
    try {
        $request->validate([
            'service_id' => 'required|exists:services,id'
        ]);

        $service = Service::with([
            'project.onGrid',
            'project.offGridHybrid',
            'project.customer',
            'project.invertor',
            'outdoorWork',
            'roofWork',
            'mainPanelWork',
            'dc',
            'ac',
            'serviceTechniciant' 
        ])->findOrFail($request->service_id);

        // Get project number based on type
        $projectNo = null;
        if ($service->project->type == 'ongrid' && $service->project->onGrid) {
            $projectNo = $service->project->onGrid->on_grid_project_id;
        } elseif ($service->project->type == 'offgrid' && $service->project->offGridHybrid) {
            $projectNo = $service->project->offGridHybrid->off_grid_hybrid_project_id;
        }

        $response = [
            'mainData' => [
                'longitude' =>  $service->project->longitude ?? null, 
                'latitude' =>  $service->project->lattitude ?? null,  
                'power' => $service->power,
                'time' => $service->power_time 
                 ? \Carbon\Carbon::parse($service->power_time)->format('H:i:s') : null,
                'wifiConnectivity' => $service->wifi_connectivity,
                'electricityBill' => $service->capture_last_bill,
            ],
            'dc' => $service->dc ? [
                'OCVoltage' => [
                    $service->dc->OC_valtage_string_1,
                    $service->dc->OC_valtage_string_2,
                    $service->dc->OC_valtage_string_3,
                    $service->dc->OC_valtage_string_4,
                    $service->dc->OC_valtage_string_5,
                    $service->dc->OC_valtage_string_6,
                    $service->dc->OC_valtage_string_7,
                    $service->dc->OC_valtage_string_8,
                ],
                'LoadVoltage' => [
                    $service->dc->load_valtage_string_1,
                    $service->dc->load_valtage_string_2,
                    $service->dc->load_valtage_string_3,
                    $service->dc->load_valtage_string_4,
                    $service->dc->load_valtage_string_5,
                    $service->dc->load_valtage_string_6,
                    $service->dc->load_valtage_string_7,
                    $service->dc->load_valtage_string_8,
                ],
                'LoadCurrent' => [
                    $service->dc->load_current_string_1,
                    $service->dc->load_current_string_2,
                    $service->dc->load_current_string_3,
                    $service->dc->load_current_string_4,
                    $service->dc->load_current_string_5,
                    $service->dc->load_current_string_6,
                    $service->dc->load_current_string_7,
                    $service->dc->load_current_string_8,
                ]
            ] : null,
            'ac' => $service->ac ? [
                'OCVoltage' => [
                    $service->ac->OC_valtage_L1_N,
                    $service->ac->OC_valtage_L2_N,
                    $service->ac->OC_valtage_L3_N,
                    $service->ac->OC_valtage_L1_L2,
                    $service->ac->OC_valtage_L1_L3,
                    $service->ac->OC_valtage_L2_L3,
                    $service->ac->OC_valtage_N_E,
                ],
                'LoadVoltage' => [
                    $service->ac->load_valtage_L1_N,
                    $service->ac->load_valtage_L2_N,
                    $service->ac->load_valtage_L3_N,
                    $service->ac->load_valtage_L1_L2,
                    $service->ac->load_valtage_L1_L3,
                    $service->ac->load_valtage_L2_L3,
                    $service->ac->load_valtage_N_E,
                ],
                'LoadCurrent' => [
                    $service->ac->load_current_L1_N,
                    $service->ac->load_current_L2_N,
                    $service->ac->load_current_L3_N,
                ]
            ] : null,
            'roof_work' => $service->roofWork ? [
                'cloudiness' => [
                    'value' => $service->roofWork->cloudness_reading,
                    'comment' => $service->roofWork->cloudness_reading_comments
                ],
                'panel_service' => [
                    'value' => $service->roofWork->panel_service,
                    'comment' => $service->roofWork->panel_service_comments
                ],
                'structure_service' => [
                    'value' => $service->roofWork->structure_service,
                    'comment' => $service->roofWork->structure_service_comments
                ],
                'nut_bolt_condition' => [
                    'value' => $service->roofWork->nut_bolt_condition,
                    'comment' => $service->roofWork->nut_bolt_condition_comments
                ],
                'shadow' => [
                    'value' => $service->roofWork->shadow,
                    'comment' => $service->roofWork->shadow_comments
                ],
                'panel_MC4_condition' => [
                    'value' => $service->roofWork->panel_MC4_condition,
                    'comment' => $service->roofWork->panel_MC4_condition_comments
                ],
                'took_photos' => [
                    'value' => $service->roofWork->took_photos,
                    'comment' => $service->roofWork->took_photos_comments
                ]
            ] : null,
            'outdoor_work' => $service->outdoorWork,
            'main_panel_work' => $service->mainPanelWork,
            'remarks' => $service->remarks,
            'technicians' => $service->serviceTechniciant->pluck('techniciant_name'),
            'project' => [
                'id' => $service->project->id,
                'project_no' => $projectNo,
                'project_name' => $service->project->project_name,
                'project_address' => $service->project->project_address,
                'customer_name' => $service->project->customer->name ?? null,
                'no_of_panels' => $service->project->no_of_panels,
                'panel_capacity' => $service->project->panel_capacity,
                'invertors' => $service->project->invertor->map(function($invertor) {
                    return [
                        'id' => $invertor->id,
                        'invertor_name' => $invertor->invertor_name,
                        'invertor_capacity' => $invertor->invertor_capacity
                    ];
                })
            ],
        ];

return response()->json([
            'status' => 'success',
            'message' => 'Service details fetched successfully',
            'data' => $response 
        ]);

    } catch (ValidationException $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage(),
            'errors' => $e->errors()
        ], 422);
        
    }catch (\Exception $e) {
        DB::rollBack();
        \Log::error('Service update error: ' . $e->getMessage(), [
            'trace' => $e->getTraceAsString()
        ]);
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage()
        ], 500);
    }
}

public function updateServiceDetails(Request $request)
{
    try {
        // Validate required fields
        $request->validate([
            'service_id' => 'required|exists:services,id',
            'mainData' => 'required|array',
            'dc' => 'nullable|array',
            'ac' => 'nullable|array',
            'roof_work' => 'nullable|array',
            'outdoor_work' => 'nullable|array',
            'main_panel_work' => 'nullable|array',
            'technicians' => 'nullable|array',
            'remarks' => 'nullable|string'
        ]);

        // Begin database transaction
        DB::beginTransaction();

        // Find the service
        $service = Service::findOrFail($request->service_id);

// Convert time string to full datetime (using today's date)
$powerTime = null;
if (!empty($request->mainData['time'])) {
    $powerTime = Carbon::createFromFormat('H:i:s', $request->mainData['time'])
        ->setDate(now()->year, now()->month, now()->day)
        ->format('Y-m-d H:i:s');
}

        // Update main service data
        $service->update([
            'power' => $request->mainData['power'] ?? null,
            'power_time' => $powerTime,
            'wifi_connectivity' => $request->mainData['wifiConnectivity'] ?? false,
            'capture_last_bill' => $request->mainData['electricityBill'] ?? false,
            'remarks' => $request->remarks ?? null,

        ]);

        if($service->project) {
            // Update project details if needed
            $service->project->update([
                'longitude' => isset($request->mainData['longitude'])
                    ? (double) $request->mainData['longitude'] : null,
                'lattitude' => isset($request->mainData['latitude'])
                    ? (double) $request->mainData['latitude'] : null
            ]);
        }

        // Update DC data if exists
        if ($request->has('dc') && $service->dc) {
            $service->dc->update([
                'OC_valtage_string_1' => $request->dc['OCVoltage'][0] ?? null,
                'OC_valtage_string_2' => $request->dc['OCVoltage'][1] ?? null,
                'OC_valtage_string_3' => $request->dc['OCVoltage'][2] ?? null,
                'OC_valtage_string_4' => $request->dc['OCVoltage'][3] ?? null,
                'OC_valtage_string_5' => $request->dc['OCVoltage'][4] ?? null,
                'OC_valtage_string_6' => $request->dc['OCVoltage'][5] ?? null,
                'OC_valtage_string_7' => $request->dc['OCVoltage'][6] ?? null,
                'OC_valtage_string_8' => $request->dc['OCVoltage'][7] ?? null,
                'load_valtage_string_1' => $request->dc['LoadVoltage'][0] ?? null,
                'load_valtage_string_2' => $request->dc['LoadVoltage'][1] ?? null,
                'load_valtage_string_3' => $request->dc['LoadVoltage'][2] ?? null,
                'load_valtage_string_4' => $request->dc['LoadVoltage'][3] ?? null,
                'load_valtage_string_5' => $request->dc['LoadVoltage'][4] ?? null,
                'load_valtage_string_6' => $request->dc['LoadVoltage'][5] ?? null,
                'load_valtage_string_7' => $request->dc['LoadVoltage'][6] ?? null,
                'load_valtage_string_8' => $request->dc['LoadVoltage'][7] ?? null,
                'load_current_string_1' => $request->dc['LoadCurrent'][0] ?? null,
                'load_current_string_2' => $request->dc['LoadCurrent'][1] ?? null,
                'load_current_string_3' => $request->dc['LoadCurrent'][2] ?? null,
                'load_current_string_4' => $request->dc['LoadCurrent'][3] ?? null,
                'load_current_string_5' => $request->dc['LoadCurrent'][4] ?? null,
                'load_current_string_6' => $request->dc['LoadCurrent'][5] ?? null,
                'load_current_string_7' => $request->dc['LoadCurrent'][6] ?? null,
                'load_current_string_8' => $request->dc['LoadCurrent'][7] ?? null,
            ]);
        }

        // Update AC data if exists
        if ($request->has('ac') && $service->ac) {
            $service->ac->update([
                'OC_valtage_L1_N' => $request->ac['OCVoltage'][0] ?? null,
                'OC_valtage_L2_N' => $request->ac['OCVoltage'][1] ?? null,
                'OC_valtage_L3_N' => $request->ac['OCVoltage'][2] ?? null,
                'OC_valtage_L1_L2' => $request->ac['OCVoltage'][3] ?? null,
                'OC_valtage_L1_L3' => $request->ac['OCVoltage'][4] ?? null,
                'OC_valtage_L2_L3' => $request->ac['OCVoltage'][5] ?? null,
                'OC_valtage_N_E' => $request->ac['OCVoltage'][6] ?? null,
                'load_valtage_L1_N' => $request->ac['LoadVoltage'][0] ?? null,
                'load_valtage_L2_N' => $request->ac['LoadVoltage'][1] ?? null,
                'load_valtage_L3_N' => $request->ac['LoadVoltage'][2] ?? null,
                'load_valtage_L1_L2' => $request->ac['LoadVoltage'][3] ?? null,
                'load_valtage_L1_L3' => $request->ac['LoadVoltage'][4] ?? null,
                'load_valtage_L2_L3' => $request->ac['LoadVoltage'][5] ?? null,
                'load_valtage_N_E' => $request->ac['LoadVoltage'][6] ?? null,
                'load_current_L1_N' => $request->ac['LoadCurrent'][0] ?? null,
                'load_current_L2_N' => $request->ac['LoadCurrent'][1] ?? null,
                'load_current_L3_N' => $request->ac['LoadCurrent'][2] ?? null,
            ]);
        }

        // Update Roof Work data if exists
        if ($request->has('roof_work') && $service->roofWork) {
            $service->roofWork->update([
                'cloudness_reading' => $request->roof_work['cloudiness']['value'] ?? null,
                'cloudness_reading_comments' => $request->roof_work['cloudiness']['comment'] ?? null,
                'panel_service' => $request->roof_work['panel_service']['value'] ?? false,
                'panel_service_comments' => $request->roof_work['panel_service']['comment'] ?? null,
                'structure_service' => $request->roof_work['structure_service']['value'] ?? false,
                'structure_service_comments' => $request->roof_work['structure_service']['comment'] ?? null,
                'nut_bolt_condition' => $request->roof_work['nut_bolt_condition']['value'] ?? false,
                'nut_bolt_condition_comments' => $request->roof_work['nut_bolt_condition']['comment'] ?? null,
                'shadow' => $request->roof_work['shadow']['value'] ?? false,
                'shadow_comments' => $request->roof_work['shadow']['comment'] ?? null,
                'panel_MC4_condition' => $request->roof_work['panel_MC4_condition']['value'] ?? false,
                'panel_MC4_condition_comments' => $request->roof_work['panel_MC4_condition']['comment'] ?? null,
                'took_photos' => $request->roof_work['took_photos']['value'] ?? false,
                'took_photos_comments' => $request->roof_work['took_photos']['comment'] ?? null,
            ]);
        }

        // Update Outdoor Work data if exists
        if ($request->has('outdoor_work') && $service->outdoorWork) {
            $service->outdoorWork->update([
                'CEB_import_reading' => $request->outdoor_work['cebImport']['value'] ?? null,
                'CEB_import_reading_comments' => $request->outdoor_work['cebImport']['comment'] ?? null,
                'CEB_export_reading' => $request->outdoor_work['cebExport']['value'] ?? null,
                'CEB_export_reading_comments' => $request->outdoor_work['cebExport']['comment'] ?? null,
                'round_resistence' => $request->outdoor_work['groundResistance']['value'] ?? null,
                'round_resistence_comments' => $request->outdoor_work['groundResistance']['comment'] ?? null,
                'earthing_rod_connection' => $request->outdoor_work['earthRod']['checked'] ?? false,
                'earthing_rod_connection_comments' => $request->outdoor_work['earthRod']['comment'] ?? null,
            ]);
        }
          
        // $powerBulbMapping = [
        //     'Slow' => 1,
        //     'Solid' => 2,
        //     'Fast' => 3
        // ];
        
        // $powerBulbValue = $powerBulbMapping[$request->main_panel_work['powerBulbBlinkingStyle']['value'] ?? ''] ?? null;
        // Update Main Panel Work data if exists
        if ($request->has('main_panel_work') && $service->mainPanelWork) {
            $service->mainPanelWork->update([
                'off_grid_valtage' => $request->main_panel_work['offlineGridVoltage']['value'] ?? null,
                'off_grid_valtage_comments' => $request->main_panel_work['offlineGridVoltage']['comment'] ?? null,
                'on_grid_valtage' => $request->main_panel_work['onlineGridVoltage']['value'] ?? null,
                'on_grid_valtage_comments' => $request->main_panel_work['onlineGridVoltage']['comment'] ?? null,
                'invertor_service_fan_time' => $request->main_panel_work['invertorServiceFanTime']['checked'] ?? false,
                'invertor_service_fan_time_comments' => $request->main_panel_work['invertorServiceFanTime']['comment'] ?? null,
                'breaker_service' => $request->main_panel_work['breakerService']['checked'] ?? false,
                'breaker_service_comments' => $request->main_panel_work['breakerService']['comment'] ?? null,
                'DC_surge_arrestors' => $request->main_panel_work['dcSurgeArrestors']['checked'] ?? false,
                'DC_surge_arrestors_comments' => $request->main_panel_work['dcSurgeArrestors']['comment'] ?? null,
                'AC_surge_arrestors' => $request->main_panel_work['acSurgeArrestors']['checked'] ?? false,
                'AC_surge_arrestors_comments' => $request->main_panel_work['acSurgeArrestors']['comment'] ?? null,
                'invertor_connection_MC4_condition' => $request->main_panel_work['invertorConnection']['checked'] ?? false,
                'invertor_connection_MC4_condition_comments' => $request->main_panel_work['invertorConnection']['comment'] ?? null,
                'low_valtage_range' => $request->main_panel_work['lowVoltageRange']['value'] ?? null,
                'low_valtage_range_comments' => $request->main_panel_work['lowVoltageRange']['comment'] ?? null,
                'high_valtage_range' => $request->main_panel_work['highVoltageRange']['value'] ?? null,
                'high_valtage_range_comments' => $request->main_panel_work['highVoltageRange']['comment'] ?? null,
                'low_freaquence_range' => $request->main_panel_work['lowFrequencyRange']['value'] ?? null,
                'low_freaquence_range_comments' => $request->main_panel_work['lowFrequencyRange']['comment'] ?? null,
                'high_freaquence_range' => $request->main_panel_work['highFrequencyRange']['value'] ?? null,
                'high_freaquence_range_comments' => $request->main_panel_work['highFrequencyRange']['comment'] ?? null,
                'invertor_startup_time' => $request->main_panel_work['invertorSetupTime']['value'] ?? null,
                'invertor_startup_time_comments' => $request->main_panel_work['invertorSetupTime']['comment'] ?? null,
                'e_today_invertor' => $request->main_panel_work['eTodayInvertor']['value'] ?? null,
                'e_today_invertor_comments' => $request->main_panel_work['eTodayInvertor']['comment'] ?? null,
                'e_total_invertor' => $request->main_panel_work['eTotalInvertor']['value'] ?? null,
                'e_total_invertor_comments' => $request->main_panel_work['eTotalInvertor']['comment'] ?? null,
                'wifi_config_done' => $request->main_panel_work['wifiConfig']['checked'] ?? false,
                'wifi_config_done_comments' => $request->main_panel_work['wifiConfig']['comment'] ?? null,
                // 'power_bulb_blinking_style' => $powerBulbValue,
                // 'power_bulb_blinking_style_comments' => $request->main_panel_work['powerBulbBlinkingStyle']['comment'] ?? null,
                 'power_bulb_blinking_style' => $request->main_panel_work['powerBulbBlinkingStyle']['value'] ?? null,
                 'power_bulb_blinking_style_comments' => $request->main_panel_work['powerBulbBlinkingStyle']['comment'] ?? null,
                'router_username' => $request->main_panel_work['routerUsername']['value'] ?? null,
                'router_username_comments' => $request->main_panel_work['routerUsername']['comment'] ?? null,
                'router_password' => $request->main_panel_work['routerPassword']['value'] ?? null,
                'router_password_comments' => $request->main_panel_work['routerPassword']['comment'] ?? null,
                'router_serial_number' => $request->main_panel_work['routerSerialNo']['value'] ?? null,
                'router_serial_number_comments' => $request->main_panel_work['routerSerialNo']['comment'] ?? null,
                'alta_vision_sticker' => $request->main_panel_work['serviceAVSticker']['checked'] ?? false,
                'alta_vision_sticker_comments' => $request->main_panel_work['serviceAVSticker']['comment'] ?? null,
                'took_photos' => $request->main_panel_work['tookPhotos']['checked'] ?? false,
                'took_photos_comments' => $request->main_panel_work['tookPhotos']['comment'] ?? null,
            ]);
        }

        // Update Technicians if needed
        if ($request->has('technicians')) {
            // Delete existing technicians
            $service->serviceTechniciant()->delete();
            
            // Add new technicians
            foreach ($request->technicians as $technician) {
                $service->serviceTechniciant()->create([
                    'techniciant_name' => $technician
                ]);
            }
        }

        // Commit transaction
        DB::commit();

        return response()->json([
            'status' => 'success',
            'message' => 'Service details updated successfully',
            'service' => $service->load('project'),
            'data' => $service->load([
                'project.onGrid',
                'project.offGridHybrid',
                'project.customer',
                'project.invertor',
                'outdoorWork',
                'roofWork',
                'mainPanelWork',
                'dc',
                'ac',
                'serviceTechniciant'
            ])
        ]);

    } catch (ValidationException $e) {
        DB::rollBack();
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage(),
            'errors' => $e->errors()
        ], 422);
        
    }catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Service update error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
}

public function monthlySummary(Request $request)
    {
         $month = $request->query('month', Carbon::now()->month);
         $year  = $request->query('year', Carbon::now()->year);

        // Load services of current month where service_done = true, with project & solar panels
        $services = Service::with(['project.solarPanel'])
            ->whereMonth('service_date', $month)
            ->whereYear('service_date', $year)
            ->where('service_done', 1)
            ->get();

        $summary = [
            'on_grid' => [],
            'off_grid' => [],
        ];

        foreach ($services as $s) {
            if (!$s->project) continue;

            // Detect grid type
            $type = strtolower($s->project->type ?? '');
            if (str_contains($type, 'on')) {
                $gridKey = 'on_grid';
            } elseif (str_contains($type, 'off')) {
                $gridKey = 'off_grid';
            } else {
                continue;
            }

            // Build group key
            $prefix = $s->service_type === 'paid' ? 'Paid Service ' : 'Service ';
            $round  = $prefix . $s->service_round_no;

            // Track unique sites
            $siteKey = $s->project->longitude . ',' . $s->project->lattitude;

            if (!isset($summary[$gridKey][$round])) {
                $summary[$gridKey][$round] = [
                    'sites'    => [],
                    'capacity' => [],
                ];
            }

            // Add site
            $summary[$gridKey][$round]['sites'][$siteKey] = true;

            // Calculate capacity from solar panels for this project
            $capacityKw = $s->project->solarPanel
                ->where('is_current', true)
                ->sum(fn($p) => ($p->wattage_of_pannel * $p->no_of_panels) / 1000); // Convert to kW

            // Assign capacity only once per project
            $summary[$gridKey][$round]['capacity'][$s->project->id] = $capacityKw;
        }

        // Transform into final format
        foreach (['on_grid', 'off_grid'] as $key) {
            $summary[$key] = collect($summary[$key] ?? [])->map(function ($row, $round) {
                return [
                    'service_round' => $round,
                    'no_of_sites'   => count($row['sites']),
                    'capacity'      => round(array_sum($row['capacity']), 2), // kW
                ];
            })->values();
        }

        return response()->json([
            'month'   => $month,
            'year'    => $year,
            'summary' => $summary,
        ]);
    }

    public function annualSummary(Request $request)
{
    $year  = $request->query('year', Carbon::now()->year);

    // Load services of current year where service_done = true, with project & solar panels
    $services = Service::with(['project.solarPanel'],$year)
        ->whereYear('service_date', $year)
        ->where('service_done', 1)
        ->get();

    $summary = [
        'on_grid' => [],
        'off_grid' => [],
    ];

    foreach ($services as $s) {
        if (!$s->project) continue;

        // Detect grid type
        $type = strtolower($s->project->type ?? '');
        if (str_contains($type, 'on')) {
            $gridKey = 'on_grid';
        } elseif (str_contains($type, 'off')) {
            $gridKey = 'off_grid';
        } else {
            continue;
        }

        // Build group key
        $prefix = $s->service_type === 'paid' ? 'Paid Service ' : 'Service ';
        $round  = $prefix . $s->service_round_no;

        // Track unique sites
        $siteKey = $s->project->longitude . ',' . $s->project->lattitude;

        if (!isset($summary[$gridKey][$round])) {
            $summary[$gridKey][$round] = [
                'sites'    => [],
                'capacity' => [],
            ];
        }

        // Add site
        $summary[$gridKey][$round]['sites'][$siteKey] = true;

        // Calculate capacity from solar panels for this project
        $capacityKw = $s->project->solarPanel
            ->where('is_current', true)
            ->sum(fn($p) => ($p->wattage_of_pannel * $p->no_of_panels) / 1000); // Convert to kW

        // Assign capacity only once per project
        $summary[$gridKey][$round]['capacity'][$s->project->id] = $capacityKw;
    }

    // Transform into final format
    foreach (['on_grid', 'off_grid'] as $key) {
        $summary[$key] = collect($summary[$key] ?? [])->map(function ($row, $round) {
            return [
                'service_round' => $round,
                'no_of_sites'   => count($row['sites']),
                'capacity'      => round(array_sum($row['capacity']), 2), // kW
            ];
        })->values();
    }

    return response()->json([
        'year'    => $year,
        'summary' => $summary,
    ]);
}

public function getDueNotifications()
{
    $projects = Project::with(['service', 'onGrid', 'offGridHybrid'])->get();
    $notifications = [];
    $today = Carbon::today();

    foreach ($projects as $project) {
        // Skip if project is on hold
        if ($project->is_hold == 1) {
            continue;
        }

        // Read "External/Internal" column safely
        // $projectType = strtolower($project['External/Internal'] ?? '');

        $serviceYears = $project->service_years_in_agreement ?? 0;
        $serviceRounds = $project->service_rounds_in_agreement ?? 0;

        // Determine service interval and notify period
        if ($serviceYears > 0 && $serviceRounds > 0) {
            $totalMonths = $serviceYears * 12;
            $intervalMonths = intval($totalMonths / $serviceRounds);
            $notifyBeforeMonths = $intervalMonths == 12 ? 8 : ($intervalMonths == 6 ? 4 : intval($intervalMonths * 0.66));
        } else {
            // Only apply fallback for external projects
             if ($project->{'External/Internal'} === 'External')
 {
                $intervalMonths = 12; // 1 year
                $notifyBeforeMonths = 8;
            } else {
                // If internal without service years/rounds → skip
                continue;
            }
        }

        // Get last service date or installation date
        $lastService = $project->service()->latest('service_date')->first();

        if ($lastService) {
            $lastDate = Carbon::parse($lastService->service_date);
            $nextRound = $lastService->service_round_no + 1;
        } else {
            $nextRound = 1;
            $lastDate = Carbon::parse($project->project_installation_date);
        }

        // Calculate due/notify dates
        $dueDate = $lastDate->copy()->addMonths($intervalMonths);
        $notifyDate = $dueDate->copy()->subMonths($notifyBeforeMonths);

        // Check if notification should be shown
        if ($today->greaterThanOrEqualTo($notifyDate) && $today->lessThan($dueDate)) {
            // Ensure no service already scheduled for this round
            $alreadyScheduled = $project->service()
                ->where('service_round_no', $nextRound)
                ->exists();

// Decide how to label the due service round
if ($nextRound <= $serviceRounds) {
    $roundLabel = $nextRound . ' (Free)';
} else {
    $paidRound = $nextRound - $serviceRounds;
    $roundLabel = $paidRound . ' (Paid)';
}

$notifications[] = [
    'project_id'   => $project->id,
    'project_no'   => $project->onGrid->on_grid_project_id ?? $project->offGridHybrid->off_grid_hybrid_project_id ?? null,
    'project_name' => $project->project_name,
    'due_service_round' => $roundLabel, // send formatted label
    'due_date'     => $dueDate->toDateString(),
    'nearest_town' => $project->neatest_town,
];
               
        }
    }

    return response()->json([
        'status' => 'success',
        'notifications' => $notifications
    ]);
}

}
