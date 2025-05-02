@extends('layouts.app')

@section('content')
    <div class="container">
        <h1>Editar Sensor</h1>
        <form action="{{ route('sensors.update', $sensor->id) }}" method="POST">
            @csrf
            @method('PUT')
            <!-- Campos del formulario -->
            <button type="submit" class="btn btn-primary">Actualizar</button>
        </form>
    </div>
@endsection