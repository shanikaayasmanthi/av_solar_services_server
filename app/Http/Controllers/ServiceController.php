<?php

namespace App\Http\Controllers;

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

    //save service data
    public function saveServiceDetails(Request $request){
        try {
            $request->validate([
                'user_id'=> 'required',
                'service_id'=>'required|exists:services,id',
                'service_data'=>'required'
            ]);

            $serviceData = json_decode($request->service_data);
            // log::info('servicedata',(array)$serviceData);

            $service = Service::findOrFail($request->service_id);
            // log::info($service);
            if($service->supervisor_id!= $request->user_id){
                return $this->error('', 'Unauthorized', 401);

            }
            $mainData = $serviceData->mainData;
            // Log::info("mainData",(array)$mainData);
            $time =Carbon::today()->setTimeFromTimeString($mainData->time)->toDateTimeString();

            // log::info($time);
            // log::info((double)$mainData->power);
            $result = $service->update([
                'power' => (double)$mainData->power,
                'power_time' =>$time,
                'wifi_connectivity' => $mainData->wifiConnectivity ?? false,
                'capture_last_bill' => $mainData->electricityBill ?? false,
]);
            if($result){
                //correct till here
                $dc = $serviceData->dc;
                $dcController = new DCController();

                $dcResult = $dcController->saveServiceDCData($request->service_id, $dc);
                
                if($dcResult){
                    $ac = $serviceData->ac;
                    $acController = new ACController();

                    $acResult = $acController->saveServiceACData($request->service_id,$ac);

                    if($acResult){
                        $roofWork = $serviceData->roof_work;

                        $roofWorkController = new RoofWorkController();
                        $roofWorkResult = $roofWorkController->saveServiceRoofWorkData($request->service_id,$roofWork);
                        if($roofWorkResult){
                            $outDoorWork = $serviceData->outdoor_work;

                            $outDoorWorkController = new OutdoorWorkController();
                            $outDoorWorkResult = $outDoorWorkController->saveServiceOutDoorWork($request->service_id,$outDoorWork);
                            if($outDoorWorkResult){
                                $mainPanelWork = $serviceData->mainpanel_work;

                                $mainPanelWorkController = new MainPanelWorkController();
                                $mainPanelWorkResult = $mainPanelWorkController->saveServiceMainPanelWork($request->service_id,$mainPanelWork);
                                if($mainPanelWorkResult){
                                    $technicians = $serviceData->technicians;
                                    if (!empty($technicians)) {
                                        $technicianController = new ServiceTechniciantController();
                                        $techResult = $technicianController->saveServiceTechnicians($request->service_id,$technicians);

                                        if($techResult){
                                            Service::where('id',$request->service_id)->update([
                                                'service_done'=>true,

                                            ]);
                                            return true;
                                        }else{
                                            return false;
                                        }
                                    }
                                }
                                
                            }

                        }
                    }
                }                
            }
        }catch(ValidationException $e){
            return $this->error('', $e, 401);

        }catch(Exception $e){
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
}
}
