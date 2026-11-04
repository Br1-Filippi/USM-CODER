@extends('layouts.navbar')

@php $interactive = ($question->execution_mode ?? 'judge0') === 'interactive'; @endphp

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

        @if($interactive && $question->files)
            <p class="small text-muted">Puedes editar los archivos para practicar, pero al enviar se califica con los originales de la pregunta.</p>
        @endif

        <div class="mt-auto">
            <h6 class="text-secondary mb-2">Acciones</h6>
            <button type="button" id="runCode" class="btn btn-success w-100 mb-2">
                Ejecutar Codigo
            </button>
            <button type="button" id="sendAnswer" class="btn btn-primary w-100">
                Enviar Respuesta
            </button>
        </div>
        <div class="d-flex justify-content-between align-items-center py-2 border-bottom bg-white">
            <div>
                @if($previousQuestion)
                    <a href="{{ route('questions.show', ['test_id' => $test_id, 'question_id' => $previousQuestion->id]) }}" class="btn btn-sm btn-outline-secondary">
                        ← Pregunta Anterior
                    </a>
                @endif
            </div>

            <div>
                @if($nextQuestion)
                    <a href="{{ route('questions.show', ['test_id' => $test_id, 'question_id' => $nextQuestion->id]) }}" class="btn btn-sm btn-outline-primary">
                        Siguiente Pregunta→
                    </a>
                @endif
            </div>
        </div>
    </div>

    <div class="flex-grow-1 d-flex flex-column">
        @if($interactive)
            {{-- Pestañas: main.py + archivos de la pregunta + los que cree el alumno o su programa --}}
            <div class="d-flex align-items-center border-bottom bg-light">
                <div id="tabs" class="d-flex flex-wrap"></div>
                <div class="ms-auto btn-group btn-group-sm p-1">
                    <button type="button" id="file-new" class="btn btn-outline-secondary">+ Nuevo</button>
                    <label class="btn btn-outline-secondary mb-0">Subir<input type="file" id="file-upload" multiple hidden></label>
                    <button type="button" id="file-download" class="btn btn-outline-secondary">Descargar</button>
                    <button type="button" id="file-delete" class="btn btn-outline-danger">Borrar</button>
                </div>
            </div>
        @endif
        <div class="flex-grow-1" style="min-height: 0;">
            <div id="editor" style="height: 100%; width: 100%;"></div>
        </div>

        <div class="border-top" style="height: 200px;">
            @if($interactive)
                {{-- Modo interactivo: consola Pyodide (xterm.js). No hay stdin batch. --}}
                <div class="row g-0 h-100">
                    <div class="col-12 d-flex flex-column">
                        <div class="bg-dark text-white px-3 py-1 d-flex justify-content-between align-items-center">
                            <small><strong>CONSOLA</strong></small>
                            <small id="term-status" class="text-info">Cargando Pyodide…</small>
                        </div>
                        <div id="term" class="flex-grow-1" style="background:#000; padding:6px; overflow:hidden;"></div>
                    </div>
                </div>
            @else
                {{-- Modo clásico: stdin batch + Judge0. --}}
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
            @endif
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/monaco-editor/0.45.0/min/vs/loader.min.js" crossorigin="anonymous"></script>

<script>
(function () {
    const starterCode = @json($question->starting_code ?? '// Responde aca tu pregunta!!');

    window.require.config({ paths: { vs: 'https://cdnjs.cloudflare.com/ajax/libs/monaco-editor/0.45.0/min/vs' }});
    window.editorReady = new Promise((resolve) => window.require(['vs/editor/editor.main'], function () {
        window.editor = monaco.editor.create(document.getElementById('editor'), {
            // Modelo propio y no `value`: si el editor es dueño del modelo, lo destruye al cambiar de pestaña.
            model: monaco.editor.createModel(starterCode, 'python'),
            theme: 'vs-light',
            automaticLayout: true,
            minimap: { enabled: false }
        });
        // El código del alumno, aunque el editor esté mostrando otra pestaña.
        window.mainModel = window.editor.getModel();
        resolve();
    }));
})();

// "Enviar Respuesta" — la corrección SIEMPRE pasa por Judge0 (autoridad de notas),
// en ambos modos.
document.getElementById('sendAnswer').addEventListener('click', async () => {
    const code = window.mainModel.getValue();
    const questionId = {{ $question->id }};

    try {
        const response = await fetch(`/question/${questionId}/submit`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
            },
            body: JSON.stringify({
                source_code: code,
                language_id: {{ $question->language_id ?? 71 }}
            }),
        });

        const data = await response.json();

        if (response.ok) {
            alert('Código enviado correctamente');
        } else {
            alert(`Error: ${data.message || data.error}`);
        }
    } catch (error) {
        alert('Error en la solicitud al servidor');
        console.error(error);
    }
});
</script>

@if($interactive)
{{-- ===== Modo interactivo: Pyodide en Web Worker + xterm.js ===== --}}
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/xterm@5.3.0/css/xterm.css" crossorigin="anonymous">
{{-- xterm es UMD: si detecta el cargador AMD global de Monaco (define.amd) se
     registra como módulo AMD y NO crea window.Terminal. Ocultamos define
     mientras carga xterm para forzar que se enganche a la variable global. --}}
<script>window.__defineBackup = window.define; window.define = undefined;</script>
<script src="https://cdn.jsdelivr.net/npm/xterm@5.3.0/lib/xterm.js" crossorigin="anonymous"></script>
<script>window.define = window.__defineBackup; delete window.__defineBackup;</script>
<script>window.__PY_WORKER_URL = "{{ route('runtime.python-worker') }}"; window.__QUESTION_FILES = @json($question->files ?? []);</script>
@verbatim
<script>
(function () {
    const statusEl = document.getElementById("term-status");
    const runBtn = document.getElementById("runCode");

    // ===== Pestañas de archivos: un modelo de Monaco por archivo =====
    const models = new Map();
    let current = "main.py";

    function openFile(name, text) {
        const model = models.get(name);
        if (!model) models.set(name, monaco.editor.createModel(text, undefined, monaco.Uri.file(name)));
        else if (model.getValue() !== text) model.setValue(text);
    }

    function show(name) {
        current = name;
        window.editor.setModel(models.get(name));
        document.getElementById("tabs").replaceChildren(...[...models.keys()].map((n) => {
            const tab = document.createElement("button");
            tab.type = "button";
            tab.className = "btn btn-sm rounded-0 " + (n === name ? "bg-white fw-bold border-bottom border-primary border-2" : "text-muted");
            tab.textContent = n;
            tab.onclick = () => show(n);
            return tab;
        }));
    }

    function extraFiles() {
        return Object.fromEntries([...models].filter(([n]) => n !== "main.py").map(([n, model]) => [n, model.getValue()]));
    }

    window.editorReady.then(() => {
        models.set("main.py", window.mainModel);
        for (const [name, text] of Object.entries(window.__QUESTION_FILES)) openFile(name, text);
        show("main.py");
    });

    document.getElementById("file-new").onclick = () => {
        const name = (prompt("Nombre del archivo:", "datos.txt") || "").trim();
        if (!name || name.includes("/")) return;
        if (!models.has(name)) openFile(name, "");
        show(name);
    };

    document.getElementById("file-upload").onchange = async (e) => {
        for (const file of e.target.files) {
            try {
                openFile(file.name, new TextDecoder("utf-8", { fatal: true }).decode(await file.arrayBuffer()));
                show(file.name);
            } catch {
                alert(`${file.name} no es un archivo de texto (UTF-8).`);
            }
        }
        e.target.value = "";
    };

    document.getElementById("file-download").onclick = () => {
        const a = document.createElement("a");
        a.href = URL.createObjectURL(new Blob([models.get(current).getValue()], { type: "text/plain" }));
        a.download = current;
        a.click();
        setTimeout(() => URL.revokeObjectURL(a.href));
    };

    document.getElementById("file-delete").onclick = () => {
        if (current === "main.py" || !confirm(`¿Borrar ${current}?`)) return;
        models.get(current).dispose();
        models.delete(current);
        show("main.py");
    };

    const term = new Terminal({
        convertEol: false,
        cursorBlink: true,
        fontFamily: "ui-monospace, monospace",
        fontSize: 13,
        theme: { background: "#000000" },
    });
    term.open(document.getElementById("term"));

    // SharedArrayBuffer requiere aislamiento cross-origin (COOP/COEP).
    if (!self.crossOriginIsolated) {
        statusEl.textContent = "Error: la página no está aislada (COOP/COEP).";
        term.write("\x1b[31mcrossOriginIsolated === false\x1b[0m\r\n");
        runBtn.disabled = true;
        return;
    }

    // Memoria compartida: control[0]=flag estado, control[1]=largo de la línea; data=bytes.
    const controlSAB = new SharedArrayBuffer(8);
    const dataSAB = new SharedArrayBuffer(8192);
    const control = new Int32Array(controlSAB);
    const data = new Uint8Array(dataSAB);

    const worker = new Worker(window.__PY_WORKER_URL);
    worker.postMessage({ type: "init", control: controlSAB, data: dataSAB });

    worker.onerror = (e) => {
        statusEl.textContent = "Error al cargar el worker.";
        term.write(`\r\n\x1b[31mWorker error: ${e.message || e}\x1b[0m\r\n`);
        console.error("Worker error:", e);
    };

    const outDecoder = new TextDecoder();
    function writeOut(bytes, isErr) {
        const text = outDecoder.decode(bytes, { stream: true }).replace(/\n/g, "\r\n");
        term.write(isErr ? `\x1b[31m${text}\x1b[0m` : text);
    }

    // Editor de línea: activo solo mientras Python espera en input().
    let inputMode = false;
    let lineBuffer = "";

    term.onData((d) => {
        if (!inputMode) return;
        for (const ch of d) {
            if (ch === "\r") {
                term.write("\r\n");
                submitLine(lineBuffer);
                lineBuffer = "";
                inputMode = false;
                break;
            } else if (ch === "\x7f") {
                if (lineBuffer.length) {
                    lineBuffer = lineBuffer.slice(0, -1);
                    term.write("\b \b");
                }
            } else if (ch >= " ") {
                lineBuffer += ch;
                term.write(ch);
            }
        }
    });

    function submitLine(line) {
        const bytes = new TextEncoder().encode(line + "\n");
        const len = Math.min(bytes.length, data.length);
        data.set(bytes.subarray(0, len));
        Atomics.store(control, 1, len);
        Atomics.store(control, 0, 1);
        Atomics.notify(control, 0, 1);
        statusEl.textContent = "Ejecutando…";
    }

    worker.onmessage = (e) => {
        const m = e.data;
        switch (m.type) {
            case "ready":
                statusEl.textContent = "Listo — Python 3.12 (Pyodide).";
                runBtn.disabled = false;
                break;
            case "init-error":
                statusEl.textContent = "Error cargando Pyodide.";
                term.write(`\r\n\x1b[31mNo se pudo cargar Pyodide:\r\n${m.message}\x1b[0m\r\n`);
                console.error("Pyodide init error:", m.message);
                break;
            case "stdout": writeOut(m.bytes, false); break;
            case "stderr": writeOut(m.bytes, true); break;
            case "input-request":
                inputMode = true;
                lineBuffer = "";
                term.focus();
                statusEl.textContent = "⌨ Esperando entrada — escribe en la consola y presiona Enter";
                break;
            case "run-start":
                statusEl.textContent = "Ejecutando…";
                term.write("\x1b[90m--- run ---\x1b[0m\r\n");
                break;
            case "run-done":
                statusEl.textContent = "Listo.";
                term.write("\r\n\x1b[90m--- done ---\x1b[0m\r\n");
                runBtn.disabled = false;
                break;
            case "run-error":
                statusEl.textContent = "Error.";
                term.write(`\r\n\x1b[31m${m.message}\x1b[0m\r\n`);
                runBtn.disabled = false;
                break;
            case "files":
                // Lo que quedó en el directorio después de correr: pestañas nuevas, cambiadas o borradas.
                for (const name of [...models.keys()]) {
                    if (name !== "main.py" && !(name in m.files)) {
                        models.get(name).dispose();
                        models.delete(name);
                    }
                }
                for (const [name, text] of Object.entries(m.files)) {
                    if (name !== "main.py") openFile(name, text);
                }
                show(models.has(current) ? current : "main.py");
                break;
        }
    };

    runBtn.disabled = true;
    runBtn.addEventListener("click", () => {
        runBtn.disabled = true;
        inputMode = false;
        lineBuffer = "";
        term.reset();
        term.focus();
        worker.postMessage({ type: "run", code: models.get("main.py").getValue(), files: extraFiles() });
    });
})();
</script>
@endverbatim
@else
{{-- ===== Modo clásico: ejecutar en Judge0 con stdin batch ===== --}}
<script>
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
</script>
@endif
@endsection
