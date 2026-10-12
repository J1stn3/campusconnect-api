<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(['email' => 'admin@university.edu'], [   
            'name' => 'System Administrator',
            'password' => Hash::make('admin@2025'),
            'role' => 'admin',
        ]);

        $faculty = [
            ['Prof. Maria Santos', 'msantos@university.edu'],
            ['Prof. Jose Reyes', 'jreyes@university.edu'],
            ['Prof. Ana Dizon', 'adizon@university.edu'],
            ['Prof. Carlos Mendoza', 'cmendoza@university.edu'],
        ];

        foreach ($faculty as [$name, $email]) {
            User::firstOrCreate(['email' => $email], [
                'name' => $name,
                'password' => Hash::make('faculty@2025'),
                'role' => 'faculty',
            ]);
        }

        $students = [
            ['Juan dela Cruz', 'student@university.edu', '2021-00123'],
            ['Maria Santos', 'maria@university.edu', '2021-00456'],
            ['Pedro Reyes', 'pedro@university.edu', '2021-00789'],
            ['Ana Gonzales', 'ana@university.edu', '2021-01012'],
            ['Carlo Villanueva', 'carlo@university.edu', '2022-00111'],
        ];

        foreach ($students as [$name, $email, $sid]) {
            User::firstOrCreate(['email' => $email], [
                'name' => $name,
                'password' => Hash::make('password123'),
                'role' => 'student',
                'student_id' => $sid,
            ]);
        }

        $this->command->info('Users seeded (1 admin, 4 faculty, 5 students).');
    }
}
