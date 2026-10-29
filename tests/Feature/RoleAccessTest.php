<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $tipo): User
    {
        $id = DB::table('users')->insertGetId([
            'email' => "$tipo@test.cl",
            'password' => bcrypt('x'),
            'tipo' => $tipo,
        ]);

        return User::find($id);
    }

    public function test_student_cannot_see_submissions_or_expected_outputs(): void
    {
        $student = $this->user('alumno');

        $this->actingAs($student)->get('/question/1/submissions')->assertForbidden();
        $this->actingAs($student)->get('/question/1/submissions/1')->assertForbidden();
        $this->actingAs($student)->postJson('/run-all-tests', [])->assertForbidden();
        $this->actingAs($student)->postJson('/run-single-test', [])->assertForbidden();
        $this->actingAs($student)->post('/test/1/questions/store', [])->assertForbidden();
    }

    public function test_professor_passes_the_role_check(): void
    {
        // 422 = pasó la autorización y falló la validación, sin llamar a Judge0.
        $this->actingAs($this->user('profesor'))->postJson('/run-all-tests', [])->assertUnprocessable();
    }
}
