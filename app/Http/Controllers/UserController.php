<?php

namespace App\Http\Controllers;

use App\Models\CustomerPhoneNo;
use App\Models\User;
use App\Models\Supervisor;
use App\Models\Admin;
use Illuminate\Support\Facades\Hash;
use App\Traits\HttpResponses;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{

    use HttpResponses;
    public function searchSupervisors(Request $request)
    {
        try{
            
        $request->validate([
            'query' => 'required|string|max:255',
        ]);

        $searchTerm = $request->input('query');

        $supervisors = User::where('user_type_id', 2)
            ->where(function ($query) use ($searchTerm) {
                $query->where('name', 'like', '%' . $searchTerm . '%');
            })
            ->get(['id', 'name',]);

            if(!$supervisors){
                return $this->error('', 'No supervisors found', 404);
            }

            return $this->success([
                'supervisors' => $supervisors
            ]);

        }catch(ValidationException $e) {
            return $this->error('', 'Invalid search query', 422);
        }catch(\Exception $e){
            return $this->error('Server Error', $e->getMessage(), 500);
        }
}

public function searchCustomer(Request $request)
    {
        try{
            
            $request->validate([
                'query' => 'required|string|max:255',
            ]);

            $searchTerm = $request->input('query');

            $customer = User::with('customer',)->where('user_type_id', 3)
                ->where(function ($query) use ($searchTerm) {
                    $query->where('email', $searchTerm);
                })->first();

            if(!$customer){
                return $this->error('', 'No customers found', 404);
            }

            $phoneNumbers = CustomerPhoneNo::where('customer_id', $customer->id)
                ->pluck('phone_no')
                ->toArray();
                

            $data['id']= $customer->id;
            $data['name'] = $customer->name;
            $data['email'] = $customer->email;
            $data['address'] = $customer->customer->address ?? 'no address';
            $data['phone_numbers'] = $phoneNumbers ?? null;


            return $this->success([
                'customer' => $data
            ]);

        }catch(ValidationException $e) {
            return $this->error('', 'Invalid search query', 422);
        }catch(\Exception $e){
            return $this->error('Server Error', $e->getMessage(), 500);
        }
    }
   
    public function getAllUsersWithTypeAndStatus()
{
    try {
        $users = User::with('userType') 
            ->select('id', 'name', 'email', 'is_active', 'user_type_id')
            ->get()
            ->map(function ($user) {
                return [
                    'id'         => $user->id,
                    'name'       => $user->name,
                    'email'      => $user->email,
                    'is_active'  => (bool) $user->is_active,
                    'user_type'  => $user->userType->name ?? 'Unknown',
                ];
            });

        return $this->success([
            'users' => $users
        ]);
    } catch (\Exception $e) {
        return $this->error('Server Error', $e->getMessage(), 500);
    }
}

public function store(Request $request)
{
    $request->validate([
        'name' => 'required|string',
        'email' => 'required|email|unique:users',
        'nic' => 'required|string|',
        'phone' => 'required|string',
        'user_type_id' => 'required|integer|exists:user_types,id',
    ]);

    // Create user
    $user = User::create([
        'name' => $request->name,
        'email' => $request->email,
        'password' => Hash::make('user123'), // default or generated password
        'user_type_id' => $request->user_type_id,
        'is_active' => true,
    ]);

    // Save extra info based on role
    if ($request->user_type_id == 2) { // Supervisor
        Supervisor::create([
            'user_id' => $user->id,
            'name' => $request->name,
            'nic' => $request->nic,
            'phone' => $request->phone,
        ]);
    } elseif (in_array($request->user_type_id, [1, 4])) { // Admin or Super Admin
        Admin::create([
            'user_id' => $user->id,
            'name' => $request->name,
            'nic' => $request->nic,
            'phone' => $request->phone,
        ]);
    }

    return response()->json([
        'message' => 'User created successfully',
        'user' => $user,
    ]);
}

//method to get profile details
public function getSupervisorProfile($userId)
{
    try{
        //find user 
        $user = User::with('supervisor','userType')->find($userId);

        if (!$user || !$user->supervisor) {
            return response()->json([
                'success' => false,
                'message' => 'User profile not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'=> [
                'user_id'   => $user->supervisor->user_id,
                'name'=>$user->supervisor->name,
                'email'=> $user->email,
                'phone'=> $user->supervisor->phone,
                'address'=> $user->supervisor->address ?? null,
                'nic'=> $user->supervisor->nic,
                'user_type'=> $user->userType ? $user->userType->name : null,
            ],
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Server Error',
            'error' => $e->getMessage()
        ], 500);
    }
}



  
}