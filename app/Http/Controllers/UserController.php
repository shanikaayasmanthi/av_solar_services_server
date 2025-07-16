<?php

namespace App\Http\Controllers;

use App\Models\CustomerPhoneNo;
use App\Models\User;
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
}