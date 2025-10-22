@extends('layouts.navbar')

@section('main-content')
<div class="container-fluid">

    <!-- ENCABEZADO Y ACCIONES -->
    <div class="row mb-3 align-items-center">
        <div class="col-md-9">
            <h2 class="mb-2">{{ $question->title }}</h2>
            <h5 class="text-muted">{{ $submission->user->email }}</h5>
            <div class="card mb-3 bg-light border-secondary">
                <div class="card-body py-2">
                    <small class="fw-bold d-block mb-1">Enunciado</small>
                    <p class="mb-0">{{ $question->statement }}</p>
                </div>
            </div>
        </div>

        <div class="col-md-3 d-flex flex-column align-items-end gap-2">
            <h4 class="text-secondary fw-light mb-0">Acciones</h4>
            <div class="d-flex gap-2 w-100 justify-content-end">
                <button type="button" id="runCode" class="btn btn-secondary flex-grow-1">
                    <i class="bi bi-play-circle"></i> Ejecutar Código
                </button>
                <button type="button" id="reEvaluate" class="btn btn-warning flex-grow-1">
                    <i class="bi bi-arrow-repeat"></i> Re-evaluar
                </button>
            </div>
        </div>
    </div>

    <hr class="mb-4">

    <div class="row">
        <div class="col-lg-9">
            <label class="form-label fw-bold">Código del Usuario</label>
            <div id="editor" style="height: 600px; border: 1px solid #ddd;"></div>
        </div>

        <div class="col-lg-3 d-flex flex-column">
            <div class="mb-3">
                <label for="stdin" class="form-label fw-bold">Entrada (stdin)</label>
                <textarea id="stdin" class="form-control" rows="8" placeholder="Ingresa los datos de entrada aquí..."></textarea>
            </div>

            <div class="mb-3 flex-grow-1 d-flex flex-column">
                <label for="output" class="form-label fw-bold">Salida (output)</label>
                <textarea id="output" class="form-control flex-grow-1" readonly placeholder="El resultado aparecerá aquí..."></textarea>
            </div>
        </div>
    </div>

    <hr class="my-4">

    <div class="mt-4">
        <h4 class="mb-3">Casos de Prueba</h4>

        @if ($unitests->isEmpty())
            <div class="alert alert-info">Esta pregunta no tiene tests configurados.</div>
        @else
            <div class="d-flex justify-content-end mb-3">
                <button class="btn btn-success" id="runAllTests">
                    <i class="bi bi-play-fill"></i> Probar Todos
                </button>
            </div>

            <div id="tests-list" class="list-group">
                @foreach ($unitests as $unitest)
                    <div class="list-group-item shadow-sm mb-2 p-3 border rounded">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="fw-bold mb-0">Test #{{ $loop->iteration }}</h6>
                            <button class="btn btn-outline-primary btn-sm run-single-test" 
                                data-stdin="{{ $unitest->stdin }}" 
                                data-expected="{{ $unitest->expected_output }}">
                                <i class="bi bi-play"></i> Ejecutar
                            </button>
                        </div>
                        <p class="mb-1"><strong>Entrada:</strong> <code>{{ $unitest->stdin }}</code></p>
                        <p class="mb-1"><strong>Salida esperada:</strong> <code>{{ $unitest->expected_output }}</code></p>
                        <p class="mb-0"><strong>Resultado:</strong> 
                            <span class="test-result text-secondary">Sin ejecutar</span>
                        </p>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

</div>

<!-- ESTILOS -->
<style>
.flex-grow-1 > textarea {
    height: 100%;
    min-height: 100px;
}
</style>

<!-- DEPENDENCIAS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/monaco-editor/0.45.0/min/vs/loader.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

<script>
(function () {
    const starterCode = `{!! addslashes($submission->code ?? '// No hay código para esta submission...') !!}`;

    window.require.config({ paths: { vs: 'https://cdnjs.cloudflare.com/ajax/libs/monaco-editor/0.45.0/min/vs' }});
    window.require(['vs/editor/editor.main'], function () {
        window.editor = monaco.editor.create(document.getElementById('editor'), {
            value: starterCode,
            language: 'python',
            theme: 'vs-light',
            automaticLayout: true,
            readOnly: true,
        });
    });
})();

document.getElementById('runCode').addEventListener('click', async () => {
    const code = window.editor.getValue();
    const stdin = document.getElementById('stdin').value;
    const outputArea = document.getElementById('output');
    outputArea.value = 'Ejecutando código...';

    try {
        const response = await fetch('{{ route("run-code") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                source_code: code,
                language_id: {{ $question->language_id ?? 71 }},
                stdin: stdin,
                wait: true
            })
        });

        const data = await response.json();

        if (data.stderr) {
            outputArea.value = `Error:\n${data.stderr}`;
        } else if (data.compile_output) {
            outputArea.value = `Error de compilación:\n${data.compile_output}`;
        } else {
            outputArea.value = `${data.stdout || '(sin salida)'}`;
        }

    } catch (err) {
        outputArea.value = 'Error al ejecutar el código';
    }
});


document.querySelectorAll('.run-single-test').forEach(btn => {
    btn.addEventListener('click', async () => {
        const stdin = btn.dataset.stdin;
        const expected = btn.dataset.expected;
        const resultSpan = btn.closest('.list-group-item').querySelector('.test-result');
        const code = window.editor.getValue();

        resultSpan.textContent = 'Ejecutando...';
        resultSpan.classList.remove('success', 'error');

        try {
            const response = await fetch('{{ route("run-code") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    source_code: code,
                    language_id: {{ $question->language_id ?? 71 }},
                    stdin: stdin,
                    wait: true
                })
            });

            const data = await response.json();
            const output = data.stdout?.trim() ?? '';

            if (output === expected.trim()) {
                resultSpan.textContent = `Correcto (${output})`;
                resultSpan.classList.add('success');
            } else {
                resultSpan.textContent = `Incorrecto (Salida: ${output})`;
                resultSpan.classList.add('error');
            }

        } catch (error) {
            resultSpan.textContent = 'Error al ejecutar test';
            resultSpan.classList.add('error');
        }
    });
});

// Ejecutar todos los tests
document.getElementById('runAllTests')?.addEventListener('click', async () => {
    const testButtons = document.querySelectorAll('.run-single-test');
    for (const btn of testButtons) {
        btn.click();
        await new Promise(r => setTimeout(r, 500)); 
    }
});
</script>
@endsection
