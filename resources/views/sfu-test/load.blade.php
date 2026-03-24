<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Load Test SFU — {{ config('app.name') }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        html { -webkit-text-size-adjust: 100%; }
        body {
            font-family: system-ui, sans-serif;
            background: #0f0f1a;
            color: #e0e0e0;
            padding: 16px;
            padding-left: max(16px, env(safe-area-inset-left, 0px));
            padding-right: max(16px, env(safe-area-inset-right, 0px));
            padding-bottom: max(16px, env(safe-area-inset-bottom, 0px));
            min-height: 100%;
            min-height: 100dvh;
        }

        /* Header */
        .header {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
            background: #1a1a2e;
            border-radius: 10px;
            padding: 14px 18px;
            margin-bottom: 16px;
        }
        .header h1 { font-size: clamp(0.9rem, 2.8vw, 1rem); font-weight: 600; }
        .badge {
            padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600;
        }
        .badge-yellow { background: #e67700; color: #fff; }
        .badge-green  { background: #2f9e44; color: #fff; }
        .badge-red    { background: #c92a2a; color: #fff; }
        .room-tag { margin-left: auto; font-size: 11px; color: #666; }
        .room-tag strong { color: #888; }

        /* Info cards */
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(min(100%, 140px), 1fr));
            gap: 10px;
            margin-bottom: 16px;
        }
        .info-card {
            background: #1a1a2e; border-radius: 8px; padding: 12px 16px;
            min-width: 0;
        }
        .info-card .lbl { font-size: 10px; text-transform: uppercase; color: #666; margin-bottom: 4px; letter-spacing: 0.5px; }
        .info-card .val { font-size: 1.1rem; font-weight: 600; font-family: monospace; color: #eee; }

        /* Videos */
        .videos-row {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 16px;
            align-items: flex-start;
        }
        .video-wrap { flex: 0 0 auto; min-width: 0; }
        .video-wrap p { font-size: 10px; color: #888; margin-bottom: 3px; }
        video { background: #111; border-radius: 7px; display: block; object-fit: cover; max-width: 100%; }
        #localVideo {
            width: min(160px, 100%);
            max-width: min(160px, 45vw);
            height: auto;
            aspect-ratio: 4 / 3;
            border: 2px solid #3b5bdb;
        }
        #remoteVideos {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            flex: 1 1 200px;
            min-width: 0;
        }
        .load-remote-wrap {
            display: inline-block;
            margin: 4px;
            vertical-align: top;
            max-width: 100%;
        }
        .load-remote-wrap p { font-size: 9px; color: #666; margin-bottom: 2px; word-break: break-all; }
        .load-remote-video {
            width: clamp(100px, 32vw, 160px);
            height: auto;
            aspect-ratio: 4 / 3;
            background: #111;
            border-radius: 5px;
            display: block;
            object-fit: cover;
        }

        /* Share box */
        .share-box {
            background: #1a1a2e; border-radius: 8px; padding: 14px;
            margin-bottom: 16px;
        }
        .share-box h3 { font-size: 10px; text-transform: uppercase; color: #666; margin-bottom: 10px; letter-spacing: 0.5px; }
        .share-url {
            font-family: monospace; font-size: 12px; color: #7ec8e3;
            word-break: break-all; user-select: all;
            background: #0a0a14; padding: 8px 10px; border-radius: 5px;
            margin-bottom: 8px;
        }
        .btn-copy {
            padding: 7px 14px; border: none; border-radius: 6px;
            background: #3b5bdb; color: #fff; cursor: pointer; font-size: 12px; font-weight: 500;
        }
        .btn-copy:hover { opacity: 0.85; }

        /* Stats mini */
        .stats-mini {
            background: #1a1a2e; border-radius: 8px; padding: 12px;
            margin-bottom: 16px; font-size: 11px; font-family: monospace; color: #888; line-height: 1.7;
        }
        .stats-mini h3 { font-size: 10px; text-transform: uppercase; color: #555; margin-bottom: 6px; }

        /* Log */
        .log-label { font-size: 10px; text-transform: uppercase; color: #555; margin-bottom: 6px; }
        #logContainer {
            background: #0a0a14; border-radius: 8px; padding: 10px;
            max-height: min(40vh, 220px);
            overflow-y: auto;
            font-size: 10px;
            font-family: monospace;
            line-height: 1.5;
            color: #888;
            -webkit-overflow-scrolling: touch;
        }

        @media (max-width: 640px) {
            body { padding: 12px; }
            .header { padding: 12px 14px; gap: 8px; }
            .room-tag {
                margin-left: 0;
                flex-basis: 100%;
                order: 3;
            }
            .stats-mini { font-size: 10px; }
        }

        @media (max-width: 400px) {
            .load-remote-video { width: 100%; max-width: 100%; }
            #remoteVideos { flex-basis: 100%; }
        }
    </style>
</head>
<body>

    <div class="header">
        <h1>⚡ Load Test SFU</h1>
        <span id="statusBadge" class="badge badge-yellow">Iniciando…</span>
        <span class="room-tag">Sala: <strong>{{ $roomId }}</strong></span>
    </div>

    <div class="info-grid">
        <div class="info-card">
            <div class="lbl">Meu ID</div>
            <div class="val" id="infoUserId" style="font-size:0.7rem; word-break:break-all;">—</div>
        </div>
        <div class="info-card">
            <div class="lbl">Participantes</div>
            <div class="val" id="infoParticipants">0</div>
        </div>
        <div class="info-card">
            <div class="lbl">Câmera</div>
            <div class="val" id="infoCam" style="color:#888;">—</div>
        </div>
        <div class="info-card">
            <div class="lbl">Microfone</div>
            <div class="val" id="infoMic" style="color:#888;">—</div>
        </div>
        <div class="info-card">
            <div class="lbl">Conexão</div>
            <div class="val" id="infoConn" style="font-size:0.9rem; color:#888;">—</div>
        </div>
    </div>

    <div class="videos-row">
        <div class="video-wrap">
            <p>Você</p>
            <video id="localVideo" autoplay muted playsinline></video>
        </div>
        <div id="remoteVideos"></div>
    </div>

    <div class="stats-mini">
        <h3>Estatísticas (auto)</h3>
        <div id="statsContent">Aguardando mídia…</div>
    </div>

    <div class="share-box">
        <h3>Abrir em outra aba ou dispositivo</h3>
        <div class="share-url" id="pageUrl"></div>
        <button class="btn-copy" id="btnCopy">Copiar URL</button>
    </div>

    <p class="log-label">Logs</p>
    <div id="logContainer"></div>

    <script>
        window.__SFU_LOAD_CONFIG__ = @json($sfuTestConfig);

        // Fill share URL
        const pageUrlEl = document.getElementById('pageUrl');
        if (pageUrlEl) pageUrlEl.textContent = window.location.href;

        // Copy button
        document.getElementById('btnCopy')?.addEventListener('click', () => {
            navigator.clipboard?.writeText(window.location.href).then(() => {
                const btn = document.getElementById('btnCopy');
                if (btn) { btn.textContent = '✓ Copiado!'; setTimeout(() => btn.textContent = 'Copiar URL', 2000); }
            }).catch(() => {});
        });
    </script>
    @vite(['resources/js/sfu-load-test-app.js'])
</body>
</html>
