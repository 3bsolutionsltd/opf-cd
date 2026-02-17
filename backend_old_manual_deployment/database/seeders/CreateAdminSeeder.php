<?php
// Run this with: php artisan db:seed --class=CreateAdminSeeder

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class CreateAdminSeeder extends Seeder
{
    public function run()    {
        User::create([
            'email' => 'admin@opf-cd.test',
            'password_hash' => Hash::make('Admin123!@#'),
            'role' => 'admin',
            'is_active' => true,
        ]);
        
        echo "Admin user created successfully!\n";
    }
}
