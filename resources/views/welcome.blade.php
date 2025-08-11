<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Code Editor</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            display: flex;
            flex-direction: row;
            min-height: 100vh;
            margin: 0;
            padding: 1rem;
            font-family: 'Inter', sans-serif;
            background-color: #1a202c;
            color: #e2e8f0;
            gap: 1rem;
        }

        .left-panel {
            display: flex;
            flex-direction: column;
            width: 30%;
            min-width: 250px;
            gap: 0.75rem;
        }

        #editor {
            flex-grow: 1;
            width: 70%;
            border: 1px solid #4a5568;
            border-radius: 0.5rem;
            overflow: hidden;
        }

        #output {
            padding: 1rem;
            background-color: #2d3748;
            color: #0f0;
            max-height: 200px;
            overflow-y: auto;
            border-radius: 0.5rem;
            border: 1px solid #4a5568;
            font-family: monospace;
            white-space: pre-wrap;
            word-break: break-all;
            flex-grow: 1;
        }

        #stdin {
            padding: 0.75rem;
            background-color: #2d3748;
            color: #0f0;
            width: 100%;
            max-height: 120px;
            resize: vertical;
            border-radius: 0.5rem;
            border: 1px solid #4a5568;
            box-sizing: border-box;
            font-family: monospace;
            flex-grow: 0;
            min-height: 60px;
        }

        button {
            background-color: #4299e1;
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 0.5rem;
            cursor: pointer;
            font-weight: bold;
            transition: background-color 0.2s ease-in-out;
            align-self: flex-start;
        }

        button:hover {
            background-color: #3182ce;
        }

        @media (max-width: 768px) {
            body {
                flex-direction: column;
                padding: 0.5rem;
            }

            .left-panel {
                width: 100%;
                min-width: unset;
            }

            #editor {
                width: 100%;
                height: 400px;
            }

            #output {
                max-height: 100px;
            }

            #stdin {
                max-height: 80px;
            }
        }
    </style>
</head>
<body>
    <div class="left-panel">
        <button onclick="submitCode()">Run</button>
        <pre id="output">OutPut: </pre>
        <textarea id="stdin" rows="4" placeholder="Stdin input"></textarea>
    </div>

    <div id="editor"></div>

<script src="https://unpkg.com/monaco-editor@latest/min/vs/loader.js"></script>
<script>
    let editor;
    const language_id = 71;

    require.config({ paths: { 'vs': 'https://unpkg.com/monaco-editor@latest/min/vs' } });
    require(['vs/editor/editor.main'], function () {
        editor = monaco.editor.create(document.getElementById('editor'), {
            value: '',
            language: 'python',
            theme: 'vs-dark',
            minimap: { enabled: false }
        });

        window.addEventListener('resize', () => {
            editor.layout();
        });
    });

    async function submitCode() {
        const code = editor.getValue();
        const stdin = document.getElementById('stdin').value;
        document.getElementById('output').textContent = 'Running...';

        try {
            const runRes = await fetch('/run-code', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    source_code: code,
                    language_id: language_id,
                    stdin: stdin
                })
            });

            if (!runRes.ok) {
                const errorData = await runRes.json();
                document.getElementById('output').textContent = `Error al enviar el código: ${errorData.message || runRes.statusText}`;
                return;
            }

            const { token } = await runRes.json();

            let result;
            let attempts = 0;
            const maxAttempts = 30;
            const delay = 1000;

            do {
                await new Promise(resolve => setTimeout(resolve, delay));
                const pollRes = await fetch(`/code-result/${token}`);

                if (!pollRes.ok) {
                    const errorData = await pollRes.json();
                    document.getElementById('output').textContent = `Error al obtener el resultado: ${errorData.message || pollRes.statusText}`;
                    return;
                }

                result = await pollRes.json();
                attempts++;

                if (attempts >= maxAttempts && ['In Queue', 'Processing'].includes(result.status?.description)) {
                    document.getElementById('output').textContent = 'Tiempo de espera agotado al obtener el resultado. Por favor, inténtalo de nuevo.';
                    return;
                }
            } while (['In Queue', 'Processing'].includes(result.status?.description));

            const output = result.stdout || result.stderr || result.compile_output || 'No Output.';
            document.getElementById('output').textContent = output;

        } catch (error) {
            document.getElementById('output').textContent = `Ocurrió un error inesperado: ${error.message}`;
            console.error('Error de fetch:', error);
        }
    }
</script>

</body>
</html>
