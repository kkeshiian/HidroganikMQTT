<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div id="monitoring" class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
    <!-- Device 1 / Kebun A -->
    <div class="bg-white rounded-xl card-shadow p-6">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-xl font-bold text-gray-800">Kebun A</h2>
                <p class="text-sm text-gray-500">Realtime Monitoring</p>
            </div>
        </div>
        <div class="grid grid-cols-3 gap-4 mb-6">
            <div class="text-center">
                <p class="text-sm text-gray-500">pH Level</p>
                <p id="device1-ph" class="text-xl font-bold text-green-600">--</p>
            </div>
            <div class="text-center">
                <p class="text-sm text-gray-500">TDS Level</p>
                <p id="device1-tds" class="text-xl font-bold text-purple-600">--</p>
            </div>
            <div class="text-center">
                <p class="text-sm text-gray-500">Suhu Air</p>
                <p id="device1-suhu" class="text-xl font-bold text-blue-600">--</p>
            </div>
        </div>
        <div class="h-72 sm:h-64 mb-4 -mx-4 sm:mx-0">
            <canvas id="chart1"></canvas>
        </div>
        <div class="bg-blue-50 rounded-lg p-4 mb-4">
            <h4 class="font-semibold text-blue-800 mb-3 flex items-center">Kontrol Pompa Kebun A</h4>
            <div class="mb-3">
                <label class="block text-sm font-medium text-blue-700 mb-2">Mode Operasi</label>
                <select id="pump-mode-a" class="w-full p-2 border border-blue-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white">
                    <option value="manual">Manual</option>
                    <option value="scheduled">Terjadwal</option>
                </select>
            </div>
            <div id="manual-control-a" class="mb-3">
                <div class="flex items-center justify-between bg-white rounded-lg p-3">
                    <span class="font-medium text-blue-700">Kontrol Manual</span>
                    <div class="flex items-center space-x-3">
                        <span id="manual-status-a" class="text-sm text-blue-600">OFF</span>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" id="manual-toggle-a" class="sr-only peer" onchange="manualToggle('A')">
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                        </label>
                    </div>
                </div>
            </div>
            <div id="schedule-settings-a" class="hidden">
                <div class="bg-white rounded-lg p-3 mb-3">
                    <label class="block text-sm font-medium text-blue-700 mb-2">⏰ Waktu Operasi</label>
                    <div class="grid grid-cols-2 gap-3 mb-3">
                        <div>
                            <label class="block text-xs text-blue-600 mb-1">Mulai</label>
                            <input type="time" id="start-time-a" value="08:00" class="w-full p-2 border border-blue-300 rounded text-center font-mono text-sm">
                        </div>
                        <div>
                            <label class="block text-xs text-blue-600 mb-1">Selesai</label>
                            <input type="time" id="end-time-a" value="18:00" class="w-full p-2 border border-blue-300 rounded text-center font-mono text-sm">
                        </div>
                    </div>
                    <div class="mb-3 bg-yellow-50 rounded p-2">
                        <p class="text-xs text-yellow-700">💡 Pompa akan menyala terus-menerus dari waktu mulai sampai selesai</p>
                    </div>
                    <button id="save-schedule-a" class="w-full bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-3 rounded text-sm transition duration-300">Simpan Jadwal</button>
                </div>
            </div>
            <div class="bg-white rounded-lg p-2">
                <div id="status-a" class="text-xs text-blue-700">Mode: Manual | Pompa: OFF</div>
            </div>
        </div>
    </div>
    <!-- Device 2 / Kebun B -->
    <div class="bg-white rounded-xl card-shadow p-6">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-xl font-bold text-gray-800">Kebun B</h2>
                <p class="text-sm text-gray-500">Realtime Monitoring</p>
            </div>
        </div>
        <div class="grid grid-cols-3 gap-4 mb-6">
            <div class="text-center">
                <p class="text-sm text-gray-500">pH Level</p>
                <p id="device2-ph" class="text-xl font-bold text-green-600">--</p>
            </div>
            <div class="text-center">
                <p class="text-sm text-gray-500">TDS Level</p>
                <p id="device2-tds" class="text-xl font-bold text-purple-600">--</p>
            </div>
            <div class="text-center">
                <p class="text-sm text-gray-500">Suhu Air</p>
                <p id="device2-suhu" class="text-xl font-bold text-blue-600">--</p>
            </div>
        </div>
        <div class="h-72 sm:h-64 mb-4 -mx-4 sm:mx-0">
            <canvas id="chart2"></canvas>
        </div>
        <div class="bg-green-50 rounded-lg p-4 mb-4">
            <h4 class="font-semibold text-green-800 mb-3 flex items-center">Kontrol Pompa Kebun B</h4>
            <div class="mb-3">
                <label class="block text-sm font-medium text-green-700 mb-2">Mode Operasi</label>
                <select id="pump-mode-b" class="w-full p-2 border border-green-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent bg-white">
                    <option value="manual">Manual</option>
                    <option value="scheduled">Terjadwal</option>
                </select>
            </div>
            <div id="manual-control-b" class="mb-3">
                <div class="flex items-center justify-between bg-white rounded-lg p-3">
                    <span class="font-medium text-green-700">Kontrol Manual</span>
                    <div class="flex items-center space-x-3">
                        <span id="manual-status-b" class="text-sm text-green-600">OFF</span>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" id="manual-toggle-b" class="sr-only peer" onchange="manualToggle('B')">
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-green-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-600"></div>
                        </label>
                    </div>
                </div>
            </div>
            <div id="schedule-settings-b" class="hidden">
                <div class="bg-white rounded-lg p-3 mb-3">
                    <label class="block text-sm font-medium text-green-700 mb-2">⏰ Waktu Operasi</label>
                    <div class="grid grid-cols-2 gap-3 mb-3">
                        <div>
                            <label class="block text-xs text-green-600 mb-1">Mulai</label>
                            <input type="time" id="start-time-b" value="08:00" class="w-full p-2 border border-green-300 rounded text-center font-mono text-sm">
                        </div>
                        <div>
                            <label class="block text-xs text-green-600 mb-1">Selesai</label>
                            <input type="time" id="end-time-b" value="18:00" class="w-full p-2 border border-green-300 rounded text-center font-mono text-sm">
                        </div>
                    </div>
                    <div class="mb-3 bg-yellow-50 rounded p-2">
                        <p class="text-xs text-yellow-700">💡 Pompa akan menyala terus-menerus dari waktu mulai sampai selesai</p>
                    </div>
                    <button id="save-schedule-b" class="w-full bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-3 rounded text-sm transition duration-300">Simpan Jadwal</button>
                </div>
            </div>
            <div class="bg-white rounded-lg p-2">
                <div id="status-b" class="text-xs text-green-700">Mode: Manual | Pompa: OFF</div>
            </div>
        </div>
    </div>

    <div class="lg:col-span-2">
        <div class="mt-1 text-center text-base md:text-md text-gray-900 bg-white rounded-lg p-4 shadow-sm border border-gray-200 font-regular">
            Terima Kasih kepada DPPM Kemendiktisaintek dan LPPM Universitas Lambung Mangkurat
        </div>
    </div>
</div>
<?= $this->endSection() ?>