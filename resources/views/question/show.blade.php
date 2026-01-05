@extends('layouts.navbar')

@section('main-content')
<div class="d-flex" style="height: calc(100vh - 56px); width: 100%;">
    <!-- COLUMNA IZQUIERDA: Info y Acciones -->
    <div class="bg-light border-end p-3 d-flex flex-column" style="width: 20%; overflow-y: auto;">
        <h4 class="fw-bold text-primary mb-3">{{ $question->title }}</h4>
        
        <div class="card shadow-sm mb-3">
            <div class="card-body">
                <h6 class="text-muted mb-2">Enunciado</h6>
                <p class="small mb-0">{{ $question->statement }}</p>
            </div>
        </div>

        <div class="mt-auto">
            <h6 class="text-secondary mb-2">Acciones</h6>
            <button type="button" id="runCode" class="btn btn-success w-100 mb-2">
                Ejecutar Codigo
            </button>
            <button type="button" id="sendAnswer" class="btn btn-primary w-100">
                Enviar Respuesta
            </button>
        </div>
    </div>

    <div class="flex-grow-1 d-flex flex-column">
        <div class="flex-grow-1" style="min-height: 0;">
            <div id="editor" style="height: 100%; width: 100%;"></div>
        </div>

        <div class="border-top" style="height: 200px;">
            <div class="row g-0 h-100">
                <div class="col-6 border-end d-flex flex-column">
                    <div class="bg-dark text-white px-3 py-1">
                        <small><strong>ENTRADA (stdin)</strong></small>
                    </div>
                    <textarea id="stdin" class="form-control border-0 rounded-0 flex-grow-1" 
                              placeholder="Ingresa los datos de entrada aquí... 
Ej : si tienes un input A = int(input()), escribe el valor de A aqui,
Si tienes mas de un input van en orden de aparicion separados por saltos de linea." 
                              style="resize: none; font-family: monospace; "></textarea>
                </div>
                <div class="col-6 d-flex flex-column">
                    <div class="bg-dark text-white px-3 py-1">
                        <small><strong>SALIDA (output)</strong></small>
                    </div>
                    <textarea id="output" class="form-control border-0 rounded-0 flex-grow-1" 
                              readonly 
                              placeholder="El resultado de tu codigo aparecerá aquí... " 
                              style="resize: none; font-family: monospace; "></textarea>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/monaco-editor/0.45.0/min/vs/loader.min.js"></script>

<script>
(function () {
    const starterCode = `{!! addslashes($question->starting_code ?? '// Responde aca tu pregunta!!') !!}`;

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
        outputArea.value = 'Error al ejecutar el código';
    }
});

document.getElementById('sendAnswer').addEventListener('click', async () => {
    const code = window.editor.getValue();
    const questionId = {{ $question->id }}; 

    try {
        const response = await fetch(`/question/${questionId}/submit`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
            },
            body: JSON.stringify({
                code: code,
                language_id: {{ $question->language_id ?? 71 }} 
            }),
        });

        const data = await response.json();

        if (response.ok) {
            alert('Código enviado correctamente');
        } else {
            alert(`Error: ${data.error || data.message}`);
        }
    } catch (error) {
        alert('Error en la solicitud al servidor');
        console.error(error);
    }
});
</script>
@endsection