<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\User;
use Illuminate\Database\Seeder;

class AddPrimaryAdmin extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminEmail = env('ADMIN_DEFAULT_EMAIL', 'admin@example.com');
    
        $user = User::where('email', $adminEmail)->first();
        if ($user && !$user->is_primary_admin) {
            $user->is_primary_admin = true;
            $user->save();
        }
    }
}
