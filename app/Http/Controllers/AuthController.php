<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\Customer;
use App\Models\Supervisor;
use App\Models\User;
use App\Models\UserType;
use App\Traits\HttpResponses;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    use HttpResponses;

    public function login(Request $request)
    {
        try{
            $request->validate([
            'email'=>'required|email|exists:users',
            'password'=>'required'
        ]);

        $user = User::where('email', $request->email)->first();
        if(!$user || !Hash::check($request->password,$user->password))
        {
            return $this->error('','Credentials do not match',401);
        }

        if($user){
            $user_type = UserType::where('id',$user->user_type_id)->first();
            $user['user_type'] = $user_type->name;
        }
        
        return $this->success([
            'user'=>$user,
            'token'=>$user->createToken('Api Token of'.$user->name)->plainTextToken
        ]);
        }catch (ValidationException $e) {
            return $this->error('','Invalid Email or Password',422);
        }catch(Exception $e){
            return $this->error('Server Error',$e,500);
        }
    }

    public function register(Request $request)
    {
        try{
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
                Customer::create([
                    'user_id'=>$user->id,
                    'name'=>$user->name
                ]);
                break;
        }
        return $this->success([
            'user'=>$user
        ]);
        }catch (ValidationException $e) {
            return $this->error('','validation error',422);
        }catch(Exception $e){
            return $this->error('Server Error',$e,500);
        }
    }

    public function logout(Request $request)
    {
       try{
         $request->user()->tokens()->delete();
        return $this->success('','You were logged out successfully');
       }catch(Exception $e){
        return $this->error('Server Error',$e,500);
       }
    }

    public function changePassword(Request $request){
        try{
            $request->validate([
                'user_id'=>'required|exists:users,id',
                'oldpassword'=>'required',
                'newpassword'=>'required|confirmed'
            ]);
            $user = User::find($request->user_id);
            Log::info($user);
            if(!$user || !Hash::check($request->oldpassword,$user->password))
        {
            Log::info('here');
            return $this->error('','Credentials do not match',401);
        }
        if($user){
            $user->password = Hash::make($request->newpassword);
            $user->save();
            Log::info('done');
            return $this->success('','Succesfully Updated');
        }
        return $this->error('','User not Found',404);

        }catch(ValidationException $e){
            Log::info($e);
            return $this->error('','validation error',422);
        }catch(Exception $e){
            Log::info($e);
            return $this->error('Server Error',$e,500);
        }
    }
}
