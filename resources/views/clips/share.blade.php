<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $video->analyzed_team_name ?? $video->title }} – Clip {{ $clip->formatted_start }} › {{ $clip->formatted_end }}</title>
    <meta property="og:title" content="{{ $video->title }}">
    <meta property="og:description" content="Clip de rugby {{ $clip->formatted_start }} – {{ $clip->formatted_end }} ({{ $clip->formatted_duration }})">
    <script src="https://cdn.jsdelivr.net/npm/hls.js@1.5.13/dist/hls.min.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            background: #0f0f0f;
            color: #fff;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        /* Header */
        .share-header {
            width: 100%;
            max-width: 960px;
            padding: 0.8rem 1.2rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .share-header img {
            height: 28px;
            opacity: 0.9;
        }
        .share-header span {
            font-size: 13px;
            color: #666;
        }

        /* Player */
        .player-wrapper {
            width: 100%;
            max-width: 960px;
            position: relative;
            background: #000;
        }
        video {
            width: 100%;
            display: block;
            max-height: 70vh;
            background: #000;
        }

        /* Clip info */
        .clip-info {
            width: 100%;
            max-width: 960px;
            padding: 1rem 1.2rem 1.5rem;
        }
        .clip-category {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.2rem 0.65rem;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            margin-bottom: 0.6rem;
            background: {{ $clip->category?->color ?? '#FFC300' }}22;
            color: {{ $clip->category?->color ?? '#FFC300' }};
            border: 1px solid {{ $clip->category?->color ?? '#FFC300' }}55;
        }
        .clip-category::before {
            content: '';
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: {{ $clip->category?->color ?? '#FFC300' }};
            flex-shrink: 0;
        }
        .video-title {
            font-size: 17px;
            font-weight: 600;
            color: #fff;
            margin-bottom: 0.3rem;
            line-height: 1.3;
        }
        .video-meta {
            font-size: 12px;
            color: #666;
            display: flex;
            flex-wrap: wrap;
            gap: 0.9rem;
        }
        .video-meta span {
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }
        .clip-times {
            margin-top: 0.75rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: #1a1a1a;
            border-radius: 6px;
            padding: 0.4rem 0.8rem;
            font-size: 12px;
            font-family: monospace;
            color: #FFC300;
        }
        .clip-times .sep { color: #444; }

        /* Replay overlay */
        .replay-overlay {
            display: none;
            position: absolute;
            inset: 0;
            background: rgba(0,0,0,0.6);
            align-items: center;
            justify-content: center;
            flex-direction: column;
            gap: 0.75rem;
            cursor: pointer;
        }
        .replay-overlay.visible { display: flex; }
        .replay-btn {
            background: #FFC300;
            border: none;
            border-radius: 50%;
            width: 64px;
            height: 64px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: transform 0.15s, background 0.15s;
        }
        .replay-btn:hover { transform: scale(1.08); background: #009d9b; }
        .replay-btn svg { width: 26px; height: 26px; fill: #fff; margin-left: 3px; }
        .replay-label {
            font-size: 13px;
            color: #ccc;
            font-weight: 500;
        }

        /* Loading */
        .loading {
            position: absolute;
            inset: 0;
            background: #000;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .spinner {
            width: 36px;
            height: 36px;
            border: 3px solid #333;
            border-top-color: #FFC300;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* Footer */
        .share-footer {
            margin-top: auto;
            padding: 1.2rem;
            font-size: 11px;
            color: #444;
            text-align: center;
        }
        .share-footer a { color: #555; text-decoration: none; }
        .share-footer a:hover { color: #FFC300; }
    </style>
</head>
<body>

    <div class="share-header">
        <img src="{{ asset('logo.png') }}" alt="RugbyKP" onerror="this.style.display='none'">
        <span>RugbyKP · Clip compartido</span>
    </div>

    <div class="player-wrapper">
        <div class="loading" id="loading">
            <div class="spinner"></div>
        </div>

        <video id="video" playsinline controls></video>

        <div class="replay-overlay" id="replayOverlay" onclick="replayClip()">
            <button class="replay-btn" title="Repetir clip">
                <svg viewBox="0 0 24 24"><path d="M12 5V1L7 6l5 5V7c3.31 0 6 2.69 6 6s-2.69 6-6 6-6-2.69-6-6H4c0 4.42 3.58 8 8 8s8-3.58 8-8-3.58-8-8-8z"/></svg>
            </button>
            <span class="replay-label">Repetir clip</span>
        </div>
    </div>

    <div class="clip-info">
        @if($clip->category)
        <div class="clip-category">{{ $clip->category->name }}</div>
        @endif

        <div class="video-title">{{ $video->title }}</div>

        <div class="video-meta">
            @if($video->analyzed_team_name)
            <span>🏉 {{ $video->analyzed_team_name }}@if($video->rival_team_name) vs {{ $video->rival_team_name }}@endif</span>
            @endif
            @if($video->match_date)
            <span>📅 {{ \Carbon\Carbon::parse($video->match_date)->format('d/m/Y') }}</span>
            @endif
            @if($video->organization)
            <span>🏛 {{ $video->organization->name }}</span>
            @endif
        </div>

        <div class="clip-times">
            {{ $clip->formatted_start }}
            <span class="sep">›</span>
            {{ $clip->formatted_end }}
            <span class="sep">·</span>
            {{ $clip->formatted_duration }}
        </div>
    </div>

    <div class="share-footer">
        <a href="{{ url('/') }}">RugbyKP · Rugby Key Performance</a>
    </div>

    <script>
        const hlsUrl    = @json($video->bunny_hls_url);
        const startTime = {{ (float) $clip->start_time }};
        const endTime   = {{ (float) $clip->end_time }};

        const videoEl      = document.getElementById('video');
        const loadingEl    = document.getElementById('loading');
        const replayEl     = document.getElementById('replayOverlay');

        function seekToClip() {
            videoEl.currentTime = startTime;
        }

        function replayClip() {
            replayEl.classList.remove('visible');
            videoEl.currentTime = startTime;
            videoEl.play().catch(() => {});
        }

        // Stop at end_time and show replay overlay
        videoEl.addEventListener('timeupdate', () => {
            if (videoEl.currentTime >= endTime) {
                videoEl.pause();
                videoEl.currentTime = endTime;
                replayEl.classList.add('visible');
            }
        });

        // Hide replay when user plays manually
        videoEl.addEventListener('play', () => {
            replayEl.classList.remove('visible');
        });

        // Seek to clip start once metadata is ready, then autoplay
        videoEl.addEventListener('loadedmetadata', () => {
            loadingEl.style.display = 'none';
            seekToClip();
            videoEl.play().catch(() => {
                // Autoplay blocked — user needs to press play (video is already at start_time)
            });
        });

        // Init HLS
        if (Hls.isSupported()) {
            const hls = new Hls({ startPosition: startTime });
            hls.loadSource(hlsUrl);
            hls.attachMedia(videoEl);
        } else if (videoEl.canPlayType('application/vnd.apple.mpegurl')) {
            // Safari native HLS
            videoEl.src = hlsUrl;
        } else {
            loadingEl.innerHTML = '<p style="color:#888;font-size:13px">Tu navegador no soporta este formato de video.</p>';
        }
    </script>
</body>
</html>
