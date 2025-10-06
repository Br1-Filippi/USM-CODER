@extends('layouts.navbar')

@section('main-content')
<div class="container-fluid">

    <div class="row mb-3 align-items-center">
        <div class="col-md-9">
            <h2 class="mb-2">{{ $question->title }}</h2>
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
                <button type="button" id="runCode" class="btn btn-secondary flex-grow-1">Run Code</button>
                <button type="button" id="sendAnswer" class="btn btn-primary flex-grow-1">Enviar Respuesta</button>
            </div>
        </div>
    </div>
    
    <hr class="mb-4">

    <div class="row">
        <div class="col-lg-9">
            <label class="form-label fw-bold">Tu Código</label>
            <div id="editor" style="height: 600px; border: 1px solid #ddd;"></div>
        </div>

        <div class="col-lg-3 d-flex flex-column">
            <div class="mb-3">
                <label for="stdin" class="form-label fw-bold">Entrada (stdin)</label>
                <textarea id="stdin" class="form-control" rows="8" placeholder="Ingresa los datos de entrada aquí..."></textarea>
            </div>

            <div class="mb-3 flex-grow-1 d-flex flex-column">
                <label for="output" class="form-label fw-bold">Salida (output)</label>
                <textarea id="output" class="form-control flex-grow-1" readonly placeholder="El resultado de la ejecución aparecerá aquí..."></textarea>
            </div>
        </div>
    </div>
</div>

<style>
.flex-grow-1 > textarea {
    height: 100%;
    min-height: 100px;
}
</style>

<script src="https://cdnjs.cloudflare.com/ajax/libs/monaco-editor/0.45.0/min/vs/loader.min.js"></script>
<script>
(function () {
    const starterCode = `{!! addslashes($question->starting_code ?? '// Escribe tu código aquí...') !!}`;

    window.require.config({ paths: { vs: 'https://cdnjs.cloudflare.com/ajax/libs/monaco-editor/0.45.0/min/vs' }});
    window.require(['vs/editor/editor.main'], function () {
        window.editor = monaco.editor.create(document.getElementById('editor'), {
            value: starterCode,
            language: 'python',
            theme: 'vs-light',
            automaticLayout: true,
            minimap: { enabled: false }
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
        outputArea.value = `Error al ejecutar el código`;
    }
});
</script>
@endsection
