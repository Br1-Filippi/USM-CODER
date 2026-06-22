/*
 * Pyodide worker with a genuinely blocking, interactive stdin.
 *
 * Why input() is overridden instead of relying on Pyodide's stdin alone:
 *   CPython block-buffers stdout, so an input() prompt (which has no trailing
 *   newline) is NOT reliably flushed to the terminal *before* the program
 *   blocks waiting for the line — the prompt would appear late or not at all.
 *   So we replace builtins.input with a function that:
 *     1. posts the prompt to the terminal explicitly (deterministic ordering),
 *     2. blocks the worker thread on Atomics.wait() until the main thread
 *        (xterm.js) writes the typed line into a SharedArrayBuffer,
 *     3. returns the line (without the trailing newline, like real input()).
 *
 * Shared memory layout:
 *   control: Int32Array(2)
 *     [0] = state flag  (0 = waiting for input, 1 = input ready)
 *     [1] = byte length of the line currently in `data`
 *   data: Uint8Array(N)  -> the UTF-8 encoded line (incl. trailing "\n")
 */

const PYODIDE_VERSION = "0.27.2";

let pyodide = null;
let control = null; // Int32Array over the shared control buffer
let data = null;    // Uint8Array over the shared data buffer
const decoder = new TextDecoder();
const encoder = new TextEncoder();

self.onmessage = async (e) => {
    const msg = e.data;

    switch (msg.type) {
        case "init":
            control = new Int32Array(msg.control);
            data = new Uint8Array(msg.data);
            try {
                await initPyodide();
                self.postMessage({ type: "ready" });
            } catch (err) {
                self.postMessage({ type: "init-error", message: String(err && err.stack || err) });
            }
            break;

        case "run":
            await runCode(msg.code);
            break;
    }
};

async function initPyodide() {
    importScripts(
        `https://cdn.jsdelivr.net/pyodide/v${PYODIDE_VERSION}/full/pyodide.js`
    );

    pyodide = await loadPyodide();

    pyodide.setStdout({
        write: (buffer) => {
            self.postMessage({ type: "stdout", bytes: buffer.slice() });
            return buffer.length;
        },
    });
    pyodide.setStderr({
        write: (buffer) => {
            self.postMessage({ type: "stderr", bytes: buffer.slice() });
            return buffer.length;
        },
    });

    // Still wire a blocking stdin so sys.stdin.read()/readline() also work.
    pyodide.setStdin({ stdin: () => blockForLine(), autoEOF: false });

    // The JS half of the input() override (callable from Python via `js`).
    self.__prompt_input = (prompt) => {
        if (prompt) {
            self.postMessage({ type: "stdout", bytes: encoder.encode(String(prompt)) });
        }
        return blockForLine().replace(/\r?\n$/, "");
    };

    // Install the override + make stdout line-buffered so print() flushes.
    await pyodide.runPythonAsync(`
import sys, builtins
from js import __prompt_input
try:
    sys.stdout.reconfigure(line_buffering=True)
    sys.stderr.reconfigure(line_buffering=True)
except Exception:
    pass

def _input(prompt=""):
    return __prompt_input("" if prompt is None else str(prompt))

builtins.input = _input
`);
}

// Blocks the worker thread until the main thread supplies a line of input.
// Returns the decoded text (including its trailing newline).
function blockForLine() {
    Atomics.store(control, 0, 0);
    self.postMessage({ type: "input-request" });
    Atomics.wait(control, 0, 0);

    const len = Atomics.load(control, 1);
    return decoder.decode(data.slice(0, len));
}

async function runCode(code) {
    self.postMessage({ type: "run-start" });
    try {
        await pyodide.runPythonAsync(code);
        self.postMessage({ type: "run-done" });
    } catch (err) {
        self.postMessage({ type: "run-error", message: String(err) });
    }
}
