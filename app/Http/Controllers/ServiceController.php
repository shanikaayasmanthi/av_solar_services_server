<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Service;
use App\Models\ServiceTechniciant;
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

                    return [
                        'project_no' => $project_no,
                        'customer_name' => $customer_name,
                        'service_round' => $service->service_round_no ?? null,
                        'service_date' => $service->service_date ?? null,
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

            $services = Service::where('project_id', $request->project_id)
                ->where('service_done', true)
                ->orderBy('service_date', 'asc')
                ->get()
                ->map(function ($service) {
                    return [
                        'service_id' => $service->id,
                        'project_id' => $service->project_id,
                        'project_no' => $service->project->type == 'ongrid' ? $service->project->onGrid->on_grid_project_id : ($service->project->type == 'offgrid' ? $service->project->offGridHybrid->off_grid_hybrid_project_id : null),
                        'customer_name' => $service->project->customer->name ?? null,
                        'nearest_town' => $service->project->neatest_town ?? null,
                        'service_round' => $service->service_round_no,
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
                'service_data' => 'required'
            ]);

            $serviceData = json_decode($request->service_data);

            $service = Service::findOrFail($request->service_id);

            if ($service->supervisor_id != $request->user_id) {
                return $this->error('', 'Unauthorized', 401);
            }

            //Wrap everything inside a transaction:
            DB::beginTransaction();

            // Update Service mainData
            $mainData = $serviceData->mainData;
            $time = Carbon::today()->setTimeFromTimeString($mainData->time)->toDateTimeString();

            $service->update([
                'power' => (float)$mainData->power,
                'power_time' => $time,
                'wifi_connectivity' => $mainData->wifiConnectivity ?? false,
                'capture_last_bill' => $mainData->electricityBill ?? false,
            ]);

            // Save DC
            $dc = $serviceData->dc;
            $dcController = new DCController();
            if (!$dcController->saveServiceDCData($request->service_id, $dc)) {
                DB::rollBack();
                return $this->error('', 'Failed saving DC', 500);
            }

            // Save AC
            $ac = $serviceData->ac;
            $acController = new ACController();
            if (!$acController->saveServiceACData($request->service_id, $ac)) {
                DB::rollBack();
                return $this->error('', 'Failed saving AC', 500);
            }

            // Save RoofWork
            $roofWork = $serviceData->roof_work;
            $roofWorkController = new RoofWorkController();
            if (!$roofWorkController->saveServiceRoofWorkData($request->service_id, $roofWork)) {
                DB::rollBack();
                return $this->error('', 'Failed saving RoofWork', 500);
            }

            // Save OutdoorWork
            $outDoorWork = $serviceData->outdoor_work;
            $outDoorWorkController = new OutdoorWorkController();
            if (!$outDoorWorkController->saveServiceOutDoorWork($request->service_id, $outDoorWork)) {
                DB::rollBack();
                return $this->error('', 'Failed saving OutdoorWork', 500);
            }

            // Save MainPanelWork
            $mainPanelWork = $serviceData->mainpanel_work;
            $mainPanelWorkController = new MainPanelWorkController();
            if (!$mainPanelWorkController->saveServiceMainPanelWork($request->service_id, $mainPanelWork)) {
                DB::rollBack();
                return $this->error('', 'Failed saving MainPanelWork', 500);
            }

            // Save Technicians
            $technicians = $serviceData->technicians;
            if (!empty($technicians)) {
                $technicianController = new ServiceTechniciantController();
                if (!$technicianController->saveServiceTechnicians($request->service_id, $technicians)) {
                    DB::rollBack();
                    return $this->error('', 'Failed saving Technicians', 500);
                }
            }

            // Finally: Mark Service done
            $service->update([
                'service_done' => true,
            ]);

            //Everything is fine commit
            DB::commit();
            return $this->success('', 'Service saved successfully');
        } catch (ValidationException $e) {
            return $this->error('', $e, 401);
        } catch (Exception $e) {
            DB::rollBack();  // rollback on any unexpected error
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

            $firstServiceCount = Service::where('service_round_no', 1)->count();
            $secondServiceCount = Service::where('service_round_no', 2)->count();

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

// In ServiceController.php

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
            'service_id' => $service->id,
            'service_round_no' => $service->service_round_no,
            'service_type' => $service->service_type,
            'service_date' => $service->service_date,
            'service_time' => $service->service_time,
            'power' => $service->power,
            'power_time' => $service->power_time,
            'wifi_connectivity' => $service->wifi_connectivity,
            'capture_last_bill' => $service->capture_last_bill,
            'remarks' => $service->remarks,

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

            'technicians' => $service->serviceTechniciant->pluck('techniciant_name'),

            'dc_data' => $service->dc ? [
                'OC_valtage_string_1' => $service->dc->OC_valtage_string_1,
                'OC_valtage_string_2' => $service->dc->OC_valtage_string_2,
                'OC_valtage_string_3' => $service->dc->OC_valtage_string_3,
                'OC_valtage_string_4' => $service->dc->OC_valtage_string_4,
                'OC_valtage_string_5' => $service->dc->OC_valtage_string_5,
                'OC_valtage_string_6' => $service->dc->OC_valtage_string_6,
                'OC_valtage_string_7' => $service->dc->OC_valtage_string_7,
                'OC_valtage_string_8' => $service->dc->OC_valtage_string_8,
                'load_valtage_string_1' => $service->dc->load_valtage_string_1,
                'load_valtage_string_2' => $service->dc->load_valtage_string_2,
                'load_valtage_string_3' => $service->dc->load_valtage_string_3,
                'load_valtage_string_4' => $service->dc->load_valtage_string_4,
                'load_valtage_string_5' => $service->dc->load_valtage_string_5,
                'load_valtage_string_6' => $service->dc->load_valtage_string_6,
                'load_valtage_string_7' => $service->dc->load_valtage_string_7,
                'load_valtage_string_8' => $service->dc->load_valtage_string_8,
                'load_current_string_1' => $service->dc->load_current_string_1,
                'load_current_string_2' => $service->dc->load_current_string_2,
                'load_current_string_3' => $service->dc->load_current_string_3,
                'load_current_string_4' => $service->dc->load_current_string_4,
                'load_current_string_5' => $service->dc->load_current_string_5,
                'load_current_string_6' => $service->dc->load_current_string_6,
                'load_current_string_7' => $service->dc->load_current_string_7,
                'load_current_string_8' => $service->dc->load_current_string_8,
            ] : null,
            
            'ac_data' => $service->ac ? [
                'OC_valtage_L1_N' => $service->ac->OC_valtage_L1_N,
                'OC_valtage_L2_N' => $service->ac->OC_valtage_L2_N,
                'OC_valtage_L3_N' => $service->ac->OC_valtage_L3_N,
                'OC_valtage_L1_L2' => $service->ac->OC_valtage_L1_L2,
                'OC_valtage_L1_L3' => $service->ac->OC_valtage_L1_L3,
                'OC_valtage_L2_L3' => $service->ac->OC_valtage_L2_L3,
                'OC_valtage_N_E' => $service->ac->OC_valtage_N_E,
                'load_valtage_L1_N' => $service->ac->load_valtage_L1_N,
                'load_valtage_L2_N' => $service->ac->load_valtage_L2_N,
                'load_valtage_L3_N' => $service->ac->load_valtage_L3_N,
                'load_valtage_L1_L2' => $service->ac->load_valtage_L1_L2,
                'load_valtage_L1_L3' => $service->ac->load_valtage_L1_L3,
                'load_valtage_L2_L3' => $service->ac->load_valtage_L2_L3,
                'load_valtage_N_E' => $service->ac->load_valtage_N_E,
                'load_current_L1_N' => $service->ac->load_current_L1_N,
                'load_current_L2_N' => $service->ac->load_current_L2_N,
                'load_current_L3_N' => $service->ac->load_current_L3_N,
            ] : null,
            
            'roof_work' => $service->roofWork ? [
                'cloudness_reading' => $service->roofWork->cloudness_reading,
                'cloudness_reading_comments' => $service->roofWork->cloudness_reading_comments,
                'panel_service' => $service->roofWork->panel_service,
                'panel_service_comments' => $service->roofWork->panel_service_comments,
                'structure_service' => $service->roofWork->structure_service,
                'structure_service_comments' => $service->roofWork->structure_service_comments,
                'nut_bolt_condition' => $service->roofWork->nut_bolt_condition,
                'nut_bolt_condition_comments' => $service->roofWork->nut_bolt_condition_comments,
                'shadow' => $service->roofWork->shadow,
                'shadow_comments' => $service->roofWork->shadow_comments,
                'panel_MC4_condition' => $service->roofWork->panel_MC4_condition,
                'panel_MC4_condition_comments' => $service->roofWork->panel_MC4_condition_comments,
                'took_photos' => $service->roofWork->took_photos,
                'took_photos_comments' => $service->roofWork->took_photos_comments,
            ] : null,
            
            'outdoor_work' => $service->outdoorWork ? [
                'CEB_import_reading' => $service->outdoorWork->CEB_import_reading,
                'CEB_import_reading_comments' => $service->outdoorWork->CEB_import_reading_comments,
                'CEB_export_reading' => $service->outdoorWork->CEB_export_reading,
                'CEB_export_reading_comments' => $service->outdoorWork->CEB_export_reading_comments,
                'round_resistence' => $service->outdoorWork->round_resistence,
                'round_resistence_comments' => $service->outdoorWork->round_resistence_comments,
                'earthing_rod_connection' => $service->outdoorWork->earthing_rod_connection,
                'earthing_rod_connection_comments' => $service->outdoorWork->earthing_rod_connection_comments,
            ] : null,
            
            'main_panel_work' => $service->mainPanelWork ? [
                'on_grid_valtage' => $service->mainPanelWork->on_grid_valtage,
                'on_grid_valtage_comments' => $service->mainPanelWork->on_grid_valtage_comments,
                'off_grid_valtage' => $service->mainPanelWork->off_grid_valtage,
                'off_grid_valtage_comments' => $service->mainPanelWork->off_grid_valtage_comments,
                'invertor_service_fan_time' => $service->mainPanelWork->invertor_service_fan_time,
                'invertor_service_fan_time_comments' => $service->mainPanelWork->invertor_service_fan_time_comments,
                'breaker_service' => $service->mainPanelWork->breaker_service,
                'breaker_service_comments' => $service->mainPanelWork->breaker_service_comments,
                'DC_surge_arrestors' => $service->mainPanelWork->DC_surge_arrestors,
                'DC_surge_arrestors_comments' => $service->mainPanelWork->DC_surge_arrestors_comments,
                'AC_surge_arrestors' => $service->mainPanelWork->AC_surge_arrestors,
                'AC_surge_arrestors_comments' => $service->mainPanelWork->AC_surge_arrestors_comments,
                'invertor_connection_MC4_condition' => $service->mainPanelWork->invertor_connection_MC4_condition,
                'invertor_connection_MC4_condition_comments' => $service->mainPanelWork->invertor_connection_MC4_condition_comments,
                'low_valtage_range' => $service->mainPanelWork->low_valtage_range,
                'low_valtage_range_comments' => $service->mainPanelWork->low_valtage_range_comments,
                'high_valtage_range' => $service->mainPanelWork->high_valtage_range,
                'high_valtage_range_comments' => $service->mainPanelWork->high_valtage_range_comments,
                'low_freaquence_range' => $service->mainPanelWork->low_freaquence_range,
                'low_freaquence_range_comments' => $service->mainPanelWork->low_freaquence_range_comments,
                'high_freaquence_range' => $service->mainPanelWork->high_freaquence_range,
                'high_freaquence_range_comments' => $service->mainPanelWork->high_freaquence_range_comments,
                'invertor_startup_time' => $service->mainPanelWork->invertor_startup_time,
                'invertor_startup_time_comments' => $service->mainPanelWork->invertor_startup_time_comments,
                'e_today_invertor' => $service->mainPanelWork->e_today_invertor,
                'e_today_invertor_comments' => $service->mainPanelWork->e_today_invertor_comments,
                'e_total_invertor' => $service->mainPanelWork->e_total_invertor,
                'e_total_invertor_comments' => $service->mainPanelWork->e_total_invertor_comments,
                'power_bulb_blinking_style' => $service->mainPanelWork->power_bulb_blinking_style,
                'power_bulb_blinking_style_comments' => $service->mainPanelWork->power_bulb_blinking_style_comments,
                'alta_vision_sticker' => $service->mainPanelWork->alta_vision_sticker,
                'alta_vision_sticker_comments' => $service->mainPanelWork->alta_vision_sticker_comments,
                'wifi_config_done' => $service->mainPanelWork->wifi_config_done,
                'wifi_config_done_comments' => $service->mainPanelWork->wifi_config_done_comments,
                'router_username' => $service->mainPanelWork->router_username,
                'router_username_comments' => $service->mainPanelWork->router_username_comments,
                'router_password' => $service->mainPanelWork->router_password,
                'router_password_comments' => $service->mainPanelWork->router_password_comments,
                'router_serial_number' => $service->mainPanelWork->router_serial_number,
                'router_serial_number_comments' => $service->mainPanelWork->router_serial_number_comments,
                'took_photos' => $service->mainPanelWork->took_photos,
                'took_photos_comments' => $service->mainPanelWork->took_photos_comments,
            ] : null
        ];

        return $this->success($response, 'Service details fetched successfully');
        
    } catch (ValidationException $e) {
        return $this->error('', $e->getMessage(), 422);
    } catch (Exception $e) {
        return $this->error('', $e->getMessage(), 500);
    }
}

}
