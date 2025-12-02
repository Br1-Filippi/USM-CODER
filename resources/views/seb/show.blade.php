@extends('layouts.navbar')

@section('main-content')
<div class="container py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            
            <h2 class="fw-bold text-primary mt-2 mb-0">
                Configuracion Safe Exam Browser
            </h2>
        </div>
    </div>

    <div class="card shadow-sm border-0 rounded-4">
        

        <div class="card-body p-4">
            @if ($errors->any())
                <div class="alert alert-danger py-2">
                    <ul class="mb-0 small">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('seb.download', $test) }}">
                @csrf

                <div class="mb-3">
                    <label for="start_url" class="form-label">URL de la prueba</label>
                    <input
                        type="url"
                        id="start_url"
                        name="start_url"
                        class="form-control @error('start_url') is-invalid @enderror"
                        value="{{ old('start_url', $test_url) }}"
                        required
                    >
                    @error('start_url')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="quit_password" class="form-label">Contraseña para cerrar safe exam</label>
                    <input
                        type="text"
                        id="quit_password"
                        name="quit_password"
                        class="form-control @error('quit_password') is-invalid @enderror"
                        value="{{ old('quit_password') }}"
                        placeholder="Opcional"
                    >
                    @error('quit_password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-check form-switch mb-4">
                    <input
                        class="form-check-input"
                        type="checkbox"
                        id="allow_reload"
                        name="allow_reload"
                        value="1"
                        {{ old('allow_reload', true) ? 'checked' : '' }}
                    >
                    <label class="form-check-label" for="allow_reload">
                        Permitir recargar la página
                    </label>
                </div>

                <button type="submit" class="btn btn-success w-100">
                    Descargar archivo .seb
                </button>
            </form>
        </div>
    </div>

</div>
@endsection
