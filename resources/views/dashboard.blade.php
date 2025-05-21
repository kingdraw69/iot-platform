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
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5>Monitor de Sensores en Tiempo Real</h5>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="realTimeToggle" checked>
                    <label class="form-check-label" for="realTimeToggle">Tiempo real</label>
                </div>
            </div>
            <div class="card-body">
                <canvas id="sensorsChart" height="300"></canvas>
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
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/luxon@3.0.1"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-luxon@1.2.0"></script>
<script>
    // Configuración inicial del gráfico
    const ctx = document.getElementById('sensorsChart').getContext('2d');
    let sensorsChart = new Chart(ctx, {
        type: 'line',
        data: {
            datasets: [] // Se llenará dinámicamente
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                intersect: false,
                mode: 'index'
            },
            scales: {
                x: {
                    type: 'time',
                    time: {
                        unit: 'minute',
                        displayFormats: {
                            minute: 'HH:mm'
                        },
                        tooltipFormat: 'DD/MM HH:mm'
                    },
                    title: {
                        display: true,
                        text: 'Tiempo'
                    }
                },
                y: {
                    title: {
                        display: true,
                        text: 'Valor'
                    }
                }
            },
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        boxWidth: 12,
                        usePointStyle: true
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            label += context.parsed.y.toFixed(2);
                            return label;
                        }
                    }
                }
            }
        }
    });

    // Variables de estado
    let isRealTimeActive = true;
    let lastUpdateTimes = {};

    // Función para cargar datos iniciales
    async function loadInitialData() {
        try {
            const response = await fetch('/api/sensors/all/readings');
            const data = await response.json();
            
            updateChartWithData(data.sensors);
            
            // Inicializar lastUpdateTimes
            data.sensors.forEach(sensor => {
                if (sensor.readings.length > 0) {
                    lastUpdateTimes[sensor.id] = sensor.readings[sensor.readings.length - 1].time;
                }
            });
        } catch (error) {
            console.error('Error loading initial data:', error);
        }
    }

    // Función para actualizar el gráfico con nuevos datos
    function updateChartWithData(sensors) {
        sensors.forEach(sensor => {
            const existingDatasetIndex = sensorsChart.data.datasets.findIndex(ds => ds.sensorId === sensor.id);
            
            const dataPoints = sensor.readings.map(reading => ({
                x: reading.time,
                y: reading.value
            }));
            
            if (existingDatasetIndex >= 0) {
                // Actualizar dataset existente
                sensorsChart.data.datasets[existingDatasetIndex].data = dataPoints;
            } else {
                // Crear nuevo dataset
                sensorsChart.data.datasets.push({
                    sensorId: sensor.id,
                    label: `${sensor.name} (${sensor.unit})`,
                    data: dataPoints,
                    borderColor: sensor.color,
                    backgroundColor: 'transparent',
                    borderWidth: 2,
                    tension: 0.1,
                    pointRadius: 0,
                    pointHoverRadius: 5,
                    fill: false
                });
            }
        });
        
        sensorsChart.update();
    }

    // Función para actualizar datos en tiempo real
    async function updateRealTimeData() {
        if (!isRealTimeActive) return;
        
        try {
            const response = await fetch('/api/sensors/all/readings?limit=1');
            const data = await response.json();
            
            data.sensors.forEach(sensor => {
                if (sensor.readings.length > 0) {
                    const latestReading = sensor.readings[0];
                    
                    // Verificar si es una lectura nueva
                    if (!lastUpdateTimes[sensor.id] || latestReading.time > lastUpdateTimes[sensor.id]) {
                        lastUpdateTimes[sensor.id] = latestReading.time;
                        
                        const existingDatasetIndex = sensorsChart.data.datasets.findIndex(
                            ds => ds.sensorId === sensor.id
                        );
                        
                        if (existingDatasetIndex >= 0) {
                            // Agregar nuevo punto
                            sensorsChart.data.datasets[existingDatasetIndex].data.push({
                                x: latestReading.time,
                                y: latestReading.value
                            });
                            
                            // Limitar a 100 puntos por sensor
                            if (sensorsChart.data.datasets[existingDatasetIndex].data.length > 100) {
                                sensorsChart.data.datasets[existingDatasetIndex].data.shift();
                            }
                        }
                    }
                }
            });
            
            sensorsChart.update();
        } catch (error) {
            console.error('Error updating real-time data:', error);
        }
    }

    // Event listeners
    document.getElementById('realTimeToggle').addEventListener('change', function() {
        isRealTimeActive = this.checked;
    });

    // Inicialización
    loadInitialData();
    
    // Actualización periódica
    setInterval(updateRealTimeData, 3000);
    
    // También actualizar cada minuto por si acaso
    setInterval(loadInitialData, 60000);
</script>
@endpush
@endsection