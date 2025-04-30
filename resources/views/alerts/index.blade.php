@extends('layouts.app')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Listado de Alertas</h5>
        <div>
            <a href="{{ route('alerts.unresolved') }}" class="btn btn-warning">
                <i class="fas fa-exclamation-triangle"></i> Ver no resueltas
            </a>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Mensaje</th>
                        <th>Sensor</th>
                        <th>Valor</th>
                        <th>Gravedad</th>
                        <th>Fecha</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($alerts as $alert)
                    <tr>
                        <td>{{ $alert->id }}</td>
                        <td>{{ $alert->alertRule->message }}</td>
                        <td>{{ $alert->sensorReading->sensor->name }}</td>
                        <td>
                            {{ $alert->sensorReading->value }} 
                            {{ $alert->sensorReading->sensor->sensorType->unit }}
                        </td>
                        <td>
                            <span class="badge badge-{{ 
                                $alert->alertRule->severity == 'danger' ? 'danger' : 
                                ($alert->alertRule->severity == 'warning' ? 'warning' : 'info') 
                            }}">
                                {{ ucfirst($alert->alertRule->severity) }}
                            </span>
                        </td>
                        <td>{{ $alert->created_at->format('d/m/Y H:i') }}</td>
                        <td>
                            <span class="badge badge-{{ $alert->resolved ? 'success' : 'danger' }}">
                                {{ $alert->resolved ? 'Resuelta' : 'Pendiente' }}
                            </span>
                        </td>
                        <td>
                            @if(!$alert->resolved)
                            <form action="{{ route('alerts.resolve', $alert) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-success" title="Marcar como resuelta">
                                    <i class="fas fa-check"></i>
                                </button>
                            </form>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            {{ $alerts->links() }}
        </div>
    </div>
</div>
@endsection