<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Project;

class ProjectController extends Controller
{
     public function getLocation($id)
    {
        $project = Project::find($id);

        if ($project) {
            return response()->json([
                // Use 'lattitude' if your database hasn't been corrected yet
                'latittude' => $project->lattitude, 
                'longitude' => $project->longitude,
            ]);
        }

        return response()->json(['error' => 'Project not found'], 404);
    }
}
