<?php

namespace App\Http\Controllers;

use App\Models\UserType;
use Illuminate\Http\Request;

class UserTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    // public function index()
    // {
    //     //
    // }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(userType $userType)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, userType $userType)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(userType $userType)
    {
        //
    }

    public function index(){

    $types = UserType::all(['id', 'name']);
    return response()->json([
        'status' => 'success',
        'data' => ['user_types' => $types]
    ]);
}

}
