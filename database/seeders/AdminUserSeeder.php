<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $superAdmin = User::updateOrCreate(
            ['email' => 'sadanand@ioepc.edu.np'],
            [
                'name'              => 'Super Admin',
                'password'          => Hash::make('S@ddy9843521965@@'),
                'email_verified_at' => now(),
                'is_active'         => true,
            ]
        );
        $superAdmin->syncRoles(['super-admin']);

    }
}
