@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-info-circle me-2"></i> Detalles del Dispositivo
                </h5>
                <div>
                    <a href="{{ route('devices.edit', $device) }}" class="btn btn-warning btn-sm">
                        <i class="fas fa-edit me-1"></i> Editar
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>Información Básica</h6>
                        <ul class="list-group list-group-flush mb-4">
                            <li class="list-group-item d-flex justify-content-between">
                                <span class="fw-bold">Nombre:</span>
                                <span>{{ $device->name }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span class="fw-bold">Número de Serie:</span>
                                <span>{{ $device->serial_number }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span class="fw-bold">Tipo:</span>
                                <span class="badge bg-info">{{ $device->deviceType->name }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span class="fw-bold">Estado:</span>
                                <span class="badge bg-{{ $device->status ? 'success' : 'danger' }}">
                                    {{ $device->status ? 'Activo' : 'Inactivo' }}
                                </span>
                            </li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h6>Ubicación y Conexión</h6>
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between">
                                <span class="fw-bold">Aula:</span>
                                <span>{{ $device->classroom->name }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span class="fw-bold">Edificio/Piso:</span>
                                <span>{{ $device->classroom->building }}, Piso {{ $device->classroom->floor }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span class="fw-bold">Dirección IP:</span>
                                <span>{{ $device->ip_address ?? 'No asignada' }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span class="fw-bold">Dirección MAC:</span>
                                <span>{{ $device->mac_address ?? 'No asignada' }}</span>
                            </li>
                        </ul>
                    </div>
                </div>
                
                <div class="mt-4">
                    <h6>Última Comunicación</h6>
                    <p class="mb-0">
                        @if($device->last_communication)
                            {{ $device->last_communication->diffForHumans() }}
                            <small class="text-muted">({{ $device->last_communication->format('d/m/Y H:i:s') }})</small>
                        @else
                            <span class="text-muted">Nunca</span>
                        @endif
                    </p>
                </div>
            </div>
        </div>
        
        <!-- Sensores asociados -->
        <div class="card shadow-sm">
            <div class="card-header bg-secondary text-white">
                <h5 class="mb-0">
                    <i class="fas fa-thermometer-half me-2"></i> Sensores Asociados
                </h5>
            </div>
            <div class="card-body">
                @if($device->sensors->count() > 0)
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Tipo</th>
                                <th>Estado</th>
                                <th>Última Lectura</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($device->sensors as $sensor)
                            <tr>
                                <td>{{ $sensor->name }}</td>
                                <td>{{ $sensor->sensorType->name }}</td>
                                <td>
                                    <span class="badge bg-{{ $sensor->status ? 'success' : 'danger' }}">
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
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="alert alert-warning mb-0">
                    <i class="fas fa-exclamation-triangle me-2"></i> Este dispositivo no tiene sensores asociados.
                </div>
                @endif
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <!-- Historial de estados -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0">
                    <i class="fas fa-history me-2"></i> Historial de Estados
                </h5>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    @forelse($device->statusLogs->sortByDesc('changed_at') as $log)
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span>
                            <i class="fas fa-{{ $log->status ? 'check-circle text-success' : 'times-circle text-danger' }} me-2"></i>
                            {{ $log->changed_at->diffForHumans() }}
                        </span>
                        <span class="badge bg-{{ $log->status ? 'success' : 'danger' }}">
                            {{ $log->status ? 'Activado' : 'Desactivado' }}
                        </span>
                    </li>
                    @empty
                    <li class="list-group-item text-center text-muted">
                        No hay registro de cambios de estado
                    </li>
                    @endforelse
                </ul>
            </div>
        </div>
        
        <!-- Acciones rápidas -->
        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0">
                    <i class="fas fa-bolt me-2"></i> Acciones Rápidas
                </h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <form action="{{ route('devices.toggle-status', $device) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-{{ $device->status ? 'danger' : 'success' }} mb-2">
                            <i class="fas fa-power-off me-2"></i> 
                            {{ $device->status ? 'Desactivar' : 'Activar' }} Dispositivo
                        </button>
                    </form>
                    
                    <a href="{{ route('sensors.create', ['device_id' => $device->id]) }}" class="btn btn-primary mb-2">
                        <i class="fas fa-plus-circle me-2"></i> Añadir Sensor
                    </a>
                    
                    <form action="{{ route('devices.destroy', $device) }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger" onclick="return confirm('¿Estás seguro de eliminar este dispositivo y todos sus sensores?')">
                            <i class="fas fa-trash-alt me-2"></i> Eliminar Dispositivo
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection