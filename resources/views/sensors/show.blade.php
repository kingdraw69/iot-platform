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
                <table class="table table-sm" id="readingsTable">
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
                            <td>{{ \Carbon\Carbon::parse($reading->reading_time)->format('d/m/Y H:i') }}</td>
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
<script src="https://js.pusher.com/7.0/pusher.min.js"></script>
<script src="https://unpkg.com/lightweight-charts/dist/lightweight-charts.standalone.production.js"></script>

<script>
    // Datos iniciales para el gráfico
    const initialData = [
        @foreach($readings->take(100) as $reading)
        { 
            time: '{{ \Carbon\Carbon::parse($reading->reading_time)->format('Y-m-d H:i:s') }}', 
            value: {{ $reading->value }} 
        },
        @endforeach
    ];

    // Configuración del gráfico
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
            timeVisible: true,
            secondsVisible: false,
        },
        rightPriceScale: {
            borderColor: '#ccc',
        },
    });

    const lineSeries = chart.addLineSeries({
        color: '#2962FF',
        lineWidth: 2,
    });
    
    lineSeries.setData(initialData);
    chart.timeScale().fitContent();

    // Configurar Pusher para actualizaciones en tiempo real
    const pusher = new Pusher('{{ config('broadcasting.connections.pusher.key') }}', {
        cluster: '{{ config('broadcasting.connections.pusher.options.cluster') }}',
        encrypted: true
    });

    // Suscribirse al canal específico del sensor
    const channel = pusher.subscribe('sensor.{{ $sensor->id }}');
    
    // Escuchar nuevos datos
    channel.bind('new-reading', function(data) {
    // Solo actualiza si es el sensor correcto
        if (data.sensor_id == {{ $sensor->id }}) {
            lineSeries.update({
                time: data.reading_time,
                value: parseFloat(data.value)
            });
            updateLastReadingTable(data);
        }
    });

    function updateLastReadingTable(data) {
        const tableBody = document.querySelector('#readingsTable tbody');
        const newRow = document.createElement('tr');
        
        newRow.innerHTML = `
            <td>${data.value} {{ $sensor->sensorType->unit }}</td>
            <td>${new Date(data.reading_time).toLocaleString('es-ES')}</td>
        `;
        
        // Insertar la nueva fila al principio de la tabla
        if(tableBody.firstChild) {
            tableBody.insertBefore(newRow, tableBody.firstChild);
        } else {
            tableBody.appendChild(newRow);
        }
        
        // Limitar el número de filas para evitar sobrecargar la tabla
        if(tableBody.children.length > 10) {
            tableBody.removeChild(tableBody.lastChild);
        }
    }
</script>
@endpush
@endsection