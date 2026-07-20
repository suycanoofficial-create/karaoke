<?php
require_once __DIR__ . '/config/app.php';

$meta_title = 'About - ' . sanitize(SITE_NAME);
$meta_description = 'Learn more about the premium synchronized karaoke experience.';
$og_title = $meta_title;
$og_description = $meta_description;
$og_image = get_setting('og_image', '');
$schema_markup = get_setting('schema_markup', '');

$total_rooms = 0;
try {
    $stmt = db()->query("SELECT COUNT(*) FROM rooms");
    $total_rooms = (int) $stmt->fetchColumn();
} catch (Exception $e) {
    $total_rooms = 0;
}
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
                    <a href="<?= BASE_URL ?>" class="nav-link">Home</a>
                    <a href="about.php" class="nav-link active">About</a>
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

    <main>
        <!-- Hero -->
        <section class="about-hero">
            <div class="about-hero-bg">
                <div class="about-hero-blob about-hero-blob-1"></div>
                <div class="about-hero-blob about-hero-blob-2"></div>
                <div class="about-hero-blob about-hero-blob-3"></div>
            </div>
            <div class="container">
                <div class="about-hero-content reveal">
                    <span class="about-hero-badge">Welcome to the future of karaoke</span>
                    <h1 class="about-hero-title">
                        <span class="text-gold">About</span> <?= sanitize(SITE_NAME) ?>
                    </h1>
                    <p class="about-hero-subtitle">The modern karaoke experience — no apps, no accounts, just sing.</p>
                    <div class="about-hero-actions">
                        <a href="<?= BASE_URL ?>" class="btn btn-primary">Create a Room</a>
                        <a href="#features" class="btn btn-ghost">Explore Features</a>
                    </div>
                </div>
            </div>
            <div class="about-hero-wave">
                <svg viewBox="0 0 1440 120" fill="none"><path d="M0 60c240 0 360 60 600 60s360-60 600-60 360 60 600 60V0H0v60z" fill="var(--midnight)"/></svg>
            </div>
        </section>

        <!-- Stats -->
        <section class="about-stats">
            <div class="container">
                <div class="about-stats-grid">
                    <div class="about-stat reveal">
                        <span class="about-stat-number">Unlimited</span>
                        <span class="about-stat-label">Songs Available</span>
                    </div>
                    <div class="about-stat reveal">
                        <span class="about-stat-number" data-count="<?= $total_rooms ?>">0</span>
                        <span class="about-stat-label">Rooms Created</span>
                    </div>
                    <div class="about-stat reveal">
                        <span class="about-stat-number" data-count="99">0</span>
                        <span class="about-stat-label">Uptime %</span>
                    </div>
                    <div class="about-stat reveal">
                        <span class="about-stat-number" data-count="0">0</span>
                        <span class="about-stat-label">Apps to Download</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- How It Works -->
        <section class="about-steps" id="features">
            <div class="container">
                <div class="section-label reveal">How It Works</div>
                <h2 class="section-title reveal">Three steps to start <span class="text-gold">singing</span></h2>
                <div class="about-steps-grid">
                    <div class="about-step glass-card reveal">
                        <div class="about-step-number">1</div>
                        <div class="about-step-icon">
                            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
                        </div>
                        <h3 class="about-step-title">Host Creates a Room</h3>
                        <p class="about-step-text">Click "Create Room" and a 6-digit code is generated instantly. The host view opens on the big screen.</p>
                    </div>
                    <div class="about-step-connector">
                        <svg width="40" height="24" viewBox="0 0 40 24" fill="none"><path d="M2 12h34M30 4l8 8-8 8" stroke="var(--gold)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" opacity="0.4"/></svg>
                    </div>
                    <div class="about-step glass-card reveal">
                        <div class="about-step-number">2</div>
                        <div class="about-step-icon">
                            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                        </div>
                        <h3 class="about-step-title">Guests Join</h3>
                        <p class="about-step-text">Players enter the <strong class="text-gold">room code</strong> on their phones — or scan the <strong class="text-gold">QR code</strong> displayed on the big screen. No sign-up, no download, you're in instantly.</p>
                        <div class="about-step-demo">
                            <div class="about-step-demo-qr">
                                <svg viewBox="0 0 100 100" fill="none">
                                    <rect x="10" y="10" width="18" height="18" rx="2" fill="var(--gold)" opacity="0.9"/>
                                    <rect x="36" y="10" width="8" height="8" rx="1" fill="var(--gold)" opacity="0.9"/>
                                    <rect x="52" y="10" width="8" height="8" rx="1" fill="var(--gold)" opacity="0.9"/>
                                    <rect x="68" y="10" width="22" height="18" rx="2" fill="var(--gold)" opacity="0.9"/>
                                    <rect x="10" y="36" width="8" height="8" rx="1" fill="var(--gold)" opacity="0.9"/>
                                    <rect x="26" y="36" width="8" height="8" rx="1" fill="var(--gold)" opacity="0.9"/>
                                    <rect x="42" y="36" width="8" height="8" rx="1" fill="var(--gold)" opacity="0.9"/>
                                    <rect x="58" y="36" width="8" height="8" rx="1" fill="var(--gold)" opacity="0.9"/>
                                    <rect x="74" y="36" width="8" height="8" rx="1" fill="var(--gold)" opacity="0.9"/>
                                    <rect x="90" y="36" width="8" height="8" rx="1" fill="var(--gold)" opacity="0.9"/>
                                    <rect x="10" y="52" width="8" height="8" rx="1" fill="var(--gold)" opacity="0.9"/>
                                    <rect x="36" y="52" width="18" height="18" rx="2" fill="var(--gold)" opacity="0.9"/>
                                    <rect x="68" y="52" width="8" height="8" rx="1" fill="var(--gold)" opacity="0.9"/>
                                    <rect x="84" y="52" width="8" height="8" rx="1" fill="var(--gold)" opacity="0.9"/>
                                    <rect x="10" y="68" width="8" height="8" rx="1" fill="var(--gold)" opacity="0.9"/>
                                    <rect x="26" y="68" width="8" height="8" rx="1" fill="var(--gold)" opacity="0.9"/>
                                    <rect x="42" y="68" width="8" height="8" rx="1" fill="var(--gold)" opacity="0.9"/>
                                    <rect x="58" y="68" width="8" height="8" rx="1" fill="var(--gold)" opacity="0.9"/>
                                    <rect x="82" y="68" width="8" height="8" rx="1" fill="var(--gold)" opacity="0.9"/>
                                    <rect x="10" y="84" width="22" height="8" rx="1" fill="var(--gold)" opacity="0.9"/>
                                    <rect x="42" y="84" width="8" height="8" rx="1" fill="var(--gold)" opacity="0.9"/>
                                    <rect x="68" y="84" width="8" height="8" rx="1" fill="var(--gold)" opacity="0.9"/>
                                    <rect x="84" y="84" width="8" height="8" rx="1" fill="var(--gold)" opacity="0.9"/>
                                </svg>
                            </div>
                            <div class="about-step-demo-code">
                                <span class="about-step-demo-label">Try it</span>
                                <span class="about-step-demo-value">KT-2415</span>
                            </div>
                        </div>
                    </div>
                    <div class="about-step-connector">
                        <svg width="40" height="24" viewBox="0 0 40 24" fill="none"><path d="M2 12h34M30 4l8 8-8 8" stroke="var(--gold)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" opacity="0.4"/></svg>
                    </div>
                    <div class="about-step glass-card reveal">
                        <div class="about-step-number">3</div>
                        <div class="about-step-icon">
                            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></svg>
                        </div>
                        <h3 class="about-step-title">Search & Sing</h3>
                        <p class="about-step-text">Guests search for songs from their devices, add to the queue. The host plays, lyrics sync, and the party starts.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Features -->
        <section class="about-features">
            <div class="container">
                <div class="section-label reveal">Why Choose Us</div>
                <h2 class="section-title reveal">Built for the <span class="text-gold">stage</span></h2>
                <div class="about-grid">
                    <div class="about-card glass-card reveal" data-delay="0">
                        <div class="about-card-image">
                            <svg viewBox="0 0 400 220" fill="none">
                                <rect width="400" height="220" rx="12" fill="url(#g1)"/>
                                <circle cx="200" cy="80" r="30" fill="rgba(212,175,55,0.15)" stroke="rgba(212,175,55,0.4)" stroke-width="1"/>
                                <circle cx="200" cy="80" r="12" fill="var(--gold)" opacity="0.6"/>
                                <rect x="100" y="130" width="200" height="4" rx="2" fill="rgba(255,255,255,0.1)"/>
                                <rect x="120" y="140" width="160" height="4" rx="2" fill="rgba(255,255,255,0.06)"/>
                                <rect x="140" y="150" width="120" height="4" rx="2" fill="rgba(255,255,255,0.04)"/>
                                <circle cx="130" cy="80" r="4" fill="rgba(255,255,255,0.15)"/>
                                <circle cx="270" cy="80" r="4" fill="rgba(255,255,255,0.15)"/>
                                <defs><linearGradient id="g1" x1="0" y1="0" x2="400" y2="220"><stop stop-color="var(--obsidian)"/><stop offset="1" stop-color="var(--midnight)"/></linearGradient></defs>
                            </svg>
                        </div>
                        <div class="about-icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><path d="M2 12h20"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                        </div>
                        <h2 class="about-card-title">Sync for Everyone</h2>
                        <p class="about-card-text">Guests join from their phones instantly — no app download, no account required. The host controls the queue, lyrics sync in real time.</p>
                    </div>
                    <div class="about-card glass-card reveal" data-delay="150">
                        <div class="about-card-image">
                            <svg viewBox="0 0 400 220" fill="none">
                                <rect width="400" height="220" rx="12" fill="url(#g2)"/>
                                <rect x="60" y="50" width="280" height="120" rx="8" fill="rgba(255,255,255,0.03)" stroke="rgba(212,175,55,0.15)" stroke-width="1"/>
                                <rect x="80" y="60" width="240" height="3" rx="1.5" fill="rgba(255,255,255,0.1)"/>
                                <circle cx="85" cy="61.5" r="4" fill="var(--danger)"/>
                                <circle cx="97" cy="61.5" r="4" fill="var(--warning)"/>
                                <circle cx="109" cy="61.5" r="4" fill="var(--success)"/>
                                <rect x="80" y="75" width="180" height="6" rx="3" fill="rgba(255,255,255,0.06)"/>
                                <rect x="80" y="89" width="140" height="6" rx="3" fill="rgba(255,255,255,0.04)"/>
                                <rect x="80" y="103" width="160" height="6" rx="3" fill="rgba(255,255,255,0.06)"/>
                                <rect x="80" y="140" width="240" height="20" rx="4" fill="var(--gold)" opacity="0.15"/>
                                <rect x="80" y="140" width="120" height="20" rx="4" fill="var(--gold)" opacity="0.3"/>
                                <defs><linearGradient id="g2" x1="0" y1="0" x2="400" y2="220"><stop stop-color="var(--obsidian)"/><stop offset="1" stop-color="var(--midnight)"/></linearGradient></defs>
                            </svg>
                        </div>
                        <div class="about-icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
                        </div>
                        <h2 class="about-card-title">Big Screen Ready</h2>
                        <p class="about-card-text">Display on any TV or projector via HDMI. The host view is optimized for large screens with queue management and skip controls.</p>
                    </div>
                    <div class="about-card glass-card reveal" data-delay="300">
                        <div class="about-card-image">
                            <svg viewBox="0 0 400 220" fill="none">
                                <rect width="400" height="220" rx="12" fill="url(#g3)"/>
                                <rect x="80" y="60" width="240" height="100" rx="8" fill="rgba(255,255,255,0.03)" stroke="rgba(212,175,55,0.12)" stroke-width="1"/>
                                <rect x="100" y="75" width="200" height="10" rx="5" fill="var(--gold)" opacity="0.15"/>
                                <rect x="100" y="92" width="140" height="6" rx="3" fill="rgba(255,255,255,0.06)"/>
                                <rect x="100" y="105" width="160" height="6" rx="3" fill="rgba(255,255,255,0.04)"/>
                                <rect x="100" y="130" width="200" height="4" rx="2" fill="rgba(212,175,55,0.2)"/>
                                <circle cx="150" cy="132" r="5" fill="var(--gold)"/>
                                <rect x="100" y="140" width="80" height="3" rx="1.5" fill="rgba(255,255,255,0.08)"/>
                                <defs><linearGradient id="g3" x1="0" y1="0" x2="400" y2="220"><stop stop-color="var(--obsidian)"/><stop offset="1" stop-color="var(--midnight)"/></linearGradient></defs>
                            </svg>
                        </div>
                        <div class="about-icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></svg>
                        </div>
                        <h2 class="about-card-title">YouTube Powered</h2>
                        <p class="about-card-text">Search and queue millions of karaoke tracks directly from YouTube. No uploading, no library management — just search and sing.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA -->
        <section class="about-cta reveal">
            <div class="container">
                <div class="about-cta-card glass-card">
                    <h2 class="about-cta-title">Ready to <span class="text-gold">rock</span> the mic?</h2>
                    <p class="about-cta-text">Create a room in seconds and start singing with friends.</p>
                    <a href="<?= BASE_URL ?>" class="btn btn-primary btn-lg">Get Started Now</a>
                </div>
            </div>
        </section>
    </main>

    <div id="toastContainer" class="toast-container"></div>

    <script>
        (function() {
            var header = document.getElementById('siteHeader');
            if (header) {
                window.addEventListener('scroll', function() {
                    header.classList.toggle('scrolled', window.scrollY > 50);
                }, { passive: true });
            }
        })();

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

        // Scroll reveal
        (function() {
            var observer = new IntersectionObserver(function(entries) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting) {
                        var delay = parseInt(entry.target.getAttribute('data-delay')) || 0;
                        setTimeout(function() {
                            entry.target.classList.add('revealed');
                        }, delay);
                        observer.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.15 });

            document.querySelectorAll('.reveal').forEach(function(el) {
                observer.observe(el);
            });
        })();

        // Counter animation
        (function() {
            var counterObserver = new IntersectionObserver(function(entries) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting) {
                        var el = entry.target;
                        var target = parseInt(el.getAttribute('data-count'));
                        var current = 0;
                        var steps = target > 10000 ? 80 : 60;
                        var increment = target / steps;
                        var timer = setInterval(function() {
                            current += increment;
                            if (current >= target) {
                                current = target;
                                clearInterval(timer);
                            }
                            el.textContent = Math.floor(current).toLocaleString();
                        }, 25);
                        counterObserver.unobserve(el);
                    }
                });
            }, { threshold: 0.5 });

            document.querySelectorAll('.about-stat-number').forEach(function(el) {
                counterObserver.observe(el);
            });
        })();

        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(function(anchor) {
            anchor.addEventListener('click', function(e) {
                var target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    e.preventDefault();
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        });
    </script>
</body>
</html>
