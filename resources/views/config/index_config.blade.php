@extends('layouts.app')

@section('content')
<div class="container-fluid px-4">
    <div class="card shadow border-0">
        <!-- Card Header -->
        <div class="card-header d-flex justify-content-between align-items-center bg-primary text-white py-3">
            <div class="d-flex align-items-center">
                <i class="fas fa-sliders-h fa-2x me-3"></i>
                <div>
                    <h4 class="mb-0">Configuración del Sistema</h4>
                    <p class="mb-0 opacity-75">Ajusta los parámetros de tu plataforma IoT</p>
                </div>
            </div>
        </div>

        <div class="card-body pt-4">
            @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show d-flex align-items-center" role="alert">
                <i class="fas fa-check-circle fa-2x me-3"></i>
                <div>
                    <h5 class="alert-heading mb-1">¡Éxito!</h5>
                    <p class="mb-0">{{ session('success') }}</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            @endif

            <form action="{{ route('config.update') }}" method="POST">
                @csrf

                <!-- Configuración General -->
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0"><i class="fas fa-cog me-2"></i> Configuración General</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="app_name" class="form-label">Nombre de la Aplicación</label>
                                <input type="text" class="form-control @error('app_name') is-invalid @enderror" 
                                    id="app_name" name="app_name" 
                                    value="{{ old('app_name', $settings['app_name']) }}" required>
                                @error('app_name')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="app_url" class="form-label">URL de la Aplicación</label>
                                <input type="url" class="form-control @error('app_url') is-invalid @enderror" 
                                    id="app_url" name="app_url" 
                                    value="{{ old('app_url', $settings['app_url']) }}" required>
                                @error('app_url')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Configuración de Email -->
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0"><i class="fas fa-envelope me-2"></i> Configuración de Email</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="mail_from" class="form-label">Email Remitente</label>
                                <input type="email" class="form-control @error('mail_from') is-invalid @enderror" 
                                    id="mail_from" name="mail_from" 
                                    value="{{ old('mail_from', $settings['mail_from']) }}" required>
                                @error('mail_from')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                                <small class="text-muted">Email desde el que se enviarán las notificaciones</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="mail_to" class="form-label">Email de Alertas</label>
                                <input type="email" class="form-control @error('mail_to') is-invalid @enderror"
                                    id="mail_to" name="mail_to"
                                    value="{{ old('mail_to', $settings['mail_to']) }}" required>
                                @error('mail_to')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                                <small class="text-muted">Email donde se recibirán las alertas del sistema</small>
                                <div class="mt-2">
                                    <strong>Email actual:</strong> <span class="text-primary">{{ $settings['mail_to'] ?? 'No configurado' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Configuración de Alertas -->
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0"><i class="fas fa-bell me-2"></i> Configuración de Alertas</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="alert_threshold" class="form-label">Umbral de Alerta (en minutos)</label>
                                <input type="number" class="form-control @error('alert_threshold') is-invalid @enderror"
                                    id="alert_threshold" name="alert_threshold"
                                    value="{{ old('alert_threshold', $settings['alert_threshold']) }}"
                                    min="0" step="1" required>
                                @error('alert_threshold')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                                <small class="text-muted">Tiempo máximo sin comunicación antes de generar alerta</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="sensor_update_interval" class="form-label">Intervalo de Actualización (ms)</label>
                                <input type="number" class="form-control @error('sensor_update_interval') is-invalid @enderror"
                                    id="sensor_update_interval" name="sensor_update_interval"
                                    value="{{ old('sensor_update_interval', $settings['sensor_update_interval']) }}"
                                    min="1000" step="100" required>
                                @error('sensor_update_interval')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                                <small class="text-muted">Frecuencia con la que se actualizan las lecturas de sensores</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Configuración Avanzada -->
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0"><i class="fas fa-tools me-2"></i> Configuración Avanzada</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <div class="d-grid">
                                    <a href="{{ route('sensor-types.create') }}" class="btn btn-outline-primary">
                                        <i class="fas fa-cogs me-2"></i> Gestionar Tipos de Sensores
                                    </a>
                                    <small class="text-muted mt-1">Crear, editar y eliminar tipos de sensores del sistema</small>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="d-grid">
                                    <a href="{{ route('device-types.create') }}" class="btn btn-outline-info">
                                        <i class="fas fa-microchip me-2"></i> Gestionar Tipos de Dispositivos
                                    </a>
                                    <small class="text-muted mt-1">Crear, editar y eliminar tipos de dispositivos IoT</small>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="d-grid">
                                    <a href="{{ route('classrooms.create') }}" class="btn btn-outline-success">
                                        <i class="fas fa-map-marker-alt me-2"></i> Gestionar Ubicaciones de Aulas
                                    </a>
                                    <small class="text-muted mt-1">Agregar y gestionar ubicaciones de aulas del campus</small>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <div class="d-grid">
                                    <a href="{{ route('alert-rules.create') }}" class="btn btn-outline-warning">
                                        <i class="fas fa-bell me-2"></i> Configurar Reglas de Alerta
                                    </a>
                                    <small class="text-muted mt-1">Definir reglas para generar alertas automáticas</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Información del Sistema -->
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i> Información del Sistema</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Versión de PHP</label>
                                <p class="text-muted mb-0">{{ phpversion() }}</p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Versión de Laravel</label>
                                <p class="text-muted mb-0">{{ app()->version() }}</p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Entorno</label>
                                <p class="text-muted mb-0">
                                    <span class="badge bg-{{ app()->environment('production') ? 'danger' : 'info' }}">
                                        {{ app()->environment() }}
                                    </span>
                                </p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Base de Datos</label>
                                <p class="text-muted mb-0">{{ config('database.default') }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Botones de Acción -->
                <div class="d-flex justify-content-between">
                    <a href="{{ route('dashboard') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i> Volver
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i> Guardar Cambios
                    </button>
                </div>
            </form>

            <!-- Configuración Avanzada -->
            <div class="card mt-4" id="advanced-configuration">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0"><i class="fas fa-tools me-2"></i> Configuración Avanzada</h5>
                        <small class="text-muted">Administra recursos adicionales sin salir del panel</small>
                    </div>
                    <a href="{{ route('device-types.create') }}" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-cogs me-1"></i> Abrir gestión completa
                    </a>
                </div>
                <div class="card-body">
                    <div class="row g-4">
                        <div class="col-lg-5">
                            <div class="border rounded p-3 h-100">
                                <h6 class="text-uppercase text-muted mb-3">Resumen</h6>
                                <p class="mb-1">
                                    <i class="fas fa-tag me-2 text-primary"></i>
                                    Tipos de dispositivos registrados:
                                    <strong>{{ $deviceTypes->count() }}</strong>
                                </p>
                                <p class="mb-3">
                                    <i class="fas fa-plug me-2 text-success"></i>
                                    Dispositivos asociados:
                                    <strong>{{ $deviceTypes->sum('devices_count') }}</strong>
                                </p>
                                <p class="text-muted mb-0">
                                    Usa la gestión completa para crear nuevos tipos, asignar descripciones
                                    y mantener organizada la plataforma.
                                </p>
                            </div>
                        </div>
                        <div class="col-lg-7">
                            <h6 class="text-uppercase text-muted mb-3">Tipos de dispositivos</h6>
                            @if($deviceTypes->isEmpty())
                                <div class="text-center py-4">
                                    <i class="fas fa-box-open fa-2x text-muted mb-2"></i>
                                    <p class="mb-1">Aún no registras tipos de dispositivos</p>
                                    <small class="text-muted">Crea uno nuevo desde la gestión completa.</small>
                                </div>
                            @else
                                <div class="table-responsive">
                                    <table class="table table-sm align-middle">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Nombre</th>
                                                <th>Descripción</th>
                                                <th class="text-center">Dispositivos</th>
                                                <th class="text-end">Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($deviceTypes as $deviceType)
                                                <tr>
                                                    <td class="fw-semibold">{{ $deviceType->name }}</td>
                                                    <td>{{ $deviceType->description ?: 'Sin descripción' }}</td>
                                                    <td class="text-center">
                                                        <span class="badge bg-info">{{ $deviceType->devices_count }}</span>
                                                    </td>
                                                    <td class="text-end">
                                                        <a href="{{ route('device-types.edit', $deviceType) }}" class="btn btn-sm btn-outline-secondary me-2">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <form action="{{ route('device-types.destroy', $deviceType) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Eliminar el tipo {{ $deviceType->name }}?')">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-sm btn-outline-danger" {{ $deviceType->devices_count > 0 ? 'disabled' : '' }}>
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .card-header.bg-light {
        background-color: #f8f9fa !important;
        border-bottom: 1px solid #dee2e6;
    }

    .form-label {
        font-weight: 500;
        color: #495057;
    }

    .form-control {
        border-radius: 0.375rem;
        border: 1px solid #dee2e6;
        padding: 0.625rem 0.875rem;
    }

    .form-control:focus {
        border-color: #0d6efd;
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
    }

    small.text-muted {
        font-size: 0.875rem;
        display: block;
        margin-top: 0.25rem;
    }
</style>
@endsection
