<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Traits\HttpResponses;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;

use function PHPSTORM_META\map;

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
                    log::info($project->customer);
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
            log::info($request);

            $service = Service::with(["project.onGrid","project.offGridHybrid"])->findOrFail($request->service_id);
            log::info($service);
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
}
