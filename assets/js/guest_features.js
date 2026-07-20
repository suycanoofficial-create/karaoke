var KTV = KTV || {};
KTV.ROOM_ID = typeof ROOM_ID !== 'undefined' ? ROOM_ID : null;
KTV.ROOM_CODE = typeof ROOM_CODE !== 'undefined' ? ROOM_CODE : '';
KTV.getNick = function() { return typeof nickname !== 'undefined' && nickname ? nickname : 'Guest'; };
KTV.cheerSounds = {};
KTV.audioCtx = null;
KTV.micStream = null;
KTV.micActive = false;
KTV.monitorActive = false;
KTV.mediaRecorder = null;
KTV.recordingChunks = [];
KTV.audioRelayTimer = null;
KTV.audioRelaySeq = 0;
KTV.lastCheerId = 0;
KTV.lastReactionId = 0;
KTV.lastCompletedTrack = null;
KTV.scoredTracks = {};

KTV.init = function() {
    if (!KTV.ROOM_ID) return;
    KTV.pollRoomStatus();
    KTV.pollHostReactions();
    KTV.injectStyles();
    KTV.injectUI();
    KTV.loadEchoSettings();
    setInterval(KTV.pollRoomStatus, 15000);
    setInterval(KTV.pollHostReactions, 10000);
    // Restart polling if already started (cookie present)
    if (typeof pollInterval !== 'undefined' && pollInterval) {
        clearInterval(pollInterval);
        if (typeof startPolling === 'function') startPolling();
    }
};

KTV.injectStyles = function() {
    var css = document.createElement('style');
    css.textContent = `
        .ktv-feature-bar {
            display:flex; gap:8px; padding:8px 0; flex-wrap:wrap; align-items:center;
        }
        .ktv-feature-btn {
            display:inline-flex; align-items:center; gap:5px;
            padding:7px 14px; border-radius:var(--radius-full);
            border:1px solid rgba(255,255,255,0.12);
            background:rgba(255,255,255,0.06);
            color:rgba(255,255,255,0.7); font-size:0.75rem; font-weight:500;
            cursor:pointer; transition:all 0.2s; font-family:var(--font-body);
            touch-action:manipulation; user-select:none;
        }
        .ktv-feature-btn:hover { background:rgba(255,255,255,0.1); color:#fff; }
        .ktv-feature-btn:active { transform:scale(0.93); }
        .ktv-feature-btn.active { background:var(--gold); color:var(--midnight); border-color:var(--gold); }
        .ktv-feature-btn.active:hover { opacity:0.9; }
        .ktv-feature-btn.danger.active { background:#ef4444; color:#fff; border-color:#ef4444; }
        .ktv-feature-btn.danger.active:hover { opacity:0.9; }
        .ktv-feature-btn.small { padding:4px 10px; font-size:0.7rem; }
        .ktv-feature-btn .badge {
            background:rgba(255,255,255,0.15); border-radius:50%;
            width:18px; height:18px; display:flex; align-items:center;
            justify-content:center; font-size:0.6rem; font-weight:700;
        }
        .ktv-feature-btn.active .badge { background:rgba(0,0,0,0.15); }

        /* Lock indicator */
        .ktv-lock-bar {
            display:flex; align-items:center; gap:8px;
            padding:8px 14px; border-radius:var(--radius-md);
            background:rgba(239,68,68,0.08); border:1px solid rgba(239,68,68,0.2);
            margin-bottom:var(--space-sm); font-size:0.75rem; color:#ef4444;
        }
        .ktv-lock-bar.hidden { display:none; }

        /* Cheer buttons */
        .ktv-cheer-grid {
            display:flex; gap:6px; flex-wrap:wrap;
            padding:var(--space-xs) 0;
        }
        .ktv-cheer-btn {
            display:inline-flex; align-items:center; gap:4px;
            padding:6px 12px; border-radius:var(--radius-full);
            border:1px solid rgba(255,255,255,0.1);
            background:rgba(255,255,255,0.05);
            color:rgba(255,255,255,0.7); font-size:0.7rem;
            cursor:pointer; transition:all 0.15s; font-family:var(--font-body);
            touch-action:manipulation;
        }
        .ktv-cheer-btn:hover { background:rgba(255,255,255,0.1); color:#fff; transform:scale(1.05); }
        .ktv-cheer-btn:active { transform:scale(0.9); }
        .ktv-cheer-btn .emoji { font-size:1rem; }

        /* Reaction buttons */
        .ktv-reaction-grid {
            display:flex; gap:6px; flex-wrap:wrap;
            padding:var(--space-xs) 0;
        }
        .ktv-react-btn {
            width:38px; height:38px; border-radius:50%;
            border:1px solid rgba(255,255,255,0.1);
            background:rgba(255,255,255,0.05);
            font-size:1.1rem; cursor:pointer; transition:all 0.15s;
            display:flex; align-items:center; justify-content:center;
            touch-action:manipulation;
        }
        .ktv-react-btn:hover { background:rgba(255,255,255,0.12); transform:scale(1.15); }
        .ktv-react-btn:active { transform:scale(0.85); }
        .ktv-react-btn.sent { animation:ktv-react-pop 0.4s ease; }
        @keyframes ktv-react-pop { 0%{transform:scale(1)} 50%{transform:scale(1.3)} 100%{transform:scale(1)} }

        /* Score modal */
        .ktv-score-overlay {
            position:fixed; inset:0; background:rgba(0,0,0,0.7); backdrop-filter:blur(8px);
            display:none; align-items:center; justify-content:center; z-index:500;
        }
        .ktv-score-overlay.active { display:flex; }
        .ktv-score-box {
            background:var(--obsidian); border:1px solid rgba(255,255,255,0.1);
            border-radius:var(--radius-xl); padding:var(--space-xl) var(--space-lg);
            max-width:340px; width:90vw; text-align:center;
        }
        .ktv-score-title { font-size:1rem; font-weight:600; margin-bottom:4px; }
        .ktv-score-sub { font-size:0.8rem; color:rgba(255,255,255,0.4); margin-bottom:var(--space-md); }
        .ktv-score-stars {
            display:flex; gap:8px; justify-content:center; margin-bottom:var(--space-lg);
            flex-direction:row-reverse;
        }
        .ktv-score-star {
            font-size:2rem; cursor:pointer; transition:all 0.15s;
            color:rgba(255,255,255,0.15); filter:grayscale(1);
            touch-action:manipulation;
        }
        .ktv-score-star:hover,
        .ktv-score-star.active,
        .ktv-score-star:hover ~ .ktv-score-star,
        .ktv-score-star.active ~ .ktv-score-star {
            color:var(--gold); filter:none; transform:scale(1.1);
        }
        .ktv-score-btn {
            width:100%; padding:10px; border-radius:var(--radius-full);
            background:var(--gold); color:var(--midnight); font-weight:700;
            font-size:0.85rem; border:none; cursor:pointer; font-family:var(--font-body);
            transition:opacity 0.2s;
        }
        .ktv-score-btn:active { opacity:0.8; }
        .ktv-score-btn:disabled { opacity:0.4; cursor:default; }

        /* Score display on completed items */
        .ktv-score-display {
            display:inline-flex; align-items:center; gap:4px;
            font-size:0.75rem; font-weight:600; color:var(--gold);
            background:rgba(212,175,55,0.12); padding:2px 10px;
            border-radius:var(--radius-full);
        }
        .ktv-score-display .num { font-size:0.85rem; }

        /* Mic status */
        .ktv-mic-status {
            display:flex; align-items:center; gap:6px;
            font-size:0.7rem; color:rgba(255,255,255,0.4);
        }
        .ktv-mic-dot {
            width:8px; height:8px; border-radius:50%;
            background:#4ade80; animation:ktv-pulse 1.5s ease-in-out infinite;
        }
        @keyframes ktv-pulse { 0%,100%{opacity:1} 50%{opacity:0.3} }
        .ktv-mic-level {
            width:60px; height:4px; border-radius:2px;
            background:rgba(255,255,255,0.1); overflow:hidden;
        }
        .ktv-mic-level-fill {
            height:100%; border-radius:2px;
            background: linear-gradient(90deg, #4ade80, #facc15, #ef4444);
            transition:width 0.1s; width:0%;
        }

        .ktv-section-label {
            font-size:0.55rem; text-transform:uppercase; letter-spacing:0.15em;
            font-weight:600; color:rgba(255,255,255,0.3); margin-top:var(--space-sm);
            padding:var(--space-xs) 0;
        }

        .ktv-feature-btn.small .ktv-echo-badge {
            display:inline-block; font-size:0.55rem; font-weight:700;
            background:rgba(255,215,0,0.2); color:var(--gold);
            padding:0 5px; border-radius:6px; margin-left:3px; line-height:1.4;
            vertical-align:middle;
        }
        .ktv-echo-panel {
            display:none; padding:var(--space-xs) 0 var(--space-sm);
        }
        .ktv-echo-panel.open { display:block; }
        .ktv-echo-row {
            display:flex; align-items:center; gap:var(--space-sm);
            padding:4px 0;
        }
        .ktv-echo-row label {
            font-size:0.65rem; color:rgba(255,255,255,0.5); min-width:55px; flex-shrink:0;
            text-transform:uppercase; letter-spacing:0.05em;
        }
        .ktv-echo-row input[type="range"] {
            flex:1; height:4px; -webkit-appearance:none; appearance:none;
            background:rgba(255,255,255,0.15); border-radius:2px; outline:none;
        }
        .ktv-echo-row input[type="range"]::-webkit-slider-thumb {
            -webkit-appearance:none; width:16px; height:16px; border-radius:50%;
            background:var(--gold); cursor:pointer; border:none;
        }
        .ktv-echo-row .val {
            font-size:0.7rem; color:var(--gold); font-weight:600;
            min-width:38px; text-align:right; font-variant-numeric:tabular-nums;
        }
    `;
    document.head.appendChild(css);
};

KTV.injectUI = function() {
    var body = document.querySelector('.g-body');
    if (!body) return;

    // Features bar
    var bar = document.createElement('div');
    bar.className = 'ktv-feature-bar';
    bar.id = 'ktvFeatureBar';
    bar.innerHTML = `
        <button class="ktv-feature-btn small" id="ktvMicBtn" onclick="KTV.toggleMic()" title="Use phone as microphone">
            <span>🎤</span> Mic
        </button>
        <button class="ktv-feature-btn small" id="ktvMonitorBtn" onclick="KTV.toggleMonitor()" title="Hear your own voice">
            <span>🔊</span> Monitor <span class="ktv-echo-badge" id="ktvEchoBadge">35%</span>
        </button>
        <!-- Echo Panel -->
        <div class="ktv-echo-panel" id="ktvEchoPanel">
            <div class="ktv-echo-row">
                <label>Delay</label>
                <input type="range" id="ktvEchoDelay" min="0.05" max="1.0" step="0.01" value="0.35" oninput="KTV.onEchoChange('delay', this.value)">
                <span class="val" id="ktvEchoDelayVal">0.35s</span>
            </div>
            <div class="ktv-echo-row">
                <label>Feedback</label>
                <input type="range" id="ktvEchoFeedback" min="0" max="0.8" step="0.01" value="0.35" oninput="KTV.onEchoChange('feedback', this.value)">
                <span class="val" id="ktvEchoFeedbackVal">35%</span>
            </div>
            <div class="ktv-echo-row">
                <label>Mix</label>
                <input type="range" id="ktvEchoMix" min="0" max="1.0" step="0.01" value="0.35" oninput="KTV.onEchoChange('mix', this.value)">
                <span class="val" id="ktvEchoMixVal">35%</span>
            </div>
        </div>
        <button class="ktv-feature-btn small" id="ktvCheerToggle" onclick="KTV.toggleCheerPanel()">
            <span>🎉</span> Cheer
        </button>
        <button class="ktv-feature-btn small" id="ktvReactToggle" onclick="KTV.toggleReactPanel()">
            <span>💫</span> React
        </button>
    `;
    body.insertBefore(bar, body.firstChild);

    // Lock bar
    var lockBar = document.createElement('div');
    lockBar.className = 'ktv-lock-bar hidden';
    lockBar.id = 'ktvLockBar';
    lockBar.innerHTML = '<span>🔒</span> Queue is locked by the host. No songs can be added.';
    body.insertBefore(lockBar, body.firstChild);

    // Cheer panel
    var cheerPanel = document.createElement('div');
    cheerPanel.id = 'ktvCheerPanel';
    cheerPanel.style.display = 'none';
    cheerPanel.innerHTML = `
        <div class="ktv-section-label">Sound Effects</div>
        <div class="ktv-cheer-grid">
            <button class="ktv-cheer-btn" onclick="KTV.sendCheer('applause')"><span class="emoji">👏</span> Applause</button>
            <button class="ktv-cheer-btn" onclick="KTV.sendCheer('cheer')"><span class="emoji">🎉</span> Cheer</button>
            <button class="ktv-cheer-btn" onclick="KTV.sendCheer('airhorn')"><span class="emoji">📯</span> Airhorn</button>
            <button class="ktv-cheer-btn" onclick="KTV.sendCheer('boo')"><span class="emoji">👎</span> Boo</button>
            <button class="ktv-cheer-btn" onclick="KTV.sendCheer('laugh')"><span class="emoji">😂</span> Laugh</button>
            <button class="ktv-cheer-btn" onclick="KTV.sendCheer('drums')"><span class="emoji">🥁</span> Drums</button>
        </div>
    `;
    body.insertBefore(cheerPanel, bar.nextSibling);

    // Reaction panel
    var reactPanel = document.createElement('div');
    reactPanel.id = 'ktvReactPanel';
    reactPanel.style.display = 'none';
    reactPanel.innerHTML = `
        <div class="ktv-section-label">Emoji Reactions</div>
        <div class="ktv-reaction-grid" id="ktvReactionGrid">
            <button class="ktv-react-btn" onclick="KTV.sendReaction('fire', event)">🔥</button>
            <button class="ktv-react-btn" onclick="KTV.sendReaction('crown', event)">👑</button>
            <button class="ktv-react-btn" onclick="KTV.sendReaction('heart', event)">❤️</button>
            <button class="ktv-react-btn" onclick="KTV.sendReaction('clap', event)">👏</button>
            <button class="ktv-react-btn" onclick="KTV.sendReaction('rocket', event)">🚀</button>
            <button class="ktv-react-btn" onclick="KTV.sendReaction('fireworks', event)">🎆</button>
            <button class="ktv-react-btn" onclick="KTV.sendReaction('100', event)">💯</button>
            <button class="ktv-react-btn" onclick="KTV.sendReaction('poop', event)">💩</button>
        </div>
    `;
    body.insertBefore(reactPanel, cheerPanel.nextSibling);

    // Mic status
    var micStatus = document.createElement('div');
    micStatus.className = 'ktv-mic-status';
    micStatus.id = 'ktvMicStatus';
    micStatus.style.display = 'none';
    micStatus.innerHTML = '<span class="ktv-mic-dot"></span><span>Mic Active</span><div class="ktv-mic-level"><div class="ktv-mic-level-fill" id="ktvMicLevel"></div></div>';
    body.insertBefore(micStatus, body.firstChild);

    // Score overlay
    var scoreOverlay = document.createElement('div');
    scoreOverlay.className = 'ktv-score-overlay';
    scoreOverlay.id = 'ktvScoreOverlay';
    scoreOverlay.innerHTML = `
        <div class="ktv-score-box">
            <div class="ktv-score-title">Rate the Performance!</div>
            <div class="ktv-score-sub" id="ktvScoreSub">How was that song?</div>
            <div class="ktv-score-stars" id="ktvScoreStars">
                <span class="ktv-score-star" data-val="10">★</span>
                <span class="ktv-score-star" data-val="9">★</span>
                <span class="ktv-score-star" data-val="8">★</span>
                <span class="ktv-score-star" data-val="7">★</span>
                <span class="ktv-score-star" data-val="6">★</span>
                <span class="ktv-score-star" data-val="5">★</span>
                <span class="ktv-score-star" data-val="4">★</span>
                <span class="ktv-score-star" data-val="3">★</span>
                <span class="ktv-score-star" data-val="2">★</span>
                <span class="ktv-score-star" data-val="1">★</span>
            </div>
            <button class="ktv-score-btn" id="ktvScoreBtn" onclick="KTV.submitScore()" disabled>Rate</button>
        </div>
    `;
    document.body.appendChild(scoreOverlay);

    // Score stars click
    document.getElementById('ktvScoreStars').addEventListener('click', function(e) {
        var star = e.target.closest('.ktv-score-star');
        if (!star) return;
        var val = parseInt(star.dataset.val);
        KTV.selectedScore = val;
        document.querySelectorAll('.ktv-score-star').forEach(function(s) {
            s.classList.toggle('active', parseInt(s.dataset.val) <= val);
        });
        document.getElementById('ktvScoreBtn').disabled = false;
    });
};

KTV.toggleCheerPanel = function() {
    var panel = document.getElementById('ktvCheerPanel');
    var btn = document.getElementById('ktvCheerToggle');
    var isOpen = panel.style.display !== 'none';
    panel.style.display = isOpen ? 'none' : 'block';
    btn.classList.toggle('active', !isOpen);
    // Close react panel
    document.getElementById('ktvReactPanel').style.display = 'none';
    document.getElementById('ktvReactToggle').classList.remove('active');
};

KTV.toggleReactPanel = function() {
    var panel = document.getElementById('ktvReactPanel');
    var btn = document.getElementById('ktvReactToggle');
    var isOpen = panel.style.display !== 'none';
    panel.style.display = isOpen ? 'none' : 'block';
    btn.classList.toggle('active', !isOpen);
    // Close cheer panel
    document.getElementById('ktvCheerPanel').style.display = 'none';
    document.getElementById('ktvCheerToggle').classList.remove('active');
};

KTV.sendCheer = function(type) {
    if (!KTV.ROOM_ID) return;
    KTV.playCheerSound(type);
    fetch('api/send_cheer.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({room_id: KTV.ROOM_ID, type: type, from_nick: KTV.getNick()})
    }).then(function(r){return r.json()}).catch(function(){});
};

KTV.playCheerSound = function(type) {
    try {
        var ctx = KTV.getAudioContext();
        if (!ctx) return;
        var now = ctx.currentTime;
        var masterGain = ctx.createGain();
        masterGain.gain.value = 0.3;
        masterGain.connect(ctx.destination);
        switch (type) {
            case 'applause':
                for (var i = 0; i < 8; i++) {
                    var noise = ctx.createBufferSource();
                    var buf = ctx.createBuffer(1, ctx.sampleRate * 0.15, ctx.sampleRate);
                    var d = buf.getChannelData(0);
                    for (var j = 0; j < d.length; j++) d[j] = (Math.random() * 2 - 1) * Math.pow(1 - j/d.length, 2);
                    noise.buffer = buf;
                    var g = ctx.createGain();
                    g.gain.setValueAtTime(0.15, now + i * 0.08);
                    g.gain.exponentialRampToValueAtTime(0.001, now + i * 0.08 + 0.3);
                    noise.connect(g);
                    g.connect(masterGain);
                    noise.start(now + i * 0.08);
                }
                break;
            case 'cheer':
                var freqs = [523, 659, 784, 1047];
                freqs.forEach(function(f, i) {
                    var osc = ctx.createOscillator();
                    osc.type = 'sine';
                    osc.frequency.value = f;
                    var g = ctx.createGain();
                    g.gain.setValueAtTime(0.15, now + i * 0.12);
                    g.gain.exponentialRampToValueAtTime(0.001, now + i * 0.12 + 0.4);
                    osc.connect(g);
                    g.connect(masterGain);
                    osc.start(now + i * 0.12);
                    osc.stop(now + i * 0.12 + 0.5);
                });
                break;
            case 'airhorn':
                var osc = ctx.createOscillator();
                osc.type = 'sawtooth';
                osc.frequency.setValueAtTime(150, now);
                osc.frequency.exponentialRampToValueAtTime(600, now + 0.3);
                osc.frequency.exponentialRampToValueAtTime(200, now + 0.6);
                var g = ctx.createGain();
                g.gain.setValueAtTime(0.2, now);
                g.gain.exponentialRampToValueAtTime(0.001, now + 0.8);
                osc.connect(g);
                g.connect(masterGain);
                osc.start(now);
                osc.stop(now + 0.8);
                break;
            case 'boo':
                var osc = ctx.createOscillator();
                osc.type = 'sawtooth';
                osc.frequency.value = 100;
                var g = ctx.createGain();
                g.gain.setValueAtTime(0.15, now);
                g.gain.linearRampToValueAtTime(0.2, now + 0.3);
                g.gain.exponentialRampToValueAtTime(0.001, now + 0.8);
                osc.connect(g);
                g.connect(masterGain);
                osc.start(now);
                osc.stop(now + 0.8);
                break;
            case 'laugh':
                for (var i = 0; i < 4; i++) {
                    var osc = ctx.createOscillator();
                    osc.type = 'square';
                    osc.frequency.value = 400 + i * 100;
                    var g = ctx.createGain();
                    g.gain.setValueAtTime(0.08, now + i * 0.15);
                    g.gain.exponentialRampToValueAtTime(0.001, now + i * 0.15 + 0.12);
                    osc.connect(g);
                    g.connect(masterGain);
                    osc.start(now + i * 0.15);
                    osc.stop(now + i * 0.15 + 0.15);
                }
                break;
            case 'drums':
                for (var i = 0; i < 6; i++) {
                    var osc = ctx.createOscillator();
                    osc.type = 'sine';
                    osc.frequency.value = 80 + Math.random() * 40;
                    var g = ctx.createGain();
                    g.gain.setValueAtTime(0.2, now + i * 0.1);
                    g.gain.exponentialRampToValueAtTime(0.001, now + i * 0.1 + 0.08);
                    osc.connect(g);
                    g.connect(masterGain);
                    osc.start(now + i * 0.1);
                    osc.stop(now + i * 0.1 + 0.1);
                }
                break;
        }
    } catch(e) {}
};

KTV.getAudioContext = function() {
    if (!KTV.audioCtx) {
        try {
            KTV.audioCtx = new (window.AudioContext || window.webkitAudioContext)();
        } catch(e) { return null; }
    }
    if (KTV.audioCtx.state === 'suspended') {
        KTV.audioCtx.resume();
    }
    return KTV.audioCtx;
};

KTV.sendReaction = function(type, evt) {
    if (!KTV.ROOM_ID) return;
    var btn = evt && evt.target;
    btn.classList.remove('sent');
    void btn.offsetWidth;
    btn.classList.add('sent');
    fetch('api/send_reaction.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({room_id: KTV.ROOM_ID, type: type, from_nick: KTV.getNick()})
    }).then(function(r){return r.json()}).catch(function(){});
};

KTV.toggleMic = function() {
    var btn = document.getElementById('ktvMicBtn');
    if (KTV.micActive) {
        KTV.stopMic();
        btn.classList.remove('active');
        btn.innerHTML = '<span>🎤</span> Mic';
        document.getElementById('ktvMicStatus').style.display = 'none';
    } else {
        KTV.startMic();
    }
};

KTV.startMic = function() {
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        showToast('Mic not supported on this device', 'error');
        return;
    }
    navigator.mediaDevices.getUserMedia({audio: {
        echoCancellation: true,
        noiseSuppression: true,
        autoGainControl: true
    }}).then(function(stream) {
        KTV.micStream = stream;
        KTV.micActive = true;
        document.getElementById('ktvMicBtn').classList.add('active');
        document.getElementById('ktvMicBtn').innerHTML = '<span>🎤</span> Mic On';
        document.getElementById('ktvMicStatus').style.display = 'flex';
        showToast('Microphone active', 'success');

        // Meter
        var ctx = KTV.getAudioContext();
        if (ctx) {
            var src = ctx.createMediaStreamSource(stream);
            var analyser = ctx.createAnalyser();
            analyser.fftSize = 256;
            src.connect(analyser);
            var data = new Uint8Array(analyser.frequencyBinCount);
            var meter = function() {
                if (!KTV.micActive) return;
                analyser.getByteFrequencyData(data);
                var avg = 0;
                for (var i = 0; i < data.length; i++) avg += data[i];
                avg = avg / data.length / 2.55;
                document.getElementById('ktvMicLevel').style.width = Math.min(100, avg) + '%';
                requestAnimationFrame(meter);
            };
            meter();
        }

        // Start audio relay to host
        KTV.startAudioRelay(stream);
    }).catch(function(err) {
        showToast('Mic access denied: ' + err.message, 'error');
    });
};

KTV.stopMic = function() {
    KTV.micActive = false;
    KTV.stopAudioRelay();
    if (KTV.micStream) {
        KTV.micStream.getTracks().forEach(function(t) { t.stop(); });
        KTV.micStream = null;
    }
};

KTV.startAudioRelay = function(stream) {
    try {
        var options = MediaRecorder.isTypeSupported('audio/webm;codecs=opus')
            ? {mimeType: 'audio/webm;codecs=opus'}
            : {};
        var recorder = new MediaRecorder(stream, options);
        KTV.mediaRecorder = recorder;
        KTV.audioRelaySeq = 0;

        recorder.ondataavailable = function(e) {
            if (e.data && e.data.size > 0) {
                var reader = new FileReader();
                reader.onload = function() {
                    var base64 = reader.result.split(',')[1];
                    KTV.sendAudioChunk(base64);
                };
                reader.readAsDataURL(e.data);
            }
        };

        recorder.start(200); // 200ms chunks
        KTV.audioRelayTimer = setInterval(function() {
            if (recorder.state === 'recording') recorder.requestData();
        }, 1000);
    } catch(e) {
        console.error('Audio relay error:', e);
    }
};

KTV.stopAudioRelay = function() {
    if (KTV.audioRelayTimer) {
        clearInterval(KTV.audioRelayTimer);
        KTV.audioRelayTimer = null;
    }
    if (KTV.mediaRecorder && KTV.mediaRecorder.state !== 'inactive') {
        KTV.mediaRecorder.stop();
        KTV.mediaRecorder = null;
    }
    fetch('api/audio_relay.php?action=stop&room_id=' + KTV.ROOM_ID).catch(function(){});
};

KTV.sendAudioChunk = function(base64) {
    fetch('api/audio_relay.php?action=send&room_id=' + KTV.ROOM_ID, {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({chunk: base64, seq: KTV.audioRelaySeq++})
    }).catch(function(){});
};

KTV.toggleMonitor = function() {
    var btn = document.getElementById('ktvMonitorBtn');
    var panel = document.getElementById('ktvEchoPanel');
    if (KTV.monitorActive) {
        KTV.stopMonitor();
        btn.classList.remove('active');
        btn.innerHTML = '<span>🔊</span> Monitor';
        if (panel) panel.classList.remove('open');
    } else {
        if (panel) panel.classList.add('open');
        KTV.startMonitor();
    }
};

KTV.loadEchoSettings = function() {
    if (!KTV.ROOM_ID) return;
    fetch('api/room_status.php?room_id=' + KTV.ROOM_ID)
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success && data.echo) {
                KTV._echoSettings = data.echo;
                KTV.applyEchoSliders();
            }
        }).catch(function(){});
};

KTV.applyEchoSliders = function() {
    var s = KTV._echoSettings;
    if (!s) return;
    var delaySlider = document.getElementById('ktvEchoDelay');
    var feedbackSlider = document.getElementById('ktvEchoFeedback');
    var mixSlider = document.getElementById('ktvEchoMix');
    var delayVal = document.getElementById('ktvEchoDelayVal');
    var feedbackVal = document.getElementById('ktvEchoFeedbackVal');
    var mixVal = document.getElementById('ktvEchoMixVal');
    if (delaySlider) { delaySlider.value = s.delay; if (delayVal) delayVal.textContent = s.delay.toFixed(2) + 's'; }
    if (feedbackSlider) { feedbackSlider.value = s.feedback; if (feedbackVal) feedbackVal.textContent = Math.round(s.feedback * 100) + '%'; }
    if (mixSlider) { mixSlider.value = s.mix; if (mixVal) mixVal.textContent = Math.round(s.mix * 100) + '%'; }
    var badge = document.getElementById('ktvEchoBadge');
    if (badge) badge.textContent = Math.round(s.mix * 100) + '%';
};

KTV.saveEchoSetting = function(field, value) {
    if (!KTV.ROOM_ID) return;
    var body = {};
    body[field] = value;
    fetch('api/echo_settings.php?room_id=' + KTV.ROOM_ID, {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify(body)
    }).then(function(r) { return r.json(); }).catch(function(){});
};

KTV.startMonitor = function() {
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        showToast('Mic not supported', 'error');
        return;
    }
    var stream = null;
    navigator.mediaDevices.getUserMedia({audio: true}).then(function(s) {
        stream = s;
        var ctx = KTV.getAudioContext();
        if (!ctx) return;
        var src = ctx.createMediaStreamSource(s);
        // Load saved echo settings
        var echo = KTV._echoSettings || { delay: 0.35, feedback: 0.35, mix: 0.35 };
        // Dry path (direct voice)
        var dryGain = ctx.createGain();
        dryGain.gain.value = 1.0 - echo.mix;
        // Echo effect: delay + feedback
        var delay = ctx.createDelay(1.5);
        delay.delayTime.value = echo.delay;
        var feedback = ctx.createGain();
        feedback.gain.value = echo.feedback;
        var wetGain = ctx.createGain();
        wetGain.gain.value = echo.mix;
        // Connect: source -> delay -> feedback -> delay (loop)
        src.connect(dryGain);
        dryGain.connect(ctx.destination);
        src.connect(delay);
        delay.connect(feedback);
        feedback.connect(delay);
        delay.connect(wetGain);
        wetGain.connect(ctx.destination);
        KTV.monitorActive = true;
        KTV._monitorStream = s;
        KTV._monitorSrc = src;
        KTV._monitorDryGain = dryGain;
        KTV._monitorDelay = delay;
        KTV._monitorFeedback = feedback;
        KTV._monitorWetGain = wetGain;
        var btn = document.getElementById('ktvMonitorBtn');
        if (btn) { btn.classList.add('active'); btn.innerHTML = '<span>🔊</span> Echo On'; }
        showToast('Voice monitoring with echo', 'success');
    }).catch(function(err) {
        showToast('Mic access denied: ' + err.message, 'error');
    });
};

KTV.stopMonitor = function() {
    KTV.monitorActive = false;
    if (KTV._monitorStream) {
        KTV._monitorStream.getTracks().forEach(function(t) { t.stop(); });
        KTV._monitorStream = null;
    }
    KTV._monitorDryGain = null;
    KTV._monitorDelay = null;
    KTV._monitorFeedback = null;
    KTV._monitorWetGain = null;
    var btn = document.getElementById('ktvMonitorBtn');
    if (btn) { btn.classList.remove('active'); btn.innerHTML = '<span>🔊</span> Monitor'; }
    var panel = document.getElementById('ktvEchoPanel');
    if (panel) panel.classList.remove('open');
};

KTV.onEchoChange = function(field, value) {
    value = parseFloat(value);
    KTV._echoSettings = KTV._echoSettings || { delay: 0.35, feedback: 0.35, mix: 0.35 };
    KTV._echoSettings[field] = value;
    // Update audio nodes in real-time
    if (field === 'delay' && KTV._monitorDelay) {
        KTV._monitorDelay.delayTime.value = value;
    } else if (field === 'feedback' && KTV._monitorFeedback) {
        KTV._monitorFeedback.gain.value = value;
    } else if (field === 'mix') {
        if (KTV._monitorDryGain) KTV._monitorDryGain.gain.value = 1.0 - value;
        if (KTV._monitorWetGain) KTV._monitorWetGain.gain.value = value;
        // Update badge on Monitor button
        var badge = document.getElementById('ktvEchoBadge');
        if (badge) badge.textContent = Math.round(value * 100) + '%';
    }
    // Update label
    var label = document.getElementById('ktvEcho' + field.charAt(0).toUpperCase() + field.slice(1) + 'Val');
    if (label) {
        label.textContent = field === 'delay' ? value.toFixed(2) + 's' : Math.round(value * 100) + '%';
    }
    // Debounced save to DB
    if (KTV._echoSaveTimer) clearTimeout(KTV._echoSaveTimer);
    KTV._echoSaveTimer = setTimeout(function() {
        KTV.saveEchoSetting(field, value);
    }, 500);
};

KTV.pollRoomStatus = function() {
    if (!KTV.ROOM_ID) return;
    fetch('api/room_status.php?room_id=' + KTV.ROOM_ID)
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (!data.success) return;
            var lockBar = document.getElementById('ktvLockBar');
            if (data.locked) {
                lockBar.classList.remove('hidden');
                KTV._roomLocked = true;
            } else {
                lockBar.classList.add('hidden');
                KTV._roomLocked = false;
            }
        }).catch(function(){});
};

KTV.pollHostReactions = function() {
    if (!KTV.ROOM_ID) return;
    fetch('api/get_cheers.php?room_id=' + KTV.ROOM_ID + '&since=' + KTV.lastCheerId)
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (!data.success || !data.cheers) return;
            data.cheers.forEach(function(c) {
                KTV.lastCheerId = Math.max(KTV.lastCheerId, parseInt(c.id));
                if (c.from_nick && c.from_nick !== (KTV.getNick())) {
                    KTV.playCheerSound(c.type);
                    showToast(c.from_nick + ' sent ' + c.type + '!', 'info');
                }
            });
        }).catch(function(){});
};

// Show score prompt when a track completes
KTV.checkForScorePrompt = function() {
    if (!KTV.ROOM_ID) return;
    fetch('api/get_queue.php?room_id=' + KTV.ROOM_ID + '&completed=1')
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (!data.success || !data.tracks) return;
            data.tracks.forEach(function(t) {
                if (t.status === 'completed' && !KTV.scoredTracks[t.id]) {
                    KTV.lastCompletedTrack = t;
                    KTV.showScorePrompt(t);
                }
            });
        }).catch(function(){});
};

KTV.showScorePrompt = function(track) {
    document.getElementById('ktvScoreSub').textContent = '"' + track.video_title + '"';
    document.querySelectorAll('.ktv-score-star').forEach(function(s) {
        s.classList.remove('active');
    });
    document.getElementById('ktvScoreBtn').disabled = true;
    document.getElementById('ktvScoreOverlay').classList.add('active');
};

KTV.submitScore = function() {
    if (!KTV.selectedScore || !KTV.lastCompletedTrack) return;
    var trackId = KTV.lastCompletedTrack.id;
    var score = KTV.selectedScore * 10;
    fetch('api/submit_score.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({
            track_id: trackId,
            room_id: KTV.ROOM_ID,
            score: score,
            scored_by: KTV.getNick()
        })
    }).then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.success) {
            KTV.scoredTracks[trackId] = true;
            showToast('Score: ' + score + '/100!', 'success');
        } else {
            showToast(data.error || 'Failed to submit score', 'error');
        }
    }).catch(function() {
        showToast('Connection error', 'error');
    });
    document.getElementById('ktvScoreOverlay').classList.remove('active');
};

// Hooks into existing fetchGuestQueue
KTV._origFetchGuestQueue = null;
KTV.hookQueuePolling = function() {
    if (typeof fetchGuestQueue === 'function') {
        KTV._origFetchGuestQueue = fetchGuestQueue;
        var orig = fetchGuestQueue;
        fetchGuestQueue = function() {
            orig();
            KTV.checkForScorePrompt();
        };
    }
};

KTV.init();
KTV.hookQueuePolling();
