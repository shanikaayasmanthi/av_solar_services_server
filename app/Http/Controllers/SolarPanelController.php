<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SolarPanel;
use App\Traits\HttpResponses;
use Exception;
use Illuminate\Validation\ValidationException;

class SolarPanelController extends Controller
{

    use HttpResponses;
    public function getSolarPanels(Request $request){
        try{

            $request->validate([
            'project_id' => 'required|integer|exists:projects,id',
        ]);

        $solarPanels = SolarPanel::where('project_id', $request->input('project_id'));

        if($solarPanels) {
            return $this->success([
                'solar_panels' => $solarPanels->get()
            ]);
;        } else {
            return $this->error('',
                'No solar panels found for this project.',
                404
            );
        }

        }catch(ValidationException $e) {
            return $this->error('','Invalid Project ID',422);
        }catch(Exception $e) {
            return $this->error('Server Error',$e,500);
        }
    }
    
}
