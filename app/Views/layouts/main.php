<?php /* Base layout for Hidroganik Alfa */ ?>
<!DOCTYPE html>
<html lang="id" data-theme="light">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1.0" />
    <title><?= esc($title ?? 'Hidroganik Alfa Monitoring') ?></title>
    <link rel="manifest" href="<?= base_url('manifest.webmanifest') ?>">
    <meta name="theme-color" content="#16a34a">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="apple-touch-icon" href="<?= base_url('assets/logo.png') ?>" />
    <link rel="icon" type="image/png" href="<?= base_url('assets/logo.png') ?>" />
    <link rel="apple-touch-icon" href="<?= base_url('assets/logo.png') ?>" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.12.10/dist/full.min.css" rel="stylesheet" type="text/css" />
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { theme: { extend: { fontFamily: { inter: ['Inter','sans-serif'] } } } };</script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://www.gstatic.com/firebasejs/9.6.10/firebase-app-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/9.6.10/firebase-database-compat.js"></script>
    <!-- MQTT over WebSocket client -->
    <script src="https://unpkg.com/mqtt/dist/mqtt.min.js"></script>
    <link rel="stylesheet" href="<?= base_url('assets/styles.css') ?>">
    <?= $this->renderSection('head_extra') ?>
</head>
<body class="bg-base-200 font-inter min-h-screen">
    <div class="navbar bg-base-100 shadow-lg sticky top-0 z-50">
        <div class="navbar-start">
            <div class="dropdown">
                <div tabindex="0" role="button" class="btn btn-ghost lg:hidden">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                </div>
                <ul tabindex="0" class="menu menu-sm dropdown-content bg-base-100 rounded-box z-[1] mt-3 w-52 p-2 shadow">
                    <li><a href="<?= base_url('/') ?>" class="<?= (url_is('/') ? 'font-semibold underline decoration-2 decoration-green-500 bg-green-100' : '') ?>">Dashboard</a></li>
                    <li><a href="<?= base_url('log') ?>" class="<?= (url_is('log') ? 'font-semibold underline decoration-2 decoration-green-500 bg-green-100' : '') ?>">Log Data</a></li>
                    <li><a href="<?= base_url('kalibrasi') ?>" class="<?= (url_is('kalibrasi') ? 'font-semibold underline decoration-2 decoration-green-500 bg-green-100' : '') ?>">Kalibrasi</a></li>
                    <?php if (session()->get('role') === 'admin'): ?>
                        <li><a href="<?= base_url('users/new') ?>" class="<?= (url_is('users*') ? 'font-semibold underline decoration-2 decoration-green-500 bg-green-100' : '') ?>">Tambah User</a></li>
                    <?php endif; ?>
                </ul>
            </div>
            <div class="flex items-center space-x-3 ml-2">
                <div class="h-10 flex items-center justify-center">
                    <img id="company-logo" src="<?= base_url('assets/logo.png') ?>" alt="Logo Hidroganik Alfa" class="h-10 w-auto object-contain max-w-[180px]" onerror="this.style.display='none'; document.getElementById('logo-fallback').style.display='inline-block'">
                    <span id="logo-fallback" class="text-primary text-xl hidden">🌱</span>
                </div>
                <div class="hidden sm:block">
                    <div class="text-lg font-bold text-success">Hidroganik Alfa</div>
                    <div class="text-xs text-base-content/60">Smart Hydroponic System</div>
                </div>
            </div>
        </div>
        <div class="navbar-center hidden lg:flex">
            <ul class="menu menu-horizontal px-1 space-x-2">
                <li><a href="<?= base_url('/') ?>" class="btn btn-sm <?= (url_is('/') ? 'underline decoration-2 decoration-green-500 bg-green-100' : 'btn-ghost') ?>">Dashboard</a></li>
                <li><a href="<?= base_url('log') ?>" class="btn btn-sm <?= (url_is('log') ? 'underline decoration-2 decoration-green-500 bg-green-100' : 'btn-ghost') ?>">Log Data</a></li>
                <li><a href="<?= base_url('kalibrasi') ?>" class="btn btn-sm <?= (url_is('kalibrasi') ? 'underline decoration-2 decoration-green-500 bg-green-100' : 'btn-ghost') ?>">Kalibrasi</a></li>
                 <?php if (session()->get('role') === 'admin'): ?>
                    <li><a href="<?= base_url('users/new') ?>" class="btn btn-sm <?= (url_is('users*') ? 'underline decoration-2 decoration-green-500 bg-green-100' : 'btn-ghost') ?>">Tambah User</a></li>
                <?php endif; ?>
            </ul>
        </div>
        <div class="navbar-end">
            <div class="lg:hidden flex flex-col items-end mr-2">
                <div class="text-sm font-bold text-success" id="real-time-clock-mobile">00:00:00</div>
                <div class="text-xs text-base-content/60" id="real-time-date-mobile">Loading...</div>
            </div>
            <div class="hidden lg:flex items-center space-x-3 mr-4">
                <div class="text-right">
                    <div class="text-lg font-bold text-success" id="real-time-clock">00:00:00</div>
                    <div class="text-xs text-base-content/60" id="real-time-date">Loading...</div>
                </div>
            </div>

            <?php if(session()->get('isLoggedIn')): ?>
            <div class="dropdown dropdown-end">
                <div tabindex="0" role="button" class="btn btn-ghost btn-circle avatar">
                    <div class="w-10 rounded-full bg-success/10 flex items-center justify-center">
                       <span class="text-xl font-bold text-success"><?= strtoupper(substr(session('displayName'), 0, 1)) ?></span>
                    </div>
                </div>
                <ul tabindex="0" class="menu menu-sm dropdown-content bg-base-100 rounded-box z-[1] mt-3 w-52 p-2 shadow">
                    <li class="menu-title"><span>Halo, <?= session('displayName') ?></span></li>
                    <li><a href="<?= base_url('logout') ?>">🚪 Logout</a></li>
                </ul>
            </div>
            <?php endif; ?>

        </div>
    </div>
    <div class="container mx-auto px-4 py-8 max-w-7xl">
        <?= $this->renderSection('content') ?>
    </div>
    <script src="<?= base_url('assets/app.js') ?>" defer></script>
    <div id="pwa-install" style="position:fixed;right:16px;bottom:16px;z-index:9999;display:none">
        <button id="btnInstall" class="btn btn-success shadow-lg">Install App</button>
    </div>
    <script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', function() {
            navigator.serviceWorker.register('<?= base_url('service-worker.js') ?>')
                .catch(function(err){ console.warn('SW registration failed:', err); });
        });
    }

    // Handle Add to Home Screen (A2HS)
    let deferredPrompt = null;
    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        deferredPrompt = e;
        const btn = document.getElementById('btnInstall');
        const container = document.getElementById('pwa-install');
        if (btn && container) container.style.display = 'block';
    });
    document.getElementById('btnInstall')?.addEventListener('click', async () => {
        if (!deferredPrompt) return;
        deferredPrompt.prompt();
        const { outcome } = await deferredPrompt.userChoice;
        if (outcome === 'accepted') {
            console.log('PWA install accepted');
        }
        deferredPrompt = null;
        const container = document.getElementById('pwa-install');
        if (container) container.style.display = 'none';
    });
    </script>
    <?= $this->renderSection('body_end') ?>
</body>
</html>