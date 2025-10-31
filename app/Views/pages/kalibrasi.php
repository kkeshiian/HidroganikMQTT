<!DOCTYPE html>
<html lang="id" data-theme="light">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Hidroganik Alfa Monitoring</title>
    <link rel="manifest" href="<?= base_url('manifest.webmanifest') ?>">
    <meta name="theme-color" content="#16a34a">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="apple-touch-icon" href="<?= base_url('assets/logo.png') ?>" />
    <link rel="icon" type="image/png" href="<?= base_url('assets/logo.png') ?>" />
    <link rel="apple-touch-icon" href="<?= base_url('assets/logo.png') ?>" />

        <!-- Google Fonts - Inter -->
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
        <link
            href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
            rel="stylesheet"
        />

        <!-- Tailwind CSS + DaisyUI -->
        <link
            href="https://cdn.jsdelivr.net/npm/daisyui@4.12.10/dist/full.min.css"
            rel="stylesheet"
            type="text/css"
        />
        <script src="https://cdn.tailwindcss.com"></script>
        <script>
            tailwind.config = {
                theme: {
                    extend: {
                        fontFamily: {
                            inter: ["Inter", "sans-serif"],
                        },
                    },
                },
            };
        </script>

        <!-- Firebase (Compat v9 seperti index.html) -->
        <script src="https://www.gstatic.com/firebasejs/9.6.10/firebase-app-compat.js"></script>
        <script src="https://www.gstatic.com/firebasejs/9.6.10/firebase-database-compat.js"></script>
    <!-- MQTT over WebSocket for direct device commands -->
    <script src="https://unpkg.com/mqtt/dist/mqtt.min.js"></script>

        <!-- Toastify -->
        <link
            rel="stylesheet"
            type="text/css"
            href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css"
        />
        <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>

        <style>
            body {
                font-family: "Inter", sans-serif;
            }
            html {
                scroll-behavior: smooth;
            }
            /* Toastify alignment tweaks */
            .toastify {
                display: flex;
                align-items: center;
                padding-right: 8px;
            }
            .toastify .toast-close {
                position: static;
                margin-left: 12px;
                opacity: 0.8;
            }
            .toastify .toast-close:hover {
                opacity: 1;
            }

            /* Custom animations */
            @keyframes pulse-glow {
                0%,
                100% {
                    box-shadow: 0 0 5px rgba(34, 197, 94, 0.5);
                }
                50% {
                    box-shadow: 0 0 20px rgba(34, 197, 94, 0.8);
                }
            }

            .status-active {
                animation: pulse-glow 2s infinite;
            }

            .sensor-card {
                transition: all 0.3s ease;
                background: linear-gradient(145deg, #ffffff 0%, #f8fafc 100%);
            }

            .sensor-card:hover {
                transform: translateY(-2px);
                box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            }

            .calibration-section {
                background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
                border-left: 4px solid #3b82f6;
            }

            .log-container {
                background: linear-gradient(145deg, #ffffff 0%, #f1f5f9 100%);
                color: #334155;
            }

            .metric-value {
                text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
            }

            .btn-primary-custom {
                background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
                border: none;
                transition: all 0.3s ease;
            }

            .btn-primary-custom:hover {
                transform: translateY(-1px);
                box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
            }
        </style>
    </head>
    <body class="bg-base-200 font-inter min-h-screen">
        <!-- DaisyUI Navbar (Standardized) -->
        <div class="navbar bg-base-100 shadow-lg sticky top-0 z-50">
            <div class="navbar-start">
                <div class="dropdown">
                    <div tabindex="0" role="button" class="btn btn-ghost lg:hidden">
                        <svg
                            class="w-5 h-5"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M4 6h16M4 12h16M4 18h16"
                            ></path>
                        </svg>
                    </div>
                    <ul
                        tabindex="0"
                        class="menu menu-sm dropdown-content bg-base-100 rounded-box z-[1] mt-3 w-52 p-2 shadow"
                    >
                                    <li><a href="/">Dashboard</a></li>
                                    <li><a href="/log">Log Data</a></li>
                        <li>
                            <a
                                            href="/kalibrasi"
                                class="font-semibold underline decoration-2 decoration-green-500 bg-green-100"
                                >Kalibrasi</a
                            >
                        </li>
                    </ul>
                </div>
                <div class="flex items-center space-x-3 ml-2">
                    <div class="h-10 flex items-center justify-center">
                        <img id="company-logo" src="<?= base_url('assets/logo.png') ?>" alt="Logo Hidroganik Alfa" class="h-10 w-auto object-contain max-w-[180px]" onerror="this.style.display='none'; document.getElementById('logo-fallback').style.display='inline-block'">
                        <span id="logo-fallback" class="text-primary text-xl hidden">🌱</span>
                    </div>
                    <div class="hidden sm:block">
                        <div class="text-lg font-bold text-success">Hidroganik Alfa</div>
                        <div class="text-xs text-base-content/60">
                            Smart Hydroponic System
                        </div>
                    </div>
                </div>
            </div>
            <div class="navbar-center hidden lg:flex">
                <ul class="menu menu-horizontal px-1 space-x-2">
                    <li>
                                    <a href="/" class="btn btn-ghost btn-sm">Dashboard</a>
                    </li>
                                <li><a href="/log" class="btn btn-ghost btn-sm">Log Data</a></li>
                    <li>
                        <a
                                        href="/kalibrasi"
                            class="btn btn-sm underline decoration-2 decoration-green-500 bg-green-100"
                            >Kalibrasi</a
                        >
                    </li>
                </ul>
            </div>
            <div class="navbar-end">
                <div class="lg:hidden flex flex-col items-end mr-2">
                    <div
                        class="text-sm font-bold text-success"
                        id="real-time-clock-mobile"
                    >
                        00:00:00
                    </div>
                    <div class="text-xs text-base-content/60" id="real-time-date-mobile">
                        Loading...
                    </div>
                </div>
                <div class="hidden lg:flex items-center space-x-3 mr-4">
                    <div class="text-right">
                        <div class="text-lg font-bold text-success" id="real-time-clock">
                            00:00:00
                        </div>
                        <div class="text-xs text-base-content/60" id="real-time-date">
                            Loading...
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main -->
        <div class="container mx-auto px-4 py-8 max-w-7xl">
            <!-- Instructions Card -->
            <div
                class="card bg-gradient-to-r from-blue-50 to-indigo-50 shadow-lg mb-8 border border-blue-200"
            >
                <div class="card-body">
                    <div class="flex items-start space-x-4">
                        <div>
                            <h3 class="text-lg font-semibold text-blue-800 mb-2">
                                Panduan Kalibrasi
                            </h3>
                            <div class="space-y-2 text-sm text-blue-700">
                                <div class="flex items-center space-x-2">
                                    <span
                                        class="w-6 h-6 bg-blue-500 text-white rounded-full flex items-center justify-center text-xs font-bold"
                                        >1</span
                                    >
                                    <span
                                        ><strong>pH:</strong> Mulai Kalibrasi → Celup ke larutan pH
                                        4.01 → Record Asam → Celup ke pH 6.86 → Record Netral</span
                                    >
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Unit Selector -->
            <div class="grid grid-cols-1 xl:grid-cols-2 gap-8">
                <!-- Unit A -->
                <div
                    class="sensor-card bg-base-100 rounded-2xl shadow-xl p-6 border border-gray-100"
                >
                    <div class="flex items-center justify-between mb-6">
                        <div class="flex items-center space-x-3">
                            <div
                                class="w-12 h-12 bg-gradient-to-br from-green-400 to-blue-500 rounded-xl flex items-center justify-center"
                            >
                                <span class="text-white font-bold text-lg">A</span>
                            </div>
                            <div>
                                <h2 class="text-xl font-bold text-gray-800">Sensor Kebun A</h2>
                                <p class="text-xs text-gray-500">Firebase: kebun-a</p>
                            </div>
                        </div>
                    </div>

                    <!-- Live Sensor Data -->
                    <div class="grid grid-cols-4 gap-4 mb-6">
                        <div
                            class="bg-gradient-to-br from-green-50 to-green-100 rounded-xl p-4 text-center border border-green-200"
                        >
                            <div
                                class="w-8 h-8 bg-green-500 rounded-full mx-auto mb-2 flex items-center justify-center"
                            >
                                <span class="text-white text-sm">🧪</span>
                            </div>
                            <p class="text-xs text-green-600 font-medium mb-1">pH</p>
                            <p
                                id="a-ph"
                                class="text-xl font-bold text-green-700 metric-value"
                            >
                                --
                            </p>
                        </div>
                        <div
                            class="bg-gradient-to-br from-purple-50 to-purple-100 rounded-xl p-4 text-center border border-purple-200"
                        >
                            <div
                                class="w-8 h-8 bg-purple-500 rounded-full mx-auto mb-2 flex items-center justify-center"
                            >
                                <span class="text-white text-sm">💧</span>
                            </div>
                            <p class="text-xs text-purple-600 font-medium mb-1">TDS</p>
                            <p
                                id="a-tds"
                                class="text-xl font-bold text-purple-700 metric-value"
                            >
                                --
                            </p>
                        </div>
                        <div
                            class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl p-4 text-center border border-blue-200"
                        >
                            <div
                                class="w-8 h-8 bg-blue-500 rounded-full mx-auto mb-2 flex items-center justify-center"
                            >
                                <span class="text-white text-sm">🌡️</span>
                            </div>
                            <p class="text-xs text-blue-600 font-medium mb-1">Suhu Air</p>
                            <p
                                id="a-suhu"
                                class="text-xl font-bold text-blue-700 metric-value"
                            >
                                --
                            </p>
                        </div>
                        <div
                            class="bg-gradient-to-br from-violet-50 to-violet-100 rounded-xl p-4 text-center border border-violet-200"
                        >
                            <div
                                class="w-8 h-8 bg-violet-500 rounded-full mx-auto mb-2 flex items-center justify-center"
                            >
                                <span class="text-white text-sm">🔬</span>
                            </div>
                            <p class="text-xs text-violet-600 font-medium mb-1">TDS Mentah</p>
                            <p
                                id="a-tds-raw"
                                class="text-xl font-bold text-violet-700 metric-value"
                            >
                                --
                            </p>
                        </div>
                    </div>

                    <!-- Calibration Values -->
                    <div
                        class="bg-gradient-to-r from-gray-50 to-gray-100 rounded-xl p-4 mb-6 border border-gray-200"
                    >
                        <h4 class="font-semibold text-gray-800 mb-3 flex items-center">
                            <span
                                class="w-6 h-6 bg-gray-600 rounded-full flex items-center justify-center mr-2"
                            >
                                <span class="text-white text-xs">🔧</span>
                            </span>
                            Nilai Kalibrasi
                        </h4>
                        <div class="grid grid-cols-3 gap-3 items-stretch">
                            <div
                                class="bg-white rounded-lg p-3 shadow-sm flex flex-col justify-center text-center min-h-[78px]"
                            >
                                <p class="text-xs text-gray-500 mb-1">pH Asam</p>
                                <p
                                    id="a-cal-asam"
                                    class="text-sm font-mono font-bold text-green-600 tracking-tight"
                                >
                                    --
                                </p>
                            </div>
                            <div
                                class="bg-white rounded-lg p-3 shadow-sm flex flex-col justify-center text-center min-h-[78px]"
                            >
                                <p class="text-xs text-gray-500 mb-1">pH Netral</p>
                                <p
                                    id="a-cal-netral"
                                    class="text-sm font-mono font-bold text-blue-600 tracking-tight"
                                >
                                    --
                                </p>
                            </div>
                            <div
                                class="bg-white rounded-lg p-3 shadow-sm flex flex-col justify-center text-center min-h-[78px]"
                            >
                                <p class="text-xs text-gray-500 mb-1">TDS K-Factor</p>
                                <p
                                    id="a-cal-tds-k"
                                    class="text-sm font-mono font-bold text-purple-600 tracking-tight"
                                >
                                    --
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- pH Calibration Section -->
                    <div class="calibration-section rounded-xl p-4 mb-4">
                        <h3 class="font-semibold text-gray-800 mb-3 flex items-center">
                            <span
                                class="w-6 h-6 bg-blue-500 rounded-full flex items-center justify-center mr-2"
                            >
                                <span class="text-white text-xs">🧪</span>
                            </span>
                            Kalibrasi pH
                        </h3>
                        <div class="space-y-2">
                            <button
                                class="btn btn-primary-custom btn-sm w-full text-white"
                                onclick="startPh('A')"
                            >
                                Mulai Kalibrasi pH
                            </button>
                            <div class="grid grid-cols-2 gap-2">
                                <button
                                    class="btn btn-warning btn-sm"
                                    id="btn-a-asam"
                                    onclick="recordPhAsam('A')"
                                    disabled
                                >
                                    Record Asam
                                </button>
                                <button
                                    class="btn btn-success btn-sm"
                                    id="btn-a-netral"
                                    onclick="recordPhNetral('A')"
                                    disabled
                                >
                                    Record Netral
                                </button>
                            </div>
                            <button
                                class="btn btn-ghost btn-sm w-full"
                                onclick="finishCalibration('A')"
                            >
                                Selesai
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Unit B -->
                <div
                    class="sensor-card bg-base-100 rounded-2xl shadow-xl p-6 border border-gray-100"
                >
                    <div class="flex items-center justify-between mb-6">
                        <div class="flex items-center space-x-3">
                            <div
                                class="w-12 h-12 bg-gradient-to-br from-orange-400 to-red-500 rounded-xl flex items-center justify-center"
                            >
                                <span class="text-white font-bold text-lg">B</span>
                            </div>
                            <div>
                                <h2 class="text-xl font-bold text-gray-800">Sensor Kebun B</h2>
                                <p class="text-xs text-gray-500">Firebase: kebun-b</p>
                            </div>
                        </div>
                    </div>

                    <!-- Live Sensor Data -->
                    <div class="grid grid-cols-4 gap-4 mb-6">
                        <div
                            class="bg-gradient-to-br from-green-50 to-green-100 rounded-xl p-4 text-center border border-green-200"
                        >
                            <div
                                class="w-8 h-8 bg-green-500 rounded-full mx-auto mb-2 flex items-center justify-center"
                            >
                                <span class="text-white text-sm">🧪</span>
                            </div>
                            <p class="text-xs text-green-600 font-medium mb-1">pH</p>
                            <p
                                id="b-ph"
                                class="text-xl font-bold text-green-700 metric-value"
                            >
                                --
                            </p>
                        </div>
                        <div
                            class="bg-gradient-to-br from-purple-50 to-purple-100 rounded-xl p-4 text-center border border-purple-200"
                        >
                            <div
                                class="w-8 h-8 bg-purple-500 rounded-full mx-auto mb-2 flex items-center justify-center"
                            >
                                <span class="text-white text-sm">💧</span>
                            </div>
                            <p class="text-xs text-purple-600 font-medium mb-1">TDS</p>
                            <p
                                id="b-tds"
                                class="text-xl font-bold text-purple-700 metric-value"
                            >
                                --
                            </p>
                        </div>
                        <div
                            class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl p-4 text-center border border-blue-200"
                        >
                            <div
                                class="w-8 h-8 bg-blue-500 rounded-full mx-auto mb-2 flex items-center justify-center"
                            >
                                <span class="text-white text-sm">🌡️</span>
                            </div>
                            <p class="text-xs text-blue-600 font-medium mb-1">Suhu Air</p>
                            <p
                                id="b-suhu"
                                class="text-xl font-bold text-blue-700 metric-value"
                            >
                                --
                            </p>
                        </div>
                        <div
                            class="bg-gradient-to-br from-violet-50 to-violet-100 rounded-xl p-4 text-center border border-violet-200"
                        >
                            <div
                                class="w-8 h-8 bg-violet-500 rounded-full mx-auto mb-2 flex items-center justify-center"
                            >
                                <span class="text-white text-sm">🔬</span>
                            </div>
                            <p class="text-xs text-violet-600 font-medium mb-1">TDS Mentah</p>
                            <p
                                id="b-tds-raw"
                                class="text-xl font-bold text-violet-700 metric-value"
                            >
                                --
                            </p>
                        </div>
                    </div>

                    <!-- Calibration Values -->
                    <div
                        class="bg-gradient-to-r from-gray-50 to-gray-100 rounded-xl p-4 mb-6 border border-gray-200"
                    >
                        <h4 class="font-semibold text-gray-800 mb-3 flex items-center">
                            <span
                                class="w-6 h-6 bg-gray-600 rounded-full flex items-center justify-center mr-2"
                            >
                                <span class="text-white text-xs">🔧</span>
                            </span>
                            Nilai Kalibrasi
                        </h4>
                        <div class="grid grid-cols-3 gap-3 items-stretch">
                            <div
                                class="bg-white rounded-lg p-3 shadow-sm flex flex-col justify-center text-center min-h-[78px]"
                            >
                                <p class="text-xs text-gray-500 mb-1">pH Asam</p>
                                <p
                                    id="b-cal-asam"
                                    class="text-sm font-mono font-bold text-green-600 tracking-tight"
                                >
                                    --
                                </p>
                            </div>
                            <div
                                class="bg-white rounded-lg p-3 shadow-sm flex flex-col justify-center text-center min-h-[78px]"
                            >
                                <p class="text-xs text-gray-500 mb-1">pH Netral</p>
                                <p
                                    id="b-cal-netral"
                                    class="text-sm font-mono font-bold text-blue-600 tracking-tight"
                                >
                                    --
                                </p>
                            </div>
                            <div
                                class="bg-white rounded-lg p-3 shadow-sm flex flex-col justify-center text-center min-h-[78px]"
                            >
                                <p class="text-xs text-gray-500 mb-1">TDS K-Factor</p>
                                <p
                                    id="b-cal-tds-k"
                                    class="text-sm font-mono font-bold text-purple-600 tracking-tight"
                                >
                                    --
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- pH Calibration Section -->
                    <div class="calibration-section rounded-xl p-4 mb-4">
                        <h3 class="font-semibold text-gray-800 mb-3 flex items-center">
                            <span
                                class="w-6 h-6 bg-blue-500 rounded-full flex items-center justify-center mr-2"
                            >
                                <span class="text-white text-xs">🧪</span>
                            </span>
                            Kalibrasi pH
                        </h3>
                        <div class="space-y-2">
                            <button
                                class="btn btn-primary-custom btn-sm w-full text-white"
                                onclick="startPh('B')"
                            >
                                Mulai Kalibrasi pH
                            </button>
                            <div class="grid grid-cols-2 gap-2">
                                <button
                                    class="btn btn-warning btn-sm"
                                    id="btn-b-asam"
                                    onclick="recordPhAsam('B')"
                                    disabled
                                >
                                    Record Asam
                                </button>
                                <button
                                    class="btn btn-success btn-sm"
                                    id="btn-b-netral"
                                    onclick="recordPhNetral('B')"
                                    disabled
                                >
                                    Record Netral
                                </button>
                            </div>
                            <button
                                class="btn btn-ghost btn-sm w-full"
                                onclick="finishCalibration('B')"
                            >
                                Selesai
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Activity Log Section -->
            <div class="mt-12">
                <div
                    class="card bg-gradient-to-r from-gray-50 to-gray-100 shadow-xl border border-gray-200"
                >
                    <div class="card-body">
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center space-x-3">
                                <div>
                                    <h3 class="text-xl font-bold text-gray-800">Log Aktivitas</h3>
                                    <p class="text-gray-500 text-sm">
                                        Real-time monitoring kalibrasi sistem
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div
                            class="log-container rounded-xl p-4 max-h-80 overflow-auto border border-gray-200"
                        >
                            <div id="log" class="text-sm font-mono space-y-1"></div>
                            <div class="text-gray-500 text-center py-4" id="log-placeholder">
                                <span class="text-gray-400">📡</span>
                                <p class="mt-2 text-gray-600">
                                    Menunggu aktivitas kalibrasi...
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Thank-you note -->
            <div class="mt-6 text-center text-base md:text-md text-gray-900 bg-white rounded-lg p-4 shadow-sm border border-gray-200 font-regular">
                Terima Kasih kepada DPPM Kemendiktisaintek dan LPPM Universitas Lambung Mangkurat
            </div>
        </div>

        <!-- Script Halaman -->
        <script>window.APP_BASE_URL = "<?= rtrim(base_url('/'), '/') ?>";</script>
        <script>
            // Firebase Config (sama dengan file lain)
            const firebaseConfig = {
                apiKey: "AIzaSyCFx2ZlJRGZfD-P6I84a53yc8D_cyFqvgs",
                authDomain: "hidroganik-monitoring.firebaseapp.com",
                databaseURL: "https://hidroganik-monitoring-default-rtdb.asia-southeast1.firebasedatabase.app",
                projectId: "hidroganik-monitoring",
                storageBucket: "hidroganik-monitoring.firebasestorage.app",
                messagingSenderId: "103705402081",
                appId: "1:103705402081:web:babc15ad263749e80535a0"
                };

            firebase.initializeApp(firebaseConfig);
            const db = firebase.database();

            // Data source selection: 'mqtt' | 'firebase' | 'both'
            const DATA_SOURCE = 'firebase';

            // MQTT setup (same broker as dashboard)
            const MQTT_WS_URL = "wss://broker.emqx.io:8084/mqtt";
            const MQTT_OPTIONS = {
                clean: true,
                connectTimeout: 4000,
                reconnectPeriod: 5000,
                keepalive: 60,
                protocolVersion: 4,
                clientId: `web-kalibrasi-${Math.random().toString(16).slice(2)}`,
            };
            let mqttClient = null;
            let mqttConnected = false;

            function initMqttCal(){
                const currentTime = new Date().toLocaleTimeString("id-ID");
                
                if (!window.mqtt){ 
                    console.warn(`[${currentTime}] ❌ MQTT library not found on calibration page`);
                    log("⚠️ MQTT library not loaded. Commands will use Firebase only.");
                    showToast("MQTT not available. Using Firebase fallback.", "warning", 3000);
                    return; 
                }
                
                console.log(`[${currentTime}] 🔌 Initializing MQTT for calibration page...`);
                log("🔌 Connecting to MQTT broker...");
                
                try {
                    mqttClient = mqtt.connect(MQTT_WS_URL, MQTT_OPTIONS);
                    
                    mqttClient.on('connect', ()=>{
                        const t = new Date().toLocaleTimeString("id-ID");
                        mqttConnected = true;
                        console.log(`[${t}] ✅ MQTT connected (kalibrasi) to ${MQTT_WS_URL}`);
                        log(`✅ MQTT connected to ${MQTT_WS_URL}`);
                        showToast("MQTT connected! Ready to send calibration commands.", "success", 3000);
                        
                        // Subscribe to telemetry topics so UI updates even if Firebase mirror delay exists
                        try { 
                            mqttClient.subscribe(['hidroganik/kebun-a/telemetry','hidroganik/kebun-b/telemetry'], (err) => {
                                if (err) {
                                    console.error(`[${t}] ❌ MQTT subscribe error:`, err);
                                } else {
                                    console.log(`[${t}] ✅ Subscribed to telemetry topics`);
                                    log("✅ Subscribed to sensor data topics");
                                }
                            });
                        }
                        catch(e){ console.warn('MQTT subscribe failed (kalibrasi):', e); }
                    });
                    
                    mqttClient.on('error', (e)=> {
                        const t = new Date().toLocaleTimeString("id-ID");
                        mqttConnected = false;
                        console.error(`[${t}] ❌ MQTT error (kalibrasi):`, e?.message||e);
                        log(`❌ MQTT error: ${e?.message || 'Connection failed'}`);
                        showToast("MQTT connection error. Using Firebase fallback.", "error", 4000);
                    });
                    
                    mqttClient.on('reconnect', ()=> {
                        const t = new Date().toLocaleTimeString("id-ID");
                        console.log(`[${t}] 🔄 MQTT reconnecting (kalibrasi)...`);
                        log("🔄 MQTT reconnecting...");
                    });
                    
                    mqttClient.on('disconnect', ()=> {
                        const t = new Date().toLocaleTimeString("id-ID");
                        mqttConnected = false;
                        console.log(`[${t}] 🔌 MQTT disconnected (kalibrasi)`);
                        log("🔌 MQTT disconnected");
                    });
                    
                    mqttClient.on('close', ()=> {
                        const t = new Date().toLocaleTimeString("id-ID");
                        mqttConnected = false;
                        console.log(`[${t}] 🔒 MQTT connection closed (kalibrasi)`);
                    });
                    
                    mqttClient.on('message', (topic, payloadBuf)=>{
                        const t = String(topic||'');
                        let unit = null;
                        if (t.includes('kebun-a')) unit = 'A';
                        else if (t.includes('kebun-b')) unit = 'B';
                        if (!unit) return;
                        try {
                            const msg = JSON.parse(payloadBuf.toString('utf8')) || {};
                            setText(`${unit.toLowerCase()}-ph`, msg.ph, 2);
                            setText(`${unit.toLowerCase()}-tds`, msg.tds, 0);
                            setText(`${unit.toLowerCase()}-tds-raw`, getRawTds(msg), 0);
                            setText(`${unit.toLowerCase()}-suhu`, getWaterTemp(msg), 1);
                            setText(`${unit.toLowerCase()}-cal-asam`, msg.cal_ph_asam, 4);
                            setText(`${unit.toLowerCase()}-cal-netral`, msg.cal_ph_netral, 4);
                            setText(`${unit.toLowerCase()}-cal-tds-k`, msg.cal_tds_k, 4);
                        } catch(e){ /* ignore parse errors */ }
                    });
                } catch(e){ 
                    console.warn(`[${currentTime}] ❌ MQTT init failed (kalibrasi):`, e);
                    log(`❌ MQTT initialization failed: ${e.message}`);
                    showToast("MQTT initialization failed. Using Firebase only.", "error", 4000);
                }
            }
            function mqttPublishCalibration(unit, payload){
                if (!mqttClient) {
                    console.warn(`[Kalibrasi] MQTT client not initialized`);
                    return false;
                }
                
                if (!mqttClient.connected) {
                    console.warn(`[Kalibrasi] MQTT client not connected`);
                    return false;
                }
                
                const topic = unit === 'A' ? 'hidroganik/kebun-a/cmd' : 'hidroganik/kebun-b/cmd';
                try {
                    const message = JSON.stringify(payload);
                    mqttClient.publish(topic, message);
                    console.log(`[Kalibrasi] 📤 MQTT published to ${topic}:`, payload);
                    return true;
                } catch(e){ 
                    console.error(`[Kalibrasi] ❌ MQTT publish failed:`, e); 
                    return false; 
                }
            }

            // Konfigurasi path commands (ubah di sini jika ESP mendengarkan path berbeda)
            const CMD_PATHS = {
                A: "kebun-a/commands",
                B: "kebun-b/commands",
            };

            // Utils
            function nowStr() {
                return new Date().toLocaleTimeString("id-ID");
            }
            // Toast utility (Toastify)
            function showToast(message, type = "info", timeout = 3500) {
                const bgMap = {
                    success: "linear-gradient(to right, #16a34a, #22c55e)",
                    error: "linear-gradient(to right, #dc2626, #ef4444)",
                    warning: "linear-gradient(to right, #d97706, #f59e0b)",
                    info: "linear-gradient(to right, #2563eb, #3b82f6)",
                };
                Toastify({
                    text: message,
                    duration: timeout,
                    gravity: "top",
                    position: "right",
                    close: true,
                    stopOnFocus: true,
                    style: {
                        background: bgMap[type] || bgMap.info,
                        boxShadow: "0 4px 12px rgba(0,0,0,0.15)",
                        fontSize: "14px",
                        fontWeight: 500,
                        borderRadius: "8px",
                        padding: "10px 14px",
                    },
                }).showToast();
            }
            function log(msg) {
                const el = document.getElementById("log");
                const placeholder = document.getElementById("log-placeholder");

                // Hide placeholder on first log
                if (placeholder) {
                    placeholder.style.display = "none";
                }

                const p = document.createElement("div");
                p.className =
                    "flex items-center space-x-2 py-1 px-2 rounded hover:bg-gray-200 transition-colors";

                // Add status icon based on message type
                let icon = "📝";
                if (msg.includes("CMD dikirim")) icon = "📤";
                if (msg.includes("Gagal")) icon = "❌";
                if (msg.includes("siap")) icon = "✅";

                p.innerHTML = `
                <span class="text-gray-500">${icon}</span>
                <span class="text-gray-600">[${nowStr()}]</span>
                <span class="text-gray-800">${msg}</span>
            `;

                el.prepend(p);

                // Keep only last 20 logs
                while (el.children.length > 20) {
                    el.removeChild(el.lastChild);
                }
            }

            function setText(id, val, dec) {
                const el = document.getElementById(id);
                if (!el) return;
                if (val === undefined || val === null || val === "" || isNaN(val)) {
                    el.textContent = "--";
                    return;
                }
                const n = parseFloat(val);
                let suffix = "";
                if (id.includes("suhu")) suffix = "";
                else if (
                    id.endsWith("-tds") ||
                    (id.includes("tds") && !id.includes("cal-tds-k"))
                )
                    suffix = "";
                // For calibration K-Factor (cal_tds_k) no suffix
                el.textContent =
                    dec !== undefined ? n.toFixed(dec) + suffix : n + suffix;
            }

            // Try multiple field names for raw TDS coming from firmware
            function getRawTds(d){
                if (!d || typeof d !== 'object') return undefined;
                const keys = [
                    'tds_raw','raw_tds','tdsMentah','tds_mentah','tds_adc','adc','raw','ec_raw','ecRaw'
                ];
                for (const k of keys){
                    const v = d[k];
                    if (v !== undefined && v !== null && v !== ''){
                        const n = Number(v);
                        if (!Number.isNaN(n)) return n;
                    }
                }
                return undefined;
            }

            // Resolve water temperature from multiple possible keys (suhu_air, suhu, temperature, temp)
            function getWaterTemp(d){
                if (!d || typeof d !== 'object') return undefined;
                const keys = ['suhu_air','suhu','temperature','temp','t','suhu_udara'];
                for (const k of keys){
                    const v = d[k];
                    if (v !== undefined && v !== null && v !== ''){
                        const n = Number(v);
                        if (!Number.isNaN(n)) return n;
                    }
                }
                // lightweight diagnostics
                if (window && window.DEBUG === true) {
                    console.debug('[kalibrasi] getWaterTemp() not found in object keys:', Object.keys(d || {}));
                }
                return undefined;
            }

            function setBadgeState(unit, text, style = "success") {
                const el = document.getElementById(`state-${unit.toLowerCase()}`);
                if (el) {
                    // Remove existing badge classes
                    el.className = "badge badge-lg";

                    // Add appropriate styling based on state
                    if (style === "info") {
                        el.className += " badge-info status-active";
                    } else if (style === "warning") {
                        el.className += " badge-warning";
                    } else if (style === "success") {
                        el.className += " badge-success";
                    } else {
                        el.className += " badge-ghost";
                    }

                    el.textContent = text;

                    // Add pulse animation for active states
                    if (text.includes("...")) {
                        el.classList.add("animate-pulse");
                    } else {
                        el.classList.remove("animate-pulse");
                    }
                }
            }

            // Helper to update UI for a unit using a data object
            function updateUnitUI(unit, d){
                const data = d || {};
                setText(`${unit.toLowerCase()}-ph`, data.ph, 2);
                setText(`${unit.toLowerCase()}-tds`, data.tds, 0);
                setText(`${unit.toLowerCase()}-tds-raw`, getRawTds(data), 0);
                setText(`${unit.toLowerCase()}-suhu`, getWaterTemp(data), 1);
                setText(`${unit.toLowerCase()}-cal-asam`, data.cal_ph_asam, 4);
                setText(`${unit.toLowerCase()}-cal-netral`, data.cal_ph_netral, 4);
                setText(`${unit.toLowerCase()}-cal-tds-k`, data.cal_tds_k, 4);
            }

            // Realtime listeners untuk menampilkan data & hasil kalibrasi
            function initRealtime() {
                const units = ['A','B'];
                units.forEach(unit => {
                    const base = unit === 'A' ? 'kebun-a' : 'kebun-b';
                    const refs = [ db.ref(`${base}/realtime`), db.ref(base) ];
                    refs.forEach(ref => {
                        ref.on('value', (snap)=>{
                            const d = snap.val() || {};
                            updateUnitUI(unit, d);
                        });
                    });
                });
            }

            // Pengiriman perintah sesuai firmware Mega (via Firebase + MQTT)
            // Konvensi: tulis ke kebun-x/commands (push) dengan field text = string perintah
            function sendCommand(unit, text) {
                const path = unit === "A" ? CMD_PATHS.A : CMD_PATHS.B;
                const cmdTopic = unit === "A" ? "hidroganik/kebun-a/cmd" : "hidroganik/kebun-b/cmd";
                
                // Prepare command payload for MQTT
                const mqttPayload = {
                    type: "calibration",
                    command: text,
                    source: "web",
                    timestamp: Date.now()
                };
                
                // Send via MQTT first (primary method for ESP32)
                const mqttSent = mqttPublishCalibration(unit, mqttPayload);
                if (mqttSent) {
                    log(`(${unit}) 📤 MQTT CMD sent: ${text}`);
                    showToast(`(${unit}) Command sent via MQTT: ${text}`, "success", 3000);
                } else {
                    log(`(${unit}) ⚠️ MQTT offline, using Firebase fallback`);
                    showToast(`(${unit}) MQTT offline, using Firebase`, "warning", 2000);
                }
                
                // Also send to Firebase (backup/logging)
                const ref = db.ref(path);
                const payload = {
                    text, // contoh: "kalibrasi ph", "record asam 4.01", "kalibrasi tds 1413"
                    source: "web",
                    createdAt: firebase.database.ServerValue.TIMESTAMP,
                };
                return ref
                    .push(payload)
                    .then((snap) => {
                        log(`(${unit}) 📝 Firebase CMD logged: ${text}`);
                        setFirebaseStatus("connected");
                        // Mirror ke last_command untuk memudahkan verifikasi manual
                        const mirrorPath = path.replace("/commands", "/last_command");
                        return db.ref(mirrorPath).set({ ...payload, key: snap.key });
                    })
                    .catch((err) => {
                        log(`(${unit}) ❌ Firebase error: ${err.message}`);
                        if (!mqttSent) {
                            alert("Gagal mengirim perintah: " + err.message);
                            showToast(`(${unit}) Gagal: ${err.message}`, "error", 5000);
                        }
                        setFirebaseStatus("error", err.message);
                    });
            }

            // Indikator koneksi Firebase
            function setFirebaseStatus(state, detail) {
                const el = document.getElementById("fb-status");
                if (!el) return;
                if (state === "connected") {
                    el.className = "badge badge-success";
                    el.textContent = "Firebase: connected";
                } else if (state === "error") {
                    el.className = "badge badge-error";
                    el.textContent = "Firebase: error";
                    if (detail) el.title = detail;
                } else {
                    el.className = "badge";
                    el.textContent = "Firebase: checking...";
                }
            }

            // Heartbeat test untuk memastikan hak tulis OK
            function heartbeatTest() {
                const hbRef = db.ref("calibration-page/heartbeat");
                return hbRef
                    .set({
                        ts: firebase.database.ServerValue.TIMESTAMP,
                        page: "kalibrasi",
                    })
                    .then(() => setFirebaseStatus("connected"))
                    .catch((err) => setFirebaseStatus("error", err.message));
            }

            // Flow pH
            function startPh(unit) {
                setBadgeState(unit, "MULAI pH", "info");
                document.getElementById(
                    `btn-${unit.toLowerCase()}-asam`
                ).disabled = false;
                document.getElementById(
                    `btn-${unit.toLowerCase()}-netral`
                ).disabled = true;
                sendCommand(unit, "kalibrasi ph");
                showToast(`(${unit}) Kalibrasi pH dimulai`, "info");
            }
            function recordPhAsam(unit) {
                setBadgeState(unit, "ASAM...", "warning");
                document.getElementById(
                    `btn-${unit.toLowerCase()}-asam`
                ).disabled = true;
                document.getElementById(
                    `btn-${unit.toLowerCase()}-netral`
                ).disabled = false;
                // Kirim perintah sesuai spesifikasi: "record asam 4.01"
                sendCommand(unit, "record asam 4.01");
                showToast(`(${unit}) Nilai pH Asam direkam`, "warning");
            }
            function recordPhNetral(unit) {
                setBadgeState(unit, "NETRAL...", "success");
                document.getElementById(
                    `btn-${unit.toLowerCase()}-netral`
                ).disabled = true;
                // Kirim perintah sesuai spesifikasi: "record netral 6.86"
                sendCommand(unit, "record netral 6.86").then(() => {
                    setBadgeState(unit, "SELESAI", "success");
                });
                showToast(`(${unit}) Nilai pH Netral direkam`, "success");
            }

            // TDS
            function calibrateTds(unit) {
                const input = document.getElementById(`${unit.toLowerCase()}-ppm`);
                const val = parseFloat(input.value);
                if (!val || val <= 0) {
                    alert("Masukkan nilai ppm standar yang valid");
                    input.focus();
                    return;
                }
                setBadgeState(unit, "KALIBRASI TDS...", "info");
                sendCommand(unit, `kalibrasi tds ${val}`).then(() => {
                    setBadgeState(unit, "SELESAI", "success");
                });
                showToast(`(${unit}) Kalibrasi TDS (${val}) dikirim`, "info");
            }

            // Tombol Selesai: tulis 'selesai' ke last_command
            function finishCalibration(unit) {
                const base = unit === "A" ? "kebun-a" : "kebun-b";
                const ref = db.ref(`${base}/last_command`);
                const payload = {
                    text: "selesai",
                    source: "web",
                    createdAt: firebase.database.ServerValue.TIMESTAMP,
                };
                ref
                    .set(payload)
                    .then(() => {
                        log(`(${unit}) CMD dikirim: selesai`);
                        setBadgeState(unit, "SELESAI", "success");
                        showToast(`(${unit}) Kalibrasi pH selesai`, "success");
                    })
                    .catch((err) => {
                        log(`(${unit}) Gagal set selesai: ${err.message}`);
                        alert("Gagal mengirim perintah selesai: " + err.message);
                        showToast(`(${unit}) Gagal set selesai: ${err.message}`, "error");
                    });
            }

            // Live clock sederhana (optional)
            function initClock() {
                function tick() {
                    const now = new Date();
                    const t = now.toLocaleTimeString("id-ID", {
                        hour: "2-digit",
                        minute: "2-digit",
                        second: "2-digit",
                    });
                    const d = now.toLocaleDateString("id-ID", {
                        weekday: "short",
                        year: "numeric",
                        month: "short",
                        day: "numeric",
                    });
                    const clock = document.getElementById("real-time-clock");
                    const date = document.getElementById("real-time-date");
                        if (clock) clock.textContent = t;
                        if (date) date.textContent = d;
                        const mobClock = document.getElementById("real-time-clock-mobile");
                        if (mobClock) mobClock.textContent = t;
                        const mobDate = document.getElementById("real-time-date-mobile");
                        if (mobDate) mobDate.textContent = d;
                }
                tick();
                setInterval(tick, 1000);
            }

            // Logo (optional)
            function initCompanyLogo() {
                const logoImg = document.getElementById("company-logo");
                const logoFallback = document.getElementById("logo-fallback");
                                const sources = [
                                    window.APP_BASE_URL + '/assets/logo.png',
                                    window.APP_BASE_URL + '/assets/logo.jpg',
                                    window.APP_BASE_URL + '/assets/logo.svg',
                                    window.APP_BASE_URL + '/logo.png',
                                    window.APP_BASE_URL + '/logo.jpg',
                                ];
                let loaded = false;
                sources.forEach((src) => {
                    if (loaded) return;
                    const test = new Image();
                    test.onload = () => {
                        if (!loaded) {
                            logoImg.src = src;
                            logoImg.classList.remove("hidden");
                            logoFallback.style.display = "none";
                            loaded = true;
                        }
                    };
                    test.src = src;
                });
            }

            // Expose handlers
            window.startPh = startPh;
            window.recordPhAsam = recordPhAsam;
            window.recordPhNetral = recordPhNetral;
            window.calibrateTds = calibrateTds;
            window.finishCalibration = finishCalibration;
            
            // Debugging helpers
            window.getMQTTStatus = function() {
                const status = {
                    initialized: mqttClient !== null,
                    connected: mqttConnected,
                    clientConnected: mqttClient?.connected || false,
                    broker: MQTT_WS_URL
                };
                console.log("📊 MQTT Status (Kalibrasi):", status);
                log(`📊 MQTT: ${status.connected ? 'Connected' : 'Disconnected'} to ${status.broker}`);
                return status;
            };
            
            window.testCalibrationCommand = function(unit = 'A') {
                const testCmd = {
                    type: "calibration",
                    command: "test command",
                    source: "web-test",
                    timestamp: Date.now()
                };
                const result = mqttPublishCalibration(unit, testCmd);
                console.log(`🧪 Test command sent to Kebun ${unit}:`, result ? 'Success' : 'Failed');
                if (result) {
                    showToast(`Test command sent to Kebun ${unit}`, "success");
                } else {
                    showToast(`Failed to send test command to Kebun ${unit}`, "error");
                }
                return result;
            };
            
            console.log("📝 Debug functions available:");
            console.log("  getMQTTStatus() - Check MQTT connection");
            console.log("  testCalibrationCommand('A') - Test command to Kebun A");
            console.log("  testCalibrationCommand('B') - Test command to Kebun B");

            // Init
            document.addEventListener("DOMContentLoaded", () => {
                initClock();
                initCompanyLogo();
                if (DATA_SOURCE !== 'mqtt') initRealtime();
                if (DATA_SOURCE !== 'firebase') initMqttCal();
                heartbeatTest();
                log("Halaman kalibrasi siap");
            });
        </script>
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
            let deferredPrompt = null;
            window.addEventListener('beforeinstallprompt', (e) => {
                e.preventDefault();
                deferredPrompt = e;
                const container = document.getElementById('pwa-install');
                if (container) container.style.display = 'block';
            });
            document.getElementById('btnInstall')?.addEventListener('click', async () => {
                if (!deferredPrompt) return;
                deferredPrompt.prompt();
                const { outcome } = await deferredPrompt.userChoice;
                deferredPrompt = null;
                const container = document.getElementById('pwa-install');
                if (container) container.style.display = 'none';
            });
        </script>
    </body>
</html>