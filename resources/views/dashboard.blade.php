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
    const channel = pusher.subscribe('sensor');

    document.addEventListener('DOMContentLoaded', function () {
        const monitorsContainer = document.getElementById('monitorsContainer');
        const addMonitorButton = document.getElementById('addMonitorButton');
        const realTimeToggle = document.getElementById('realTimeToggle');
        const deviceSelectMain = document.getElementById('deviceSelect_main');
        const sensorSelectMain = document.getElementById('sensorSelect_main');
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const preferencesEndpoints = {
            load: '{{ route('dashboard.preferences.show') }}',
            save: '{{ route('dashboard.preferences.store') }}',
        };

        let dashboardState = {
            main: {
                device_id: null,
                sensor_id: null,
            },
            monitors: [],
        };
        let isRestoring = false;
        let saveTimeout;
        const liveUpdateIntervals = new Map();
        const chartInstances = new Map();

        function getMonitorContainerId(chartId) {
            return `monitor-${chartId}`;
        }

        function clearLiveUpdate(chartId) {
            if (liveUpdateIntervals.has(chartId)) {
                clearInterval(liveUpdateIntervals.get(chartId));
                liveUpdateIntervals.delete(chartId);
            }
        }

        function restartLiveUpdates() {
            if (!realTimeToggle.checked) {
                liveUpdateIntervals.forEach(intervalId => clearInterval(intervalId));
                liveUpdateIntervals.clear();
                return;
            }

            const mainInstance = chartInstances.get('main');
            if (dashboardState.main.sensor_id && mainInstance) {
                startLiveUpdates('main', dashboardState.main.sensor_id, mainInstance);
            }

            dashboardState.monitors.forEach(monitor => {
                if (!monitor.sensor_id) {
                    clearLiveUpdate(monitor.id);
                    return;
                }
                const instance = chartInstances.get(monitor.id);
                if (instance) {
                    startLiveUpdates(monitor.id, monitor.sensor_id, instance);
                }
            });
        }

        async function persistPreferences() {
            try {
                await fetch(preferencesEndpoints.save, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({ layout: dashboardState }),
                });
            } catch (error) {
                console.error('Error al guardar preferencias:', error);
            }
        }

        function persistPreferencesDebounced() {
            clearTimeout(saveTimeout);
            saveTimeout = setTimeout(persistPreferences, 400);
        }

        function updateStateForChart(chartId, partialState) {
            if (chartId === 'main') {
                dashboardState.main = {
                    ...dashboardState.main,
                    ...partialState,
                };
                return;
            }

            const monitor = dashboardState.monitors.find(m => m.id === chartId);
            if (monitor) {
                Object.assign(monitor, partialState);
            }
        }

        async function loadSensors(deviceId, sensorSelect) {
            sensorSelect.innerHTML = '<option value="" disabled selected>Seleccione un sensor</option>';

            if (!deviceId) {
                return;
            }

            try {
                const response = await fetch(`/api/devices/${deviceId}/sensors`);
                if (!response.ok) {
                    throw new Error(`Error ${response.status}: ${response.statusText}`);
                }
                const sensors = await response.json();
                if (sensors.length === 0) {
                    sensorSelect.innerHTML += '<option value="" disabled>No hay sensores disponibles</option>';
                    return;
                }

                sensors.forEach(sensor => {
                    const option = document.createElement('option');
                    option.value = sensor.id;
                    option.textContent = sensor.name;
                    sensorSelect.appendChild(option);
                });
            } catch (error) {
                console.error('Error al cargar sensores:', error);
                sensorSelect.innerHTML = '<option value="" disabled selected>Error al cargar sensores</option>';
            }
        }

        function startLiveUpdates(chartId, sensorId, chartInstance) {
            clearLiveUpdate(chartId);

            if (!sensorId) {
                return;
            }

            const intervalId = setInterval(async () => {
                try {
                    const response = await fetch(`/api/sensors/${sensorId}/readings?limit=1`);
                    if (!response.ok) {
                        throw new Error(`Error ${response.status}: ${response.statusText}`);
                    }
                    const data = await response.json();
                    const lectura = data[0];
                    if (!lectura) {
                        return;
                    }

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
                } catch (error) {
                    console.error('Error al actualizar lecturas:', error);
                }
            }, 2000);

            liveUpdateIntervals.set(chartId, intervalId);
        }

        function initializeChart(chartId) {
            const deviceSelect = document.getElementById(`deviceSelect_${chartId}`);
            const sensorSelect = document.getElementById(`sensorSelect_${chartId}`);
            const canvas = document.getElementById(`sensorsChart_${chartId}`);

            if (!deviceSelect || !sensorSelect || !canvas) {
                console.error(`Elementos no encontrados para chartId: ${chartId}`);
                return null;
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
                        pointRadius: 0,
                    }],
                },
                options: {
                    responsive: true,
                    animation: { duration: 500 },
                    scales: {
                        x: {
                            display: true,
                            title: {
                                display: true,
                                text: 'Tiempo',
                            },
                        },
                        y: {
                            display: true,
                            title: {
                                display: true,
                                text: 'Valor',
                            },
                        },
                    },
                },
            });

            chartInstances.set(chartId, chartInstance);

            channel.bind('App\\Events\\NewSensorReading', function(data) {
                const selectedSensorId = sensorSelect.value;
                if (String(data.sensor_id) === String(selectedSensorId)) {
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
                updateStateForChart(chartId, {
                    device_id: deviceId ? Number(deviceId) : null,
                    sensor_id: null,
                });

                clearLiveUpdate(chartId);
                await loadSensors(deviceId, sensorSelect);

                if (!isRestoring) {
                    persistPreferencesDebounced();
                }
            });

            sensorSelect.addEventListener('change', function () {
                const sensorId = this.value;
                updateStateForChart(chartId, {
                    sensor_id: sensorId ? Number(sensorId) : null,
                });

                if (sensorId && realTimeToggle.checked) {
                    startLiveUpdates(chartId, sensorId, chartInstance);
                } else {
                    clearLiveUpdate(chartId);
                }

                if (!isRestoring) {
                    persistPreferencesDebounced();
                }
            });

            return chartInstance;
        }

        async function renderMonitorFromState(monitor) {
            const chartId = monitor.id;
            const containerId = getMonitorContainerId(chartId);

            const existing = document.getElementById(containerId);
            if (existing) {
                existing.remove();
            }

            const monitorHTML = `
                <div class="col-md-6 mb-3" id="${containerId}">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5>Monitor de Sensores</h5>
                            <button class="btn btn-danger btn-sm" onclick="removeMonitor('${chartId}')">Eliminar</button>
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

            const chartInstance = initializeChart(chartId);
            if (!chartInstance) {
                return;
            }

            const deviceSelect = document.getElementById(`deviceSelect_${chartId}`);
            const sensorSelect = document.getElementById(`sensorSelect_${chartId}`);

            if (monitor.device_id) {
                deviceSelect.value = monitor.device_id;
                await loadSensors(monitor.device_id, sensorSelect);
            }

            if (monitor.sensor_id) {
                sensorSelect.value = monitor.sensor_id;
                if (realTimeToggle.checked) {
                    startLiveUpdates(chartId, monitor.sensor_id, chartInstance);
                }
            }
        }

        window.removeMonitor = function (chartId) {
            const container = document.getElementById(getMonitorContainerId(chartId));
            if (container) {
                container.remove();
            }

            const monitorIndex = dashboardState.monitors.findIndex(m => m.id === chartId);
            if (monitorIndex !== -1) {
                dashboardState.monitors.splice(monitorIndex, 1);
            }

            chartInstances.delete(chartId);
            clearLiveUpdate(chartId);

            if (!isRestoring) {
                persistPreferencesDebounced();
            }
        };

        addMonitorButton.addEventListener('click', async function () {
            const chartId = `chart-${Date.now()}`;

            const monitorState = {
                id: chartId,
                device_id: null,
                sensor_id: null,
            };

            dashboardState.monitors.push(monitorState);

            await renderMonitorFromState(monitorState);

            if (!isRestoring) {
                persistPreferencesDebounced();
            }
        });

        realTimeToggle.addEventListener('change', function () {
            restartLiveUpdates();
        });

        async function loadPreferences() {
            try {
                const response = await fetch(preferencesEndpoints.load, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                if (!response.ok) {
                    throw new Error(`Error ${response.status}: ${response.statusText}`);
                }

                const data = await response.json();
                const layout = data.layout ?? {};

                dashboardState = {
                    main: {
                        device_id: layout.main?.device_id ?? null,
                        sensor_id: layout.main?.sensor_id ?? null,
                    },
                    monitors: Array.isArray(layout.monitors)
                        ? layout.monitors
                            .filter(monitor => monitor && monitor.id)
                            .map(monitor => ({
                                id: monitor.id,
                                device_id: monitor.device_id ?? null,
                                sensor_id: monitor.sensor_id ?? null,
                            }))
                        : [],
                };
            } catch (error) {
                console.error('Error al cargar preferencias:', error);
                dashboardState = {
                    main: {
                        device_id: null,
                        sensor_id: null,
                    },
                    monitors: [],
                };
            }
        }

        async function applyPreferences() {
            isRestoring = true;

            try {
                if (dashboardState.main.device_id) {
                    deviceSelectMain.value = dashboardState.main.device_id;
                    await loadSensors(dashboardState.main.device_id, sensorSelectMain);
                } else {
                    sensorSelectMain.innerHTML = '<option value="" disabled selected>Seleccione un sensor</option>';
                }

                if (dashboardState.main.sensor_id) {
                    sensorSelectMain.value = dashboardState.main.sensor_id;
                    const mainChartInstance = chartInstances.get('main');
                    if (realTimeToggle.checked && mainChartInstance) {
                        startLiveUpdates('main', dashboardState.main.sensor_id, mainChartInstance);
                    }
                }

                monitorsContainer.innerHTML = '';
                for (const monitor of dashboardState.monitors) {
                    await renderMonitorFromState(monitor);
                }
            } finally {
                isRestoring = false;
            }
        }

        initializeChart('main');

        loadPreferences()
            .then(applyPreferences)
            .catch(error => console.error('No se pudieron aplicar las preferencias:', error));
    });
</script>
@endpush
@endsection
