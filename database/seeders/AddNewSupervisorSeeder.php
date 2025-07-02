<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Supervisor;
use Illuminate\Support\Facades\Hash;

class AddNewSupervisorSeeder extends Seeder
{
    public function run()
    {
        // Create the user in the users table
        $user = User::create([
            'id' => 5,
            'name' => 'John Doe', 
            'email' => 'john123@gmail.com',
            'password' => Hash::make('Supervisor123'),
            'user_type_id' => 2, 
        ]);

        // Create the supervisor record in the supervisors table
        Supervisor::create([
            'user_id' => $user->id,
            'name' => 'John Doe', // Supervisor's name
            'nic' => '123456789V', // National ID or NIC
            'phone' => '0771234567', // Phone number
        ]);
    }
}