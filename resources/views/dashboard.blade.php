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
                    <select id="deviceSelect" class="form-select me-2">
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
                    <select id="sensorSelect" class="form-select">
                        <option value="" disabled selected>Seleccione un sensor</option>
                    </select>
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

@push('scripts')

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/luxon@3.0.1"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-luxon@1.2.0"></script>
<script>
    // Configuración del gráfico

    const ctx = document.getElementById('sensorsChart').getContext('2d');
    let sensorsChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: [], // Etiquetas vacías inicialmente
            datasets: [{
                label: 'Lectura del sensor',
                data: [],
                borderColor: '#2196F3',
                backgroundColor: 'rgba(33, 150, 243, 0.2)',
                borderWidth: 2,
                tension: 0.3,
                pointRadius: 0,
            }]
        },
        options: {
            responsive: true,
            animation: {
                duration: 500,
            },
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

    document.addEventListener('DOMContentLoaded', function () {
        const deviceSelect = document.getElementById('deviceSelect');
        const sensorSelect = document.getElementById('sensorSelect');
        const realTimeToggle = document.getElementById('realTimeToggle');

        let refreshInterval = null;

        // Función para reiniciar la gráfica
        function resetChart() {
            sensorsChart.data.labels = [];
            sensorsChart.data.datasets[0].data = [];
            sensorsChart.update();
        }

        // Event listener para el selector de dispositivos
        deviceSelect.addEventListener('change', async function () {
            const deviceId = this.value;

            // Limpiar las opciones anteriores del selector de sensores
            sensorSelect.innerHTML = '<option value="" disabled selected>Seleccione un sensor</option>';
            resetChart(); // Reiniciar la gráfica

            if (deviceId) {
                try {
                    const response = await fetch(`/api/devices/${deviceId}/sensors`);
                    if (!response.ok) {
                        throw new Error(`Error en la API: ${response.statusText}`);
                    }

                    const data = await response.json();

                    if (Array.isArray(data) && data.length > 0) {
                        data.forEach(sensor => {
                            const option = document.createElement('option');
                            option.value = sensor.id;
                            option.textContent = sensor.name;
                            sensorSelect.appendChild(option);
                        });
                    } else {
                        const option = document.createElement('option');
                        option.value = "";
                        option.textContent = "No hay sensores disponibles";
                        sensorSelect.appendChild(option);
                    }
                } catch (error) {
                    console.error('Error al cargar sensores:', error);
                    alert('Error al cargar sensores del dispositivo: ' + error.message);
                }
            }
        });

        // Event listener para el selector de sensores
        sensorSelect.addEventListener('change', function () {
            const sensorId = this.value;

            if (refreshInterval) clearInterval(refreshInterval); // Detener actualizaciones en tiempo real
            resetChart(); // Reiniciar la gráfica

            if (sensorId) {
                if (realTimeToggle.checked) {
                    startLiveUpdates(sensorId);
                } else {
                    loadSensorReadings(sensorId);
                }
            }
        });

        // Función para cargar lecturas históricas
        async function loadSensorReadings(sensorId) {
            try {
                const response = await fetch(`/api/sensors/${sensorId}/readings`);
                if (!response.ok) {
                    throw new Error(`Error en la API: ${response.statusText}`);
                }

                const data = await response.json();

                const labels = data.map(reading => reading.reading_time.replace('T', ' ').slice(0, 19));
                const values = data.map(reading => parseFloat(reading.value));

                sensorsChart.data.labels = labels;
                sensorsChart.data.datasets[0].data = values;
                sensorsChart.update();
            } catch (error) {
                console.error('Error al cargar lecturas:', error);
                alert('Error al cargar lecturas: ' + error.message);
            }
        }

        // Función para actualizaciones en tiempo real
        function startLiveUpdates(sensorId) {
            refreshInterval = setInterval(async () => {
                try {
                    const response = await fetch(`/api/sensors/${sensorId}/readings?limit=1`);
                    if (!response.ok) {
                        throw new Error(`Error en la API: ${response.statusText}`);
                    }

                    const data = await response.json();
                    const lectura = data[0]; // Ajustar según la estructura de la respuesta

                    if (lectura) {
                        const tiempo = lectura.reading_time.replace('T', ' ').slice(0, 19);
                        const valor = parseFloat(lectura.value);

                        if (!sensorsChart.data.labels.includes(tiempo)) {
                            agregarNuevoDato(tiempo, valor);
                        }
                    }
                } catch (error) {
                    console.error('Error al actualizar lecturas en vivo:', error);
                }
            }, 2000);
        }

        // Función para agregar nuevo dato al gráfico
        function agregarNuevoDato(tiempo, valor) {
            sensorsChart.data.labels.push(tiempo);
            sensorsChart.data.datasets[0].data.push(valor);

            if (sensorsChart.data.labels.length > 100) {
                sensorsChart.data.labels.shift();
                sensorsChart.data.datasets[0].data.shift();
            }

            sensorsChart.update();
        }
    });
</script>
@endpush
@endsection