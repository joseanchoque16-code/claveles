<nav class="side-menu">
    <div class="app-logo">
        <a class="logo d-inline-block" href="{{ route('panel') }}">
            <img src="{{ asset('images/logo.webp') }}" alt="Logo" width="130">
        </a>
    </div>

    <div class="app-nav" id="app-simple-bar">
        <ul class="main-nav p-0 mt-2">

            {{-- Dashboard --}}
            <li class="no-sub">
                <a href="{{ route('panel') }}" class="{{ request()->routeIs('panel') ? 'active' : '' }}">
                    <i class="ph-duotone ph-house"></i>
                    <span class="menu-text">Panel</span>
                </a>
            </li>

            <li class="menu-title mt-2"><span>MONITOREO</span></li>

            @if(auth()->check() && in_array(auth()->user()->role, ['admin','operador']))
            {{-- Lecturas --}}
            <li class="no-sub">
                <a href="{{ route('lecturas.index') }}" class="{{ request()->routeIs('lecturas.*') ? 'active' : '' }}">
                    <i class="ph-duotone ph-chart-line-up"></i>
                    <span class="menu-text">Lecturas</span>
                </a>
            </li>

            {{-- Sensores --}}
            <li class="no-sub">
                <a href="{{ route('sensores.index') }}" class="{{ request()->routeIs('sensores.*') ? 'active' : '' }}">
                    <i class="ph-duotone ph-thermometer"></i>
                    <span class="menu-text">Sensores</span>
                </a>
            </li>
            @endif

            {{-- CONTROL: Operador + Admin --}}
            @if(auth()->check() && in_array(auth()->user()->role, ['admin','operador']))
                <li class="menu-title mt-2"><span>CONTROL</span></li>

                {{-- Actuadores (OPCIÓN A: si NO tenés la ruta actuadores.index) --}}
                <li class="no-sub">

                    <a href="{{ route('dispositivos.index') }}" class="{{ (request()->routeIs('dispositivos.*') || request()->routeIs('dispositivos.*')) ? 'active' : '' }}"> 
                        <i class="ph-duotone ph-toggle-left"></i>
                        <span class="menu-text">Actuadores</span>
                    </a>
                </li>

                {{-- Actuadores (OPCIÓN B: si SÍ creaste route('actuadores.index')) --}}
                {{--
                <li class="no-sub">
                    <a href="{{ route('dispositivos.index') }}" class="{{ (request()->routeIs('dispositivos.*') || request()->routeIs('dispositivos.*')) ? 'active' : '' }}">
                        <i class="ph-duotone ph-toggle-left"></i>
                        <span class="menu-text">Actuadores</span>
                    </a>
                </li>
                --}}

                {{-- Alertas --}}
                <li class="no-sub">
                    <a href="{{ route('alertas.index') }}" class="{{ request()->routeIs('alertas.*') ? 'active' : '' }}">
                        <i class="ph-duotone ph-bell"></i>
                        <span class="menu-text">Alertas</span>

                        @if(isset($alertasNoVistas) && $alertasNoVistas > 0)
                            <span class="badge bg-danger ms-auto">{{ $alertasNoVistas }}</span>
                        @endif
                    </a>
                </li>
            @endif

            {{-- CONFIGURACIÓN: Solo Admin --}}
            @if(auth()->check() && auth()->user()->role === 'admin')
                <li class="menu-title mt-2"><span>CONFIGURACIÓN</span></li>

                {{-- Reglas Auto --}}
                <li class="no-sub">
                    <a href="{{ route('control-reglas.index') }}" class="{{ request()->routeIs('control-reglas.*') ? 'active' : '' }}">
                        <i class="ph-duotone ph-sliders-horizontal"></i>
                        <span class="menu-text">Reglas (Auto)</span>
                    </a>
                </li>

                {{-- Config global --}}
                <li class="no-sub">
                    <a href="{{ route('config.edit') }}" class="{{ request()->routeIs('config.*') ? 'active' : '' }}">
                        <i class="ph-duotone ph-gear"></i>
                        <span class="menu-text">Modo / Parámetros</span>
                    </a>
                </li>
            @endif

        </ul>
    </div>
</nav>
