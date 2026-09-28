<x-app-layout header-title="Dashboard">
    @php
        $tempSensor  = $sensores->firstWhere('codigo', 'S_TEMP');
        $hrSensor    = $sensores->firstWhere('codigo', 'S_HR');
        $soilSensor  = $sensores->firstWhere('codigo', 'S_HSUELO');
        $luzSensor   = $sensores->firstWhere('codigo', 'S_LUZ');
        $nivelSensor = $sensores->firstWhere('codigo', 'S_NIVEL');

        $tempVal  = is_numeric(optional($tempSensor)->valor_actual) ? (float) $tempSensor->valor_actual : null;
        $hrVal    = is_numeric(optional($hrSensor)->valor_actual) ? (float) $hrSensor->valor_actual : null;
        $soilVal  = is_numeric(optional($soilSensor)->valor_actual) ? (float) $soilSensor->valor_actual : null;
        $luzVal   = is_numeric(optional($luzSensor)->valor_actual) ? (float) $luzSensor->valor_actual : null;
        $nivelVal = is_numeric(optional($nivelSensor)->valor_actual) ? (float) $nivelSensor->valor_actual : null;

        $nivelPct = $nivelVal !== null ? max(0, min(100, $nivelVal <= 5 ? $nivelVal * 20 : $nivelVal)) : 0;
        $riegoPermitido = $nivelVal === null ? 'Sí' : ($nivelPct >= 25 ? 'Sí' : 'No');

        $sensoresActivos = $resumen['sensores_activos'] ?? 0;
        $activosOn       = $resumen['actuadores_encendidos'] ?? 0;
        $criticas        = $resumen['alertas_criticas'] ?? 0;
        $noVistas        = $resumen['alertas_no_vistas'] ?? 0;

        $ultimaActuacion = $eventos->first();
        $ultimaActuacionTexto = $ultimaActuacion
            ? ((optional($ultimaActuacion->dispositivo)->nombre ?? 'Dispositivo') . ' · ' . strtoupper($ultimaActuacion->accion))
            : 'Sin eventos recientes';

        $reglaDominante = $reglasResumen->first()['texto'] ?? 'Sin regla automática visible';

        $resumenOperativo = [
            ['label' => 'Modo actual', 'value' => strtoupper($modo), 'class' => 'text-primary'],
            ['label' => 'Nivel de agua', 'value' => round($nivelPct) . '%', 'class' => 'text-info'],
            ['label' => 'Riego', 'value' => $riegoPermitido === 'Sí' ? 'Permitido' : 'Bloqueado', 'class' => $riegoPermitido === 'Sí' ? 'text-success' : 'text-danger'],
            ['label' => 'Alertas críticas', 'value' => $criticas, 'class' => $criticas > 0 ? 'text-danger' : 'text-success'],
            ['label' => 'Actuadores ON', 'value' => $activosOn, 'class' => 'text-secondary'],
            ['label' => 'Última actuación', 'value' => $ultimaActuacionTexto, 'class' => 'text-dark'],
        ];

        $grafTemp = $tempVal ?? 0;
        $grafHr   = $hrVal ?? 0;
        $grafNiv  = round($nivelPct);
    @endphp

    <div class="container-fluid">

        {{-- BARRA SUPERIOR PRO --}}
        <div class="row mb-4">
            <div class="col-12">
                <div class="card equal-card">
                    <div class="card-body">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                            <div>
                                <h3 class="mb-1">Dashboard {{ $roleLabel }}</h3>
                                <div class="d-flex flex-wrap align-items-center gap-3 text-secondary">
                                    <span><b>Rol:</b> {{ $roleLabel }}</span>
                                    <span><b>Modo:</b> <span id="js-top-modo">{{ strtoupper($modo) }}</span></span>
                                    <span class="d-inline-flex align-items-center gap-2">
                                        <b>ESP32:</b>
                                        <span id="js-esp32-status" class="badge {{ $esp32Status['state'] === 'online' ? 'bg-success' : ($esp32Status['state'] === 'offline' ? 'bg-danger' : 'bg-secondary') }}">
                                            {{ $esp32Status['state'] === 'online' ? 'CONECTADO' : ($esp32Status['state'] === 'offline' ? 'SIN COMUNICACIÓN' : 'SIN REGISTRO') }}
                                        </span>
                                        <small id="js-esp32-last-seen" class="text-secondary">
                                            {{ $esp32Status['last_seen'] ? 'Último sync: ' . date('H:i:s', $esp32Status['last_seen']) : 'Esperando primer sync' }}
                                        </small>
                                    </span>
                                    <span><b>Última actualización:</b> <span id="js-last-refresh">—</span></span>
                                    <span><b>Alertas activas:</b> <span id="js-res-novistas">{{ $noVistas }}</span></span>
                                </div>
                            </div>

                            <div class="d-flex align-items-center gap-4">
                                <div class="text-end">
                                    <p class="text-secondary mb-1">Temperatura actual</p>
                                    <h2 class="text-primary mb-0">
                                        <span class="js-sensor-value" data-id="{{ optional($tempSensor)->id }}">
                                            {{ $tempVal !== null ? number_format($tempVal, 1) : '—' }}
                                        </span>°C
                                    </h2>
                                </div>
                                <div class="text-end">
                                    <p class="text-secondary mb-1">Nivel de agua</p>
                                    <h2 class="text-info mb-0">
                                        <span class="js-water-pct-main">{{ round($nivelPct) }}</span>%
                                    </h2>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- KPIS --}}
        <div class="kpi-slider row">
            <div class="col-sm-6 col-lg-4 col-xxl-2">
                <div class="card eshop-cards">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="bg-primary h-40 w-40 d-flex-center b-r-15 f-s-18">
                                <i class="ph-bold ph-wave-sine"></i>
                            </span>
                        </div>
                        <div class="mt-3">
                            <p class="f-s-16 mb-0">Sensores activos</p>
                            <h5 id="js-res-sensores">{{ $sensoresActivos }}</h5>
                            <p class="text-secondary mb-0">Variables monitoreadas</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-lg-4 col-xxl-2">
                <div class="card eshop-cards">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="bg-secondary h-40 w-40 d-flex-center b-r-15 f-s-18">
                                <i class="ph-bold ph-lightning"></i>
                            </span>
                        </div>
                        <div class="mt-3">
                            <p class="f-s-16 mb-0">Actuadores ON</p>
                            <h5 id="js-res-activos">{{ $activosOn }}</h5>
                            <p class="text-secondary mb-0">Equipos operando</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-lg-4 col-xxl-2">
                <div class="card eshop-cards">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="bg-danger h-40 w-40 d-flex-center b-r-15 f-s-18">
                                <i class="ph-bold ph-warning-circle"></i>
                            </span>
                        </div>
                        <div class="mt-3">
                            <p class="f-s-16 mb-0">Alertas críticas</p>
                            <h5 class="text-danger" id="js-res-criticas">{{ $criticas }}</h5>
                            <p class="text-secondary mb-0">Atención inmediata</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-lg-4 col-xxl-2">
                <div class="card eshop-cards">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="bg-warning h-40 w-40 d-flex-center b-r-15 f-s-18">
                                <i class="ph-bold ph-bell-ringing"></i>
                            </span>
                        </div>
                        <div class="mt-3">
                            <p class="f-s-16 mb-0">No vistas</p>
                            <h5 class="text-warning" id="js-res-novistas-kpi">{{ $noVistas }}</h5>
                            <p class="text-secondary mb-0">Pendientes de revisar</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-lg-4 col-xxl-2">
                <div class="card eshop-cards">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="bg-success h-40 w-40 d-flex-center b-r-15 f-s-18">
                                <i class="ph-bold ph-sliders-horizontal"></i>
                            </span>
                        </div>
                        <div class="mt-3">
                            <p class="f-s-16 mb-0">Modo actual</p>
                            <h5 id="js-res-modo">{{ strtoupper($modo) }}</h5>
                            <p class="text-secondary mb-0">Control global</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-lg-4 col-xxl-2">
                <div class="card eshop-cards">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="bg-info h-40 w-40 d-flex-center b-r-15 f-s-18">
                                <i class="ph-bold ph-drop"></i>
                            </span>
                        </div>
                        <div class="mt-3">
                            <p class="f-s-16 mb-0">Nivel de agua</p>
                            <h5 id="js-res-agua">{{ round($nivelPct) }}%</h5>
                            <p class="text-secondary mb-0">Disponibilidad para riego</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- FILA SENSORES + PANEL OPERATIVO --}}
        <div class="row mt-4">
            <div class="col-lg-8">
                <div class="sensor-slider row">
                    @foreach([
                        ['sensor' => $tempSensor, 'titulo' => 'Temperatura', 'unidad' => '°C', 'icon' => 'ph-thermometer-hot', 'bg' => 'bg-primary', 'codigo' => 'S_TEMP'],
                        ['sensor' => $hrSensor, 'titulo' => 'Humedad Relativa', 'unidad' => '%', 'icon' => 'ph-cloud-rain', 'bg' => 'bg-secondary', 'codigo' => 'S_HR'],
                        ['sensor' => $soilSensor, 'titulo' => 'Humedad de Suelo', 'unidad' => '%', 'icon' => 'ph-plant', 'bg' => 'bg-success', 'codigo' => 'S_HSUELO'],
                        ['sensor' => $luzSensor, 'titulo' => 'Luz (LDR)', 'unidad' => '%', 'icon' => 'ph-sun', 'bg' => 'bg-warning', 'codigo' => 'S_LUZ'],
                    ] as $item)
                        @php
                            $s = $item['sensor'];
                            $valor = is_numeric(optional($s)->valor_actual) ? (float) $s->valor_actual : null;
                            $estadoClase = 'text-light-success';
                            $estadoTexto = 'Normal';

                            if ($item['codigo'] === 'S_TEMP' && $valor !== null) {
                                if ($valor < 10 || $valor >= 24) { $estadoClase = 'text-light-danger'; $estadoTexto = 'Crítico'; }
                                elseif ($valor < 14 || $valor >= 22) { $estadoClase = 'text-light-warning'; $estadoTexto = 'Advertencia'; }
                            }

                            if ($item['codigo'] === 'S_HSUELO' && $valor !== null) {
                                if ($valor > 85) { $estadoClase = 'text-light-danger'; $estadoTexto = 'Exceso'; }
                                elseif ($valor < 60 || $valor > 75) { $estadoClase = 'text-light-warning'; $estadoTexto = 'Fuera de rango'; }
                            }

                            if ($item['codigo'] === 'S_HR' && $valor !== null && $valor >= 75) {
                                $estadoClase = 'text-light-warning';
                                $estadoTexto = 'Ventilación requerida';
                            }

                            $reglasSensor = $reglasResumen->where('sensor_codigo', $item['codigo']);
                        @endphp

                        <div class="col-md-6">
                            <div class="card equal-card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <span class="{{ $item['bg'] }} h-40 w-40 d-flex-center b-r-15 f-s-18">
                                            <i class="ph-bold {{ $item['icon'] }}"></i>
                                        </span>
                                        <span class="badge {{ $estadoClase }}">{{ $estadoTexto }}</span>
                                    </div>

                                    <div class="mt-3">
                                        <p class="f-s-16 mb-0">{{ $item['titulo'] }}</p>
                                        <h3 class="mb-1">
                                            <span class="js-sensor-value" data-id="{{ optional($s)->id }}">
                                                {{ $valor !== null ? number_format($valor, 1) : '—' }}
                                            </span>{{ $item['unidad'] }}
                                        </h3>
                                        <p class="text-secondary mb-1">{{ $item['codigo'] }}</p>
                                        <p class="text-secondary mb-0">Última lectura: {{ optional($s)->updated_at ?? '—' }}</p>
                                    </div>

                                    <div class="app-divider-v dotted py-2"></div>

                                    <p class="f-w-600 mb-2 text-primary">Referencia automática</p>
                                    @forelse($reglasSensor as $r)
                                        <p class="text-secondary mb-1">{{ $r['texto'] }}</p>
                                    @empty
                                        <p class="text-secondary mb-0">Sin regla automática visible</p>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card active-user-card h-100">
                    <div class="card-body">
                        <div>
                            <h5 class="text-dark">Resumen operativo</h5>
                        </div>

                        <div class="d-flex gap-2 mt-3">
                            <div class="active-user-content">
                                <h2 class="text-primary mb-0">{{ round($nivelPct) }}%</h2>
                                <p class="text-secondary text-nowrap mb-0">Nivel actual del tanque</p>

                                <div class="app-divider-v dashed py-3"></div>

                                <p class="f-w-500">Operación actual</p>
                                <div class="d-flex flex-column gap-2">
                                    @foreach($resumenOperativo as $item)
                                        <span class="text-secondary">{{ $item['label'] }}:
                                            <b class="{{ $item['class'] }}">{{ $item['value'] }}</b>
                                        </span>
                                    @endforeach
                                </div>
                            </div>

                            <div class="card card-primary flex-grow-1 user-chart-card">
                                <div class="card-body d-flex flex-column justify-content-center">
                                    <h6 class="text-white mb-2">Regla dominante</h6>
                                    <p class="text-light mb-0">{{ $reglaDominante }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="active-users-content mt-3">
                            <div>
                                <h6 class="mb-0 js-water-pct-main">{{ round($nivelPct) }}%</h6>
                                <p class="text-secondary mb-0">Agua</p>
                            </div>
                            <div>
                                <h6 class="mb-0">{{ $riegoPermitido }}</h6>
                                <p class="text-secondary mb-0">Riego</p>
                            </div>
                            <div>
                                <h6 class="mb-0">{{ $tempVal !== null ? number_format($tempVal,1) : '—' }}°C</h6>
                                <p class="text-secondary mb-0">Temp</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- FILA CONTROL --}}
        <div class="row mt-4">
            <div class="col-lg-8">
                <div class="card equal-card top-product-card">
                    <div class="card-header card-header-title">
                        <div>
                            <h5>Control de Actuadores</h5>
                            <p class="text-secondary mb-0">Operación manual o guiada por reglas automáticas</p>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="actuador-slider row">
                            @foreach($dispositivos as $d)
                                @php
                                    $isManualMode = (($modo ?? 'automatico') === 'manual');
                                    $disabled = !($d->habilitado ?? true) || !$isManualMode;
                                    $isOn = ($d->estado === 'ON') || ((string) $d->estado === '1') || ($d->estado === 1) || ($d->estado === true);
                                    $pendingCommand = $pendingCommands->get($d->id);
                                    $isPending = $pendingCommand !== null;

                                    $deviceIcon = match($d->codigo) {
                                        'D_RIEGO' => 'ph-drop-half-bottom',
                                        'D_LUZ' => 'ph-lightbulb',
                                        'D_FAN' => 'ph-fan',
                                        'D_CALEF' => 'ph-fire',
                                        default => 'ph-plug'
                                    };
                                @endphp

                                <div class="col-md-6 col-xl-3">
                                    <div class="card equal-card h-100">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <span class="bg-light-primary h-40 w-40 d-flex-center b-r-15 f-s-18">
                                                    <i class="ph-duotone {{ $deviceIcon }}"></i>
                                                </span>

                                                <span class="badge {{ $isPending ? 'text-light-warning' : ($isOn ? 'text-light-success' : 'text-light-secondary') }} js-estado-badge-wrap">
                                                    <i class="ph-duotone {{ $isPending ? 'ph-clock' : ($isOn ? 'ph-check-circle' : 'ph-stop-circle') }}"></i>
                                                    <span class="js-estado-badge">{{ $isPending ? 'PENDIENTE: ' . strtoupper($pendingCommand->accion) : ($isOn ? 'ON' : 'OFF') }}</span>
                                                </span>
                                            </div>

                                            <div class="mt-3">
                                                <p class="f-s-16 mb-0">{{ $d->nombre }}</p>
                                                <p class="text-secondary mb-1">{{ $d->codigo }}</p>
                                                <p class="text-secondary mb-0">Origen: <b>{{ $modo === 'automatico' ? 'Automático' : 'Manual' }}</b></p>
                                            </div>

                                            <div class="app-divider-v dotted py-2"></div>

                                            <div class="mb-3">
                                                @php $reglasDisp = $reglasResumen->where('dispositivo_codigo', $d->codigo); @endphp
                                                @forelse($reglasDisp as $r)
                                                    <p class="text-secondary mb-1 f-s-12">{{ $r['texto'] }}</p>
                                                @empty
                                                    <p class="text-secondary mb-0 f-s-12">Sin regla automática visible.</p>
                                                @endforelse
                                            </div>

                                            <x-toggle-onoff
                                                :id="$d->id"
                                                :is-on="$isOn"
                                                :disabled="$disabled"
                                                :url="route('dispositivos.manual', ['dispositivo' => $d->id], false)"
                                            />
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card contries-details-card h-100">
                    <div class="card-body">
                        <div class="card-header-title mb-3">
                            <h5>Nivel de agua</h5>
                            <p class="text-secondary mb-0">Condición del tanque y permiso de riego</p>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div>
                                <h2 class="text-primary mb-0"><span class="js-water-pct-main">{{ round($nivelPct) }}</span>%</h2>
                                <p class="text-secondary mb-0">Lectura actual</p>
                            </div>
                            <span class="badge {{ $riegoPermitido === 'Sí' ? 'text-light-success' : 'text-light-danger' }}">
                                Riego {{ $riegoPermitido === 'Sí' ? 'permitido' : 'bloqueado' }}
                            </span>
                        </div>

                        <div class="progress h-10 mb-3">
                            <div class="progress-bar bg-primary js-water-bar-main" style="width: {{ $nivelPct }}%"></div>
                        </div>

                        <div class="d-flex justify-content-between text-secondary f-s-12 mb-3">
                            <span>0%</span><span>25%</span><span>50%</span><span>100%</span>
                        </div>

                        <div class="app-divider-v dotted py-2"></div>

                        <ul class="contries-details-list">
                            <li>
                                <div class="position-relative">
                                    <span class="position-absolute">
                                        <i class="ph-duotone ph-drop text-primary f-s-24"></i>
                                    </span>
                                    <p class="f-w-500 mg-s-30 mb-0">Nivel actual</p>
                                </div>
                                <div>
                                    <h6 class="text-primary mb-0">{{ round($nivelPct) }}%</h6>
                                </div>
                            </li>
                            <li>
                                <div class="position-relative">
                                    <span class="position-absolute">
                                        <i class="ph-duotone ph-funnel text-warning f-s-24"></i>
                                    </span>
                                    <p class="f-w-500 mg-s-30 mb-0">Mínimo recomendado</p>
                                </div>
                                <div>
                                    <h6 class="text-warning mb-0">25%</h6>
                                </div>
                            </li>
                            <li>
                                <div class="position-relative">
                                    <span class="position-absolute">
                                        <i class="ph-duotone ph-clock text-success f-s-24"></i>
                                    </span>
                                    <p class="f-w-500 mg-s-30 mb-0">Última lectura</p>
                                </div>
                                <div>
                                    <h6 class="text-success mb-0">{{ optional($nivelSensor)->updated_at ?? '—' }}</h6>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        {{-- FILA ALERTAS / GRAFICAS / CONFIG --}}
        <div class="row mt-4">
            <div class="col-lg-4">
                <div class="card equal-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="card-header-title mb-0">
                                <h5>Alertas recientes</h5>
                                <p class="text-secondary mb-0">Últimos eventos críticos</p>
                            </div>
                            <a href="{{ route('alertas.index') }}" class="btn btn-primary btn-sm">Ver todas</a>
                        </div>

                        @forelse($alertas as $a)
                            <div class="notification-message head-box">
                                <div class="message-images">
                                    <span class="bg-light-dark h-35 w-35 d-flex-center b-r-10 position-relative">
                                        <i class="ph-duotone ph-warning f-s-18"></i>
                                    </span>
                                </div>
                                <div class="message-content-box flex-grow-1 ps-2">
                                    <span class="badge
                                        @if($a->nivel === 'critical') text-light-danger
                                        @elseif($a->nivel === 'warning') text-light-warning
                                        @else text-light-primary
                                        @endif">
                                        {{ strtoupper($a->nivel) }}
                                    </span>
                                    <p class="f-s-15 text-secondary mb-0 mt-1">{{ $a->mensaje }}</p>
                                    <span class="badge text-light-secondary mt-2">{{ $a->created_at }}</span>
                                </div>
                            </div>
                        @empty
                            <p class="text-secondary mb-0">Sin alertas.</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card equal-card">
                    <div class="card-body">
                        <div class="card-header-title mb-3">
                            <h5>Tendencias rápidas</h5>
                            <p class="text-secondary mb-0">Vista resumida del estado actual</p>
                        </div>

                        <div class="mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="mb-0">Temperatura</h6>
                                <span class="text-primary">{{ $grafTemp }}°C</span>
                            </div>
                            <div class="progress h-10">
                                <div class="progress-bar bg-primary" style="width: {{ min(100, max(0, ($grafTemp ?? 0) * 3)) }}%"></div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="mb-0">Humedad relativa</h6>
                                <span class="text-secondary">{{ $grafHr }}%</span>
                            </div>
                            <div class="progress h-10">
                                <div class="progress-bar bg-secondary" style="width: {{ min(100, max(0, $grafHr ?? 0)) }}%"></div>
                            </div>
                        </div>

                        <div>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="mb-0">Nivel de agua</h6>
                                <span class="text-success">{{ $grafNiv }}%</span>
                            </div>
                            <div class="progress h-10">
                                <div class="progress-bar bg-success js-water-bar-main" style="width: {{ $grafNiv }}%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card equal-card">
                    <div class="card-body">
                        <div class="card-header-title mb-3">
                            <h5>Configuración automática</h5>
                            <p class="text-secondary mb-0">Setpoints y reglas activas</p>
                        </div>

                        @forelse($reglasResumen->groupBy('dispositivo_codigo') as $cod => $grupo)
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h6 class="mb-0">{{ optional($dispositivos->firstWhere('codigo', $cod))->nombre ?? $cod }}</h6>
                                    <span class="badge text-light-primary">{{ $cod }}</span>
                                </div>

                                @foreach($grupo as $r)
                                    <p class="text-secondary mb-1">{{ $r['texto'] }}</p>
                                    @if(($r['min_on_s'] ?? 0) > 0 || ($r['min_off_s'] ?? 0) > 0)
                                        <p class="text-secondary f-s-12 mb-1">
                                            ON: {{ $r['min_on_s'] ?? 0 }} s · OFF: {{ $r['min_off_s'] ?? 0 }} s
                                        </p>
                                    @endif
                                @endforeach
                            </div>

                            @if(!$loop->last)
                                <div class="app-divider-v dotted py-2"></div>
                            @endif
                        @empty
                            <p class="text-secondary mb-0">No hay reglas automáticas activas.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        {{-- FILA EVENTOS --}}
        <div class="row mt-4">
            <div class="col-12">
                <div class="card equal-card order-timeline-card">
                    <div class="card-header">
                        <h5>Eventos recientes</h5>
                    </div>
                    <div class="card-body app-scroll">
                        <ul class="app-timeline-box order-timeline">
                            @forelse($eventos as $e)
                                <li class="timeline-section">
                                    <div class="timeline-icon">
                                        <span class="bg-primary h-35 w-35 d-flex-center b-r-50">
                                            <i class="ph-duotone ph-gear f-s-18"></i>
                                        </span>
                                    </div>
                                    <div class="timeline-content">
                                        <div class="order-timeline-box">
                                            <div class="position-relative">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <h6 class="text-primary f-w-600 mb-0">
                                                        {{ optional($e->dispositivo)->nombre ?? 'Dispositivo' }}
                                                    </h6>
                                                    <p class="f-s-12 mb-0 text-danger">{{ $e->ejecutado_en }}</p>
                                                </div>
                                            </div>
                                            <div class="mt-2 order-timeline-content">
                                                <p class="mb-0">Acción <span>{{ strtoupper($e->accion) }}</span></p>
                                                <p class="mb-0">Origen <span>{{ ucfirst($e->origen) }}</span></p>
                                                @if(!is_null($e->valor_sensor))
                                                    <p class="mb-0">Valor sensor <span>{{ $e->valor_sensor }}</span></p>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </li>
                            @empty
                                <p class="text-secondary mb-0">Sin eventos recientes.</p>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>
        </div>

    </div>

    @push('scripts')
    <script>
        function setModoUI(modo) {
            const txt = (modo || '').toUpperCase();
            $('#js-res-modo').text(txt);
            $('#js-top-modo').text(txt);
        }

        function setEsp32Status(status) {
            const state = status?.state || 'never';
            const labels = {
                online: 'CONECTADO',
                offline: 'SIN COMUNICACIÓN',
                never: 'SIN REGISTRO'
            };
            const classes = {
                online: 'bg-success',
                offline: 'bg-danger',
                never: 'bg-secondary'
            };

            $('#js-esp32-status')
                .removeClass('bg-success bg-danger bg-secondary')
                .addClass(classes[state] || classes.never)
                .text(labels[state] || labels.never);

            const timestamp = Number(status?.last_seen);
            $('#js-esp32-last-seen').text(timestamp
                ? `Último sync: ${new Date(timestamp * 1000).toLocaleTimeString()}`
                : 'Esperando primer sync');
        }

        function initMobileSlider(selector, slidesToShowMobile = 1) {
            const $el = $(selector);
            if (!$el.length) return;

            if (window.innerWidth < 768) {
                if (!$el.hasClass('slick-initialized')) {
                    $el.slick({
                        arrows: false,
                        dots: true,
                        infinite: false,
                        slidesToShow: slidesToShowMobile,
                        slidesToScroll: 1,
                        adaptiveHeight: true,
                        mobileFirst: true
                    });
                }
            } else {
                if ($el.hasClass('slick-initialized')) {
                    $el.slick('unslick');
                }
            }
        }

        function initDashboardSliders() {
            initMobileSlider('.kpi-slider', 1);
            initMobileSlider('.sensor-slider', 1);
            initMobileSlider('.actuador-slider', 1);
        }

        function refreshPanel() {
            $.get("{{ route('panel.data', [], false) }}").done(function (r) {
                $('#js-last-refresh').text((r.ts || '').replace(' ', ' · '));

                if (r.modo) setModoUI(r.modo);
                setEsp32Status(r.esp32);

                if (r.resumen) {
                    $('#js-res-sensores').text(r.resumen.sensores_activos ?? 0);
                    $('#js-res-activos').text(r.resumen.actuadores_encendidos ?? 0);
                    $('#js-res-criticas').text(r.resumen.alertas_criticas ?? 0);
                    $('#js-res-novistas').text(r.resumen.alertas_no_vistas ?? 0);
                    $('#js-res-novistas-kpi').text(r.resumen.alertas_no_vistas ?? 0);

                    const agua = parseFloat(r.resumen.nivel_agua);
                    if (!isNaN(agua)) {
                        let pct = (agua <= 5) ? (agua * 20) : agua;
                        pct = Math.max(0, Math.min(100, pct));
                        $('#js-res-agua').text(Math.round(pct) + '%');
                        $('.js-water-bar-main').css('width', pct + '%');
                        $('.js-water-pct-main').text(Math.round(pct));
                    }
                }

                (r.sensores || []).forEach(s => {
                    $(`.js-sensor-value[data-id="${s.id}"]`).text(s.valor_actual ?? '—');
                });

                (r.dispositivos || []).forEach(d => {
                    const wrap = $(`.toggle-onoff[data-id="${d.id}"]`);
                    if (!wrap.length) return;

                    const isOn = (d.estado === 'ON') || (String(d.estado) === '1') || (d.estado === 1) || (d.estado === true);

                    wrap.find('.js-toggle').removeClass('is-active').attr('aria-pressed','false');
                    wrap.find(`.js-toggle[data-estado="${isOn ? 1 : 0}"]`).addClass('is-active').attr('aria-pressed','true');

                    const badge = wrap.closest('.card').find('.js-estado-badge');
                    badge.text(d.pending ? `PENDIENTE: ${Number(d.pending_estado) === 1 ? 'ON' : 'OFF'}` : (isOn ? 'ON' : 'OFF'));

                    const stateWrap = wrap.closest('.card').find('.js-estado-badge-wrap');
                    stateWrap.removeClass('text-light-success text-light-secondary')
                             .removeClass('text-light-warning')
                             .addClass(d.pending ? 'text-light-warning' : (isOn ? 'text-light-success' : 'text-light-secondary'));
                    stateWrap.find('i').attr('class', `ph-duotone ${d.pending ? 'ph-clock' : (isOn ? 'ph-check-circle' : 'ph-stop-circle')}`);
                });
            });
        }

        $(document).ready(function () {
            initDashboardSliders();
        });

        $(window).on('resize', function () {
            initDashboardSliders();
        });

        setInterval(refreshPanel, 12000);
        setTimeout(refreshPanel, 2000);

    </script>
    @endpush
</x-app-layout>
