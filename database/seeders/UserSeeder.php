<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    
    public function run(): void
    {
        $users = [
          
            [
                'email' => 'admin@example.com',
                'name'  => 'Admin User',
                'role'  => UserRole::Admin,
            ],

           
            [
                'email' => 'alice@example.com',
                'name'  => 'Alice Teacher',
                'role'  => UserRole::Teacher,
            ],
            [
                'email' => 'bob@example.com',
                'name'  => 'Bob Teacher',
                'role'  => UserRole::Teacher,
            ],
          
            [
                'email' => 'teacher1@example.com',
                'name'  => 'Teacher One',
                'role'  => UserRole::Teacher,
            ],
            [
                'email' => 'teacher2@example.com',
                'name'  => 'Teacher Two',
                'role'  => UserRole::Teacher,
            ],
            [
                'email' => 'teacher3@example.com',
                'name'  => 'Teacher Three',
                'role'  => UserRole::Teacher,
            ],

          
            [
                'email' => 'carol@example.com',
                'name'  => 'Carol Student',
                'role'  => UserRole::Student,
            ],
            [
                'email' => 'dave@example.com',
                'name'  => 'Dave Student',
                'role'  => UserRole::Student,
            ],
            [
                'email' => 'eve@example.com',
                'name'  => 'Eve Student',
                'role'  => UserRole::Student,
            ],
        
            [
                'email' => 'student1@example.com',
                'name'  => 'Student One',
                'role'  => UserRole::Student,
            ],
            [
                'email' => 'student2@example.com',
                'name'  => 'Student Two',
                'role'  => UserRole::Student,
            ],
            [
                'email' => 'student3@example.com',
                'name'  => 'Student Three',
                'role'  => UserRole::Student,
            ],
            [
                'email' => 'student4@example.com',
                'name'  => 'Student Four',
                'role'  => UserRole::Student,
            ],
            [
                'email' => 'student5@example.com',
                'name'  => 'Student Five',
                'role'  => UserRole::Student,
            ],
            [
                'email' => 'student6@example.com',
                'name'  => 'Student Six',
                'role'  => UserRole::Student,
            ],
            [
                'email' => 'student7@example.com',
                'name'  => 'Student Seven',
                'role'  => UserRole::Student,
            ],
        ];

        foreach ($users as $data) {
            User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name'              => $data['name'],
                    'password'          => Hash::make('password'),
                    'role'              => $data['role'],
                    'email_verified_at' => now(),
                ]
            );
        }
    }
}
