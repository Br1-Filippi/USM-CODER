<?php

namespace App\Http\Controllers;

use App\Models\Language;
use App\Models\Question;
use App\Models\UniTest;
use Illuminate\Http\Request;

class QuestionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('question.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create($test_id)
    {
        $lenguajes = Language::all();

        return view('question.create', compact('lenguajes', 'test_id'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, $test_id) // Crear form request
    {

        $question = new Question;
        $question->title = $request->input('title');
        $question->statement = $request->input('statement');
        $question->score = $request->input('score');
        $question->starting_code = $request->input('starting_code');

        // The language select encodes "{language_id}|{mode}" so a single
        // control can offer e.g. "Python" (judge0) and "Python interactivo".
        [$languageId, $mode] = array_pad(explode('|', (string) $request->input('language'), 2), 2, 'judge0');
        $question->language_id = $languageId;
        $question->execution_mode = in_array($mode, ['judge0', 'interactive'], true) ? $mode : 'judge0';

        $question->test_id = $test_id;

        $question->save();

        if (! empty($request->input('tests'))) {
            foreach ($request->input('tests') as $testData) {
                $test = new UniTest;
                $test->stdin = $testData['stdin'];
                $test->expected_output = $testData['expected_output'];
                $test->question_id = $question->id;
                $test->save();
            }
        }

        return redirect()->route('tests.show', $test_id);
    }

    /**
     * Display the specified resource.
     */
    public function show($test_id, $question_id)
    {
        $question = Question::findOrFail($question_id);

        $previousQuestion = Question::find($question->id - 1);
        $nextQuestion = Question::find($question->id + 1);

        // dd($question, $previousQuestion, $nextQuestion);

        return view('question.show', compact('question', 'test_id', 'previousQuestion', 'nextQuestion'));
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
