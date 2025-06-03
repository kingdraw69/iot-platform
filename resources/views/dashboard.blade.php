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
                <div class="d-flex align-items-center">
                    <!-- Toggle de Tiempo Real -->
                    <div class="form-check form-switch me-3">
                        <input class="form-check-input" type="checkbox" id="realTimeToggle" checked>
                        <label class="form-check-label" for="realTimeToggle">Tiempo Real</label>
                    </div>

                    <!-- Selección de dispositivo -->
                    <select id="deviceSelect_main" class="form-select me-2 device-select" aria-label="Seleccione un dispositivo">
                        <option value="" disabled selected>Seleccione un dispositivo</option>
                        @if($devices->isEmpty())
                            <option value="" disabled>No hay dispositivos disponibles</option>
                        @else
                            @foreach($devices as $device)
                                <option value="{{ $device->id }}">{{ $device->name }}</option>
                            @endforeach
                        @endif
                    </select>

                    <!-- Selección de sensor -->
                    <select id="sensorSelect_main" class="form-select sensor-select" aria-label="Seleccione un sensor">
                        <option value="" disabled selected>Seleccione un sensor</option>
                    </select>
                </div>
            </div>
            <div class="card-body">
                <canvas id="sensorsChart_main" class="sensor-chart" height="300"></canvas>
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
                        @foreach($reading->alerts as $alert)
                            <a href="#" class="list-group-item list-group-item-action">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1">Sensor: {{ $reading->sensor->name }}</h6>
                                    <small>{{ \Carbon\Carbon::parse($alert->created_at)->diffForHumans() }}</small>
                                </div>
                                <p class="mb-1">Mensaje: {{ $alert->alertRule->message }}</p>
                                <small>Valor detectado: {{ $reading->value }} {{ $reading->sensor->sensorType->unit }}</small>
                            </a>
                        @endforeach
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <button id="addMonitorButton" class="btn btn-primary mb-3">Agregar Monitor de Sensores</button>
    </div>
</div>
<div id="monitorsContainer" class="row">
    <!-- Aquí se agregarán los monitores dinámicamente -->
</div>

@push('scripts')
<script src="https://js.pusher.com/7.0/pusher.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    

    // Suscripción al canal de sensores
    const channel = pusher.subscribe('sensor');

    document.addEventListener('DOMContentLoaded', function () {
        const monitorsContainer = document.getElementById('monitorsContainer');
        const addMonitorButton = document.getElementById('addMonitorButton');

        // Control del modo en vivo
        let liveUpdateInterval;
        const realTimeToggle = document.getElementById('realTimeToggle');
        realTimeToggle.addEventListener('change', function () {
            if (!this.checked && liveUpdateInterval) {
                clearInterval(liveUpdateInterval);
            }
        });

        window.removeMonitor = function (monitorId) {
            document.getElementById(monitorId)?.remove();
        };

        function initializeChart(chartId) {
            const deviceSelect = document.getElementById(`deviceSelect_${chartId}`);
            const sensorSelect = document.getElementById(`sensorSelect_${chartId}`);
            const canvas = document.getElementById(`sensorsChart_${chartId}`);
            
            if (!deviceSelect || !sensorSelect || !canvas) {
                console.error(`Elementos no encontrados para chartId: ${chartId}`);
                return;
            }

            const ctx = canvas.getContext('2d');
            let chartInstance = Chart.getChart(canvas);
            if (chartInstance) {
                chartInstance.destroy();
            }
            chartInstance = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Lectura del sensor',
                        data: [],
                        borderColor: '#2196F3',
                        backgroundColor: 'rgba(33, 150, 243, 0.2)',
                        borderWidth: 2,
                        tension: 0.3,
                        pointRadius: 0
                    }]
                },
                options: {
                    responsive: true,
                    animation: { duration: 500 },
                    scales: {
                        x: {
                            display: true,
                            title: {
                                display: true,
                                text: 'Tiempo'
                            }
                        },
                        y: {
                            display: true,
                            title: {
                                display: true,
                                text: 'Valor'
                            }
                        }
                    }
                }
            });

            // Actualizar gráficos con Pusher
            channel.bind('App\\Events\\NewSensorReading', function(data) {
                const selectedSensorId = sensorSelect.value;
                if (data.sensor_id == selectedSensorId) {
                    const tiempo = data.reading_time.replace('T', ' ').slice(0, 19);
                    const valor = parseFloat(data.value);
                    if (!chartInstance.data.labels.includes(tiempo)) {
                        chartInstance.data.labels.push(tiempo);
                        chartInstance.data.datasets[0].data.push(valor);
                        if (chartInstance.data.labels.length > 100) {
                            chartInstance.data.labels.shift();
                            chartInstance.data.datasets[0].data.shift();
                        }
                        chartInstance.update();
                    }
                }
            });

            deviceSelect.addEventListener('change', async function () {
                const deviceId = this.value;
                sensorSelect.innerHTML = '<option value="" disabled selected>Seleccione un sensor</option>';
                if (deviceId) {
                    await loadSensors(deviceId, sensorSelect);
                }
            });

            sensorSelect.addEventListener('change', function () {
                const sensorId = this.value;
                if (sensorId && realTimeToggle.checked) {
                    startLiveUpdates(sensorId, chartInstance);
                }
            });
        }

        async function loadSensors(deviceId, sensorSelect) {
            try {
                console.log(`Cargando sensores para deviceId: ${deviceId}`);
                const response = await fetch(`/api/devices/${deviceId}/sensors`);
                if (!response.ok) {
                    throw new Error(`Error ${response.status}: ${response.statusText}`);
                }
                const sensors = await response.json();
                console.log('Sensores recibidos:', sensors);
                sensorSelect.innerHTML = '<option value="" disabled selected>Seleccione un sensor</option>';
                if (sensors.length === 0) {
                    sensorSelect.innerHTML += '<option value="" disabled>No hay sensores disponibles</option>';
                } else {
                    sensors.forEach(sensor => {
                        const option = document.createElement('option');
                        option.value = sensor.id;
                        option.textContent = sensor.name;
                        sensorSelect.appendChild(option);
                    });
                }
            } catch (error) {
                console.error('Error al cargar sensores:', error);
                sensorSelect.innerHTML = '<option value="" disabled selected>Error al cargar sensores</option>';
            }
        }

        function startLiveUpdates(sensorId, chartInstance) {
            if (liveUpdateInterval) {
                clearInterval(liveUpdateInterval);
            }
            liveUpdateInterval = setInterval(async () => {
                try {
                    const response = await fetch(`/api/sensors/${sensorId}/readings?limit=1`);
                    if (!response.ok) {
                        throw new Error(`Error ${response.status}: ${response.statusText}`);
                    }
                    const data = await response.json();
                    const lectura = data[0];
                    if (lectura) {
                        const tiempo = lectura.reading_time.replace('T', ' ').slice(0, 19);
                        const valor = parseFloat(lectura.value);
                        if (!chartInstance.data.labels.includes(tiempo)) {
                            chartInstance.data.labels.push(tiempo);
                            chartInstance.data.datasets[0].data.push(valor);
                            if (chartInstance.data.labels.length > 100) {
                                chartInstance.data.labels.shift();
                                chartInstance.data.datasets[0].data.shift();
                            }
                            chartInstance.update();
                        }
                    }
                } catch (error) {
                    console.error('Error al actualizar lecturas:', error);
                }
            }, 2000);
        }

        // Agregar monitores dinámicos
        addMonitorButton.addEventListener('click', function() {
            const monitorId = `monitor-${Date.now()}`;
            const chartId = `chart-${Date.now()}`;
            const monitorHTML = `
                <div class="col-md-6 mb-3" id="${monitorId}">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5>Monitor de Sensores</h5>
                            <button class="btn btn-danger btn-sm" onclick="removeMonitor('${monitorId}')">Eliminar</button>
                        </div>
                        <div class="card-body">
                            <select id="deviceSelect_${chartId}" class="form-select mb-2 device-select" aria-label="Seleccione un dispositivo">
                                <option value="" disabled selected>Seleccione un dispositivo</option>
                                @foreach($devices as $device)
                                    <option value="{{ $device->id }}">{{ $device->name }}</option>
                                @endforeach
                            </select>
                            <select id="sensorSelect_${chartId}" class="form-select mb-2 sensor-select" aria-label="Seleccione un sensor">
                                <option value="" disabled selected>Seleccione un sensor</option>
                            </select>
                            <canvas id="sensorsChart_${chartId}" class="sensor-chart" height="300"></canvas>
                        </div>
                    </div>
                </div>
            `;
            monitorsContainer.insertAdjacentHTML('beforeend', monitorHTML);
            initializeChart(chartId);
        });

        // Inicializar gráfica principal
        initializeChart('main');
    });
</script>
@endpush
@endsection