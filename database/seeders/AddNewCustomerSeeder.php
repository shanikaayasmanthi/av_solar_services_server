<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AddNewCustomerSeeder extends Seeder
{
    public function run()
    {
        User::create([
            'id' => 4,
            'name' => 'Nimal Perera', // You can set a name; adjust as needed
            'email' => 'nimal123@gmail.com',
            'password' => Hash::make('nimal123'), // Hashes the password
            'user_type_id' => 3, // Replace with the actual customer user_type_id from Step 1
        ]);
    }
}