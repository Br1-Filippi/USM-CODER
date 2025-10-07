<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Code Runner</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/monaco-editor@0.44.0/min/vs/loader.js"></script>
</head>
<body class="bg-gray-900 text-white min-h-screen flex flex-col">
    <div class="container mx-auto p-4 flex-1 flex flex-col space-y-4">
        <!-- Main layout: Editor | Stdin & Output -->
        <div class="flex flex-row gap-4 flex-1">
            <!-- Editor: 3/4 -->
            <div class="w-3/4 flex flex-col">
                <div id="editor" class="h-full min-h-[400px] border border-gray-700 rounded"></div>
            </div>
            <!-- Stdin & Output: 1/4 -->
            <div class="w-1/4 flex flex-col gap-4">
                <!-- Botones arriba -->
                <div class="flex space-x-2 mb-2">
                    <button onclick="runCode()" class="bg-green-600 hover:bg-green-700 px-4 py-2 rounded">Ejecutar</button>
                    <button onclick="clearOutput()" class="bg-red-600 hover:bg-red-700 px-4 py-2 rounded">Limpiar</button>
                </div>
                <!-- Stdin: top half -->
                <div class="flex-1 flex flex-col">
                    <label for="stdin" class="block text-sm mb-2">Entrada:</label>
                    <textarea id="stdin" class="w-full flex-1 p-2 rounded bg-gray-800 border border-gray-700 text-white resize-none" rows="6" placeholder="Escribe aquí la entrada para el programa..."></textarea>
                </div>
                <!-- Output: bottom half -->
                <div class="flex-1 flex flex-col">
                    <h2 class="text-lg font-semibold mb-2">Output:</h2>
                    <pre id="output" class="bg-black text-green-400 p-4 rounded flex-1 min-h-[120px] overflow-auto"></pre>
                </div>
            </div>
        </div>
        <!-- Testing Section: full width below -->
        <div class="bg-gray-800 p-4 rounded mb-4 mt-4">
            <h2 class="text-lg font-semibold mb-2">Testing</h2>
            <div id="tests"></div>
            <button onclick="addTest()" class="bg-blue-600 hover:bg-blue-700 px-3 py-1 rounded mt-2">Agregar Test</button>
            <button onclick="runAllTests()" class="bg-green-600 hover:bg-green-700 px-3 py-1 rounded mt-2 ml-2">Ejecutar</button>
            <div id="testResults" class="mt-4"></div>
        </div>
    </div>
    <script>
        let editor;

        // Configuración de Monaco
        require.config({ paths: { 'vs': 'https://cdn.jsdelivr.net/npm/monaco-editor@0.44.0/min/vs' }});
        require(['vs/editor/editor.main'], function() {
            editor = monaco.editor.create(document.getElementById('editor'), {
                value: "print('Hola mundo')",
                language: "python",
                theme: "vs-dark"
            });
        });

        // Ejecutar el código
        function runCode() {
            const code = editor.getValue();
            const stdin = document.getElementById('stdin').value;

            // Judge0 language_id for Python is 71
            fetch('{{ route("run-code") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    language_id: 71,
                    source_code: code,
                    stdin: stdin
                })
            })
            .then(res => res.json())
            .then(res => {
                if (!res.token) {
                    document.getElementById('output').innerText = 'Error: No token received.';
                    return;
                }
                pollResult(res.token);
            })
            .catch(err => {
                document.getElementById('output').innerText = 'Error: ' + err;
            });
        }

        function pollResult(token) {
            fetch(`/code-result/${token}`)
                .then(res => res.json())
                .then(res => {
                    if (res.status && res.status.id < 3) {
                        // En cola o procesando
                        setTimeout(() => pollResult(token), 1000);
                    } else {
                        let output = res.stdout || res.stderr || res.compile_output || 'Sin salida.';
                        document.getElementById('output').innerText = output;
                    }
                })
                .catch(err => {
                    document.getElementById('output').innerText = 'Error: ' + err;
                });
        }

        // Limpiar output
        function clearOutput() {
            document.getElementById('output').innerText = '';
        }

        // Testing logic
        let tests = [];

        function addTest() {
            tests.push({stdin: '', expected: ''});
            renderTests();
        }

        function removeTest(idx) {
            tests.splice(idx, 1);
            renderTests();
        }

        function renderTests() {
            const container = document.getElementById('tests');
            container.innerHTML = '';
            tests.forEach((test, idx) => {
                container.innerHTML += `
                    <div class="mb-2 p-2 border border-gray-700 rounded">
                        <label class="block text-xs mb-1">Entrada (stdin):</label>
                        <textarea class="w-full p-1 rounded bg-gray-900 border border-gray-700 text-white mb-1" rows="2"
                            onchange="tests[${idx}].stdin = this.value">${test.stdin}</textarea>
                        <label class="block text-xs mb-1">Output esperado:</label>
                        <textarea class="w-full p-1 rounded bg-gray-900 border border-gray-700 text-white mb-1" rows="1"
                            onchange="tests[${idx}].expected = this.value">${test.expected}</textarea>
                        <button onclick="removeTest(${idx})" class="bg-red-600 hover:bg-red-700 px-2 py-1 rounded text-xs">Eliminar</button>
                    </div>
                `;
            });
        }

        async function runAllTests() {
            const code = editor.getValue();
            const resultsDiv = document.getElementById('testResults');
            resultsDiv.innerHTML = '';
            for (let i = 0; i < tests.length; i++) {
                const test = tests[i];
                resultsDiv.innerHTML += `<div id="test-result-${i}" class="mb-2">Test ${i+1}: Ejecutando...</div>`;
                // Ejecutar el código con el stdin del test
                const res = await fetch('{{ route("run-code") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        language_id: 71,
                        source_code: code,
                        stdin: test.stdin
                    })
                }).then(r => r.json());
                if (!res.token) {
                    document.getElementById(`test-result-${i}`).innerText = `Test ${i+1}: Error al enviar código`;
                    continue;
                }
                // Poll result
                let output = '';
                let status = '';
                for (let tries = 0; tries < 10; tries++) {
                    const poll = await fetch(`/code-result/${res.token}`).then(r => r.json());
                    if (poll.status && poll.status.id < 3) {
                        await new Promise(r => setTimeout(r, 1000));
                        continue;
                    }
                    output = poll.stdout || poll.stderr || poll.compile_output || '';
                    status = poll.status && poll.status.description ? poll.status.description : '';
                    break;
                }
                // Comparar output esperado
                const ok = output.trim() === test.expected.trim();
                document.getElementById(`test-result-${i}`).innerHTML = `
                    <span class="font-bold">Test ${i+1}:</span>
                    <span class="${ok ? 'text-green-400' : 'text-red-400'}">${ok ? '✔️ OK' : '❌ Falló'}</span>
                    <br><span class="text-xs">Output: <pre class="inline">${output}</pre></span>
                    <br><span class="text-xs">Esperado: <pre class="inline">${test.expected}</pre></span>
                `;
            }
        }

        // Render tests on load
        renderTests();
    </script>

</body>
</html>
