<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\OnGrid;
use App\Models\OffGridHybrid;
use App\Traits\HttpResponses;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Exception;

class ExternalProjectController extends Controller
{
    use HttpResponses;

    /**
     * Get external project details by project_id
     */
        public function getAllExternalProjects(Request $request)
    {
        try {

            $query = Project::with(['onGrid', 'offGridHybrid'])
            ->where('External/Internal', 'External');

            $type = Str::lower($request->input('type'));
            $searchTerm =  $request->input('query', '');
           

            // if (!empty($searchTerm)) {
            //     $query->where('project_name', 'like', '%' . $searchTerm . '%');
            // }
            if (!empty($searchTerm)) {
                $query->where(function ($q) use ($searchTerm) {
                    // Search in project_name and project_address on the projects table
                    $q->where('project_name', 'like', '%' . $searchTerm . '%')
                      ->orWhere('project_address', 'like', '%' . $searchTerm . '%');

                    // Search project_no in the related onGrid table
                    $q->orWhereHas('onGrid', function ($onGridQuery) use ($searchTerm) {
                        $onGridQuery->where('on_grid_project_id', 'like', '%' . $searchTerm . '%');
                    });

                    // Search project_no in the related offGridHybrid table
                    $q->orWhereHas('offGridHybrid', function ($offGridQuery) use ($searchTerm) {
                        $offGridQuery->where('off_grid_hybrid_project_id', 'like', '%' . $searchTerm . '%');
                    });
                });
            }

            if($type==''){
                $projects = $query->orderBy('created_at', 'asc')->paginate(6);

                
            }else{
                $projects = $query->where('type', $type)
                    ->orderBy('created_at', 'desc')
                    ->paginate(6);
            }

            
            return $this->success([
                'projects' => $projects
            ]);
        }catch (ValidationException $e) {
            return $this->error('', 'Validation Error', 404);
        }catch (Exception $e) {
            return $this->error('', 'Error occurred', 500);
        }
    }


}