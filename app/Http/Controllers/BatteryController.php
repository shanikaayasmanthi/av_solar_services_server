<?php

namespace App\Http\Controllers;

use App\Models\Battery;
use App\Traits\HttpResponses;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class BatteryController extends Controller
{
    use HttpResponses;
    public function getBatteries(Request $request){
        try{

            $request->validate([
                'off_grid_hybrid_project_id' => 'required|integer|exists:off_grid_hybrids,off_grid_hybrid_project_id',
            ]);

            $batteries = Battery::where('off_grid_hybrid_project_id', $request->input('off_grid_hybrid_project_id'))->where('is_current', true);

            if($batteries){
                return $this->success([
                    'batteries' => $batteries->get()
                ]);
            } else {
                return $this->error('', 'No batteries found for this project.', 404);
            }
                
        }catch(ValidationException $e) {
            return $this->error('', 'Invalid project id', 422);
        }catch(Exception $e){
            return $this->error('Server Error', $e->getMessage(), 500);
        }   
    }

    public function changeBatteries(Request $request){
        try{

            $request->validate([
                'off_grid_hybrid_project_id' => 'required|integer|exists:off_grid_hybrids,off_grid_hybrid_project_id',
                'batteries_to_add' => 'nullable|array',
                'batteries_to_add.*.battery_brand' => 'required|string',
                'batteries_to_add.*.battery_serial_no' => 'required|string',
                'batteries_to_add.*.battery_model' => 'required|string',
                'batteries_to_add.*.battery_capacity' => 'required|numeric',
                'battery_ids_to_remove' => 'nullable|array',
                'battery_ids_to_remove.*' => 'required|integer|exists:batteries,id',
            ]);

            $offGridHybridProjectId = $request->input('off_grid_hybrid_project_id');
            $batteriesToAdd = $request->input('batteries_to_add', []);
            $batteryIdsToRemove = $request->input('battery_ids_to_remove', []);

            // Remove specified batteries
            if(!empty($batteryIdsToRemove)){
                foreach($batteryIdsToRemove as $batteryId){
                    $battery = Battery::find($batteryId)->where('off_grid_hybrid_project_id', $offGridHybridProjectId)->first();
                    if($battery){
                        $battery->is_current = false;
                        $battery->save();
                    }
                }
            }

            // Add new batteries
            if(!empty($batteriesToAdd)){
                foreach($batteriesToAdd as $batteryData){
                    $battery = new Battery();
                    $battery->off_grid_hybrid_project_id = $offGridHybridProjectId;
                    $battery->battery_brand = $batteryData['battery_brand'];
                    $battery->battery_serial_no = $batteryData['battery_serial_no'];
                    $battery->battery_model = $batteryData['battery_model'];
                    $battery->battery_capacity = $batteryData['battery_capacity'];
                    $battery->is_current = true;
                    $battery->save();
                }
            }

            return $this->success('', 'Batteries updated successfully.');
        }
        catch(ValidationException $e) {
            return $this->error('', 'Invalid Request', 422);
        } catch (Exception $e) {
            return $this->error('Server Error', $e->getMessage(), 500);
        }
    }
}
