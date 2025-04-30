@extends('layouts.app')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Listado de Sensores</h5>
        <a href="{{ route('sensors.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Nuevo Sensor
        </a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Tipo</th>
                        <th>Dispositivo</th>
                        <th>Aula</th>
                        <th>Estado</th>
                        <th>Última Lectura</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($sensors as $sensor)
                    <tr>
                        <td>{{ $sensor->name }}</td>
                        <td>{{ $sensor->sensorType->name }}</td>
                        <td>{{ $sensor->device->name }}</td>
                        <td>{{ $sensor->device->classroom->name }}</td>
                        <td>
                            <span class="badge badge-{{ $sensor->status ? 'success' : 'danger' }}">
                                {{ $sensor->status ? 'Activo' : 'Inactivo' }}
                            </span>
                        </td>
                        <td>
                            @if($sensor->readings->count() > 0)
                                {{ $sensor->readings->first()->value }} {{ $sensor->sensorType->unit }}
                            @else
                                Sin datos
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('sensors.show', $sensor) }}" class="btn btn-sm btn-info">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="{{ route('sensors.edit', $sensor) }}" class="btn btn-sm btn-warning">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form action="{{ route('sensors.destroy', $sensor) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('¿Estás seguro?')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection