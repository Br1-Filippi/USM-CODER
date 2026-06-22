# USM-CODER

Plataforma de exámenes de programación tipo LeetCode para cursos universitarios.
Los profesores crean pruebas con preguntas de programación; los estudiantes las
resuelven en un editor dentro del navegador; el código se evalúa automáticamente
contra tests unitarios ocultos. Las pruebas pueden bloquearse con **Safe Exam
Browser (SEB)**.

> Construido con Laravel 12. La interfaz está en español.

---

## Características

- **Cursos y roles** — carreras → cursos → pruebas, con tres roles de usuario
  (`alumno` / `profesor` / `admin`).
- **Preguntas de programación** — cada pregunta tiene un lenguaje, una plantilla
  inicial y un conjunto de tests unitarios (pares stdin / salida esperada).
- **Evaluación automática** — las entregas de los estudiantes se ejecutan contra
  los tests unitarios mediante [Judge0](https://judge0.com); el puntaje es el
  porcentaje de tests aprobados.
- **Experiencia en el editor** — editor Monaco con consola de ejecución.
- **Safe Exam Browser** — genera un archivo de configuración `.seb` para rendir
  una prueba en modo kiosco bloqueado.
- **Ejecutor interactivo en el navegador** *(experimental — Fase 0)* — ejecuta
  Python completamente en el navegador (Pyodide + Web Worker) con un `input()`
  real y bloqueante y un sistema de archivos virtual, para práctica/aprendizaje
  sin pasar por el servidor.

---

## Stack tecnológico

| Área | Herramientas |
|------|--------------|
| Backend | PHP 8.2+, Laravel 12 |
| Base de datos | MySQL |
| Build del frontend | Vite |
| Interfaz | Bootstrap 5, Tailwind 4, editor Monaco |
| Ejecución de código (evaluación) | Judge0 (self-hosted o RapidAPI) |
| Ejecutor en el navegador | Pyodide, xterm.js, Web Workers, SharedArrayBuffer |

---

## Requisitos

- PHP **8.2+** con Composer
- Node.js **18+** y npm
- MySQL
- Un endpoint de Judge0 — una instancia self-hosted o una clave de
  [RapidAPI](https://rapidapi.com/judge0-official/api/judge0-ce)

---

## Puesta en marcha

```bash
# 1. Instalar dependencias
composer install
npm install

# 2. Entorno
cp .env.example .env
php artisan key:generate

# 3. Configurar el .env (ver abajo), crear la base de datos y migrar
php artisan migrate

# 4. Levantar todo (servidor + cola + logs + Vite) con un solo comando
composer dev
```

`composer dev` levanta el servidor PHP, el worker de la cola, el visor de logs
(Pail) y el servidor de desarrollo de Vite a la vez. ¿Prefieres separarlos?
Ejecuta `php artisan serve` y `npm run dev` en dos terminales.

### Variables de entorno

Base de datos (por defecto MySQL):

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=usm_leet_code
DB_USERNAME=tu_usuario
DB_PASSWORD=tu_password
```

Judge0 — los valores dependen de si usas una instancia self-hosted o RapidAPI:

```env
# Judge0 self-hosted
JUDGE_API_URL=http://localhost:2358
JUDGE_API_KEY=tu_token           # se envía como X-Auth-Token

# Judge0 CE en RapidAPI
JUDGE_API_URL=https://judge0-ce.p.rapidapi.com
JUDGE_API_KEY=tu_clave_rapidapi  # se envía como X-RapidAPI-Key
JUDGE_API_HOST=judge0-ce.p.rapidapi.com
```

Puedes verificar la conexión en `GET /test-judge0`, que consulta el endpoint
`/languages` de Judge0.

---

## Tests

```bash
composer test                                   # suite completa (PHPUnit)
php artisan test --filter nombre_del_test       # un solo test
php artisan test tests/Feature/ExampleTest.php  # un solo archivo
php artisan test --testsuite=Unit               # una suite (Unit | Feature)
```

Los tests corren contra una base de datos SQLite en memoria (configurada en
`phpunit.xml`).

---

## Notas de arquitectura

**Roles.** La autenticación es personalizada (no usa el scaffolding de Laravel).
El enum `users.tipo` (`alumno` / `profesor` / `admin`) gobierna la autorización,
y las filas de rol (`Student` / `Profesor` / `Admin`) comparten la misma llave
primaria que su `User`. La protección de rutas usa el middleware `check.auth`;
las verificaciones de rol por acción viven dentro de los controladores.

**Modelo de dominio.** `Career → Course → Test → Question → UniTest`, más
`Submission`. Una pregunta referencia su lenguaje mediante `lenguaje_id` y posee
sus tests unitarios (`stdin` + `expected_output`).

**Evaluación.** `CodeController` envía el código a Judge0 y compara
`trim(stdout) === trim(expected_output)` por cada test unitario.
`SubmissionController` guarda el porcentaje resultante como puntaje de la entrega.

**Ejecutor en el navegador (experimental).** Una prueba de concepto temporal
vive en `GET /spike/python`. Ejecuta Python en un Web Worker mediante Pyodide,
con un `input()` bloqueante implementado sobre `SharedArrayBuffer` + `Atomics` y
una terminal xterm.js. Requiere aislamiento de origen cruzado, por lo que el
middleware `cross-origin-isolation` envía las cabeceras
`Cross-Origin-Opener-Policy` / `Cross-Origin-Embedder-Policy` en esas rutas.

---

## Licencia

MIT.
