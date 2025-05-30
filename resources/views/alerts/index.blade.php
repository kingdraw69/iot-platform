@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Alertas Activas</h1>
    <div class="list-group">
        @foreach($alerts as $alert)
            <a href="#" class="list-group-item list-group-item-action">
                <div class="d-flex w-100 justify-content-between">
                    <h6 class="mb-1">Sensor: {{ $alert->sensorReading->sensor->name }}</h6>
                    <small>{{ \Carbon\Carbon::parse($alert->created_at)->diffForHumans() }}</small>
                </div>
                <p class="mb-1">Mensaje: {{ $alert->alertRule->message }}</p>
                <small>Valor detectado: {{ $alert->sensorReading->value }} {{ $alert->sensorReading->sensor->sensorType->unit }}</small>
                <small>Aula: {{ $alert->sensorReading->sensor->device->classroom->name }}</small>
            </a>
        @endforeach
    </div>
    {{ $alerts->links() }}

    <div class="card mt-4">
        <div class="card-header">
            <h5>Historial de Alertas</h5>
        </div>
        <div class="card-body">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Sensor</th>
                        <th>Dispositivo</th>
                        <th>Regla</th>
                        <th>Fecha</th>
                        <th>Resuelta en</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($alertHistory as $alert)
                        <tr>
                            <td>{{ $alert->sensorReading->sensor->name }}</td>
                            <td>{{ $alert->sensorReading->sensor->device->name }}</td>
                            <td>{{ $alert->alertRule->message }}</td>
                            <td>{{ $alert->created_at }}</td>
                            <td>{{ $alert->resolved_at }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection