@extends('layouts.navbar')

@section('main-content')
    <div class="container">
        <h2>{{ $test->id }}</h2>
        
        <a href="{{ route('questions.create', $test ) }}" class="btn btn-primary">
            Crear Nueva Pregunta
        </a>

        <div class="mt-4">
            <h3>Preguntas del Test</h3>
            @if($questions->isEmpty())
                <p>No hay preguntas asociadas a este test.</p>
            @else
                <ul class="list-group">
                    @foreach($questions as $question)
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <h5>{{ $question->title }}</h5>
                                <p>{{ $question->statement }}</p>
                            </div>
                            <a href="{{ route('submissions.index', ['question_id' => $question->id]) }}" class="btn btn-warning text-white">
                                Ver Respuestas
                            </a>    
                            <a href="{{ route('questions.show', ['test_id' => $test->id, 'question_id' => $question->id]) }}" class="btn btn-success">
                                Responder
                            </a>    
                        </li>
                    @endforeach
                </ul>   
            @endif
        </div> 
    </div>
@endsection
