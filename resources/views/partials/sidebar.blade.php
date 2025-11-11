<div class="sidebar">
    <div class="sidebar-header">
        <h3>SINOA</h3>
    </div>
    <nav class="sidebar-nav">
        <ul class="nav">
            
            <li class="nav-item">
                <a class="nav-link {{ request()->is('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->is('devices*') ? 'active' : '' }}" href="{{ route('devices.index') }}">
                    <i class="fas fa-microchip"></i> Dispositivos
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->is('sensors*') ? 'active' : '' }}" href="{{ route('sensors.index') }}">
                    <i class="fas fa-thermometer-half"></i> Sensores
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->is('alerts*') ? 'active' : '' }}" href="{{ route('alerts.index') }}">
                    <i class="fas fa-bell"></i> Alertas
                    @if($unresolvedAlertsCount = \App\Models\Alert::where('resolved', false)->count())
                        <span class="badge badge-danger">{{ $unresolvedAlertsCount }}</span>
                    @endif
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link {{ request()->is('config*') ? 'active' : '' }}" href="{{ route('config.index') }}">
                    <i class="fas fa-sliders-h"></i> Configuración
                </a>
            </li>
        </ul>
    </nav>
</div>