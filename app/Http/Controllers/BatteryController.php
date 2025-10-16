<?php

namespace App\Http\Controllers;

use App\Models\Battery;
use App\Traits\HttpResponses;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\Models\Project;

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

  
public function addBatteries(Request $request)
{
    try {
        \Log::info('Add Batteries Request:', $request->all());
        
        $validated = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'batteries' => 'required|array|min:1',
            'batteries.*.brand' => 'required|string|max:255',
            'batteries.*.model_code' => 'required|string|max:255',
            'batteries.*.serial_no' => 'required|string|max:255',
            'batteries.*.capacity' => 'required|numeric|min:0.1',
        ]);

        $project = Project::with('offGridHybrid')->findOrFail($validated['project_id']);
        
        if ($project->isInstalled) {
            throw new \Exception('Cannot add batteries to installed projects');
        }

        if (!$project->offGridHybrid) {
            throw new \Exception('Project is not an off-grid/hybrid project');
        }

        $createdBatteries = [];
        foreach ($validated['batteries'] as $batteryData) {
            $battery = Battery::create([
                'off_grid_hybrid_project_id' => $project->offGridHybrid->off_grid_hybrid_project_id,
                'battery_brand' => $batteryData['brand'],
                'battery_model' => $batteryData['model_code'],
                'battery_serial_no' => $batteryData['serial_no'],
                'battery_capacity' => $batteryData['capacity'],
                'is_current' => true
            ]);
            
            $createdBatteries[] = $battery;
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Batteries added successfully',
            'data' => $createdBatteries
        ]);

    } catch (ValidationException $e) {
        \Log::error('Validation Error:', $e->errors());
        return response()->json([
            'status' => 'error',
            'message' => 'Validation failed',
            'errors' => $e->errors()
        ], 422);
    } catch (\Exception $e) {
        \Log::error('Server Error: ' . $e->getMessage());
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage()
        ], 500);
    }
}


    
}
