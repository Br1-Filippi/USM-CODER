@extends('layouts.navbar')

@section('main-content')
    <div class="d-flex justify-content-center mt-5 mb-5">
        <div class="card shadow-lg w-75 w-md-50">
            <div class="card-body">
                <h4 class="card-title text-center mb-4">Nueva Pregunta</h4>

                <form action="{{ route('questions.store', $test_id ) }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label for="title" class="form-label">Título de la Pregunta</label>
                        <input type="text" class="form-control" id="title" name="title" required>
                    </div>

                    <div class="mb-3">
                        <label for="statement" class="form-label">Enunciado</label>
                        <textarea class="form-control" id="statement" name="statement" rows="5" required></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="language" class="form-label">Lenguaje</label>
                        <select class="form-select" id="language" name="language" required>
                            <option value="" disabled selected>Selecciona un lenguaje</option>
                            @foreach ($lenguajes as $lenguaje)
                                <option value="{{ $lenguaje->id }}">{{ $lenguaje->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="starter_code" class="form-label">Código de inicio / ejemplo</label>
                        <div id="monaco-editor" style="height: 200px; border:1px solid #ddd;"></div>
                        <textarea id="starter_code" name="starting_code" hidden></textarea>
                    </div>

                    <div class="mb-3">
                        <button type="button" class="btn btn-primary">Agregar Test</button>
                    </div>

                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-success">Guardar Pregunta</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/monaco-editor/0.40.0/min/vs/loader.min.js"></script>
    <script>
        require.config({ paths: { 'vs': 'https://cdnjs.cloudflare.com/ajax/libs/monaco-editor/0.40.0/min/vs' }});
        require(['vs/editor/editor.main'], function() {
            var editor = monaco.editor.create(document.getElementById('monaco-editor'), {
                value: '',
                language: 'python', 
                theme: 'vs-light',
                automaticLayout: true
            });

            // Copiar contenido al textarea al enviar el formulario
            document.querySelector('form').addEventListener('submit', function() {
                document.getElementById('starter_code').value = editor.getValue();
            });
        });
    </script>
@endsection
