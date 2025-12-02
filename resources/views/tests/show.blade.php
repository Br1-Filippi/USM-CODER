@extends('layouts.navbar')

@section('main-content')
<div class="container py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold text-primary">Test #{{ $test->id }}</h2>

        <div class="d-flex gap-2">
            <a href="{{ route('seb.config', $test) }}" class="btn btn-outline-secondary">
                Configurar Safe Exam Browser
            </a>

            <a href="{{ route('questions.create', $test ) }}" class="btn btn-primary shadow">
                <i class="bi bi-plus-circle"></i> Nueva Pregunta
            </a>
        </div>
    </div>

    <div class="card shadow-sm mb-5">
        <div class="card-header bg-white">
            <h4 class="mb-0 text-secondary"><i class="bi bi-list-task"></i> Preguntas del Test</h4>
        </div>

        <div class="card-body">
            @if($questions->isEmpty())
                <div class="alert alert-info text-center">
                    <i class="bi bi-info-circle"></i> Aún no hay preguntas en este test.
                </div>
            @else
                <ul class="list-group list-group-flush">
                    @foreach($questions as $question)
                        <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                            <div>
                                <h5 class="fw-bold">{{ $question->title }}</h5>
                                <p class="text-muted mb-1">{{ $question->statement }}</p>
                            </div>

                            <div class="d-flex gap-2">
                                <a href="{{ route('submissions.index', ['question_id' => $question->id]) }}"
                                   class="btn btn-warning text-white btn-sm shadow-sm">
                                    <i class="bi bi-eye"></i> Respuestas
                                </a>

                                <a href="{{ route('questions.show', ['test_id' => $test->id, 'question_id' => $question->id]) }}"
                                   class="btn btn-success btn-sm shadow-sm">
                                    <i class="bi bi-pencil-square"></i> Contestar
                                </a>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>

</div>
@endsection
