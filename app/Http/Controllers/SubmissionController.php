<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Submission;

class SubmissionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
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

        $user = auth()->user();

        if (!$user || $user->tipo != 'alumno') {
            return response()->json(['error' => 'Unauthorized'], 403);
        } else {

            $submission = new Submission();

            $submission->code = $request->input('code');
            $submission->user_id = $user->id;
            $submission->question_id = $question_id;

            $submission->save();
        }

        return response()->json([
        'message' => 'Código enviado correctamente',
        'submission_id' => $submission->id,
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
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
