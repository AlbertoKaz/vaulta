<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoUserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'name' => 'Alberto',
                'email' => 'alberto@example.com',
                'password' => 'password1',
            ],
            [
                'name' => 'Judith',
                'email' => 'judith@example.com',
                'password' => 'password2',
            ],
            [
                'name' => 'John',
                'email' => 'john@example.com',
                'password' => 'password3',
            ],
            [
                'name' => 'Lucy',
                'email' => 'lucy@example.com',
                'password' => 'password4',
            ],
            [
                'name' => 'Mike',
                'email' => 'mike@example.com',
                'password' => 'password5',
            ],
        ];

        foreach ($users as $user) {
            User::updateOrCreate(
                ['email' => $user['email']],
                [
                    'name' => $user['name'],
                    'password' => Hash::make($user['password']),
                    'email_verified_at' => now(),
                ]
            );
        }
    }
}
