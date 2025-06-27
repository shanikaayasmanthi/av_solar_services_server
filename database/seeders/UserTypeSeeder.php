<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\UserType;


class UserTypeSeeder extends Seeder
{
    
    /**
     * Run the database seeds.
     */
    public function run()
    {
        UserType::insert([
            ['name'=>"admin"],
            ['name'=>"supervisor"],
            ['name'=>"customer"],
            ['name'=>"Super Admin"]
        ]);
    }
}
