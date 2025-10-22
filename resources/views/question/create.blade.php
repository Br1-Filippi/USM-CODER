@extends('layouts.navbar')

@section('main-content')
<div class="d-flex justify-content-center mt-5 mb-5">
    <div class="card shadow-lg w-75 w-md-50">
        <div class="card-body">
            <h4 class="card-title text-center mb-4">Nueva Pregunta</h4>

            <form action="{{ route('questions.store', $test_id ) }}" method="POST">
                @csrf
                <!-- Título -->
                <div class="mb-3">
                    <label for="title" class="form-label fw-bold">Título de la Pregunta</label>
                    <input type="text" class="form-control" id="title" name="title" required>
                </div>

                <!-- Enunciado -->
                <div class="mb-3">
                    <label for="statement" class="form-label fw-bold">Enunciado</label>
                    <textarea class="form-control" id="statement" name="statement" rows="4" required></textarea>
                </div>

                <!-- Lenguaje -->
                <div class="mb-3">
                    <label for="language" class="form-label fw-bold">Lenguaje</label>
                    <select class="form-select" id="language" name="language" required>
                        <option value="" disabled selected>Selecciona un lenguaje</option>
                        @foreach ($lenguajes as $lenguaje)
                            <option value="{{ $lenguaje->id }}">{{ $lenguaje->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Código inicial -->
                <div class="mb-4">
                    <label for="starter_code" class="form-label fw-bold">Código de inicio / ejemplo</label>
                    <div id="monaco-editor" style="height: 200px; border:1px solid #ddd;"></div>
                    <textarea id="starter_code" name="starting_code" hidden></textarea>
                </div>

                <!-- Tests dinámicos -->
                <div class="mb-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <label class="form-label fw-bold">Casos de Prueba</label>
                        <button type="button" id="add-test" class="btn btn-outline-primary btn-sm">
                            + Agregar Test
                        </button>
                    </div>

                    <div id="tests-container">
                        <!-- Aquí se insertan los tests dinámicamente -->
                    </div>
                </div>

                <!-- Botón guardar -->
                <div class="d-flex justify-content-end">
                    <button type="submit" class="btn btn-success px-4">Guardar Pregunta</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/monaco-editor/0.40.0/min/vs/loader.min.js"></script>
<script>
    // --- MONACO EDITOR ---
    require.config({ paths: { 'vs': 'https://cdnjs.cloudflare.com/ajax/libs/monaco-editor/0.40.0/min/vs' }});
    require(['vs/editor/editor.main'], function() {
        const editor = monaco.editor.create(document.getElementById('monaco-editor'), {
            value: '',
            language: 'python',
            theme: 'vs-light',
            automaticLayout: true
        });

        document.querySelector('form').addEventListener('submit', function() {
            document.getElementById('starter_code').value = editor.getValue();
        });
    });

    // --- TESTS DINÁMICOS ---
    const testsContainer = document.getElementById('tests-container');
    const addTestBtn = document.getElementById('add-test');

    let testIndex = 0;

    addTestBtn.addEventListener('click', () => {
        const testCard = document.createElement('div');
        testCard.classList.add('card', 'mb-3', 'shadow-sm', 'p-3');
        testCard.innerHTML = `
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="fw-bold mb-0">Test #${testIndex + 1}</h6>
                <button type="button" class="btn btn-outline-danger btn-sm remove-test">Eliminar</button>
            </div>
            <div class="mb-2">
                <label class="form-label">Entrada (stdin)</label>
                <textarea class="form-control" name="tests[${testIndex}][stdin]" rows="2" placeholder="Ej: 3" required></textarea>
            </div>
            <div>
                <label class="form-label">Salida esperada</label>
                <textarea class="form-control" name="tests[${testIndex}][expected_output]" rows="2" placeholder="Ej: 8" required></textarea>
            </div>
        `;

        // botón eliminar
        testCard.querySelector('.remove-test').addEventListener('click', () => {
            testCard.remove();
        });

        testsContainer.appendChild(testCard);
        testIndex++;
    });
</script>
@endsection
