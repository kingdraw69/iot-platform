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
                @if(isset($latestReadings) && !$latestReadings->isEmpty())
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
                @else
                    <div class="alert alert-info mb-0">
                        No hay alertas recientes disponibles.
                    </div>
                @endif
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
    document.addEventListener('DOMContentLoaded', function () {
        const MAX_POINTS = 10;
        const monitorsContainer = document.getElementById('monitorsContainer');
        const addMonitorButton = document.getElementById('addMonitorButton');
        const realTimeToggle = document.getElementById('realTimeToggle');
        const deviceSelectMain = document.getElementById('deviceSelect_main');
        const sensorSelectMain = document.getElementById('sensorSelect_main');
        const csrfToken = document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').getAttribute('content') : null;
        const preferencesEndpoints = {
            load: '{{ route('dashboard.preferences.show') }}',
            save: '{{ route('dashboard.preferences.store') }}',
        };

        // Null checks for critical elements
        if (!monitorsContainer) {
            console.error('monitorsContainer not found');
            return;
        }
        if (!addMonitorButton) {
            console.error('addMonitorButton not found');
            return;
        }
        if (!realTimeToggle) {
            console.error('realTimeToggle not found');
            return;
        }
        if (!deviceSelectMain) {
            console.error('deviceSelect_main not found');
            return;
        }
        if (!sensorSelectMain) {
            console.error('sensorSelect_main not found');
            return;
        }

        let dashboardState = {
            main: { device_id: null, sensor_id: null },
            monitors: [],
        };
        let isRestoring = false;
        let saveTimeout;
        const liveUpdateIntervals = new Map();
        const chartInstances = new Map();
        const sensorChannelSubscriptions = new Map();

        function getMonitorContainerId(chartId) {
            return `monitor-${chartId}`;
        }

        function unsubscribeFromSensorChannel(chartId) {
            const subscription = sensorChannelSubscriptions.get(chartId);
            if (!subscription || !window.pusher) {
                return;
            }

            const { channelName, handler } = subscription;
            const channel = pusher.channel(channelName);
            if (channel) {
                channel.unbind('App\\Events\\NewSensorReading', handler);

                const stillUsed = Array.from(sensorChannelSubscriptions.entries())
                    .some(([key, sub]) => key !== chartId && sub.channelName === channelName);

                if (!stillUsed) {
                    pusher.unsubscribe(channelName);
                }
            }

            sensorChannelSubscriptions.delete(chartId);
        }

        function clearLiveUpdate(chartId) {
            if (liveUpdateIntervals.has(chartId)) {
                clearInterval(liveUpdateIntervals.get(chartId));
                liveUpdateIntervals.delete(chartId);
            }
            unsubscribeFromSensorChannel(chartId);
        }

        function formatTimestamp(timestamp) {
            return timestamp ? timestamp.replace('T', ' ').slice(0, 19) : '';
        }

        function pushDataPoint(chartInstance, timestamp, value) {
            if (!chartInstance || !timestamp || Number.isNaN(value)) {
                return;
            }

            const labels = chartInstance.data.labels;
            const dataset = chartInstance.data.datasets[0].data;
            const existingIndex = labels.indexOf(timestamp);

            if (existingIndex !== -1) {
                dataset[existingIndex] = value;
            } else {
                labels.push(timestamp);
                dataset.push(value);
                if (labels.length > MAX_POINTS) {
                    labels.shift();
                    dataset.shift();
                }
            }

            chartInstance.update();
        }

        function buildReadingsUrl(sensorId, limit = MAX_POINTS, sort = null) {
            const params = new URLSearchParams({ limit });
            if (sort) {
                params.append('sort', sort);
            }
            return `/api/sensors/${sensorId}/readings?${params.toString()}`;
        }

        function subscribeToSensorChannel(chartId, sensorId, chartInstance) {
            unsubscribeFromSensorChannel(chartId);

            if (!sensorId || !window.pusher || !realTimeToggle.checked) {
                return;
            }

            const channelName = `sensor.${sensorId}`;
            const channel = pusher.channel(channelName) ?? pusher.subscribe(channelName);

            const handler = function (data) {
                if (!data || Number(data.sensor_id) !== Number(sensorId)) {
                    return;
                }
                pushDataPoint(chartInstance, formatTimestamp(data.reading_time), parseFloat(data.value));
            };

            channel.bind('App\\Events\\NewSensorReading', handler);
            sensorChannelSubscriptions.set(chartId, { channelName, handler });
        }

        function restartLiveUpdates() {
            if (!realTimeToggle.checked) {
                liveUpdateIntervals.forEach(intervalId => clearInterval(intervalId));
                liveUpdateIntervals.clear();
                sensorChannelSubscriptions.forEach((_, chartId) => unsubscribeFromSensorChannel(chartId));
                return;
            }

            const mainInstance = chartInstances.get('main');
            if (dashboardState.main.sensor_id && mainInstance) {
                startLiveUpdates('main', dashboardState.main.sensor_id, mainInstance);
                subscribeToSensorChannel('main', dashboardState.main.sensor_id, mainInstance);
            }

            dashboardState.monitors.forEach(monitor => {
                if (!monitor.sensor_id) {
                    clearLiveUpdate(monitor.id);
                    return;
                }
                const instance = chartInstances.get(monitor.id);
                if (instance) {
                    startLiveUpdates(monitor.id, monitor.sensor_id, instance);
                    subscribeToSensorChannel(monitor.id, monitor.sensor_id, instance);
                }
            });
        }

        async function persistPreferences() {
            if (!csrfToken) {
                console.error('CSRF token not found');
                return;
            }
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
                dashboardState.main = { ...dashboardState.main, ...partialState };
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

        async function loadHistoricalData(sensorId, chartInstance) {
            if (!sensorId || !chartInstance) {
                return;
            }

            try {
                const response = await fetch(buildReadingsUrl(sensorId, MAX_POINTS, 'desc'));
                if (!response.ok) {
                    throw new Error(`Error ${response.status}: ${response.statusText}`);
                }

                let rawData = await response.json();

                // Reverse to get chronological order (oldest first) since backend returns desc
                rawData = rawData.reverse();

                const labels = rawData.map(lectura => formatTimestamp(lectura.reading_time));
                const values = rawData.map(lectura => parseFloat(lectura.value));

                chartInstance.data.labels = labels;
                chartInstance.data.datasets[0].data = values;
                chartInstance.update('none');
            } catch (error) {
                console.error('Error al cargar datos históricos:', error);
            }
        }

        function startLiveUpdates(chartId, sensorId, chartInstance) {
            clearLiveUpdate(chartId);

            if (!sensorId) {
                return;
            }

            const intervalId = setInterval(async () => {
                try {
                    const response = await fetch(buildReadingsUrl(sensorId, 1));
                    if (!response.ok) {
                        throw new Error(`Error ${response.status}: ${response.statusText}`);
                    }
                    const data = await response.json();
                    const lectura = data[0];
                    if (!lectura) {
                        return;
                    }

                    pushDataPoint(chartInstance, formatTimestamp(lectura.reading_time), parseFloat(lectura.value));
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
                        borderColor: '#2563eb',
                        backgroundColor: 'rgba(37, 99, 235, 0.15)',
                        borderWidth: 2,
                        tension: 0.3,
                        pointRadius: 2,
                        pointBackgroundColor: '#2563eb',
                    }],
                },
                options: {
                    responsive: true,
                    animation: { duration: 500 },
                    scales: {
                        x: {
                            display: true,
                            title: { display: true, text: 'Tiempo' },
                        },
                        y: {
                            display: true,
                            title: { display: true, text: 'Valor' },
                        },
                    },
                },
            });

            chartInstances.set(chartId, chartInstance);

            deviceSelect.addEventListener('change', async function () {
                const deviceId = this.value;
                updateStateForChart(chartId, {
                    device_id: deviceId ? Number(deviceId) : null,
                    sensor_id: null,
                });

                clearLiveUpdate(chartId);
                sensorSelect.innerHTML = '<option value="" disabled selected>Seleccione un sensor</option>';
                await loadSensors(deviceId, sensorSelect);

                if (!isRestoring) {
                    persistPreferencesDebounced();
                }
            });

            sensorSelect.addEventListener('change', async function () {
                const sensorId = this.value;
                updateStateForChart(chartId, {
                    sensor_id: sensorId ? Number(sensorId) : null,
                });

                clearLiveUpdate(chartId);

                if (sensorId) {
                    await loadHistoricalData(sensorId, chartInstance);
                    if (realTimeToggle.checked) {
                        startLiveUpdates(chartId, sensorId, chartInstance);
                        subscribeToSensorChannel(chartId, sensorId, chartInstance);
                    }
                }

                if (!sensorId) {
                    unsubscribeFromSensorChannel(chartId);
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

            document.getElementById(containerId)?.remove();

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

            if (monitor.device_id && deviceSelect) {
                deviceSelect.value = monitor.device_id;
                await loadSensors(monitor.device_id, sensorSelect);
            }

            if (monitor.sensor_id && sensorSelect) {
                sensorSelect.value = monitor.sensor_id;
                await loadHistoricalData(monitor.sensor_id, chartInstance);
                if (realTimeToggle.checked) {
                    startLiveUpdates(chartId, monitor.sensor_id, chartInstance);
                    subscribeToSensorChannel(chartId, monitor.sensor_id, chartInstance);
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

        if (addMonitorButton) {
            addMonitorButton.addEventListener('click', async function () {
                const chartId = `chart-${Date.now()}`;
                const monitorState = { id: chartId, device_id: null, sensor_id: null };
                dashboardState.monitors.push(monitorState);

                await renderMonitorFromState(monitorState);

                if (!isRestoring) {
                    persistPreferencesDebounced();
                }
            });
        }

        if (realTimeToggle) {
            realTimeToggle.addEventListener('change', function () {
                restartLiveUpdates();
            });
        }

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
                    main: { device_id: null, sensor_id: null },
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
                    await loadHistoricalData(dashboardState.main.sensor_id, chartInstances.get('main'));
                    if (realTimeToggle.checked) {
                        const mainInstance = chartInstances.get('main');
                        if (mainInstance) {
                            startLiveUpdates('main', dashboardState.main.sensor_id, mainInstance);
                            subscribeToSensorChannel('main', dashboardState.main.sensor_id, mainInstance);
                        }
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