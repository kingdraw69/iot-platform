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

<div class="row">
    <div class="col-md-12">
        <button id="addChartButton" class="btn btn-primary mb-3">+ Agregar Monitor de Sensores</button>
        <div id="chartsContainer">
            <!-- Contenedor dinámico para monitores de sensores -->
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const chartsContainer = document.getElementById('chartsContainer');
        const addChartButton = document.getElementById('addChartButton');

        // Agregar nuevo gráfico
        addChartButton.addEventListener('click', function () {
            const chartId = `chart-${Date.now()}`;
            const chartDiv = document.createElement('div');
            chartDiv.className = 'card mb-3';

            const chartHeader = document.createElement('div');
            chartHeader.className = 'card-header d-flex justify-content-between align-items-center';
            chartHeader.innerHTML = `<h5>Monitor de Sensores en Tiempo Real</h5>
                                     <button class="btn btn-danger btn-sm" onclick="removeChart('${chartId}')">Eliminar</button>`;

            const chartBody = document.createElement('div');
            chartBody.className = 'card-body';
            chartBody.innerHTML = `<div class="d-flex align-items-center mb-3">
                                        <select id="deviceSelect_${chartId}" class="form-select me-2">
                                            <option value="" disabled selected>Seleccione un dispositivo</option>
                                            @foreach($devices as $device)
                                                <option value="{{ $device->id }}">{{ $device->name }}</option>
                                            @endforeach
                                        </select>
                                        <select id="sensorSelect_${chartId}" class="form-select">
                                            <option value="" disabled selected>Seleccione un sensor</option>
                                        </select>
                                    </div>
                                    <canvas id="sensorsChart_${chartId}" height="300"></canvas>`;

            chartDiv.appendChild(chartHeader);
            chartDiv.appendChild(chartBody);
            chartsContainer.appendChild(chartDiv);

            initializeChart(chartId); // Inicializar el nuevo gráfico
        });

        window.removeChart = function (chartId) {
            const chartDiv = document.getElementById(`sensorsChart_${chartId}`).closest('.card');
            chartsContainer.removeChild(chartDiv);
        };

        function initializeChart(chartId) {
            const deviceSelect = document.getElementById(`deviceSelect_${chartId}`);
            const sensorSelect = document.getElementById(`sensorSelect_${chartId}`);
            const ctx = document.getElementById(`sensorsChart_${chartId}`).getContext('2d');

            const chartInstance = new Chart(ctx, {
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

            deviceSelect.addEventListener('change', async function () {
                const deviceId = this.value;
                sensorSelect.innerHTML = '<option value="" disabled selected>Seleccione un sensor</option>';

                if (deviceId) {
                    try {
                        const response = await fetch(`/api/devices/${deviceId}/sensors`);
                        if (!response.ok) {
                            throw new Error(`Error en la API: ${response.statusText}`);
                        }

                        const sensors = await response.json();

                        sensors.forEach(sensor => {
                            const option = document.createElement('option');
                            option.value = sensor.id;
                            option.textContent = sensor.name;
                            sensorSelect.appendChild(option);
                        });
                    } catch (error) {
                        console.error('Error al cargar sensores:', error);
                        alert('Error al cargar sensores del dispositivo: ' + error.message);
                    }
                }
            });

            sensorSelect.addEventListener('change', function () {
                const sensorId = this.value;

                if (sensorId) {
                    startLiveUpdates(sensorId, chartInstance);
                }
            });
        }

        function startLiveUpdates(sensorId, chartInstance) {
            setInterval(async () => {
                try {
                    const response = await fetch(`/api/sensors/${sensorId}/readings?limit=1`);
                    if (!response.ok) {
                        throw new Error(`Error en la API: ${response.statusText}`);
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
                    console.error('Error al actualizar lecturas en vivo:', error);
                }
            }, 2000);
        }

        // Inicializar la gráfica principal
        const ctx = document.getElementById('sensorsChart').getContext('2d');
        new Chart(ctx, {
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

        const deviceSelect = document.getElementById('deviceSelect');
        const sensorSelect = document.getElementById('sensorSelect');

        deviceSelect.addEventListener('change', async function () {
            const deviceId = this.value;
            sensorSelect.innerHTML = '<option value="" disabled selected>Seleccione un sensor</option>';

            if (deviceId) {
                try {
                    const response = await fetch(`/api/devices/${deviceId}/sensors`);
                    if (!response.ok) {
                        throw new Error(`Error en la API: ${response.statusText}`);
                    }

                    const sensors = await response.json();

                    sensors.forEach(sensor => {
                        const option = document.createElement('option');
                        option.value = sensor.id;
                        option.textContent = sensor.name;
                        sensorSelect.appendChild(option);
                    });
                } catch (error) {
                    console.error('Error al cargar sensores:', error);
                    alert('Error al cargar sensores del dispositivo: ' + error.message);
                }
            }
        });
    });
</script>
@endpush
@endsection