<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;
use App\Models\SolarPanel;
use App\Traits\HttpResponses;
use Exception;
use Illuminate\Validation\ValidationException;

class SolarPanelController extends Controller
{

    use HttpResponses;
    public function getSolarPanels(Request $request)
    {
        try {

            $request->validate([
                'project_id' => 'required|integer|exists:projects,id',
            ]);

            $solarPanels = SolarPanel::where('project_id', $request->input('project_id'))->where('is_current', true);

            if ($solarPanels) {
                return $this->success([
                    'solar_panels' => $solarPanels->get()
                ]);;
            } else {
                return $this->error(
                    '',
                    'No solar panels found for this project.',
                    404
                );
            }
        } catch (ValidationException $e) {
            return $this->error('', 'Invalid Project ID', 422);
        } catch (Exception $e) {
            return $this->error('Server Error', $e, 500);
        }
    }

    public function addNewSolarPanels(Request $request)
    {
        try {
            $request->validate([
                'project_id' => 'required|integer|exists:projects,id',
                'total_panels' => 'required|integer|min:1',
                'panel_sets' => 'required|array',
                'panel_sets.*.model' => 'required|string',
                'panel_sets.*.modelCode' => 'required|string',
                'panel_sets.*.type' => 'required|string',
                'panel_sets.*.numPanels' => 'required|integer|min:1',
                'panel_sets.*.wattage' => 'required|numeric|min:0',
                'option' => 'required|string'
            ]);

            $solarPanels = $request->panel_sets;
            $project = Project::find($request->project_id);
            if (!$project) {
                return $this->error('', 'Project not found', 404);
            }

            if ($request->option === 'expanding') {
                $result = false;
                foreach ($solarPanels as $panelSet) {
                    $result = SolarPanel::create([
                        'project_id' => $request->project_id,
                        'solar_panel_model' => $panelSet['model'],
                        'panel_model_code' => $panelSet['modelCode'],
                        'panel_type' => $panelSet['type'],
                        'no_of_panels' => $panelSet['numPanels'],
                        'wattage_of_pannel' => $panelSet['wattage'],
                    ]);
                }

                if ($result) {
                    $total_panels = $project->no_of_panels + $request->total_panels;
                    $project->no_of_panels = $total_panels;
                    $projectResult = $project->save();

                    if ($projectResult) {
                        return $this->success([
                            'message' => 'Solar panels added successfully',
                        ]);
                    } else {
                        return $this->error('', 'Failed to update project panel count', 500);
                    }
                }
            } elseif ($request->option === 'add') {
                $existingpanels = SolarPanel::where('project_id', $request->project_id);
                $existingpanelCount = 0;
                foreach ($existingpanels->get() as $panel) {
                    $existingpanelCount += $panel->no_of_panels;
                }; 
                if ($project->no_of_panels-$existingpanelCount >= $request->total_panels) {

                    foreach ($solarPanels as $panelSet) {
                        $result = SolarPanel::create([
                            'project_id' => $request->project_id,
                            'solar_panel_model' => $panelSet['model'],
                            'panel_model_code' => $panelSet['modelCode'],
                            'panel_type' => $panelSet['type'],
                            'no_of_panels' => $panelSet['numPanels'],
                            'wattage_of_pannel' => $panelSet['wattage'],
                        ]);
                    }
                }else{
                    return $this->error('', 'Total panels exceed project capacity', 400);
                }


                if ($result) {
                    return $this->success([
                        'message' => 'Solar panels added successfully',
                    ]);
                } else {
                    return $this->error('', 'Failed to add solar panels', 500);
                }
            }
        } catch (ValidationException $e) {
            return $this->error('Invalid Input', $e, 422);
        } catch (Exception $e) {
            return $this->error('Server Error', $e, 500);
        }
    }

    public function changeSolarPanels(Request $request)
    {
        try {

            $request->validate([
                'project_id' => 'required|integer|exists:projects,id',
                'total_panels_to_change' => 'required|integer|min:1',
                'panels_to_remove' => 'required|array',
                'panels_to_remove.*.id' => 'required|integer|exists:solar_panels,id',
                'panels_to_remove.*.numPanels' => 'required|integer|min:1',
                'panels_to_add'=> 'nullable|array',
                'panels_to_add.*.model' => 'required|string',
                'panels_to_add.*.modelCode' => 'required|string',
                'panels_to_add.*.type' => 'required|string',
                'panels_to_add.*.numPanels' => 'required|integer|min:1',
                'panels_to_add.*.wattage' => 'required|numeric|min:0',
                'option' => 'required|string'

            ]);

            if($request->option !== 'changing')
                {
                    return $this->error('', 'Invalid option provided', 400);
                }

            $project = Project::find($request->project_id);
            if (!$project) {
                return $this->error('', 'Project not found', 404);}

            $panelsToRemove = $request->panels_to_remove;
            $totalPanelsToRemove = 0;
            foreach ($panelsToRemove as $panel) {
                $totalPanelsToRemove += $panel['numPanels'];
                $solarPanel = SolarPanel::find($panel['id']);
                if ($solarPanel) {
                    $existingPanelCount = $solarPanel->no_of_panels - $panel['numPanels'];
                    if ($existingPanelCount > 0) {
                        $solarPanel->no_of_panels = $panel['numPanels'];
                        $solarPanel->is_current = false; 
                        $solarPanel->save();

                        SolarPanel::create([
                            'project_id' => $request->project_id,
                            'solar_panel_model' => $solarPanel->solar_panel_model,
                            'panel_model_code' => $solarPanel->panel_model_code,
                            'panel_type' => $solarPanel->panel_type,
                            'no_of_panels' => $existingPanelCount,
                            'wattage_of_pannel' => $solarPanel->wattage_of_pannel,
                        ]);
                    }else if($existingPanelCount === 0) {
                        $solarPanel->is_current = false;
                        $solarPanel->save();
                    }
                }
            }
            $totalPanelsToAdd = 0;
            if($request->panels_to_add){
            
                $panelsToAdd = $request->panels_to_add;
                
                $result = false;
                foreach ($panelsToAdd as $panelSet) {
                    $totalPanelsToAdd += $panelSet['numPanels'];
                    $result = SolarPanel::create([
                        'project_id' => $request->project_id,
                        'solar_panel_model' => $panelSet['model'],
                        'panel_model_code' => $panelSet['modelCode'],
                        'panel_type' => $panelSet['type'],
                        'no_of_panels' => $panelSet['numPanels'],
                        'wattage_of_pannel' => $panelSet['wattage'],
                    ]);
                }

                
            }
            $project->no_of_panels = $project->no_of_panels - $totalPanelsToRemove + $totalPanelsToAdd;
                $projectResult = $project->save();
                if ($projectResult) {
                    return $this->success([
                        'message' => 'Solar panels changed successfully',
                    ]);
                } else {
                    return $this->error('', 'Failed to change solar panels', 500);
                }
        }catch (ValidationException $e) {
            return $this->error('Invalid Input', $e, 422);
        } catch (Exception $e) {
            return $this->error('Server Error', $e, 500);
        }
    }
}
