<!DOCTYPE html>
<html class="dark" lang="pt-BR">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <meta name="robots" content="noindex"/>
    <title>SFU COMMAND | {{ config('app.name') }}</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "surface-container-low": "#131b2e",
                        "primary-fixed": "#d8e2ff",
                        "surface-container": "#171f33",
                        "on-error": "#690005",
                        "tertiary": "#ffb3ad",
                        "secondary": "#4edea3",
                        "on-tertiary": "#68000a",
                        "primary-container": "#00102c",
                        "surface-container-highest": "#2d3449",
                        "secondary-fixed": "#6ffbbe",
                        "outline-variant": "#45474b",
                        "error-container": "#93000a",
                        "error": "#ffb4ab",
                        "on-surface-variant": "#c6c6cb",
                        "surface-tint": "#adc7ff",
                        "inverse-primary": "#005bc0",
                        "outline": "#8f9095",
                        "on-primary": "#002e68",
                        "background": "#0b1326",
                        "tertiary-fixed-dim": "#ffb3ad",
                        "on-tertiary-container": "#e53d3e",
                        "on-primary-fixed": "#001a41",
                        "surface-variant": "#2d3449",
                        "on-secondary-fixed": "#002113",
                        "inverse-on-surface": "#283044",
                        "on-background": "#dae2fd",
                        "tertiary-fixed": "#ffdad7",
                        "on-secondary": "#003824",
                        "surface-bright": "#31394d",
                        "on-surface": "#dae2fd",
                        "secondary-fixed-dim": "#4edea3",
                        "on-secondary-fixed-variant": "#005236",
                        "on-tertiary-fixed-variant": "#930013",
                        "on-error-container": "#ffdad6",
                        "tertiary-container": "#2c0002",
                        "surface-dim": "#0b1326",
                        "primary": "#adc7ff",
                        "on-secondary-container": "#00311f",
                        "on-primary-container": "#0078f9",
                        "secondary-container": "#00a572",
                        "surface-container-lowest": "#060e20",
                        "surface-container-high": "#222a3d",
                        "on-primary-fixed-variant": "#004493",
                        "surface": "#0b1326",
                        "primary-fixed-dim": "#adc7ff",
                        "inverse-surface": "#dae2fd",
                        "on-tertiary-fixed": "#410004"
                    },
                    fontFamily: {
                        "headline": ["Space Grotesk"],
                        "body": ["Inter"],
                        "label": ["Space Grotesk"]
                    },
                    borderRadius: {"DEFAULT": "0.125rem", "lg": "0.25rem", "xl": "0.5rem", "full": "0.75rem"},
                },
            },
        }
    </script>
    <style>
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
            vertical-align: middle;
        }
        ::-webkit-scrollbar { width: 4px; height: 4px; }
        ::-webkit-scrollbar-track { background: #0b1326; }
        ::-webkit-scrollbar-thumb { background: #2d3449; border-radius: 2px; }
        .technical-grid {
            background-size: 20px 20px;
            background-image: linear-gradient(to right, #ffffff05 1px, transparent 1px), linear-gradient(to bottom, #ffffff05 1px, transparent 1px);
        }
        /* Quality colors used by JS */
        .q-green  { color: #4ade80; }
        .q-yellow { color: #fbbf24; }
        .q-red    { color: #f87171; }
        .q-na     { color: #45474b; }
        /* Button base */
        button { cursor: pointer; transition: opacity 0.12s; }
        button:disabled { opacity: 0.3; cursor: not-allowed; }
        button:not(:disabled):hover { opacity: 0.85; }

        /* ── Responsive ──────────────────────────────────────────── */
        @media (max-width: 900px) {
            html, body { overflow-x: hidden; overflow-y: auto; height: auto; min-height: 100dvh; }
            .admin-layout { height: auto !important; min-height: 100dvh; }
            .main-area {
                flex-direction: column !important;
                flex: 1 1 auto;
                min-height: 0;
                overflow: visible !important;
            }
            .sidebar-left {
                width: 100% !important;
                max-height: min(42vh, 320px);
                border-right: none !important;
                border-bottom: 1px solid rgba(69,71,75,0.15);
                flex-shrink: 0;
            }
            .video-center {
                flex: 1 1 auto;
                min-height: min(50vh, 420px);
            }
            .sidebar-right {
                width: 100% !important;
                max-height: none;
                border-left: none !important;
                border-top: 1px solid rgba(69,71,75,0.15);
                flex-shrink: 0;
            }
            .perf-grid-center {
                grid-template-columns: repeat(2, 1fr) !important;
            }
            .local-pip video {
                width: min(140px, 32vw) !important;
                height: auto !important;
                aspect-ratio: 4 / 3;
            }
            .log-panel {
                height: min(28vh, 180px) !important;
                padding-bottom: env(safe-area-inset-bottom, 0px);
            }
        }
        @media (max-width: 480px) {
            .remote-grid-inner {
                grid-template-columns: 1fr !important;
            }
            .perf-grid-center {
                grid-template-columns: 1fr !important;
            }
            .video-center { min-height: 40vh; }
        }
        @media (min-width: 901px) {
            html, body { overflow: hidden; }
        }
    </style>
</head>
<body class="bg-background text-on-surface font-body selection:bg-primary/30 antialiased overflow-hidden">

<div class="admin-layout flex flex-col h-screen" style="height: 100dvh;">

    <!-- ── TopNavBar ──────────────────────────────────────────────────── -->
    <header class="flex justify-between items-center w-full px-4 md:px-6 h-14 shrink-0 bg-background font-headline tracking-tight border-b border-outline-variant/15">
        <!-- Left -->
        <div class="flex items-center gap-3 md:gap-6 min-w-0">
            <span class="text-lg md:text-xl font-bold tracking-tighter text-primary uppercase whitespace-nowrap">SFU COMMAND</span>
            <div class="flex items-center gap-3">
                <span class="hidden sm:inline bg-surface-container-highest px-2 py-0.5 rounded text-[10px] font-mono text-primary border border-outline-variant/20 tracking-widest">{{ $roomId }}</span>
                <div class="flex items-center gap-1.5 text-xs font-medium whitespace-nowrap">
                    <span id="statusConnDot" class="w-2 h-2 rounded-full bg-error shrink-0"></span>
                    <span id="statusConn" class="text-error text-[10px] md:text-xs">DESCONECTADO</span>
                </div>
            </div>
        </div>
        <!-- Right -->
        <div class="flex items-center gap-3 md:gap-6 shrink-0">
            <!-- CAM / MIC / VIDEO indicators -->
            <div class="hidden lg:flex items-center gap-4 border-r border-outline-variant/20 pr-6">
                <div class="flex flex-col items-center">
                    <span id="statusCamIcon" class="material-symbols-outlined text-sm text-error">videocam_off</span>
                    <span id="statusCam" class="text-[10px] uppercase font-bold tracking-tighter text-error/70">CAM: OFF</span>
                </div>
                <div class="flex flex-col items-center">
                    <span id="statusMicIcon" class="material-symbols-outlined text-sm text-error">mic_off</span>
                    <span id="statusMic" class="text-[10px] uppercase font-bold tracking-tighter text-error/70">MIC: OFF</span>
                </div>
                <div class="flex flex-col items-center">
                    <span id="statusVideoIcon" class="material-symbols-outlined text-sm text-on-surface-variant">sensors_off</span>
                    <span id="statusVideo" class="text-[10px] uppercase font-bold tracking-tighter text-on-surface-variant/70">—</span>
                </div>
            </div>
            <span id="statusAutoReconn" class="hidden md:inline text-[10px] px-2 py-0.5 bg-surface-container-highest rounded text-on-surface-variant/50 tracking-wider font-mono">AUTO-REC: OFF</span>
            <div class="flex items-center gap-2 bg-surface-container-high px-2 md:px-3 py-1 rounded">
                <span class="material-symbols-outlined text-sm text-on-surface-variant">group</span>
                <span id="statusPeers" class="text-sm font-bold">0</span>
            </div>
            <button id="btnCleanup" class="bg-error/90 text-white text-[10px] font-black px-3 py-1.5 rounded hover:bg-error transition-colors tracking-widest whitespace-nowrap">KILL SWITCH</button>
        </div>
    </header>

    <!-- ── Main 3-column area ─────────────────────────────────────────── -->
    <div class="main-area flex flex-1 min-h-0 overflow-hidden">

        <!-- ── Left Sidebar — Controls ────────────────────────────────── -->
        <aside class="sidebar-left w-64 shrink-0 bg-background border-r border-outline-variant/10 overflow-y-auto flex flex-col font-body text-sm font-medium">
            <div class="p-4 space-y-6">

                <!-- 1. Connection -->
                <div>
                    <h3 class="text-[10px] uppercase tracking-widest text-on-surface-variant/50 mb-3 flex items-center gap-2 font-headline">
                        <span class="w-1 h-1 bg-primary rounded-full"></span> Conexão
                    </h3>
                    <div class="grid grid-cols-2 gap-2">
                        <button id="btnJoin" class="bg-surface-container-high p-2 text-[10px] font-bold text-secondary flex flex-col items-center gap-1 hover:bg-surface-bright transition-all rounded">
                            <span class="material-symbols-outlined text-base">login</span> ENTRAR
                        </button>
                        <button id="btnLeave" class="bg-surface-container-high p-2 text-[10px] font-bold text-error flex flex-col items-center gap-1 hover:bg-surface-bright transition-all rounded" disabled>
                            <span class="material-symbols-outlined text-base">logout</span> SAIR
                        </button>
                        <button id="btnStartCamera" class="bg-surface-container-high p-2 text-[10px] font-bold text-primary flex flex-col items-center gap-1 hover:bg-surface-bright transition-all rounded" disabled>
                            <span class="material-symbols-outlined text-base">videocam</span> CÂMERA
                        </button>
                        <button id="btnStartNoCamera" class="bg-surface-container-high p-2 text-[10px] font-bold text-on-surface-variant flex flex-col items-center gap-1 hover:bg-surface-bright transition-all rounded" disabled>
                            <span class="material-symbols-outlined text-base">videocam_off</span> SEM CAM
                        </button>
                    </div>
                </div>

                <!-- 2. Media Control -->
                <div>
                    <h3 class="text-[10px] uppercase tracking-widest text-on-surface-variant/50 mb-3 flex items-center gap-2 font-headline">
                        <span class="w-1 h-1 bg-primary rounded-full"></span> Mídia Local
                    </h3>
                    <div class="space-y-2">
                        <button id="btnToggleCam" class="w-full text-left text-[10px] p-2 bg-surface-container-high border border-outline-variant/10 hover:border-primary/40 transition-colors rounded flex items-center gap-2 text-on-surface" disabled>
                            Ligar câmera
                        </button>
                        <button id="btnToggleMic" class="w-full text-left text-[10px] p-2 bg-surface-container-high border border-outline-variant/10 hover:border-primary/40 transition-colors rounded flex items-center gap-2 text-on-surface" disabled>
                            Mutar mic
                        </button>
                        <button id="btnPauseVideo" class="w-full text-left text-[10px] p-2 bg-surface-container-lowest/50 border-l-2 border-primary hover:bg-surface-container-high transition-colors rounded flex items-center justify-between text-on-surface" disabled>
                            <span>Pausar vídeo</span>
                            <span class="material-symbols-outlined text-xs">pause</span>
                        </button>
                        <button id="btnBlackVideo" class="w-full text-left text-[10px] p-2 bg-surface-container-lowest/50 border-l-2 border-on-surface-variant/20 hover:bg-surface-container-high transition-colors rounded flex items-center justify-between text-on-surface" disabled>
                            <span>Vídeo preto</span>
                            <span class="material-symbols-outlined text-xs">brightness_1</span>
                        </button>
                        <button id="btnActivateAudio" class="w-full text-left text-[10px] p-2 bg-secondary/10 border border-secondary/30 hover:bg-secondary/20 transition-colors rounded flex items-center gap-2 text-secondary" style="display:none">
                            <span class="material-symbols-outlined text-sm">volume_up</span> Ativar áudio remoto
                        </button>
                    </div>
                </div>

                <!-- 3. Hardware -->
                <div class="space-y-3">
                    <h3 class="text-[10px] uppercase tracking-widest text-on-surface-variant/50 mb-1 flex items-center gap-2 font-headline">
                        <span class="w-1 h-1 bg-primary rounded-full"></span> Hardware
                    </h3>
                    <div class="space-y-2">
                        <div class="flex gap-2 items-center">
                            <select id="selCamera" class="flex-1 bg-surface-container-lowest border-none text-[10px] py-1.5 pl-2 pr-8 focus:ring-1 focus:ring-primary rounded appearance-none text-on-surface-variant">
                                <option value="">Câmera padrão</option>
                            </select>
                            <button id="btnSwitchCam" class="p-1.5 bg-surface-container-high text-on-surface-variant hover:bg-surface-bright rounded transition-colors shrink-0" disabled title="Trocar câmera">
                                <span class="material-symbols-outlined text-xs">refresh</span>
                            </button>
                        </div>
                        <div class="flex gap-2 items-center">
                            <select id="selAudioIn" class="flex-1 bg-surface-container-lowest border-none text-[10px] py-1.5 pl-2 pr-8 focus:ring-1 focus:ring-primary rounded appearance-none text-on-surface-variant">
                                <option value="">Microfone padrão</option>
                            </select>
                            <button id="btnSwitchAudioIn" class="p-1.5 bg-surface-container-high text-on-surface-variant hover:bg-surface-bright rounded transition-colors shrink-0" disabled title="Trocar microfone">
                                <span class="material-symbols-outlined text-xs">refresh</span>
                            </button>
                        </div>
                        <div class="flex gap-2 items-center">
                            <select id="selAudioOut" class="flex-1 bg-surface-container-lowest border-none text-[10px] py-1.5 pl-2 pr-8 focus:ring-1 focus:ring-primary rounded appearance-none text-on-surface-variant">
                                <option value="">Alto-falante padrão</option>
                            </select>
                            <button id="btnSwitchAudioOut" class="p-1.5 bg-surface-container-high text-on-surface-variant hover:bg-surface-bright rounded transition-colors shrink-0" title="Trocar saída">
                                <span class="material-symbols-outlined text-xs">refresh</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- 4. Reconnection & Failures -->
                <div>
                    <h3 class="text-[10px] uppercase tracking-widest text-on-surface-variant/50 mb-3 flex items-center gap-2 font-headline">
                        <span class="w-1 h-1 bg-primary rounded-full"></span> Reconexão &amp; Falhas
                    </h3>
                    <div class="grid grid-cols-2 gap-2">
                        <button id="btnReconnect" class="bg-surface-container-high p-2 text-[10px] font-bold text-tertiary flex flex-col items-center gap-1 hover:bg-surface-bright transition-all rounded">
                            <span class="material-symbols-outlined text-base">refresh</span> GRACEFUL
                        </button>
                        <button id="btnForceDisconnect" class="bg-surface-container-high p-2 text-[10px] font-bold text-error flex flex-col items-center gap-1 hover:bg-surface-bright transition-all rounded">
                            <span class="material-symbols-outlined text-base">bolt</span> FORÇAR
                        </button>
                    </div>
                    <button id="btnToggleAutoReconn" class="w-full mt-2 text-[10px] p-2 bg-surface-container-high border border-outline-variant/10 hover:border-primary/40 transition-colors rounded flex items-center justify-between text-on-surface">
                        <span>Auto-reconexão: OFF</span>
                        <span class="material-symbols-outlined text-xs">sync</span>
                    </button>
                </div>

                <!-- 5. SFU Orchestration -->
                <div>
                    <h3 class="text-[10px] uppercase tracking-widest text-on-surface-variant/50 mb-3 flex items-center gap-2 font-headline">
                        <span class="w-1 h-1 bg-primary rounded-full"></span> SFU Orchestration
                    </h3>
                    <div class="space-y-2">
                        <div class="flex gap-2">
                            <button id="btnPauseProducerSFU" class="flex-1 text-[10px] p-2 bg-surface-container-high border border-outline-variant/10 hover:border-primary/40 transition-colors rounded text-center text-on-surface" disabled>
                                PAUSE PRODUCER
                            </button>
                            <button id="btnResumeProducerSFU" class="flex-1 text-[10px] p-2 bg-surface-container-high border border-outline-variant/10 hover:border-primary/40 transition-colors rounded text-center text-on-surface" disabled>
                                RESUME
                            </button>
                        </div>
                        <div class="flex gap-2">
                            <button id="btnPauseAllConsumers" class="flex-1 text-[10px] p-2 bg-surface-container-high border border-outline-variant/10 hover:border-primary/40 transition-colors rounded text-center text-on-surface" disabled>
                                PAUSE CONSUMERS
                            </button>
                            <button id="btnResumeAllConsumers" class="flex-1 text-[10px] p-2 bg-surface-container-high border border-outline-variant/10 hover:border-primary/40 transition-colors rounded text-center text-on-surface" disabled>
                                RESUME
                            </button>
                        </div>
                    </div>
                </div>

                <!-- 6. Remote & Tests -->
                <div>
                    <h3 class="text-[10px] uppercase tracking-widest text-on-surface-variant/50 mb-3 flex items-center gap-2 font-headline">
                        <span class="w-1 h-1 bg-primary rounded-full"></span> Remoto &amp; Testes
                    </h3>
                    <div class="space-y-2">
                        <button id="btnToggleRemoteMute" class="w-full text-left text-[10px] p-2 bg-surface-container-high border border-outline-variant/10 hover:border-primary/40 transition-colors rounded flex items-center gap-2 text-on-surface">
                            <span class="material-symbols-outlined text-sm">volume_off</span> Mutar remoto
                        </button>
                        <button id="btnTestCleanup" class="w-full text-left text-[10px] p-2 bg-surface-container-high border border-outline-variant/10 hover:border-primary/40 transition-colors rounded flex items-center gap-2 text-on-surface">
                            <span class="material-symbols-outlined text-sm">bug_report</span> Detectar vazamento
                        </button>
                    </div>
                </div>

            </div>

            <!-- Sidebar bottom -->
            <div class="mt-auto border-t border-outline-variant/10 p-4">
                <button onclick="document.getElementById('logPanel').style.display = document.getElementById('logPanel').style.display === 'none' ? 'flex' : 'none'" class="w-full flex items-center gap-3 text-xs text-on-surface-variant/60 p-2 hover:bg-surface-container-high transition-colors rounded">
                    <span class="material-symbols-outlined text-sm">terminal</span> Logs
                </button>
            </div>
        </aside>

        <!-- ── Video Center ───────────────────────────────────────────── -->
        <main class="video-center flex-1 min-w-0 flex flex-col bg-surface-dim overflow-hidden">
            <div class="flex-1 relative overflow-hidden">
                <!-- Remote peers grid -->
                <div id="remoteVideos" class="remote-grid-inner w-full h-full overflow-y-auto p-6 technical-grid" style="display:grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 16px; align-content: start;"></div>

                <!-- Empty state -->
                <div id="videoEmptyState" class="absolute inset-0 flex flex-col items-center justify-center gap-4 pointer-events-none">
                    <span class="material-symbols-outlined text-5xl text-on-surface-variant/20">videocam_off</span>
                    <p class="text-xs text-on-surface-variant/30 uppercase tracking-widest font-bold font-headline">Conecte-se para iniciar a chamada</p>
                </div>

                <!-- Local PiP -->
                <div class="local-pip absolute bottom-4 right-4 z-20 rounded-xl overflow-hidden border-2 border-primary/40 bg-surface-container-low" style="box-shadow: 0 4px 20px rgba(0,0,0,0.6);">
                    <div class="absolute inset-0 bg-primary/5 pointer-events-none z-10"></div>
                    <video id="localVideo" autoplay muted playsinline class="block object-cover bg-surface-container-lowest" style="width:180px; height:135px;"></video>
                    <div class="absolute top-2 left-2 flex items-center gap-1.5 bg-primary/80 px-2 py-0.5 rounded z-20">
                        <span class="text-[9px] font-mono font-black text-on-primary uppercase tracking-widest">VOCÊ (HOST)</span>
                    </div>
                    <div class="absolute bottom-2 right-2 bg-surface-container-highest/80 backdrop-blur-sm px-2 py-1 flex items-center gap-3 z-20">
                        <div class="flex items-center gap-1">
                            <span class="w-1 h-1 bg-secondary rounded-full animate-ping"></span>
                            <span class="text-[8px] font-mono text-secondary">LIVE</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Performance grid -->
            <div class="perf-grid-center grid grid-cols-5 gap-3 px-6 py-4 shrink-0 border-t border-outline-variant/10 bg-background/50">
                <div class="bg-surface-container-low p-3 border-l-2 border-primary rounded">
                    <span class="text-[9px] uppercase tracking-widest text-on-surface-variant mb-1 block">Heap JS</span>
                    <div class="flex items-baseline gap-1">
                        <span id="perfHeap" class="text-xl font-mono font-bold">—</span>
                    </div>
                </div>
                <div class="bg-surface-container-low p-3 border-l-2 border-secondary rounded">
                    <span class="text-[9px] uppercase tracking-widest text-on-surface-variant mb-1 block">Tracks Ativas</span>
                    <div class="flex items-baseline gap-1">
                        <span id="perfTracks" class="text-xl font-mono font-bold">0</span>
                    </div>
                </div>
                <div class="bg-surface-container-low p-3 border-l-2 border-tertiary rounded">
                    <span class="text-[9px] uppercase tracking-widest text-on-surface-variant mb-1 block">Reconexões</span>
                    <div class="flex items-baseline gap-1">
                        <span id="perfReconns" class="text-xl font-mono font-bold">0</span>
                    </div>
                </div>
                <div class="bg-surface-container-low p-3 border-l-2 border-on-primary-container rounded">
                    <span class="text-[9px] uppercase tracking-widest text-on-surface-variant mb-1 block">Uptime</span>
                    <div class="flex items-baseline gap-1">
                        <span id="perfUptime" class="text-xl font-mono font-bold">—</span>
                    </div>
                </div>
                <div class="bg-surface-container-low p-3 border-l-2 border-secondary rounded">
                    <span class="text-[9px] uppercase tracking-widest text-on-surface-variant mb-1 block">Auto-Recon</span>
                    <div class="flex items-baseline gap-1">
                        <span id="perfAutoReconn" class="text-xl font-mono font-bold text-on-surface-variant/50">OFF</span>
                    </div>
                </div>
            </div>
        </main>

        <!-- ── Right Sidebar — Network Quality ────────────────────────── -->
        <aside class="sidebar-right w-72 shrink-0 bg-background border-l border-outline-variant/10 flex flex-col overflow-y-auto">
            <div class="flex justify-between items-center p-4 border-b border-outline-variant/10 shrink-0">
                <h3 class="text-[10px] uppercase tracking-widest font-black text-primary font-headline">Live Telemetry</h3>
                <button id="btnToggleStats" class="text-[9px] bg-surface-container-highest px-2 py-0.5 rounded text-on-surface-variant hover:text-on-surface transition-colors">PAUSAR</button>
            </div>
            <div id="statsCards" class="p-4 flex-1 overflow-y-auto">
                <p class="text-[11px] text-on-surface-variant/40 text-center py-4">Aguardando mídia…</p>
            </div>
        </aside>

    </div><!-- /.main-area -->

    <!-- ── Log Panel ──────────────────────────────────────────────────── -->
    <div id="logPanel" class="log-panel shrink-0 flex flex-col border-t border-outline-variant/15 bg-surface-container-low" style="height: 148px;">
        <div class="flex items-center justify-between px-4 h-8 shrink-0 font-['JetBrains_Mono'] text-[10px] uppercase tracking-widest border-b border-outline-variant/10">
            <div class="flex items-center gap-6">
                <span class="text-secondary font-bold">SYSTEM LOGS</span>
            </div>
            <div class="flex items-center gap-4">
                <button id="btnCopyLogs" class="hover:text-white text-secondary transition-colors">COPY</button>
                <button id="btnClearLogs" class="hover:text-white text-on-surface-variant/50 transition-colors">CLEAR</button>
                <span class="text-on-surface-variant/40 border-l border-outline-variant/20 pl-4 hidden sm:inline">v4.2.0-STABLE</span>
            </div>
        </div>
        <div id="logContainer" class="flex-1 overflow-y-auto px-4 py-2 font-['JetBrains_Mono'] text-[10px] leading-relaxed text-on-surface-variant/60"></div>
    </div>

</div><!-- /.admin-layout -->

<script>
    window.__SFU_TEST_CONFIG__ = @json($sfuTestConfig);
</script>
@vite(['resources/js/sfu-test-app.js'])
</body>
</html>
