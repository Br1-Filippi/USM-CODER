# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Overview

USM-CODER is a Laravel 12 (PHP 8.2) LeetCode-style coding-exam platform for a university. Professors create tests with coding questions; students solve them in an in-browser Monaco editor; code is executed and graded against unit tests by a **Judge0** instance. Exams can be locked down via **Safe Exam Browser (SEB)** config files. UI strings and messages are in Spanish.

## Commands

```bash
composer dev            # Run server + queue worker + log tailer (pail) + vite concurrently
php artisan serve       # Just the PHP dev server
npm run dev             # Vite dev server (assets) — needed for the editor/UI to work
npm run build           # Production asset build

composer test           # Clears config then runs the full PHPUnit suite
php artisan test                                   # Run all tests
php artisan test --filter test_method_name         # Run a single test by method
php artisan test tests/Feature/ExampleTest.php     # Run a single test file
php artisan test --testsuite=Unit                  # Run one suite (Unit | Feature)

./vendor/bin/pint       # Format/lint (Laravel Pint) — the project's code style tool
php artisan migrate     # Apply migrations (SQLite by default)
```

Tests run against an in-memory SQLite DB (see `phpunit.xml`); the dev DB defaults to SQLite at `database/database.sqlite`.

## Environment

Judge0 connection is required for any code execution and is read directly via `env()` in `CodeController` (not through a config file):

- `JUDGE_API_URL` — base URL of the Judge0 API (e.g. `http://localhost:8000/api`)
- `JUDGE_API_KEY` — sent as the `X-Auth-Token` header
- `JUDGE_API_HOST` — used only by the `/test-judge0` debug route

## Architecture

### Custom authentication & roles (not Laravel's scaffolding)
- Auth is hand-rolled. `users.tipo` is an enum: `alumno` (student), `profesor`, `admin`. **Role checks compare against `$user->tipo` directly** (e.g. `SubmissionController` rejects anyone whose `tipo != 'alumno'`).
- `User` has `hasOne` relations to `Student`/`Profesor`/`Admin` that **share the same primary key as the user** (`id`-to-`id`, with `incrementing = false` on those models) rather than a foreign key. A user row and its role row have the same `id`.
- Protected routes use the `check.auth` middleware alias (`App\Http\Middleware\CheckAuth`), registered in `bootstrap/app.php`. It only verifies `Auth::check()` and redirects to `home`; it does **not** enforce roles — role enforcement lives inside controllers.
- Login flow: `LoginRequest::authenticate()` calls `Auth::attempt()` and throws a `ValidationException` (Spanish message) on failure.

### Code execution & grading pipeline
`CodeController` is the integration layer with Judge0. All grading is stdout-comparison based:
- `submitToJudge0()` posts a submission (optionally with `&wait=true`); `pollSubmission()` polls the token until the status leaves the "In Queue/Processing" states (status ids 1 and 2).
- `runCode` — free-run with arbitrary stdin (the playground).
- `runSingleTest` / `runAllTests` — run code against a question's `UniTest` rows, comparing `trim(stdout) === trim(expected_output)`.
- `getScore()` reuses `runAllTests` and returns the **percentage of unit tests passed**. `SubmissionController::submitCode` calls this and persists the percentage as `Submission.score`.

### Domain model relationships
`Career → Course → Test → Question → UniTest`, plus `Submission`.
- `Question` belongs to a `Language` via the **`lenguaje_id`** column (Spanish), has many `UniTest` (the graded test cases: `stdin` + `expected_output`) and many `Submission`.
- `Course` ↔ `Profesor`/`Student` are many-to-many through `profesor_course` / `student_course` pivot tables.
- Most models use `SoftDeletes`. Several (`Question`, `UniTest`, `Submission`) set `public $timestamps = false`.

### SEB (Safe Exam Browser) integration
`SebController` generates a `.seb` config file for a test by hand-building an Apple plist XML (`arrayToPlist`) and returning it as a downloadable attachment, so students can launch the exam in locked-down kiosk mode.

### Frontend
Blade views under `resources/views/` (no SPA). Assets compiled by Vite from `resources/sass/app.scss` and `resources/js/app.js`. Stack: Bootstrap 5 + Tailwind 4 + the **Monaco editor** (the coding surface). Asset entry points are declared in `vite.config.js`.

## Conventions & gotchas
- Many resource controllers (`Admin`, `Career`, `Profesor`, `Student`, `Course`, `Language`, `UniTest`, etc.) are **scaffolded stubs with empty methods** — not all routes are wired up. Check `routes/web.php` for what is actually active.
- Domain naming mixes English and Spanish (`tipo`, `alumno`, `lenguaje_id`, `profesors`); follow the existing column/relation names exactly when querying.
- Judge0 credentials are accessed with `env()` inside controllers, so they are **not** cached by `config:cache`.
