@extends('layouts.app')

@section('content')
<div class="row">
    <!-- Summary Cards -->
    <div class="col-md-4">
        <div class="card text-white bg-primary mb-3">
            <div class="card-body">
                <h5 class="card-title">Dispositivos Totales</h5>
                <p class="card-text display-4">{{ $totalDevices }}</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-white bg-success mb-3">
            <div class="card-body">
                <h5 class="card-title">Dispositivos Activos</h5>
                <p class="card-text display-4">{{ $activeDevices }}</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-white bg-danger mb-3">
            <div class="card-body">
                <h5 class="card-title">Alertas Activas</h5>
                <p class="card-text display-4">{{ $activeAlerts }}</p>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5>Últimas Lecturas de Sensores</h5>
            </div>
            <div class="card-body">
                <div id="realTimeChart" style="height: 300px;"></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5>Últimas Alertas</h5>
            </div>
            <div class="card-body">
                <div class="list-group">
                    @foreach($latestReadings as $reading)
                        @if($reading->alerts->count() > 0)
                            <a href="#" class="list-group-item list-group-item-action">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1">{{ $reading->sensor->name }}</h6>
                                    <small>{{ \Carbon\Carbon::parse($reading->reading_time)->diffForHumans() }}</small>
                                </div>
                                <p class="mb-1">Valor: {{ $reading->value }} {{ $reading->sensor->sensorType->unit }}</p>
                                <small>Aula: {{ $reading->sensor->device->classroom->name }}</small>
                            </a>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://js.pusher.com/7.0/pusher.min.js"></script>
<script src="https://unpkg.com/lightweight-charts/dist/lightweight-charts.standalone.production.js"></script>

<script>
    // Configuración del gráfico en tiempo real
    const chart = LightweightCharts.createChart(document.getElementById('realTimeChart'), {
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
            timeVisible: true,
            secondsVisible: false,
        },
    });

    const lineSeries = chart.addLineSeries({
        color: '#2962FF',
        lineWidth: 2,
    });

    // Datos iniciales
    const initialData = [
        @foreach($latestReadings as $reading)
        { 
            time: '{{ \Carbon\Carbon::parse($reading->reading_time)->toDateTimeString() }}', 
            value: {{ $reading->value }} 
        },
        @endforeach
    ];
    
    lineSeries.setData(initialData);
    chart.timeScale().fitContent();

    // Configurar Pusher para actualizaciones en tiempo real
    const pusher = new Pusher('{{ config('broadcasting.connections.pusher.key') }}', {
        cluster: '{{ config('broadcasting.connections.pusher.options.cluster') }}',
        encrypted: true
    });

    // Escuchar nuevos datos en tiempo real
    const channel = pusher.subscribe('sensor-readings');
    channel.bind('App\\Events\\NewSensorReading', function(data) {
        lineSeries.update({
            time: data.reading_time,
            value: parseFloat(data.value)
        });
    });
</script>
@endpush
@endsection