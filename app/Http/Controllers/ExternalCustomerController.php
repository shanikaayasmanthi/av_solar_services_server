<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ExternalCustomer;
use Illuminate\Support\Facades\Validator;

class ExternalCustomerController extends Controller
{
   //add new external customer
    public function store(Request $request)
    {
        // Validate request input
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'nic' => 'required|string|max:20|unique:external_customer,nic',
            'email' => 'required|email|max:255|unique:external_customer,email',
            'phone_no' => 'required|string|max:15',
            'address' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // Create the new external customer
            $customer = ExternalCustomer::create([
                'name' => $request->name,
                'nic' => $request->nic,
                'email' => $request->email,
                'phone_no' => $request->phone_no,
                'address' => $request->address,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'External customer added successfully',
                'data' => $customer,
            ], 201);
        } catch (\Exception $e) {
            // Catch and return error
            return response()->json([
                'success' => false,
                'message' => 'Failed to add customer',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    public function findExternalCustomer(Request $request)
{
    // Validate input
    $validator = Validator::make($request->all(), [
        'email' => 'nullable|email',
        'name' => 'nullable|string|max:255',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'message' => 'Validation error',
            'errors' => $validator->errors(),
        ], 422);
    }

    // Ensure at least one search parameter is provided
    if (!$request->email && !$request->name) {
        return response()->json([
            'success' => false,
            'message' => 'Please provide either an email or name to search.',
        ], 400);
    }

    // Query based on input
    $query = ExternalCustomer::query();

    if ($request->email) {
        $query->orWhere('email', $request->email);
    }

    if ($request->name) {
        $query->orWhere('name', 'LIKE', '%' . $request->name . '%');
    }

    $results = $query->get();

    if ($results->isEmpty()) {
        return response()->json([
            'success' => false,
            'message' => 'No external customer found.',
        ], 404);
    }

    return response()->json([
        'success' => true,
        'message' => 'External customer(s) found.',
        'data' => $results,
    ]);
}

}
