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

            $batteries = Battery::where('off_grid_hybrid_project_id', $request->input('off_grid_hybrid_project_id'));

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
}
