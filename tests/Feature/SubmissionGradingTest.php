<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SubmissionGradingTest extends TestCase
{
    use RefreshDatabase;

    private User $student;

    private int $questionId;

    protected function setUp(): void
    {
        parent::setUp();

        $career = DB::table('careers')->insertGetId(['name' => 'Informática']);
        $course = DB::table('courses')->insertGetId(['career_id' => $career]);
        $test = DB::table('tests')->insertGetId(['course_id' => $course]);
        $this->questionId = DB::table('questions')->insertGetId(['title' => 't', 'statement' => 's', 'score' => 10, 'test_id' => $test]);
        DB::table('uni_tests')->insert([
            ['stdin' => '3', 'expected_output' => '8', 'question_id' => $this->questionId],
            ['stdin' => '4', 'expected_output' => '16', 'question_id' => $this->questionId],
        ]);
        $this->student = User::find(DB::table('users')->insertGetId(['email' => 'a@test.cl', 'password' => 'x', 'tipo' => 'alumno']));
    }

    private function submit()
    {
        return $this->actingAs($this->student)
            ->postJson("/question/{$this->questionId}/submit", ['source_code' => 'print(1)', 'language_id' => 71]);
    }

    public function test_grade_is_the_share_of_passed_cases(): void
    {
        Http::fakeSequence()
            ->push(['stdout' => "8\n", 'status' => ['id' => 3, 'description' => 'Accepted']])
            ->push(['stdout' => "15\n", 'status' => ['id' => 3, 'description' => 'Accepted']]);

        $this->submit()->assertOk()->assertJson(['score' => 50]);
        $this->assertDatabaseHas('submissions', ['user_id' => $this->student->id, 'score' => 50]);
    }

    public function test_judge0_failure_saves_no_grade(): void
    {
        Http::fakeSequence()
            ->push(['stdout' => "8\n", 'status' => ['id' => 3, 'description' => 'Accepted']])
            ->push(['status' => ['id' => 13, 'description' => 'Internal Error']]);

        $this->submit()->assertStatus(500);
        $this->assertDatabaseCount('submissions', 0);
    }
}
