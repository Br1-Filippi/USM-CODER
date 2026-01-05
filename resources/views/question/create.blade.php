@extends('layouts.navbar')

@section('main-content')
<form action="{{ route('questions.store', $test_id ) }}" method="POST">
    @csrf
    <div class="d-flex" style="height: calc(100vh - 56px); width: 100%;">
        <!-- COLUMNA IZQUIERDA: Información de la pregunta -->
        <div class="bg-light border-end p-3 d-flex flex-column" style="width: 25%; overflow-y: auto;">
            <h4 class="fw-bold text-primary mb-3">Nueva Pregunta</h4>
            
            <div class="card shadow-sm mb-3">
                <div class="card-body">
                    <label for="title" class="form-label fw-bold">Título</label>
                    <input type="text" class="form-control" id="title" name="title" required>
                </div>
            </div>

            <div class="card shadow-sm mb-3">
                <div class="card-body">
                    <label for="statement" class="form-label fw-bold">Enunciado</label>
                    <textarea class="form-control" id="statement" name="statement" rows="6" required></textarea>
                </div>
            </div>

            <div class="card shadow-sm mb-3">
                <div class="card-body">
                    <label for="language" class="form-label fw-bold">Lenguaje</label>
                    <select class="form-select mb-3" id="language" name="language" required>
                        <option value="" disabled selected>Selecciona un lenguaje</option>
                        @foreach ($lenguajes as $lenguaje)
                            <option value="{{ $lenguaje->id }}">{{ $lenguaje->name }}</option>
                        @endforeach
                    </select>

                    <label for="score" class="form-label fw-bold">Puntaje</label>
                    <input type="number" class="form-control" id="score" name="score" min="0" step="1" required>
                </div>
            </div>

            <div class="mt-auto">
                <button type="submit" class="btn btn-success w-100">
                    Guardar Pregunta
                </button>
            </div>
        </div>

        <!-- COLUMNA CENTRAL: Editor de código -->
        <div class="flex-grow-1 d-flex flex-column">
            <div class="bg-secondary text-white px-3 py-2">
                <small><strong>CÓDIGO DE INICIO / EJEMPLO</strong></small>
            </div>
            <div class="flex-grow-1" style="min-height: 0;">
                <div id="monaco-editor" style="height: 100%; width: 100%;"></div>
                <textarea id="starter_code" name="starting_code" hidden></textarea>
            </div>

            <!-- SECCIÓN INFERIOR: Casos de prueba -->
            <div class="border-top" style="height: 250px; overflow-y: auto;">
                <div class="bg-dark text-white px-3 py-2 d-flex justify-content-between align-items-center sticky-top">
                    <small><strong>CASOS DE PRUEBA</strong></small>
                    <button type="button" id="add-test" class="btn btn-sm btn-outline-light">
                        + Agregar Test
                    </button>
                </div>
                
                <div id="tests-container" class="p-3">
                    <!-- Los tests se agregarán aquí dinámicamente -->
                </div>
            </div>
        </div>
    </div>
</form>

<script src="https://cdnjs.cloudflare.com/ajax/libs/monaco-editor/0.45.0/min/vs/loader.min.js"></script>
<script>
    require.config({ paths: { 'vs': 'https://cdnjs.cloudflare.com/ajax/libs/monaco-editor/0.45.0/min/vs' }});
    require(['vs/editor/editor.main'], function() {
        const editor = monaco.editor.create(document.getElementById('monaco-editor'), {
            value: '# Escribe el código de inicio aquí...',
            language: 'python',
            theme: 'vs-light',
            automaticLayout: true,
            minimap: { enabled: false }
        });

        document.querySelector('form').addEventListener('submit', function() {
            document.getElementById('starter_code').value = editor.getValue();
        });
    });

    const testsContainer = document.getElementById('tests-container');
    const addTestBtn = document.getElementById('add-test');

    let testIndex = 0;

    addTestBtn.addEventListener('click', () => {
        const testCard = document.createElement('div');
        testCard.classList.add('card', 'mb-3', 'shadow-sm');
        testCard.innerHTML = `
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold mb-0 text-primary">Test #${testIndex + 1}</h6>
                    <button type="button" class="btn btn-outline-danger btn-sm remove-test">Eliminar</button>
                </div>
                <div class="row">
                    <div class="col-6">
                        <label class="form-label fw-bold">Entrada (stdin)</label>
                        <textarea class="form-control" name="tests[${testIndex}][stdin]" rows="3" placeholder="Ej: 3" style="font-family: monospace;" required></textarea>
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-bold">Salida esperada</label>
                        <textarea class="form-control" name="tests[${testIndex}][expected_output]" rows="3" placeholder="Ej: 8" style="font-family: monospace;" required></textarea>
                    </div>
                </div>
            </div>
        `;

        testCard.querySelector('.remove-test').addEventListener('click', () => {
            testCard.remove();
        });

        testsContainer.appendChild(testCard);
        testIndex++;
    });
</script>
@endsection