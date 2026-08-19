<header class="header-main">
    <div class="container-fluid">
        <div class="row">
            <div class="col-6 col-sm-4 d-flex align-items-center header-left p-0">
                <span class="header-toggle me-3">
                    <i class="ph-duotone ph-circles-four f-s-22"></i>
                </span>

                <div class="d-none d-md-block">
                    <h5 class="mb-0 f-w-600">{{ $headerTitle ?? 'Panel de Control' }}</h5>
                    <small class="text-muted">
                        Invernadero IoT · Modo:
                        <b class="{{ $modo === 'automatico' ? 'text-success' : 'text-warning' }}">
                            {{ $modo ? strtoupper($modo) : '—' }}
                        </b>
                    </small>
                </div>
            </div>

            <div class="col-6 col-sm-8 d-flex align-items-center justify-content-end header-right p-0">
                <ul class="d-flex align-items-center mb-0 list-unstyled gap-3">

                    {{-- Alertas (solo admin/operador) --}}
                    @if(in_array($role, ['admin','operador']))
                        <li class="header-notifications">
                            <a href="{{ route('alertas.index') }}" class="head-icon d-flex align-items-center text-decoration-none position-relative">
                                <i class="ph-duotone ph-bell f-s-26 text-primary"></i>
                                @if($alertasNoVistas > 0)
                                    <span class="position-absolute top-0 start-100 translate-middle badge-2 rounded-pill bg-danger">
                                        {{ $alertasNoVistas }}
                                    </span>
                                @endif
                            </a>
                        </li>
                    @endif

                    {{-- Temperatura real --}}
                    <li class="header-cloud d-none d-sm-block">
                        <div class="head-icon d-flex align-items-center">
                            <i class="ph-duotone ph-cloud-sun text-primary f-s-26 me-1"></i>
                            <span class="f-w-500">
                                {{ is_null($temp) ? '—' : (number_format($temp, 1) . '°C') }}
                            </span>
                        </div>
                    </li>

                    <li class="header-profile">
                        <div class="dropdown">
                            <a href="#" class="d-flex align-items-center head-icon text-decoration-none"
                               data-bs-toggle="dropdown" aria-expanded="false">
                                <div class="text-end me-2 d-none d-lg-block">
                                    <p class="mb-0 f-s-13 f-w-600 text-dark">{{ Auth::user()->name }}</p>
                                    <p class="mb-0 f-s-11 text-secondary">{{ $roleLabel }}</p>
                                </div>
                                <i class="ph-duotone ph-user-circle f-s-32 text-primary"></i>
                            </a>

                            <ul class="dropdown-menu dropdown-menu-end border-0 shadow-sm mt-3">
                                <li>
                                    <a class="dropdown-item d-flex align-items-center" href="{{ route('profile.edit') }}">
                                        <i class="ph-duotone ph-user me-2"></i> Mi Perfil
                                    </a>
                                </li>

                                {{-- acceso rápido a config solo admin --}}
                                @if($role === 'admin')
                                    <li>
                                        <a class="dropdown-item d-flex align-items-center" href="{{ route('config.edit') }}">
                                            <i class="ph-duotone ph-gear me-2"></i> Configuración
                                        </a>
                                    </li>
                                @endif

                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit"
                                                class="dropdown-item d-flex align-items-center text-danger border-0 bg-transparent">
                                            <i class="ph-duotone ph-sign-out me-2"></i> Cerrar Sesión
                                        </button>
                                    </form>
                                </li>
                            </ul>
                        </div>
                    </li>

                </ul>
            </div>
        </div>
    </div>
</header>
