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
    public function getInvertors(Request $request)
    {
        try {
            $request->validate([
                'project_id' => 'required|integer|exists:projects,id',
            ]);

            $invertors = Invertor::where('project_id', $request->input('project_id'))->where('is_current', true);

            if ($invertors) {
                return $this->success([
                    'inverters' => $invertors->get()
                ]);
            } else {
                return $this->error('', 'No invertors found for this project.', 404);
            }
        } catch (ValidationException $e) {
            return $this->error('', 'Invalid Request', 422);
        } catch (Exception $e) {
            return $this->error('Server Error', $e, 500);
        }
    }

    public function changeInverters(Request $request)
    {
        try {

            $request->validate([
                'project_id' => 'required|integer|exists:projects,id',
                'inverter_ids_to_remove' => 'nullable|array',
                'inverter_ids_to_remove.*' => 'required|integer|exists:invertors,id',
                'inverters_to_add' => 'nullable|array',
                'inverters_to_add.*.brand' => 'required|string',
                'inverters_to_add.*.model_no' => 'required|string',
                'inverters_to_add.*.check_code' => 'required|string',
                'inverters_to_add.*.serial_no' => 'required|string',
                'inverters_to_add.*.capacity' => 'required|numeric',
            ]);

            $projectId = $request->input('project_id');
            $invertorsToRemove = $request->input('inverter_ids_to_remove', []);
            $invertersToAdd = $request->input('inverters_to_add', []);

            // Remove specified invertors
            if (!empty($invertorsToRemove)) {
                foreach ($invertorsToRemove as $invertorId) {
                    $invertor = Invertor::find($invertorId);
                    if ($invertor) {
                        $invertor->is_current = false;
                        $invertor->save();
                    }
                }
            }

            // Add new invertors
            if (!empty($invertersToAdd)) {
                foreach ($invertersToAdd as $invertorData) {
                    Invertor::create([
                        'project_id' => $projectId,
                        'invertor_model_no' => $invertorData['model_no'],
                        'invertor_check_code' => $invertorData['check_code'],
                        'invertor_serial_no' => $invertorData['serial_no'],
                        'brand' => $invertorData['brand'],
                        'invertor_capacity' => $invertorData['capacity'],
                        'is_current' => true,
                    ]);
                }

            }

            return $this->success('', 'Inverters updated successfully.');

        } catch (ValidationException $e) {
            return $this->error('', 'Invalid Request', 422);
        } catch (Exception $e) {
            return $this->error('Server Error', $e, 500);
        }
    }


}
