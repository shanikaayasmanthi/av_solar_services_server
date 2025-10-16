<?php

namespace App\Http\Controllers;
use App\Models\User;
use App\Models\Customer;
use App\Models\CustomerPhoneNo;
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
        'phone_numbers' => 'required|array|min:1',
        'phone_numbers.*' => 'required|string|distinct|min:5|max:20'
    ]);

    DB::beginTransaction();

    try {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make('user123'),
            'user_type_id' => 3 
        ]);

        $customer = Customer::create([
            'user_id' => $user->id,
            'name' => $request->name,
            'address' => $request->address,
        ]);

        if(!$customer){
            throw new \Exception('Customer creation failed');
        }

        // Create phone numbers with proper timestamp handling
        foreach ($request->phone_numbers as $phoneNumber) {
            CustomerPhoneNo::create([
                'customer_id' => $customer->user_id, // Use user_id as customer_id
                'phone_no' => $phoneNumber,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        
        DB::commit();

        return response()->json([
            'message' => 'Customer created successfully.',
            'customer' => $customer,
            'user_id' => $user->id
        ], 201);

    } catch (\Exception $e) {
        DB::rollBack();
        \Log::error('Customer creation error: ' . $e->getMessage());
        
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

 //Get customer details for non-installed projects

public function getCustomersForNonInstalledProjects(Request $request)
{
    try {
       
        $request->validate([
            'project_id' => 'nullable|exists:projects,id',
            'customer_id' => 'nullable|exists:customers,user_id'
        ]);

        
        $query = Project::with([
                'customer.user', 
                'customer.customerPhoneNo'
            ])
            ->where('isInstalled', false);

        
        if ($request->has('project_id')) {
            $query->where('id', $request->project_id);
        }

        if ($request->has('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

    
        $projects = $query->get();

        if ($projects->isEmpty()) {
            return response()->json([
                'status' => 'success',
                'message' => 'No non-installed projects found',
                'customers' => []
            ]);
        }

        
        $customers = $projects->map(function ($project) {
            return [
                'project_id' => $project->id,
                'project_name' => $project->project_name,
                'customer_id' => $project->customer_id,
                'customer_name' => $project->customer->name ?? $project->customer->user->name,
                'email' => $project->customer->user->email,
                'address' => $project->customer->address,
                'telephone_numbers' => $project->customer->customerPhoneNo->pluck('phone_no')->toArray()
            ];
        });

        return response()->json([
            'status' => 'success',
            'customers' => $customers
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Failed to retrieve customer details',
            'error' => $e->getMessage()
        ], 500);
    }
} public function updateCustomerDetails(Request $request)
{
    $request->validate([
        'project_id' => 'required|exists:projects,id',
        'name' => 'required|string|max:255',
        'email' => 'required|email',
        'address' => 'required|string',
        'phone_numbers' => 'required|array|min:1',
        'phone_numbers.*' => 'required|string|min:5|max:20'
    ]);

    DB::beginTransaction();

    try {
    
        $project = Project::with('customer.user', 'customer.customerPhoneNo')
                        // ->where('isInstalled', false)
                        ->findOrFail($request->project_id);

        
        $project->customer->user->update([
            'name' => $request->name,
            'email' => $request->email
        ]);

        
        $project->customer->update([
            'name' => $request->name,
            'address' => $request->address
        ]);

        // Update phone numbers - delete old and create new
        $project->customer->customerPhoneNo()->delete();
        
        $phoneNumbers = [];
        foreach ($request->phone_numbers as $phone) {
            $phoneNumbers[] = [
                'customer_id' => $project->customer_id,
                'phone_no' => $phone,
                'created_at' => now(),
                'updated_at' => now()
            ];
        }
        CustomerPhoneNo::insert($phoneNumbers);

        DB::commit();

        return response()->json([
            'status' => 'success',
            'message' => 'Customer details updated successfully',
            'customer' => [
                'project_id' => $project->id,
                'customer_name' => $request->name,
                'email' => $request->email,
                'address' => $request->address,
                'telephone_numbers' => $request->phone_numbers
            ]
        ]);

    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'status' => 'error',
            'message' => 'Failed to update customer details',
            'error' => $e->getMessage()
        ], 500);
    }
}

}
