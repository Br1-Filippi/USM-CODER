<?php

namespace Tests\Feature;

use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class QuestionFilesTest extends TestCase
{
    use RefreshDatabase;

    private int $testId;

    protected function setUp(): void
    {
        parent::setUp();

        $career = DB::table('careers')->insertGetId(['name' => 'Informática']);
        $course = DB::table('courses')->insertGetId(['career_id' => $career]);
        $this->testId = DB::table('tests')->insertGetId(['course_id' => $course]);
        DB::table('languages')->insert(['id' => 71, 'name' => 'Python']);
    }

    private function user(string $tipo): User
    {
        return User::find(DB::table('users')->insertGetId(['email' => "$tipo@test.cl", 'password' => 'x', 'tipo' => $tipo]));
    }

    private function storeQuestion(UploadedFile $file)
    {
        return $this->actingAs($this->user('profesor'))->post("/test/{$this->testId}/questions/store", [
            'title' => 'Sumar', 'statement' => 'Suma los números de años.txt', 'score' => 10, 'language' => '71|interactive',
            'tests' => [['stdin' => '-', 'expected_output' => '7']],
            'files' => [$file],
        ]);
    }

    public function test_professor_files_reach_judge0_when_grading(): void
    {
        $this->storeQuestion(UploadedFile::fake()->createWithContent('años.txt', "3\n4\n"))->assertRedirect();
        $question = Question::first();
        $this->assertSame(['años.txt' => "3\n4\n"], $question->files);

        Http::fake(['*' => Http::response(['stdout' => "7\n", 'status' => ['id' => 3, 'description' => 'Accepted']])]);

        $this->actingAs($this->user('alumno'))
            ->postJson("/question/{$question->id}/submit", ['source_code' => 'print(7)', 'language_id' => 71])
            ->assertOk()->assertJson(['score' => 100]);

        // Zip sin compresión: el nombre y el contenido van tal cual dentro de additional_files.
        Http::assertSent(function (Request $request) {
            $zip = base64_decode($request['additional_files'] ?? '');

            return str_starts_with($zip, "PK\x03\x04") && str_contains($zip, "años.txt3\n4\n");
        });
    }

    public function test_non_utf8_file_is_rejected(): void
    {
        $this->storeQuestion(UploadedFile::fake()->createWithContent('datos.csv', "a\xF1o\n"))
            ->assertSessionHasErrors('files.0');

        $this->assertSame(0, Question::count());
    }
}
