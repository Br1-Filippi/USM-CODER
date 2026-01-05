@extends('layouts.navbar')

@section('main-content')
<div class="d-flex" style="height: calc(100vh - 56px); width: 100%;">
    <!-- COLUMNA IZQUIERDA: Info y Acciones -->
    <div class="bg-light border-end p-3 d-flex flex-column" style="width: 25%; overflow-y: auto;">
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
                Ejecutar Código
            </button>
            <button type="button" id="reEvaluate" class="btn btn-warning w-100">
                Re-evaluar
            </button>
        </div>
    </div>

    <!-- COLUMNA CENTRAL Y DERECHA -->
    <div class="flex-grow-1 d-flex flex-column">
        <!-- EDITOR DE CÓDIGO -->
        <div class="flex-grow-1" style="min-height: 0;">
            <div id="editor" style="height: 100%; width: 100%;"></div>
        </div>

        <!-- SECCIÓN INFERIOR: Tests y Output -->
        <div class="border-top" style="height: 250px;">
            <div class="row g-0 h-100">
                <!-- Columna de Entrada/Salida -->
                <div class="col-3 border-end d-flex flex-column">
                    <div class="bg-dark text-white px-3 py-1">
                        <small><strong>ENTRADA (stdin)</strong></small>
                    </div>
                    <textarea id="stdin" class="form-control border-0 rounded-0" 
                              placeholder="Ingresa datos de prueba..." 
                              style="resize: none; font-family: monospace; height: 50%;"></textarea>
                    
                    <div class="bg-dark text-white px-3 py-1 border-top">
                        <small><strong>SALIDA (output)</strong></small>
                    </div>
                    <textarea id="output" class="form-control border-0 rounded-0" 
                              readonly 
                              placeholder="Resultado..." 
                              style="resize: none; font-family: monospace; height: 50%;"></textarea>
                </div>

                <!-- Columna de Tests -->
                <div class="col-9 d-flex flex-column">
                    <div class="bg-dark text-white px-3 py-1 d-flex justify-content-between align-items-center">
                        <small><strong>CASOS DE PRUEBA</strong></small>
                        @if (!$unitests->isEmpty())
                        <button class="btn btn-sm btn-success" id="runAllTests">
                            Probar Todos
                        </button>
                        @endif
                    </div>
                    
                    <div style="overflow-y: auto; overflow-x: hidden; height: 100%;" class="p-2">
                        @if ($unitests->isEmpty())
                            <div class="alert alert-info m-2">Esta pregunta no tiene tests configurados.</div>
                        @else
                            <div id="tests-list">
                                @foreach ($unitests as $unitest)
                                    <div class="card mb-2 shadow-sm test-card" data-test-index="{{ $loop->index }}">
                                        <div class="card-body p-2">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <h6 class="fw-bold mb-0 small">Test #{{ $loop->iteration }}</h6>
                                                <button class="btn btn-outline-primary btn-sm run-single-test" 
                                                    data-stdin="{{ $unitest->stdin }}" 
                                                    data-expected="{{ $unitest->expected_output }}">
                                                    Ejecutar
                                                </button>
                                            </div>
                                            <div class="row small">
                                                <div class="col-6">
                                                    <strong>Entrada:</strong> <code>{{ $unitest->stdin }}</code>
                                                </div>
                                                <div class="col-6">
                                                    <strong>Esperado:</strong> <code>{{ $unitest->expected_output }}</code>
                                                </div>
                                            </div>
                                            <p class="mb-0 mt-1 small">
                                                <strong>Resultado:</strong> 
                                                <span class="test-result text-secondary">Sin ejecutar</span>
                                            </p>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para resultados de todos los tests -->
<div class="modal fade" id="resultsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Resultados de Tests</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="modalSummary" class="mb-3"></div>
                <div id="modalDetails"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<style>
.test-result.success {
    color: #28a745 !important;
    font-weight: bold;
}
.test-result.error {
    color: #dc3545 !important;
    font-weight: bold;
}
.test-card.passed {
    border-left: 4px solid #28a745;
}
.test-card.failed {
    border-left: 4px solid #dc3545;
}
</style>

<script src="https://cdnjs.cloudflare.com/ajax/libs/monaco-editor/0.45.0/min/vs/loader.min.js"></script>

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
        console.error(err);
    }
});

document.querySelectorAll('.run-single-test').forEach(btn => {
    btn.addEventListener('click', async () => {
        const stdin = btn.dataset.stdin;
        const expected = btn.dataset.expected;
        const testCard = btn.closest('.test-card');
        const resultSpan = testCard.querySelector('.test-result');
        const code = window.editor.getValue();

        // Resetear estado visual
        resultSpan.textContent = 'Ejecutando...';
        resultSpan.classList.remove('success', 'error');
        testCard.classList.remove('passed', 'failed');
        
        // Deshabilitar boton temporalmente
        btn.disabled = true;
        const originalText = btn.textContent;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

        try {
            const response = await fetch('{{ route("run-single-test") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    source_code: code,
                    language_id: {{ $question->language_id ?? 71 }},
                    stdin: stdin,
                    expected_output: expected
                })
            });

            const data = await response.json();

            if (data.success) {
                if (data.passed) {
                    resultSpan.textContent = `Correcto (${data.output})`;
                    resultSpan.classList.add('success');
                    testCard.classList.add('passed');
                } else {
                    const errorMsg = data.stderr 
                        ? `Error: ${data.stderr}` 
                        : `Incorrecto (Salida: ${data.output || 'vacio'})`;
                    resultSpan.textContent = errorMsg;
                    resultSpan.classList.add('error');
                    testCard.classList.add('failed');
                }
            } else {
                resultSpan.textContent = `Error: ${data.message || data.error}`;
                resultSpan.classList.add('error');
                testCard.classList.add('failed');
            }

        } catch (error) {
            resultSpan.textContent = 'Error al ejecutar test';
            resultSpan.classList.add('error');
            testCard.classList.add('failed');
            console.error(error);
        } finally {
            btn.disabled = false;
            btn.textContent = originalText;
        }
    });
});

document.getElementById('runAllTests')?.addEventListener('click', async () => {
    const code = window.editor.getValue();
    const runAllBtn = document.getElementById('runAllTests');
    
    console.log('Iniciando runAllTests...');
    console.log('Question ID:', {{ $question->id }});
    console.log('Language ID:', {{ $question->language_id ?? 71 }});
    
    // Deshabilitar boton mientras se ejecuta
    runAllBtn.disabled = true;
    runAllBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Ejecutando...';
    
    // Resetear todos los resultados
    document.querySelectorAll('.test-result').forEach(span => {
        span.textContent = 'Ejecutando...';
        span.classList.remove('success', 'error');
    });
    document.querySelectorAll('.test-card').forEach(card => {
        card.classList.remove('passed', 'failed');
    });

    try {
        const payload = {
            source_code: code,
            language_id: {{ $question->language_id ?? 71 }},
            question_id: {{ $question->id }}
        };
        
        console.log('Enviando payload:', payload);
        
        const response = await fetch('{{ route("run-all-tests") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify(payload)
        });

        console.log('Respuesta recibida:', response.status, response.statusText);
        
        if (!response.ok) {
            const errorText = await response.text();
            console.error('Error response:', errorText);
            throw new Error(`HTTP error! status: ${response.status}, body: ${errorText}`);
        }

        const data = await response.json();
        console.log('Data recibida:', data);
        console.log('data.success:', data.success);
        console.log('data.results:', data.results);

        if (data.success) {
            data.results.forEach((result) => {
                const testCard = document.querySelector(`[data-test-index="${result.test_number - 1}"]`);
                if (testCard) {
                    const resultSpan = testCard.querySelector('.test-result');
                    
                    if (result.passed) {
                        resultSpan.textContent = `Correcto (${result.output})`;
                        resultSpan.classList.add('success');
                        testCard.classList.add('passed');
                    } else {
                        const errorMsg = result.error 
                            ? `Error: ${result.error}`
                            : result.stderr
                            ? `Error: ${result.stderr}`
                            : `Incorrecto (Salida: ${result.output || 'vacio'})`;
                        resultSpan.textContent = errorMsg;
                        resultSpan.classList.add('error');
                        testCard.classList.add('failed');
                    }
                }
            });

        } else {
            console.warn('Success=false:', data.message);
            alert(data.message || 'Error al ejecutar tests');
        }

    } catch (error) {
        console.error('Error catch:', error);
        alert('Error al ejecutar los tests: ' + error.message);
    } finally {
        runAllBtn.disabled = false;
        runAllBtn.textContent = 'Probar Todos';
    }
});

function showResultsModal(summary, results) {
    const modalSummary = document.getElementById('modalSummary');
    const modalDetails = document.getElementById('modalDetails');
    
    const summaryClass = summary.passed === summary.total ? 'alert-success' : 
                        summary.passed > summary.failed ? 'alert-warning' : 'alert-danger';
    
    modalSummary.className = `alert ${summaryClass}`;
    modalSummary.innerHTML = `
        <h6 class="mb-2"><strong>Resumen de Ejecución</strong></h6>
        <div class="row text-center">
            <div class="col-4">
                <div class="fs-4 fw-bold">${summary.total}</div>
                <small>Total</small>
            </div>
            <div class="col-4">
                <div class="fs-4 fw-bold text-success">${summary.passed}</div>
                <small>Aprobados</small>
            </div>
            <div class="col-4">
                <div class="fs-4 fw-bold text-danger">${summary.failed}</div>
                <small>Fallados</small>
            </div>
        </div>
        <div class="mt-2 text-center">
            <strong>Porcentaje de éxito: ${summary.percentage}%</strong>
        </div>
    `;
    
    // Detalles de cada test
    modalDetails.innerHTML = results.map(result => {
        const statusClass = result.passed ? 'success' : 'danger';
        const statusIcon = result.passed ? '✓' : '✗';
        
        return `
            <div class="card mb-2 border-${statusClass}">
                <div class="card-header bg-${statusClass} text-white py-1">
                    <strong>${statusIcon} Test #${result.test_number}</strong>
                </div>
                <div class="card-body p-2 small">
                    <div class="row">
                        <div class="col-6">
                            <strong>Entrada:</strong> <code>${result.stdin}</code>
                        </div>
                        <div class="col-6">
                            <strong>Esperado:</strong> <code>${result.expected}</code>
                        </div>
                    </div>
                    <div class="mt-1">
                        <strong>Salida:</strong> <code>${result.output || 'vacío'}</code>
                    </div>
                    ${result.error ? `<div class="mt-1 text-danger"><strong>Error:</strong> ${result.error}</div>` : ''}
                    ${result.stderr ? `<div class="mt-1 text-danger"><strong>stderr:</strong> ${result.stderr}</div>` : ''}
                </div>
            </div>
        `;
    }).join('');
    
}
</script>
@endsection