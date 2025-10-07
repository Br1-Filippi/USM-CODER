@extends('layouts.navbar')

@section('main-content')
<div class="container mt-5">

    <!-- Encabezado -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold text-primary mb-0">
            <i class="bi bi-question-circle me-2"></i>{{ $question->title }}
        </h2>
    </div>

    <!-- Lista de Submissions -->
    @if ($submissions->isEmpty())
        <div class="alert alert-info shadow-sm">
            <i class="bi bi-info-circle me-2"></i>No hay submissions para esta pregunta.
        </div>
    @else
        <div class="card shadow-lg border-0">
            <div class="card-header bg-primary text-white fw-bold">
                <i class="bi bi-list-check me-2"></i> Lista de Respuestas
            </div>
            <ul class="list-group list-group-flush">
                @foreach ($submissions as $submission)
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fw-bold text-dark">
                                <i class="bi bi-person-circle me-1 text-primary"></i>{{ $submission->user->email }}
                            </div>
                            <small class="text-muted">
                                <i class="bi bi-hash"></i> ID de envío: {{ $submission->id }}
                            </small>
                        </div>
                        <a href="{{ route('submissions.show', ['submission_id' => $submission->id ,'question_id' => $question->id, ]) }}" class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-eye"></i> Revisar
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

</div>
@endsection
