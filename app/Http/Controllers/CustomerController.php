<?php

namespace App\Http\Controllers;
use App\Models\User;
use App\Models\Customer;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class CustomerController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users',
            'address' => 'required|string',
            'phone' => 'required|string',
        ]);

        DB::beginTransaction();

        try {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make('user123'), // default password
                'user_type_id' => 3 
            ]);

            // 2. Create customer record
            $customer = Customer::create([
                'user_id' => $user->id,
                'name' => $request->name,
                'address' => $request->address,
                'phone' => $request->phone
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Customer created successfully.',
                'customer' => $customer
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Error creating customer',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function find(Request $request) {
    $keyword = $request->input('keyword');

    $user = User::where('email', $keyword)
               ->orWhere('name', 'LIKE', "%{$keyword}%")
               ->first();

    if (!$user) {
        return response()->json(['message' => 'Customer not found'], 404);
    }

    $customer = Customer::where('user_id', $user->id)->first();

    return response()->json([
        'id' => $customer->id,
        'name' => $user->name,
        'email' => $user->email,
        'address' => $customer->address,
        'telephone' => $customer->phone
    ]);
}


 public function openProject(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,user_id',
            'type' => 'required|in:on_grids,offgridhybrids',
            'project_name' => 'nullable|string',
            'project_address' => 'required|string',
            'neatest_town' => 'required|string',
            'no_of_panels' => 'required|integer|min:1',
            'panel_capacity' => 'numeric|min:0.1',
            'service_years_in_agreement' => 'required|integer|min:1',
            'service_rounds_in_agreement' => 'required|integer|min:1',
            'project_installation_date' => 'required|date',
            'longitude' => 'nullable|numeric',
            'lattitude' => 'nullable|numeric',
            'location' => 'nullable|string',
            'remarks' => 'nullable|string',
        ]);

        $project = Project::create([
            'customer_id' => $request->customer_id,
            'type' => $request->type,
            'project_name' => $request->project_name,
            'project_address' => $request->project_address,
            'neatest_town' => $request->neatest_town,
            'no_of_panels' => $request->no_of_panels,
            'panel_capacity' => $request->panel_capacity,
            'service_years_in_agreement' => $request->service_years_in_agreement,
            'service_rounds_in_agreement' => $request->service_rounds_in_agreement,
            'project_installation_date' => $request->project_installation_date,
            'system_on' => now(), // or set it explicitly
            'longitude' => $request->longitude,
            'lattitude' => $request->lattitude,
            'location' => $request->location,
            'remarks' => $request->remarks,
        ]);

        return response()->json([
            'message' => 'Project created successfully.',
            'project' => $project
        ], 201);
    }
    

    public function getAllProjects()
{
    try {
        $projects = Project::with(['customer'])   
            ->orderBy('created_at', 'desc')
            //->paginate(6);
             ->get();

        return response()->json([
            'status' => 'success',
            'projects' => $projects
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Failed to retrieve projects',
        ], 500);
    }
}

}
//das