<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\Customer;
use App\Models\Supervisor;
use App\Models\User;
use App\Models\UserType;
use App\Traits\HttpResponses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    use HttpResponses;

    public function login(Request $request)
    {
        $request->validate([
            'email'=>'required|email|exists:users',
            'password'=>'required'
        ]);

        $user = User::where('email', $request->email)->first();
        if(!$user || !Hash::check($request->password,$user->password))
        {
            return $this->error('','Credentials do not match',401);
        }

        return $this->success([
            'user'=>$user,
            'token'=>$user->createToken('Api Token of'.$user->name)->plainTextToken
        ]);
    }

    public function register(Request $request)
    {
        $request->validate([
            'name'=>'required|max:255',
            'email'=>'required|email|unique:users',
            'user_type' => 'required|string|exists:user_types,name',
            'password'=>'required|confirmed'
        ]);

        $userTypeID = UserType::where('name',$request['user_type'])->firstOrFail()->id;

        $fields = [
            'name'=>$request['name'],
            'email'=>$request['email'],
            'password'=>$request['password'],
            'user_type_id'=>$userTypeID
        ];

        $user = User::create($fields);

        switch($request['user_type'])
        {
            case 'admin':
                Admin::create([
                    'user_id'=>$user->id,
                    'name'=>$user->name
                ]);
                break;
            case 'supervisor':
                Supervisor::create([
                    'user_id'=>$user->id,
                    'name'=>$user->name
                ]);
                break;
            case 'customer':
                Customer::craete([
                    'user_id'=>$user->id,
                    'name'=>$user->name
                ]);
                break;
        }
        return $this->success([
            'user'=>$user
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();
        return $this->success('','You were logged out successfully');
    }
}
