<?php

namespace App\Http\Controllers;
use App\Models\User;
use App\Models\Customer;
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

}
//das