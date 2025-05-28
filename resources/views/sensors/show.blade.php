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
                
                <canvas id="sensorChart" style="width: 100%; height: 300px;"></canvas>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5>Últimas Lecturas</h5>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label for="filterDate">Filtrar por Fecha:</label>
                    <input type="datetime-local" id="filterDate" name="filterDate" class="form-control">
                    <button id="filterButton" class="btn btn-primary mt-2">Filtrar</button>
                </div>
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
                            <td>{{ \Carbon\Carbon::parse($reading->reading_time)->format('d/m/Y ') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="d-flex justify-content-center mt-3">
                    {{ $readings->links() }}
                </div>

            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    // Obtener contexto del canvas
    const ctx = document.getElementById('sensorChart').getContext('2d');

    // Etiquetas (tiempo) y valores iniciales desde Blade
    const labels = [
        @foreach($readings->take(100) as $reading)
            "{{ \Carbon\Carbon::parse($reading->reading_time)->format('Y-m-d ') }}",
        @endforeach
    ];

    const dataValues = [
        @foreach($readings->take(100) as $reading)
            {{ floatval($reading->value) }},
        @endforeach
    ];

    // Crear gráfico de líneas con Chart.js
    const sensorChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Lectura del sensor',
                data: dataValues,
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

    // Función para agregar nuevo dato al gráfico
    function agregarNuevoDato(tiempo, valor) {
        sensorChart.data.labels.push(tiempo);
        sensorChart.data.datasets[0].data.push(valor);

        // Limitar a 100 datos
        if (sensorChart.data.labels.length > 100) {
            sensorChart.data.labels.shift();
            sensorChart.data.datasets[0].data.shift();
        }

        sensorChart.update();
    }

    // Elimina la simulación local:
    // setInterval(() => { ... }, 3000);

    // ID del sensor (puedes pasarlo desde Blade)
    const sensorId = {{ $sensor->id }};

    // Última fecha conocida (para evitar duplicados)
    let ultimaFecha = labels.length > 0 ? labels[labels.length - 1] : null;

    // Función para consultar la API periódicamente
    setInterval(() => {
        fetch(`/api/sensors/${sensorId}/readings?limit=1`)
            .then(response => response.json())
            .then(data => {
                if (data.readings && data.readings.data && data.readings.data.length > 0) {
                    const lectura = data.readings.data[0];
                    const tiempo = lectura.reading_time.replace('T', ' ').slice(0, 19);
                    const valor = parseFloat(lectura.value);

                    // Solo agregar si es una lectura nueva
                    if (tiempo !== ultimaFecha) {
                        agregarNuevoDato(tiempo, valor);
                        ultimaFecha = tiempo;
                    }
                }
            });
    }, 3000);

    const filterButton = document.getElementById('filterButton');
    // Ajustar el código de filtrado para asegurar que los datos se actualicen correctamente
    filterButton.addEventListener('click', () => {
    let rawDate = document.getElementById('filterDate').value;
    if (!rawDate) {
        alert('Selecciona una fecha válida.');
        return;
    }

    const dateOnly = rawDate.split('T')[0]; // Extraer solo la parte de la fecha

    fetch(`/api/sensors/${sensorId}/readings?date=${dateOnly}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`Error en la API: ${response.statusText}`);
            }
            return response.json();
        })
        .then(data => {
            const tbody = document.querySelector('#readingsTable tbody');
            tbody.innerHTML = '';

            if (data.readings && data.readings.data && data.readings.data.length > 0) {
                const filteredLabels = [];
                const filteredValues = [];

                data.readings.data.forEach(reading => {
                    const date = new Date(reading.reading_time);
                    const formattedDate = `${String(date.getDate()).padStart(2, '0')}/${String(date.getMonth()+1).padStart(2, '0')}/${date.getFullYear()}`;

                    filteredLabels.push(formattedDate);
                    filteredValues.push(parseFloat(reading.value));

                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${reading.value} ${sensor.sensorType.unit}</td>
                        <td>${formattedDate}</td>
                    `;
                    tbody.appendChild(row);
                });

                // Actualizar gráfico
                sensorChart.data.labels = filteredLabels;
                sensorChart.data.datasets[0].data = filteredValues;
                sensorChart.update();

                // Ocultar paginación cuando se filtra
                document.querySelector('.d-flex.justify-content-center.mt-3')?.classList.add('d-none');
            } else {
                alert('No se encontraron lecturas para la fecha seleccionada.');
                sensorChart.data.labels = [];
                sensorChart.data.datasets[0].data = [];
                sensorChart.update();
                document.querySelector('.d-flex.justify-content-center.mt-3')?.classList.add('d-none');
            }
        })
        .catch(error => {
            console.error('Error al filtrar lecturas:', error);
            alert('Error al filtrar lecturas: ' + error.message);
        });
    });

</script>
@endpush
@endsection