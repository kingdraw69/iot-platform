@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5>Detalles del Sensor: {{ $sensor->name }}</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Tipo:</strong> {{ $sensor->sensorType->name }}</p>
                        <p><strong>Unidad:</strong> {{ $sensor->sensorType->unit }}</p>
                        <p><strong>Rango:</strong> {{ $sensor->sensorType->min_range }} - {{ $sensor->sensorType->max_range }}</p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Dispositivo:</strong> {{ $sensor->device->name }}</p>
                        <p><strong>Aula:</strong> {{ $sensor->device->classroom->name }}</p>
                        <p><strong>Estado:</strong> 
                            <span class="badge badge-{{ $sensor->status ? 'success' : 'danger' }}">
                                {{ $sensor->status ? 'Activo' : 'Inactivo' }}
                            </span>
                        </p>
                    </div>
                </div>
                
                <hr>
                
                <div id="sensorChart" style="height: 300px;"></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5>Últimas Lecturas</h5>
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Valor</th>
                            <th>Fecha</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($readings as $reading)
                        <tr>
                            <td>{{ $reading->value }} {{ $sensor->sensorType->unit }}</td>
                            <td>{{ $reading->reading_time->format('d/m/Y H:i') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                {{ $readings->links() }}
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Configuración del gráfico del sensor
    const chart = LightweightCharts.createChart(document.getElementById('sensorChart'), {
        layout: {
            backgroundColor: '#ffffff',
            textColor: '#333',
        },
        grid: {
            vertLines: {
                color: '#f0f0f0',
            },
            horzLines: {
                color: '#f0f0f0',
            },
        },
        timeScale: {
            borderColor: '#ccc',
        },
    });

    const lineSeries = chart.addLineSeries({
        color: '#2962FF',
        lineWidth: 2,
    });

    // Datos iniciales
    const initialData = [
        @foreach($readings->take(100) as $reading)
        { time: '{{ $reading->reading_time->toDateTimeString() }}', value: {{ $reading->value }} },
        @endforeach
    ];
    
    lineSeries.setData(initialData);

    // Escuchar nuevos datos en tiempo real para este sensor
    const channel = pusher.subscribe('sensor-readings');
    channel.bind('App\\Events\\NewSensorReading', function(data) {
        if(data.sensor_id == {{ $sensor->id }}) {
            lineSeries.update({
                time: data.reading_time,
                value: data.value
            });
        }
    });
</script>
@endpush
@endsection