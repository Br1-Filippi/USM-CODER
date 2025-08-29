@extends('layouts/master')

@section('main-content')
    <div class="d-flex justify-content-center align-items-center vh-100">
        <div class="card shadow" style="width: 22rem;">
            <div class="card-body text-center">
                <h5 class="card-title mb-4">Hola!</h5>
                <a href='{{route('loginForm')}}' class="btn btn-primary me-2">Iniciar Sesion</a>
                <button class="btn btn-secondary">Registrarse</button>
            </div>
        </div>
    </div>
@endsection