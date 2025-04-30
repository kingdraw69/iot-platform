<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'IoT School Security') }}</title>

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=Nunito" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-SgOJa3DmI69IUzQ2PVdRZhwQ+dy64/BUtbMJw1MZ8t5HZApcHrRKUc4W0kG879m7" crossorigin="anonymous">
    
    <!-- Lightweight Charts -->
    <script src="https://unpkg.com/lightweight-charts/dist/lightweight-charts.standalone.production.js"></script>
    
    <!-- Custom CSS -->
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    
    @stack('styles')
</head>
<body>
    <div id="app">
        <!-- Navbar Superior -->
        <nav class="navbar navbar-expand-md navbar-dark bg-primary shadow-sm">
            <div class="container-fluid">
                <button class="navbar-toggler me-2" type="button" data-bs-toggle="collapse" data-bs-target="#sidebar">
                    <span class="navbar-toggler-icon"></span>
                </button>
                
                <a class="navbar-brand" href="{{ url('/') }}">
                    <i class="fas fa-shield-alt me-2"></i> IoT School Security
                </a>
                
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                    <ul class="navbar-nav ms-auto">
                        @guest
                            @if (Route::has('login'))
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('login') }}">{{ __('Login') }}</a>
                                </li>
                            @endif

                            @if (Route::has('register'))
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('register') }}">{{ __('Register') }}</a>
                                </li>
                            @endif
                        @else
                            <li class="nav-item dropdown">
                                <a id="navbarDropdown" class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
                                    <i class="fas fa-user-circle me-1"></i> {{ Auth::user()->name }}
                                </a>

                                <div class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                                    <a class="dropdown-item" href="#">
                                        <i class="fas fa-user me-1"></i> Perfil
                                    </a>
                                    <a class="dropdown-item" href="{{ route('logout') }}"
                                       onclick="event.preventDefault();
                                                     document.getElementById('logout-form').submit();">
                                        <i class="fas fa-sign-out-alt me-1"></i> {{ __('Logout') }}
                                    </a>

                                    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                        @csrf
                                    </form>
                                </div>
                            </li>
                        @endguest
                    </ul>
                </div>
            </div>
        </nav>

        <!-- Contenedor Principal -->
        <div class="container-fluid">
            <div class="row">
                <!-- Sidebar -->
                @auth
                <div class="col-md-3 col-lg-2 d-md-block bg-dark sidebar collapse" id="sidebar">
                    <div class="position-sticky pt-3">
                        <ul class="nav flex-column">
                            <li class="nav-item">
                                <a class="nav-link {{ request()->is('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                                    <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->is('devices*') ? 'active' : '' }}" href="{{ route('devices.index') }}">
                                    <i class="fas fa-microchip me-2"></i> Dispositivos
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->is('sensors*') ? 'active' : '' }}" href="{{ route('sensors.index') }}">
                                    <i class="fas fa-thermometer-half me-2"></i> Sensores
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->is('alerts*') ? 'active' : '' }}" href="{{ route('alerts.index') }}">
                                    <i class="fas fa-bell me-2"></i> Alertas
                                    @if($unresolvedAlertsCount = \App\Models\Alert::where('resolved', false)->count())
                                        <span class="badge bg-danger float-end">{{ $unresolvedAlertsCount }}</span>
                                    @endif
                                </a>
                            </li>
                            <li class="nav-item mt-3">
                                <hr class="dropdown-divider bg-light">
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="#">
                                    <i class="fas fa-cog me-2"></i> Configuración
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
                @endauth

                <!-- Contenido Principal -->
                <main class="@auth col-md-9 col-lg-10 ms-sm-auto @else col-12 @endauth px-md-4 py-4">
                    @yield('content')
                </main>
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js" integrity="sha384-k6d4wzSIapyDyv1kpU366/PK5hCdSbCRGRCMv+eplOQJWyd1fbcAu9OCUj5zNLiq" crossorigin="anonymous"></script>
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Pusher para actualización en tiempo real -->
    <script src="https://js.pusher.com/7.0/pusher.min.js"></script>
    <script>
        // Enable pusher logging - don't include this in production
        Pusher.logToConsole = true;

        var pusher = new Pusher('{{ env('PUSHER_APP_KEY') }}', {
            cluster: '{{ env('PUSHER_APP_CLUSTER') }}',
            forceTLS: true
        });
    </script>
    
    @stack('scripts')
</body>
</html>