@extends('layouts.navbar')

@section('main-content')
<div class="container my-4">
    <div class="row row-cols-1 row-cols-md-4 g-4 justify-content-center">
        @foreach ($tests as $test)
            <div class="col">
                <div class="card h-100 shadow-sm">
                    <div class="card-body text-center">
                        <h5 class="card-title">{{$test->id}}</h5>
                        <a href="{{ route('tests.show', $test->id) }}" class="btn btn-primary">Editar Test</a>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection
