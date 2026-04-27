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

        // Professor
        DB::table('users')->insert(['email' => 'gabriel.jara@usm.cl', 'password' => Hash::make('Jara_gabriel'), 'tipo' => 'profesor', 'deleted_at' => null]);

        // Admin
        DB::table('users')->insert(['email' => 'prof.admin@usm.cl', 'password' => Hash::make('1234'), 'tipo' => 'admin', 'deleted_at' => null]);

        // 22 Real Students
        DB::table('users')->insert(['email' => 'mbritog@usm.cl', 'password' => Hash::make('Gallegos_Marcelo'), 'tipo' => 'alumno', 'deleted_at' => null]);
        DB::table('users')->insert(['email' => 'gcisterna@usm.cl', 'password' => Hash::make('Salinas_Gonzalo'), 'tipo' => 'alumno', 'deleted_at' => null]);
        DB::table('users')->insert(['email' => 'jcordovaq@usm.cl', 'password' => Hash::make('Quispe_Jose'), 'tipo' => 'alumno', 'deleted_at' => null]);
        DB::table('users')->insert(['email' => 'sebastian.farias@alumnos.usm.cl', 'password' => Hash::make('Nuñez_Sebastian'), 'tipo' => 'alumno', 'deleted_at' => null]);
        DB::table('users')->insert(['email' => 'sergio.franjola.13@sansano.usm.cl', 'password' => Hash::make('Muñoz_Sergio'), 'tipo' => 'alumno', 'deleted_at' => null]);
        DB::table('users')->insert(['email' => 'ihernandezh@usm.cl', 'password' => Hash::make('Herrera_Ignacio'), 'tipo' => 'alumno', 'deleted_at' => null]);
        DB::table('users')->insert(['email' => 'iloncon@usm.cl', 'password' => Hash::make('Riquelme_Ignacio'), 'tipo' => 'alumno', 'deleted_at' => null]);
        DB::table('users')->insert(['email' => 'emery@usm.cl', 'password' => Hash::make('Rondoño_Elisabet'), 'tipo' => 'alumno', 'deleted_at' => null]);
        DB::table('users')->insert(['email' => 'rnecul@usm.cl', 'password' => Hash::make('Necul_Ricardo'), 'tipo' => 'alumno', 'deleted_at' => null]);
        DB::table('users')->insert(['email' => 'molavarriat@usm.cl', 'password' => Hash::make('Troncoso_Matias'), 'tipo' => 'alumno', 'deleted_at' => null]);
        DB::table('users')->insert(['email' => 'folivareso@usm.cl', 'password' => Hash::make('Olmos_Francisco'), 'tipo' => 'alumno', 'deleted_at' => null]);
        DB::table('users')->insert(['email' => 'srobles@usm.cl', 'password' => Hash::make('Quilodran_Sofia'), 'tipo' => 'alumno', 'deleted_at' => null]);
        DB::table('users')->insert(['email' => 'jromanu@usm.cl', 'password' => Hash::make('Uribe_Juan'), 'tipo' => 'alumno', 'deleted_at' => null]);
        DB::table('users')->insert(['email' => 'psanchezs@usm.cl', 'password' => Hash::make('Saavedra_Pablo'), 'tipo' => 'alumno', 'deleted_at' => null]);
        DB::table('users')->insert(['email' => 'cseguelh@usm.cl', 'password' => Hash::make('Hernandez_Constanza'), 'tipo' => 'alumno', 'deleted_at' => null]);
        DB::table('users')->insert(['email' => 'ssotol@usm.cl', 'password' => Hash::make('Ledezma_Sebastian'), 'tipo' => 'alumno', 'deleted_at' => null]);
        DB::table('users')->insert(['email' => 'asotov@usm.cl', 'password' => Hash::make('Valencia_Ariel'), 'tipo' => 'alumno', 'deleted_at' => null]);
        DB::table('users')->insert(['email' => 'eric.urquieta@sansano.usm.cl', 'password' => Hash::make('Herrera_Eric'), 'tipo' => 'alumno', 'deleted_at' => null]);
        DB::table('users')->insert(['email' => 'fvelsquez@usm.cl', 'password' => Hash::make('Flores_Fernanda'), 'tipo' => 'alumno', 'deleted_at' => null]);
        DB::table('users')->insert(['email' => 'kvera@usm.cl', 'password' => Hash::make('Rodriguez_Kevin'), 'tipo' => 'alumno', 'deleted_at' => null]);
        DB::table('users')->insert(['email' => 'nwachtendorff@usm.cl', 'password' => Hash::make('Vargas_Nicolas'), 'tipo' => 'alumno', 'deleted_at' => null]);
        DB::table('users')->insert(['email' => 'azavala@usm.cl', 'password' => Hash::make('Sepulveda_Andres'), 'tipo' => 'alumno', 'deleted_at' => null]);

        // 8 Generic Students
        DB::table('users')->insert(['email' => 'student01@test.usm.cl', 'password' => Hash::make('Pass_Kx7mQ2'), 'tipo' => 'alumno', 'deleted_at' => null]);
        DB::table('users')->insert(['email' => 'student02@test.usm.cl', 'password' => Hash::make('Pass_Lp3nR8'), 'tipo' => 'alumno', 'deleted_at' => null]);
        DB::table('users')->insert(['email' => 'student03@test.usm.cl', 'password' => Hash::make('Pass_Wq9tZ5'), 'tipo' => 'alumno', 'deleted_at' => null]);
        DB::table('users')->insert(['email' => 'student04@test.usm.cl', 'password' => Hash::make('Pass_Bv4jY1'), 'tipo' => 'alumno', 'deleted_at' => null]);
        DB::table('users')->insert(['email' => 'student05@test.usm.cl', 'password' => Hash::make('Pass_Nm6cX0'), 'tipo' => 'alumno', 'deleted_at' => null]);
        DB::table('users')->insert(['email' => 'student06@test.usm.cl', 'password' => Hash::make('Pass_Hd2sA4'), 'tipo' => 'alumno', 'deleted_at' => null]);
        DB::table('users')->insert(['email' => 'student07@test.usm.cl', 'password' => Hash::make('Pass_Ry8fU6'), 'tipo' => 'alumno', 'deleted_at' => null]);
        DB::table('users')->insert(['email' => 'student08@test.usm.cl', 'password' => Hash::make('Pass_Tz1eV3'), 'tipo' => 'alumno', 'deleted_at' => null]);
        
    }
}
