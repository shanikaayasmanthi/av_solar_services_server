<?php

namespace App\Http\Controllers;

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
        
        $request->validate([
            
        ]);

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
      

    $projectIds = Service ::where('service_done', true)
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
                    'project_no' => $service->project->type == 'ongrid' ? $service->project->onGrid->on_grid_project_id : 
                                    ($service->project->type == 'offgrid' ? $service->project->offGridHybrid->off_grid_hybrid_project_id : null),
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
                'user_id'=>'required|exists:supervisors,user_id',
                'service_id'=>'required|exists:services,id',
                'project_id'=>'required|exists:projects,id',
                'time'=> 'required',
                ]);

                $service = Service::where('id',$request->service_id)->where('supervisor_id',$request->user_id)->where('project_id',$request->project_id)->first();
                log::info($service);
                if($service){
                    $result = Service::where('id',$request->service_id)->update(['service_time'=>$request->time]);
                    return $this->success([
                        'result'=>$result,
                    ],'Success');
                }else{
                   return $this->error('','Error Occurred',500);
                }
        } catch (ValidationException $e) {
            return $this->error('', 'Unauthorized access', 401);
        } catch (Exception $e) {
            return $this->error('', $e, 500);
        }
    }

    //get project id and offgrid or ongrid no from service id
    public function getProjectNo(Request $request){
        try {
            $request->validate([
                'user_id'=>'required|exists:supervisors,user_id',
                'service_id'=>'required|exists:services,id'
            ]);
            // log::info($request);

            $service = Service::with(["project.onGrid","project.offGridHybrid"])->findOrFail($request->service_id);
            // log::info($service);
            $project = $service->project;
            $project_no = null;
            if($project->type == "ongrid"&&$project->onGrid){
                $project_no = $project->onGrid->on_grid_project_id;
            }elseif($project->type == "offgrid"&&$project->offGridHybrid){
                $project_no = $project->offGridHybrid->off_grid_hybrid_project_id;
            }

            return $this->success([
                "project_id"=> $project->id,
                "project_no"=>$project_no,
                "project_name"=>$project->project_name
            ]);

        }catch(ValidationException $e ) {
            return $this->error('', $e, 401);

        }catch (Exception $e) {
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
public function getServiceCounts(){
    try{

        $firstServiceCount = Service::where('service_round_no', 1)->count();
        $secondServiceCount = Service::where('service_round_no', 2)->count();

        return $this->success([
            'first_service_count' => $firstServiceCount,
            'second_service_count' => $secondServiceCount
        ]);
    }catch (ValidationException $e) {
        return $this->error('', 'Validation Error', 422);
    } catch (Exception $e) {
        return $this->error('', 'Error occurred', 500);
    }
}

public function getServicesSummery(Request $request){

    try{
        $request->validate([ 
            'project_id'=>'required|exists:projects,id'
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
                    
    }catch (ValidationException $e) {
        return $this->error('', 'Validation Error', 422);
    } catch (Exception $e) {
        return $this->error('', 'Error occurred', 500);
    }
}

//get next service round for shecule service
public function getNextServiceRound(Request $request){
    try{

        $request->validate([
            'project_id' => 'required|exists:projects,id'
        ]);

        $lastService = Service::where('project_id', $request->project_id)
            ->orderBy('service_date', 'desc')
            ->first();
        if (!$lastService) {
            return $this->error('', 'No completed services found for this project', 404);
        }
        if($lastService->service_done == false){
            return $this->error('', 'Already have a service to complete', 409);
        }
        $nextServiceRound = $lastService->service_round_no + 1;

        return $this->success([
            'next_service_round' => $nextServiceRound,
        ]);

    }catch (ValidationException $e) {
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
            'service_date' => 'required|date_format:Y-m-d',
            'supervisor_id'=>'required|exists:users,id',
        ]);

        $serviceExists = Service::where('project_id', $request->project_id)
            ->where('service_round_no', $request->service_round)
            ->exists();
        if ($serviceExists) {
            return $this->error('', 'Service for this round already exists', 409);
        }

        $lastService = Service::where('project_id', $request->project_id)
        ->where('service_done', true)
            ->orderBy('service_date', 'desc')
            ->first();

            if ($lastService ){
                return $this->error('', 'Service have to complete', 409);
            }


        $carbonDate = Carbon::parse($request->service_date);
        if($carbonDate == false) {
            return $this->error('', 'Invalid date format', 422);
        }
        $service = new Service();
        $service->project_id = $request->project_id;
        $service->service_round_no = $request->service_round;
        $service->service_date = $carbonDate->toDateTimeString();
        $service->supervisor_id = $request->supervisor_id;
        if($request->service_round == 1){
            $service->service_type = 'free';
        }else{
            $service->service_type = 'paid';
        }
        $service->save();

        return $this->success('','Service scheduled successfully');
    } catch (ValidationException $e) {
        return $this->error('', 'Validation Error', 422);
    } catch (Exception $e) {
        return $this->error('', 'Failed to schedule service', 500);
    }
}






}
