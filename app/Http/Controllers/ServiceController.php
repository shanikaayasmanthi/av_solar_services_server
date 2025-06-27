<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Service;
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

}
