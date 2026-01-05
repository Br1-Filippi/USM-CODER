<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Submission;
use App\Models\Question;
use App\Models\UniTest;

class SubmissionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index($question_id)
    {
        $submissions = Submission::where('question_id', $question_id)->get();
        $question = Question::find($question_id);

        return view('submissions.index', compact('submissions', 'question'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function submitCode(Request $request, $question_id)
    {
        try {
            $user = auth()->user();
            if (!$user || $user->tipo != 'alumno') {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            $request->validate([
                'code' => 'required|string',
                'language_id' => 'required|integer'
            ]);

            $codeController = new CodeController();
            
            $score = $codeController->getScore(
                $request->input('code'),
                $request->input('language_id'),
                $question_id
            );

            $submission = new Submission();
            $submission->code = $request->input('code');
            $submission->user_id = $user->id;
            $submission->question_id = $question_id;
            $submission->score = $score;
            $submission->save();

            return response()->json([
                'message' => 'Código enviado correctamente',
                'submission_id' => $submission->id,
                'score' => $score
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $e->errors()
            ], 422);
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al enviar código',
                'message' => $e->getMessage()
            ], 500);
        }
    }



    /**
     * Display the specified resource.
     */
    public function show(string $submission_id, $question_id)
    {

        $question = Question::find($question_id);
        $submission = Submission::find($submission_id);

        $unitests = UniTest::where('question_id', $question_id)->get();

        return view('submissions.show', compact('submission', 'question', 'unitests'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

}
