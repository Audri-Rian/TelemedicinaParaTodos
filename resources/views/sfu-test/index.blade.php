<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Teste SFU — {{ config('app.name') }}</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        html {
            -webkit-text-size-adjust: 100%;
        }
        html, body {
            height: 100%;
            font-family: system-ui, -apple-system, sans-serif;
            background: #0a0a14;
            color: #e0e0e0;
            font-size: 12px;
        }

        /* ── Layout ──────────────────────────────────────────────────────── */
        .admin-layout {
            display: flex;
            flex-direction: column;
            height: 100vh;
            height: 100dvh;
        }

        /* ── Topbar ───────────────────────────────────────────────────────── */
        .topbar {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
            background: #12121f;
            border-bottom: 1px solid #1e1e30;
            padding: 7px 14px;
            flex-shrink: 0;
            font-size: 11px;
        }
        .topbar .app-name {
            font-size: 13px;
            font-weight: 700;
            color: #fff;
            margin-right: 4px;
            white-space: nowrap;
        }
        .topbar .room-badge {
            background: #1e1e30;
            border-radius: 4px;
            padding: 2px 8px;
            color: #888;
            font-family: monospace;
            font-size: 10px;
        }
        .topbar .sep { color: #252530; }
        .status-chip {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: 600;
            background: #1a1a2a;
            white-space: nowrap;
        }
        #statusConn { color: #f87171; }

        /* ── Main 3-column area ──────────────────────────────────────────── */
        .main-area {
            display: flex;
            flex: 1;
            min-height: 0;
            overflow: hidden;
        }

        /* ── Left sidebar ────────────────────────────────────────────────── */
        .sidebar-left {
            width: 216px;
            flex-shrink: 0;
            background: #111120;
            border-right: 1px solid #1e1e30;
            overflow-y: auto;
            padding: 10px 8px;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .sidebar-left::-webkit-scrollbar { width: 4px; }
        .sidebar-left::-webkit-scrollbar-thumb { background: #2c2c3e; border-radius: 2px; }

        .ctrl-section { display: flex; flex-direction: column; gap: 5px; }
        .ctrl-section-title {
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #444;
            padding: 2px 0;
            border-bottom: 1px solid #1a1a2e;
            margin-bottom: 2px;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .ctrl-section-title .icon { font-size: 11px; }
        .btn-row { display: flex; flex-wrap: wrap; gap: 4px; }
        .device-row {
            display: flex;
            gap: 4px;
            align-items: center;
        }
        select {
            flex: 1;
            padding: 5px 6px;
            border: 1px solid #2c2c3e;
            border-radius: 5px;
            background: #0a0a14;
            color: #bbb;
            font-size: 10px;
            cursor: pointer;
        }

        /* ── Video center ────────────────────────────────────────────────── */
        .video-center {
            flex: 1;
            min-width: 0;
            position: relative;
            display: flex;
            flex-direction: column;
            background: #08080f;
        }
        .video-stage {
            flex: 1;
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: flex-start;
        }
        .remote-grid {
            width: 100%;
            height: 100%;
            overflow-y: auto;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 8px;
            padding: 12px;
            align-content: start;
        }
        .remote-grid::-webkit-scrollbar { width: 4px; }
        .remote-grid::-webkit-scrollbar-thumb { background: #1e1e30; border-radius: 2px; }

        .video-empty {
            position: absolute;
            inset: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 10px;
            color: #333;
            pointer-events: none;
        }
        .video-empty .empty-icon { font-size: 40px; opacity: 0.4; }
        .video-empty p { font-size: 12px; color: #3a3a50; }

        /* ── Local PiP ───────────────────────────────────────────────────── */
        .local-pip {
            position: absolute;
            bottom: 12px;
            right: 12px;
            z-index: 20;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.6);
            border: 2px solid #2c2c3e;
            background: #0f0f1a;
        }
        .local-pip-label {
            position: absolute;
            bottom: 4px;
            left: 6px;
            font-size: 9px;
            color: rgba(255,255,255,0.55);
            background: rgba(0,0,0,0.55);
            padding: 1px 5px;
            border-radius: 3px;
            z-index: 2;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        #localVideo {
            width: 160px;
            height: 120px;
            display: block;
            object-fit: cover;
            background: #111;
        }

        /* ── Remote peer card (created by JS) ────────────────────────────── */

        /* ── Right sidebar ───────────────────────────────────────────────── */
        .sidebar-right {
            width: 280px;
            flex-shrink: 0;
            background: #0d0d1c;
            border-left: 1px solid #1e1e30;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
        }
        .sidebar-right::-webkit-scrollbar { width: 4px; }
        .sidebar-right::-webkit-scrollbar-thumb { background: #2c2c3e; border-radius: 2px; }

        .sidebar-right-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 12px 8px;
            border-bottom: 1px solid #1a1a2e;
            flex-shrink: 0;
        }
        .sidebar-right-header h3 {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            color: #4aa3df;
        }

        /* Stat cards */
        .stats-cards { padding: 10px 10px 0; flex: 1; }
        .stats-empty { color: #3a3a55; font-size: 11px; padding: 12px 0; text-align: center; }
        .stat-card {
            background: #111827;
            border-radius: 8px;
            padding: 9px 10px;
            margin-bottom: 7px;
            border-left: 3px solid #1e3a5f;
        }
        .stat-card-title {
            font-size: 10px;
            color: #778;
            margin-bottom: 7px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .stat-card-title .stat-state {
            font-size: 9px;
            padding: 1px 5px;
            border-radius: 3px;
            background: #1a1a2e;
            color: #888;
        }
        .stat-metrics {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 5px 8px;
        }
        .stat-metric { display: flex; flex-direction: column; gap: 1px; }
        .stat-metric-label {
            font-size: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #3a3a55;
        }
        .stat-metric-value {
            font-size: 12px;
            font-family: monospace;
            font-weight: 700;
            color: #778;
        }

        /* Perf grid */
        .perf-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1px;
            border-top: 1px solid #1a1a2e;
            flex-shrink: 0;
        }
        .perf-item {
            padding: 8px 12px;
            background: #0a0a14;
            display: flex;
            flex-direction: column;
            gap: 1px;
            border-bottom: 1px solid #111;
        }
        .perf-item:nth-child(odd) { border-right: 1px solid #111; }
        .perf-label { font-size: 8px; text-transform: uppercase; letter-spacing: 0.5px; color: #3a3a55; }
        .perf-item strong { font-size: 12px; font-family: monospace; color: #aaa; }

        /* ── Log panel ───────────────────────────────────────────────────── */
        .log-panel {
            height: 148px;
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
            border-top: 1px solid #1e1e30;
            background: #08080f;
        }
        .log-panel-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 4px 12px;
            background: #10101e;
            border-bottom: 1px solid #181828;
            flex-shrink: 0;
        }
        .log-panel-header span {
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            color: #444;
        }
        #logContainer {
            flex: 1;
            overflow-y: auto;
            padding: 6px 12px;
            font-family: monospace;
            font-size: 10px;
            line-height: 1.6;
            color: #666;
        }
        #logContainer::-webkit-scrollbar { width: 4px; }
        #logContainer::-webkit-scrollbar-thumb { background: #1e1e30; border-radius: 2px; }

        /* ── Buttons ─────────────────────────────────────────────────────── */
        button {
            padding: 5px 9px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 10px;
            font-weight: 500;
            transition: opacity 0.12s;
            white-space: nowrap;
            line-height: 1.3;
        }
        button:disabled { opacity: 0.3; cursor: not-allowed; }
        button:not(:disabled):hover { opacity: 0.78; }
        .btn-primary  { background: #3b5bdb; color: #fff; }
        .btn-danger   { background: #c92a2a; color: #fff; }
        .btn-warning  { background: #d9480f; color: #fff; }
        .btn-success  { background: #2f9e44; color: #fff; }
        .btn-neutral  { background: #22222e; color: #bbb; }
        .btn-info     { background: #1864ab; color: #fff; }
        .btn-purple   { background: #6741d9; color: #fff; }
        .btn-teal     { background: #0b7285; color: #fff; }
        .btn-audio    { background: #0d7377; color: #fff; display: none; }
        .btn-active   { outline: 2px solid #4ade80 !important; }
        .btn-swap     { padding: 5px 7px; font-size: 12px; flex-shrink: 0; }
        .btn-small    { padding: 3px 7px; font-size: 9px; }

        /* ── Quality colors ──────────────────────────────────────────────── */
        .q-green  { color: #4ade80; }
        .q-yellow { color: #fbbf24; }
        .q-red    { color: #f87171; }
        .q-na     { color: #444; }

        /* ── Responsivo (tablets / mobile) ───────────────────────────────── */
        @media (max-width: 1024px) {
            .remote-grid {
                grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
                gap: 6px;
                padding: 8px;
            }
        }

        @media (max-width: 900px) {
            html, body {
                overflow-x: hidden;
                overflow-y: auto;
                height: auto;
                min-height: 100%;
                min-height: 100dvh;
            }
            .admin-layout {
                height: auto;
                min-height: 100dvh;
            }
            .topbar {
                padding-left: max(10px, env(safe-area-inset-left, 0px));
                padding-right: max(10px, env(safe-area-inset-right, 0px));
                padding-top: max(6px, env(safe-area-inset-top, 0px));
            }
            .main-area {
                flex-direction: column;
                flex: 1 1 auto;
                min-height: 0;
                overflow: visible;
            }
            .sidebar-left {
                width: 100%;
                max-height: min(42vh, 320px);
                border-right: none;
                border-bottom: 1px solid #1e1e30;
                flex-shrink: 0;
            }
            .video-center {
                flex: 1 1 auto;
                min-height: min(50vh, 420px);
            }
            .remote-grid {
                grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
            }
            .sidebar-right {
                width: 100%;
                max-height: none;
                border-left: none;
                border-top: 1px solid #1e1e30;
                flex-shrink: 0;
            }
            .local-pip {
                bottom: max(8px, env(safe-area-inset-bottom, 0px));
                right: max(8px, env(safe-area-inset-right, 0px));
            }
            #localVideo {
                width: min(140px, 32vw);
                height: auto;
                aspect-ratio: 4 / 3;
            }
            .log-panel {
                height: min(28vh, 180px);
                padding-bottom: env(safe-area-inset-bottom, 0px);
            }
        }

        @media (max-width: 480px) {
            .topbar .app-name { font-size: 12px; }
            .status-chip { font-size: 9px; padding: 2px 6px; }
            #statusAutoReconn { margin-left: 0 !important; margin-top: 4px; flex-basis: 100%; justify-content: center; }
            .remote-grid {
                grid-template-columns: 1fr;
            }
            .video-center { min-height: 40vh; }
        }

        @media (min-width: 901px) {
            html, body { overflow: hidden; }
        }
    </style>
</head>
<body>

<div class="admin-layout">

    <!-- ── Topbar ──────────────────────────────────────────────────────────── -->
    <header class="topbar">
        <span class="app-name">SFU MediaSoup</span>
        <span class="room-badge">sala: {{ $roomId }}</span>
        <span class="sep">|</span>
        <span id="statusConn" class="status-chip">🔴 Desconectado</span>
        <span id="statusPeers" class="status-chip">0 participantes</span>
        <span id="statusCam"   class="status-chip">📷 OFF</span>
        <span id="statusMic"   class="status-chip">🎤 OFF</span>
        <span id="statusVideo" class="status-chip">📹 —</span>
        <span id="statusAutoReconn" class="status-chip" style="margin-left:auto; color:#444;">Auto-reconexão: OFF</span>
    </header>

    <!-- ── Main 3-column ───────────────────────────────────────────────────── -->
    <div class="main-area">

        <!-- Left sidebar — controls -->
        <aside class="sidebar-left">

            <!-- 1. Conexão -->
            <div class="ctrl-section">
                <div class="ctrl-section-title"><span class="icon">🔌</span>Conexão</div>
                <div class="btn-row">
                    <button id="btnJoin" class="btn-primary">Entrar na sala</button>
                    <button id="btnLeave" class="btn-danger" disabled>Sair</button>
                </div>
                <div class="btn-row">
                    <button id="btnStartCamera"   class="btn-success" disabled>Iniciar câmera</button>
                    <button id="btnStartNoCamera" class="btn-neutral" disabled>Sem câmera</button>
                </div>
            </div>

            <!-- 2. Mídia Local -->
            <div class="ctrl-section">
                <div class="ctrl-section-title"><span class="icon">🎬</span>Mídia Local</div>
                <div class="btn-row">
                    <button id="btnToggleCam" class="btn-neutral" disabled>📷 Ligar câmera</button>
                    <button id="btnToggleMic" class="btn-neutral" disabled>🎤 Mutar mic</button>
                </div>
                <div class="btn-row">
                    <button id="btnPauseVideo"    class="btn-neutral" disabled>⏸ Pausar vídeo</button>
                    <button id="btnBlackVideo"    class="btn-neutral" disabled>⬛ Vídeo preto</button>
                    <button id="btnActivateAudio" class="btn-audio">🔊 Ativar áudio</button>
                </div>
            </div>

            <!-- 3. Dispositivos -->
            <div class="ctrl-section">
                <div class="ctrl-section-title"><span class="icon">🎛</span>Dispositivos</div>
                <div class="device-row">
                    <select id="selCamera"><option value="">Câmera padrão</option></select>
                    <button id="btnSwitchCam" class="btn-neutral btn-swap" disabled title="Trocar câmera">↻</button>
                </div>
                <div class="device-row">
                    <select id="selAudioIn"><option value="">Microfone padrão</option></select>
                    <button id="btnSwitchAudioIn" class="btn-neutral btn-swap" disabled title="Trocar microfone">↻</button>
                </div>
                <div class="device-row">
                    <select id="selAudioOut"><option value="">Alto-falante padrão</option></select>
                    <button id="btnSwitchAudioOut" class="btn-neutral btn-swap" title="Trocar saída">↻</button>
                </div>
            </div>

            <!-- 4. Reconexão & Falhas -->
            <div class="ctrl-section">
                <div class="ctrl-section-title"><span class="icon">🔁</span>Reconexão &amp; Falhas</div>
                <div class="btn-row">
                    <button id="btnReconnect"       class="btn-warning">↩ Graceful</button>
                    <button id="btnForceDisconnect" class="btn-danger">⚡ Forçar queda</button>
                </div>
                <div class="btn-row">
                    <button id="btnToggleAutoReconn" class="btn-neutral">Auto-reconexão: OFF</button>
                    <button id="btnCleanup"          class="btn-danger">☠ Encerrar tudo</button>
                </div>
            </div>

            <!-- 5. SFU Producer/Consumer -->
            <div class="ctrl-section">
                <div class="ctrl-section-title"><span class="icon">🧠</span>SFU Producer/Consumer</div>
                <div class="btn-row">
                    <button id="btnPauseProducerSFU"  class="btn-purple" disabled>⏸ Pausar producer</button>
                    <button id="btnResumeProducerSFU" class="btn-purple" disabled>▶ Retomar</button>
                </div>
                <div class="btn-row">
                    <button id="btnPauseAllConsumers"  class="btn-teal" disabled>⏸ Pausar consumers</button>
                    <button id="btnResumeAllConsumers" class="btn-teal" disabled>▶ Retomar</button>
                </div>
            </div>

            <!-- 6. Remoto & Testes -->
            <div class="ctrl-section">
                <div class="ctrl-section-title"><span class="icon">👥</span>Remoto &amp; Testes</div>
                <div class="btn-row">
                    <button id="btnToggleRemoteMute" class="btn-neutral">🔇 Mutar remoto</button>
                    <button id="btnTestCleanup"      class="btn-neutral">🧹 Detectar vazamento</button>
                </div>
            </div>

        </aside>

        <!-- Video center -->
        <main class="video-center">
            <div class="video-stage">
                <!-- Remote peers grid -->
                <div id="remoteVideos" class="remote-grid"></div>

                <!-- Empty state -->
                <div id="videoEmptyState" class="video-empty">
                    <div class="empty-icon">🎥</div>
                    <p>Conecte-se para iniciar a chamada</p>
                </div>

                <!-- Local PiP -->
                <div class="local-pip">
                    <span class="local-pip-label">Você</span>
                    <video id="localVideo" autoplay muted playsinline></video>
                </div>
            </div>
        </main>

        <!-- Right sidebar — stats always visible -->
        <aside class="sidebar-right">
            <div class="sidebar-right-header">
                <h3>📊 Qualidade de rede</h3>
                <button id="btnToggleStats" class="btn-small btn-info">⏸ Pausar</button>
            </div>

            <div id="statsCards" class="stats-cards">
                <p class="stats-empty">Aguardando mídia…</p>
            </div>

            <!-- Performance metrics -->
            <div class="perf-grid">
                <div class="perf-item">
                    <span class="perf-label">Heap JS</span>
                    <strong id="perfHeap">—</strong>
                </div>
                <div class="perf-item">
                    <span class="perf-label">Tracks ativas</span>
                    <strong id="perfTracks">0</strong>
                </div>
                <div class="perf-item">
                    <span class="perf-label">Reconexões</span>
                    <strong id="perfReconns">0</strong>
                </div>
                <div class="perf-item">
                    <span class="perf-label">Uptime</span>
                    <strong id="perfUptime">—</strong>
                </div>
                <div class="perf-item" style="grid-column:span 2;">
                    <span class="perf-label">Auto-reconexão</span>
                    <strong id="perfAutoReconn" style="color:#555;">OFF</strong>
                </div>
            </div>
        </aside>

    </div><!-- /.main-area -->

    <!-- ── Log panel ────────────────────────────────────────────────────────── -->
    <div class="log-panel">
        <div class="log-panel-header">
            <span>Logs de diagnóstico</span>
            <button id="btnCopyLogs"  class="btn-small btn-neutral">📋 Copiar</button>
            <button id="btnClearLogs" class="btn-small btn-neutral">🗑 Limpar</button>
        </div>
        <div id="logContainer"></div>
    </div>

</div><!-- /.admin-layout -->

<script>
    window.__SFU_TEST_CONFIG__ = @json($sfuTestConfig);
</script>
@vite(['resources/js/sfu-test-app.js'])
</body>
</html>
