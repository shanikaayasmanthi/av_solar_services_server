<?php

namespace App\Http\Controllers;

use App\Models\Invertor;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\Traits\HttpResponses;
use Exception;

class InvertorController extends Controller
{
    use HttpResponses;
    public function getInvertors(Request $request){
        try{
            $request->validate([
                'project_id' => 'required|integer|exists:projects,id',
            ]);

            $invertors = Invertor::where('project_id', $request->input('project_id'));

            if($invertors){
                return $this->success([
                    'inverters' => $invertors->get()
                ]);
            } else {
                return $this->error('', 'No invertors found for this project.', 404);
            }
        }catch(ValidationException $e) {
            return $this->error('', 'Invalid Request', 422);
        }catch(Exception $e){
            return $this->error('Server Error', $e, 500);
        }
    }
}
