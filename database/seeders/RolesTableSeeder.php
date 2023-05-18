<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Log;

class RolesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = array('Admin', 'Manager', 'Designer', 'Planner', 'Client');
        foreach($roles as $role){
            Role::create([
                'name'  => $role
            ]);
        }
    }
}
