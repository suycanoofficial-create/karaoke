<?php
/**
 * KTV LOUNGE - Landing Page
 */
require_once __DIR__ . '/config/app.php';

$meta_title = get_setting('meta_title', 'KTV LOUNGE - Premium Synchronized Karaoke Experience');
$meta_description = get_setting('meta_description', 'An elite, app-free synchronized karaoke lounge.');
$og_title = get_setting('og_title', 'KTV LOUNGE - Premium Karaoke');
$og_description = get_setting('og_description', 'Seamless synchronized karaoke for the modern lounge experience.');
$og_image = get_setting('og_image', '');
$schema_markup = get_setting('schema_markup', '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= sanitize($meta_title) ?></title>
    <meta name="description" content="<?= sanitize($meta_description) ?>">
    <meta property="og:title" content="<?= sanitize($og_title) ?>">
    <meta property="og:description" content="<?= sanitize($og_description) ?>">
    <?php if ($og_image): ?>
    <meta property="og:image" content="<?= sanitize($og_image) ?>">
    <?php endif; ?>
    <meta property="og:type" content="website">
    <?php if ($schema_markup): ?>
    <script type="application/ld+json"><?= $schema_markup ?></script>
    <?php endif; ?>
    <link rel="stylesheet" href="assets/css/style.css?v=<?= ASSETS_VERSION ?>">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🎤</text></svg>">
    <script src="assets/js/app.js?v=<?= ASSETS_VERSION ?>" defer></script>
</head>
<body>
    <header class="site-header" id="siteHeader">
        <div class="container">
            <div class="site-header-inner">
                <a href="<?= BASE_URL ?>" class="site-logo"><?= sanitize(SITE_NAME) ?></a>
                <nav class="site-nav" id="siteNav">
                    <a href="<?= BASE_URL ?>" class="nav-link active">Home</a>
                    <a href="about.php" class="nav-link">About</a>
                    <a href="admin/" class="nav-link nav-link-highlight">Admin</a>
                </nav>
                <button class="hamburger" id="hamburger" aria-label="Toggle navigation" aria-expanded="false">
                    <span class="hamburger-line"></span>
                    <span class="hamburger-line"></span>
                    <span class="hamburger-line"></span>
                </button>
            </div>
        </div>
    </header>

    <main class="landing">
        <div class="landing-center">
            <h1 class="landing-title">
                <span class="text-gold"><?= sanitize(SITE_NAME) ?></span>
            </h1>
            <p class="landing-tagline"><?= sanitize(get_setting('site_tagline', 'Elevate Your Night')) ?></p>

            <div class="landing-actions">
                <!-- Host -->
                <div class="landing-card glass-card">
                    <!-- Create Mode -->
                    <div id="hostCreate">
                        <div class="landing-card-icon">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
                        </div>
                        <h2>Host</h2>
                        <p>Create a room, display the QR, guests join from their phones</p>
                        <button class="btn btn-primary" id="createRoomBtn" onclick="createRoom()" style="width:100%">Create Room</button>
                    </div>
                    <!-- Recover Mode -->
                    <div id="hostRecover" style="display:none">
                        <div class="landing-card-icon">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/></svg>
                        </div>
                        <h2>Recover Room</h2>
                        <p>Restore a room you accidentally closed</p>
                        <input type="text" class="form-input" id="recoverCode" placeholder="ROOM CODE" maxlength="6" autocomplete="off" style="text-transform:uppercase;text-align:center;font-weight:600;letter-spacing:0.2em;font-size:1.1rem;margin-bottom:var(--space-sm)">
                        <button class="btn btn-primary" onclick="recoverRoom()" style="width:100%">Recover</button>
                    </div>
                    <!-- Toggle -->
                    <div style="margin-top:var(--space-md);padding-top:var(--space-md);border-top:1px solid rgba(255,255,255,0.06)">
                        <div style="display:flex;align-items:center;gap:6px;cursor:pointer;font-size:0.75rem;color:rgba(255,255,255,0.4);justify-content:center" onclick="toggleHostMode()">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" id="hostToggleArrow"><polyline points="9 18 15 12 9 6"/></svg>
                            <span id="hostToggleLabel">Recover a room</span>
                        </div>
                    </div>
                </div>

                <div class="landing-divider">
                    <span>or join</span>
                </div>

                <!-- Join -->
                <div class="landing-card glass-card">
                    <div class="landing-card-icon">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    </div>
                    <h2>Join</h2>
                    <p>Enter the room code shown on screen</p>
                    <form id="joinRoomForm" onsubmit="joinRoom(event)" style="width:100%">
                        <input 
                            type="text" 
                            class="form-input" 
                            id="roomCodeInput"
                            placeholder="ROOM CODE"
                            maxlength="6"
                            autocomplete="off"
                            required
                            style="text-transform:uppercase;text-align:center;font-weight:600;letter-spacing:0.2em;font-size:1.1rem"
                        >
                        <button type="submit" class="btn btn-secondary" style="width:100%;margin-top:var(--space-sm)">
                            Enter
                        </button>
                    </form>
                </div>
            </div>

            <p class="landing-footer">No downloads. No accounts. Just karaoke.</p>
        </div>
    </main>

    <div id="toastContainer" class="toast-container"></div>

    <script>
        // Header scroll effect
        (function() {
            var header = document.getElementById('siteHeader');
            var lastScroll = 0;
            if (header) {
                window.addEventListener('scroll', function() {
                    var scrollY = window.scrollY;
                    if (scrollY > 50) {
                        header.classList.add('scrolled');
                    } else {
                        header.classList.remove('scrolled');
                    }
                    lastScroll = scrollY;
                }, { passive: true });
            }
        })();

        // Mobile nav toggle
        (function() {
            var hamburger = document.getElementById('hamburger');
            var nav = document.getElementById('siteNav');
            if (hamburger && nav) {
                hamburger.addEventListener('click', function() {
                    var open = nav.classList.toggle('open');
                    hamburger.classList.toggle('active');
                    hamburger.setAttribute('aria-expanded', open);
                    document.body.style.overflow = open ? 'hidden' : '';
                });
                nav.querySelectorAll('.nav-link').forEach(function(link) {
                    link.addEventListener('click', function() {
                        nav.classList.remove('open');
                        hamburger.classList.remove('active');
                        hamburger.setAttribute('aria-expanded', 'false');
                        document.body.style.overflow = '';
                    });
                });
            }
        })();

        if (typeof showToast !== 'function') {
            function showToast(msg, type) {
                var c = document.getElementById('toastContainer');
                if (!c) return;
                var t = document.createElement('div');
                t.className = 'toast toast-' + (type || 'info');
                t.textContent = msg;
                c.appendChild(t);
                setTimeout(function() { t.style.opacity = '0'; setTimeout(function() { t.remove(); }, 300); }, 3000);
            }
        }
        async function createRoom() {
            const btn = document.getElementById('createRoomBtn');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner spinner-sm"></span> Creating...';
            
            try {
                const res = await fetch('api/create_room.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' }
                });
                const data = await res.json();
                
                if (data.success) {
                    window.location.href = 'host.php?code=' + data.room_code + '&token=' + data.token;
                } else {
                    showToast(data.error || 'Failed to create room', 'error');
                    btn.disabled = false;
                    btn.innerHTML = 'Create Room';
                }
            } catch (err) {
                showToast('Connection error. Please try again.', 'error');
                btn.disabled = false;
                btn.innerHTML = 'Create Room';
            }
        }

        function joinRoom(e) {
            e.preventDefault();
            const code = document.getElementById('roomCodeInput').value.trim().toUpperCase();
            if (code.length >= 4) {
                window.location.href = 'guest.php?code=' + code;
            }
        }

        document.getElementById('roomCodeInput').addEventListener('input', function(e) {
            e.target.value = e.target.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
        });

        document.getElementById('recoverCode').addEventListener('input', function(e) {
            e.target.value = e.target.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
        });

        function toggleHostMode() {
            var create = document.getElementById('hostCreate');
            var recover = document.getElementById('hostRecover');
            var arrow = document.getElementById('hostToggleArrow');
            var label = document.getElementById('hostToggleLabel');
            var isRecover = recover.style.display === 'block';
            create.style.display = isRecover ? 'block' : 'none';
            recover.style.display = isRecover ? 'none' : 'block';
            arrow.style.transform = isRecover ? '' : 'rotate(90deg)';
            arrow.style.transition = 'transform 0.2s';
            label.textContent = isRecover ? 'Recover a room' : 'Create a room';
        }

        async function recoverRoom() {
            var code = document.getElementById('recoverCode').value.trim();
            if (!code) { showToast('Enter room code', 'error'); return; }

            try {
                var res = await fetch('api/recover_room.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ room_code: code })
                });
                var data = await res.json();
                if (data.success) {
                    showToast(data.message, 'success');
                    if (data.redirect) {
                        setTimeout(function() { window.location.href = data.redirect; }, 800);
                    }
                } else {
                    showToast(data.error || 'Failed to recover room', 'error');
                }
            } catch(e) {
                showToast('Connection error', 'error');
            }
        }
    </script>
</body>
</html>
