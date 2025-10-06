<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Career
        $careerId = DB::table('careers')->insertGetId([
            'name' => 'Computer Science',
            'deleted_at' => null,
        ]);

        // User
        $userId = DB::table('users')->insertGetId([
            'email' => 'profesor@usm.cl',
            'password' => Hash::make('1234'),
            'tipo' => 'profesor',
            'deleted_at' => null,
        ]);

        DB::table('users')->insertGetId([
            'email' => 'alumno@usm.cl',
            'password' => Hash::make('1234'),
            'tipo' => 'alumno',
            'deleted_at' => null,
        ]);

        // Course
        $courseId = DB::table('courses')->insertGetId([
            'career_id' => $careerId,
            'deleted_at' => null,
        ]);

        // Test
        DB::table('tests')->insert([
            'course_id' => $courseId,
            'deleted_at' => null,
        ]);

        DB::table('languages')->insert([
            ['id' => 71 ,'name' => 'Python', 'deleted_at' => null],
           
        ]);
    }
}
