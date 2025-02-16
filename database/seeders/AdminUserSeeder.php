<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\User;
use Illuminate\Database\Seeder;

use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $adminEmail = env('ADMIN_DEFAULT_EMAIL', 'admin@example.com');
    
        if (!User::where('email', $adminEmail)->exists()) {
            $user = User::create([
                'name' => 'Admin User',
                'email' => $adminEmail,
                'password' => Hash::make(env('ADMIN_DEFAULT_PASSWORD', 'password')),
                'role' => 'admin',
                'approval_status' => 'approved',
            ]);
    
            $user->save();
        }
    }
}