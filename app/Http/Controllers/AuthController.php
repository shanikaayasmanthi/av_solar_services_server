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
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Password;
use App\Mail\PasswordResetMail;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\RateLimiter;

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

        if (!$user->is_active) {
        return $this->error('','Your account has been deactivated. Please contact administrator.',403);
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
                    'name'=>$user->name,
                    'nic'=>$request['nic'] ?? null, 
                    'phone'=>$request['phone'] ?? null,
                    'address'=>$request['address'] ?? null
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
        Log::info('logout called');
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

        private $tokenExpiryMinutes = 60; // 60 min expiry
    private $maxRequestsPerMinute = 4; // for forgot requests

public function forgotPassword(Request $request) {
     $request->validate(['email' => 'required|email|exists:users,email']);
      $email = $request->email; 
      // Rate limiting per email or IP
       $key = 'forgot-password|' . $email;
        if (RateLimiter::tooManyAttempts($key, $this->maxRequestsPerMinute)) {
             return response()->json([
                 'status' => 'error',
                  'message' => 'Too many password reset requests. Please try again later.'
                 ], 429); } 
                 RateLimiter::hit($key, 60); // keep for 60 seconds 
                 // Generate token
                 $plainToken = Str::random(60); 
                 // Hash token before saving (avoid storing plaintext tokens)
                  $hashedToken = Hash::make($plainToken);
                   // Save hashed token and created_at 
                   DB::table('password_resets')->updateOrInsert( 
                    ['email' => $email], [ 
                        'email' => $email,
                         'token' => $hashedToken,
                          'created_at' => Carbon::now()
                           ]
                         ); 
                         
                         // Build reset URL (frontend must have route that accepts token + email)
                          $frontendResetUrl = config('app.frontend_url') ?? 
                          env('FRONTEND_URL', null); 
                          if (!$frontendResetUrl) {
                            
                            // fallback to same site route if you host frontend in the same app
                             $frontendResetUrl = config('app.url');
                             }
                             
                             // include plain token in link — this is the only place the plain token travels (via email)
                              $resetUrl = rtrim($frontendResetUrl, '/') . '/reset-password?token=' . urlencode($plainToken) . '&email=' . urlencode($email);
                               // Send email (do not return token in API response)
                                try {
                                     Mail::to($email)->send(new PasswordResetMail($resetUrl, $this->tokenExpiryMinutes));
                                     } 
                                catch (\Exception $e) {
                                     \Log::error('Password reset mail failed: ' . $e->getMessage());
                                      \Log::error($e->getTraceAsString()); return response()->json([ 'status' => 'error', 'message' => 'Could not send reset email. Check logs for details.' ], 500);
                                     } return response()->json([ 'status' => 'success', 'message' => 'If that email exists in our system, a password reset link has been sent.' ]);
                                     }
                                     
public function resetPassword(Request $request) { 
    // Validate payload
     $request->validate([
         'email' => 'required|email|exists:users,email',
          'token' => 'required|string',
          'password' => 'required|confirmed|min:8',
         ]); 
         
         $email = $request->email;
          $plainToken = $request->token;
          
          $record = DB::table('password_resets')->where('email', $email)->first();
          
          if (!$record) { return response()->json(['status' => 'error', 'message' => 'Invalid or expired token.'], 400);
         }
          // Check expiry
           $createdAt = Carbon::parse($record->created_at);
            if (Carbon::now()->diffInMinutes($createdAt) > $this->tokenExpiryMinutes)
                 { 
                    // Delete token
                     DB::table('password_resets')->where('email', $email)->delete();
                      return response()->json(['status' => 'error', 'message' => 'Token expired. Please request a new password reset.'], 400); 
                    } 
                    
                    // Verify hashed token
                     if (!Hash::check($plainToken, $record->token)) { 
                        return response()->json(['status' => 'error', 'message' => 'Invalid token.'], 400);
                     }
                     
                     // All good — update password
                      $user = User::where('email', $email)->firstOrFail();
                       $user->password = Hash::make($request->password);
                        $user->save(); 
                        // Invalidate token (single use)
                          DB::table('password_resets')->where('email', $email)->delete(); return response()->json(['status' => 'success', 'message' => 'Password reset successfully.']);
                         } 

}
