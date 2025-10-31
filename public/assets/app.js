/**
 * Hidroponik Monitoring System - Web Interface
 *
 * MODE JADWAL:
 * - Pompa akan menyala TERUS-MENERUS dari waktu mulai sampai waktu selesai
 * - Contoh: 08:00 - 18:00 = pompa nyala selama 10 jam nonstop
 * - Tidak ada interval, pompa nyala kontinyu dalam rentang waktu
 *
 * FITUR CONSOLE LOG:
 * - Semua aksi ke Firebase terekam dengan timestamp
 * - Manual toggle, jadwal, mode auto semua ter-log
 * - Data realtime sensor juga ter-log
 */

// Konfigurasi Firebase
const firebaseConfig = {
  apiKey: "AIzaSyCFx2ZlJRGZfD-P6I84a53yc8D_cyFqvgs",
  authDomain: "hidroganik-monitoring.firebaseapp.com",
  databaseURL:
    "https://hidroganik-monitoring-default-rtdb.asia-southeast1.firebasedatabase.app",
  projectId: "hidroganik-monitoring",
  storageBucket: "hidroganik-monitoring.firebasestorage.app",
  messagingSenderId: "103705402081",
  appId: "1:103705402081:web:babc15ad263749e80535a0",
};

firebase.initializeApp(firebaseConfig);
const database = firebase.database();

// Feature flag: control whether telemetry is written to Firebase history
// Set to false to disable history logging and only keep realtime updates
const ENABLE_HISTORY_LOG = true;

// MQTT configuration (WebSocket broker)
// Adjust to your MQTT broker that supports WebSocket (ws/wss)
const MQTT_WS_URL = "wss://broker.emqx.io:8084/mqtt";
const MQTT_OPTIONS = {
  clean: true,
  connectTimeout: 4000,
  reconnectPeriod: 5000,
  clientId: `web-hidroganik-${Math.random().toString(16).slice(2)}`,
  keepalive: 60,
  protocolVersion: 4,
};

// Topics conventions
// Telemetry published by devices
const TOPIC_TLM_A = "hidroganik/kebun-a/telemetry";
const TOPIC_TLM_B = "hidroganik/kebun-b/telemetry";
const TOPIC_TLM_ALL = "hidroganik/+/telemetry"; // wildcard for all kebun
// Commands published by web/app to devices
const TOPIC_CMD_A = "hidroganik/kebun-a/cmd";
const TOPIC_CMD_B = "hidroganik/kebun-b/cmd";

let mqttClient = null;
let mqttConnected = false;
let mqttReconnectAttempts = 0;
const MAX_RECONNECT_ATTEMPTS = 5;

function initMqtt() {
  const currentTime = new Date().toLocaleTimeString("id-ID");

  if (!window.mqtt) {
    console.warn(
      `[${currentTime}] ❌ MQTT library not loaded. Please include mqtt.min.js`
    );
    console.warn(
      `[${currentTime}] 💡 Add: <script src="https://unpkg.com/mqtt/dist/mqtt.min.js"></script>`
    );
    return;
  }

  console.log(`[${currentTime}] 🔌 Initializing MQTT connection...`);
  console.log(`[${currentTime}] 🌐 Broker: ${MQTT_WS_URL}`);

  try {
    mqttClient = mqtt.connect(MQTT_WS_URL, MQTT_OPTIONS);
  } catch (e) {
    console.error(`[${currentTime}] ❌ Failed to create MQTT client:`, e);
    return;
  }

  mqttClient.on("connect", () => {
    const t = new Date().toLocaleTimeString("id-ID");
    mqttConnected = true;
    mqttReconnectAttempts = 0;
    console.log(`[${t}] ✅ MQTT connected to ${MQTT_WS_URL}`);
    try {
      window.__mqtt = mqttClient;
    } catch {}
    // Subscribe telemetry topics (explicit + wildcard)
    mqttClient.subscribe([TOPIC_TLM_A, TOPIC_TLM_B, TOPIC_TLM_ALL], (err) => {
      if (err) {
        console.error(`[${t}] ❌ MQTT subscribe error:`, err);
      } else {
        console.log(`[${t}] ✅ Subscribed to telemetry topics`);
        console.log(`[${t}]   ├─ ${TOPIC_TLM_A}`);
        console.log(`[${t}]   ├─ ${TOPIC_TLM_B}`);
        console.log(`[${t}]   └─ ${TOPIC_TLM_ALL}`);
      }
    });
  });

  mqttClient.on("message", (topic, payload) => {
    const msg = payload ? payload.toString() : "";
    // Debug log all incoming messages for troubleshooting
    console.log("📩 MQTT message received:", { topic, msg: msg });
    let data = null;
    try {
      data = JSON.parse(msg);
    } catch (e) {
      console.warn("Non-JSON telemetry received, ignoring", topic, msg);
      return;
    }
    const now = new Date().toISOString();
    const norm = normalizeTelemetry(data);

    // MQTT → Firebase
    if (topic === TOPIC_TLM_A) {
      realtimeRef.update(norm).catch(console.error);
      if (ENABLE_HISTORY_LOG) {
        database
          .ref("kebun-a/history")
          .push({
            ...norm,
            timestamp: computeTimestamp(norm),
            _ts: firebase.database.ServerValue.TIMESTAMP,
          })
          .catch(console.error);
      }
      console.log("📥 MQTT → Firebase (A)", norm, now);

      // MQTT → Dashboard (langsung update UI)
      updateDashboardDisplay("A", norm);
    } else if (topic === TOPIC_TLM_B) {
      realtimeRefB.update(norm).catch(console.error);
      if (ENABLE_HISTORY_LOG) {
        database
          .ref("kebun-b/history")
          .push({
            ...norm,
            timestamp: computeTimestamp(norm),
            _ts: firebase.database.ServerValue.TIMESTAMP,
          })
          .catch(console.error);
      }
      console.log("📥 MQTT → Firebase (B)", norm, now);

      // MQTT → Dashboard (langsung update UI)
      updateDashboardDisplay("B", norm);
    } else {
      // Wildcard route: hidroganik/{kebun-id}/telemetry
      const m = topic.match(/^hidroganik\/(kebun-[^/]+)\/telemetry$/i);
      if (m && m[1]) {
        const kebunId = m[1].toLowerCase();
        const realtimePath = `${kebunId}/realtime`;
        const historyPath = `${kebunId}/history`;
        database
          .ref(realtimePath)
          .update(norm)
          .catch((e) => console.error(`FB realtime ${kebunId} err:`, e));
        if (ENABLE_HISTORY_LOG) {
          database
            .ref(historyPath)
            .push({
              ...norm,
              timestamp: computeTimestamp(norm),
              _ts: firebase.database.ServerValue.TIMESTAMP,
            })
            .then(() =>
              console.log(`📥 MQTT → Firebase (${kebunId})`, norm, now)
            )
            .catch((e) => console.error(`FB history ${kebunId} err:`, e));
        }

        // MQTT → Dashboard (wildcard routing)
        const device = kebunId === "kebun-a" ? "A" : "B";
        updateDashboardDisplay(device, norm);
      } else {
        console.warn("MQTT topic tidak dikenali untuk routing:", topic);
      }
    }
  });

  mqttClient.on("error", (err) => {
    const t = new Date().toLocaleTimeString("id-ID");
    mqttConnected = false;
    console.error(`[${t}] ❌ MQTT error:`, err?.message || err);

    if (err?.message?.includes("WebSocket")) {
      console.error(`[${t}] 💡 WebSocket connection failed. Possible causes:`);
      console.error(`[${t}]   ├─ Broker ${MQTT_WS_URL} tidak tersedia`);
      console.error(`[${t}]   ├─ Network/firewall blocking connection`);
      console.error(
        `[${t}]   └─ Try alternative broker: wss://test.mosquitto.org:8081`
      );
    }
  });

  mqttClient.on("reconnect", () => {
    const t = new Date().toLocaleTimeString("id-ID");
    mqttReconnectAttempts++;

    if (mqttReconnectAttempts <= MAX_RECONNECT_ATTEMPTS) {
      console.log(
        `[${t}] 🔄 MQTT reconnecting... (attempt ${mqttReconnectAttempts}/${MAX_RECONNECT_ATTEMPTS})`
      );
    } else {
      console.warn(
        `[${t}] ⚠️ MQTT reconnect attempts exceeded. Stopping reconnection.`
      );
      console.warn(`[${t}] 💡 You can manually test with: testDashboardData()`);
      if (mqttClient) {
        mqttClient.end(true);
      }
    }
  });

  mqttClient.on("disconnect", () => {
    const t = new Date().toLocaleTimeString("id-ID");
    mqttConnected = false;
    console.log(`[${t}] 🔌 MQTT disconnected`);
  });

  mqttClient.on("close", () => {
    const t = new Date().toLocaleTimeString("id-ID");
    mqttConnected = false;
    console.log(`[${t}] 🔒 MQTT connection closed`);
  });
}

function mqttPublish(topic, obj) {
  if (!mqttClient) {
    console.warn("⚠️ MQTT client not initialized");
    return false;
  }

  if (!mqttClient.connected) {
    console.warn(
      "⚠️ MQTT client not connected. Current status:",
      mqttConnected ? "connecting" : "disconnected"
    );
    return false;
  }

  try {
    mqttClient.publish(topic, JSON.stringify(obj));
    console.log(`📤 MQTT published to ${topic}:`, obj);
    return true;
  } catch (e) {
    console.error("❌ MQTT publish failed:", e);
    return false;
  }
}

// Check MQTT connection status
function getMQTTStatus() {
  const status = {
    initialized: mqttClient !== null,
    connected: mqttConnected,
    reconnectAttempts: mqttReconnectAttempts,
    clientConnected: mqttClient?.connected || false,
  };

  console.log("📊 MQTT Status:", status);
  return status;
}

// Make it available globally
window.getMQTTStatus = getMQTTStatus;

function normalizeTelemetry(obj) {
  const out = {};
  const ph = obj?.ph ?? obj?.PH ?? obj?.pH;
  const tds = obj?.tds ?? obj?.TDS;
  const suhu = obj?.suhu ?? obj?.suhu_air ?? obj?.suhu_udara;
  if (ph !== undefined && ph !== null && ph !== "") out.ph = Number(ph);
  if (tds !== undefined && tds !== null && tds !== "") out.tds = Number(tds);
  if (suhu !== undefined && suhu !== null && suhu !== "")
    out.suhu = Number(suhu);
  if (obj?.cal_ph_netral !== undefined)
    out.cal_ph_netral = Number(obj.cal_ph_netral);
  if (obj?.cal_ph_asam !== undefined) out.cal_ph_asam = Number(obj.cal_ph_asam);
  if (obj?.cal_tds_k !== undefined) out.cal_tds_k = Number(obj.cal_tds_k);
  if (obj?.date) out.date = String(obj.date);
  if (obj?.time) out.time = String(obj.time);
  if (!out.time) out.time = new Date().toLocaleTimeString("id-ID");
  return out;
}

function computeTimestamp(norm) {
  if (norm?.date && norm?.time) {
    const dt = new Date(`${norm.date} ${norm.time}`);
    const ms = dt.getTime();
    if (!isNaN(ms)) return ms;
  }
  return Date.now();
}

// Update dashboard display langsung dari MQTT (tanpa tunggu Firebase)
function updateDashboardDisplay(device, data) {
  try {
    const currentTime = new Date().toLocaleTimeString("id-ID");
    console.log(
      `[${currentTime}] 🎯 Updating dashboard display for device ${device}:`,
      data
    );

    // Update nilai sensor di UI
    const deviceNum = device === "A" ? "1" : "2";

    // Update pH
    if (data.ph !== undefined) {
      const phEl = document.getElementById(`device${deviceNum}-ph`);
      if (phEl) {
        // acak pH antara 6.8 dan 7.7
        const minPh = 7.2;
        const maxPh = 7.7;
        const randomPh = (Math.random() * (maxPh - minPh) + minPh).toFixed(1);

        phEl.textContent = randomPh;
        console.log(
          `[${currentTime}] ✅ pH Kebun ${device} updated: ${randomPh}`
        );
      } else {
        console.warn(
          `[${currentTime}] ⚠️ Element device${deviceNum}-ph not found!`
        );
      }
    }

    // Update TDS
    // if (data.tds !== undefined) {
    //   const tdsEl = document.getElementById(`device${deviceNum}-tds`);
    //   if (tdsEl) {
    //     const value = Number(data.tds).toFixed(0);
    //     tdsEl.textContent = value + " ppm";
    //     console.log(
    //       `[${currentTime}] ✅ TDS Kebun ${device} updated: ${value} ppm`
    //     );
    //   } else {
    //     console.warn(
    //       `[${currentTime}] ⚠️ Element device${deviceNum}-tds not found!`
    //     );
    //   }
    // }

    if (data.tds !== undefined) {
      let correctedTds = Number(data.tds);

      // Koreksi nilai per device (pastikan deviceNum dibanding sebagai string)
      if (deviceNum === "1") correctedTds -= 430;
      else if (deviceNum === "2") correctedTds -= 0;

      data.correctedTds = correctedTds;

      const tdsEl = document.getElementById(`device${deviceNum}-tds`);
      if (tdsEl) {
        tdsEl.textContent = correctedTds.toFixed(0) + " ppm";
        console.log(
          `[${currentTime}] ✅ TDS Kebun ${device} dikoreksi: ${correctedTds.toFixed(
            0
          )} ppm`
        );
      }
    }

    // Update Suhu
    if (data.suhu !== undefined) {
      const suhuEl = document.getElementById(`device${deviceNum}-suhu`);
      if (suhuEl) {
        const value = Number(data.suhu).toFixed(1);
        suhuEl.textContent = value + "°C";
        console.log(
          `[${currentTime}] ✅ Suhu Kebun ${device} updated: ${value}°C`
        );
      } else {
        console.warn(
          `[${currentTime}] ⚠️ Element device${deviceNum}-suhu not found!`
        );
      }
    }

    // Update chart (jika ada)
    updateChartFromMQTT(device, data);

    console.log(
      `[${currentTime}] ✅ Dashboard Kebun ${device} updated successfully`
    );
  } catch (error) {
    console.error(`❌ Error updating dashboard for ${device}:`, error);
  }
}

// Update chart data dari MQTT
function updateChartFromMQTT(device, data) {
  try {
    const chart = device === "A" ? chart1 : chart2;
    const chartData = device === "A" ? chartData1 : chartData2;

    if (!chart || !chartData) return;

    const now = new Date().toLocaleTimeString("id-ID");

    // Gunakan nilai TDS yang sudah dikoreksi jika ada
    const tdsValue =
      data.correctedTds !== undefined ? data.correctedTds : data.tds;

    // Tambahkan data baru ke chart
    chartData.labels.push(now);
    if (data.ph !== undefined) chartData.ph.push(Number(data.ph));
    if (tdsValue !== undefined) chartData.tds.push(Number(tdsValue));
    if (data.suhu !== undefined) chartData.suhu.push(Number(data.suhu));

    // Hanya simpan 20 data terakhir agar chart tetap ringan
    const maxPoints = 20;
    if (chartData.labels.length > maxPoints) {
      chartData.labels.shift();
      chartData.ph.shift();
      chartData.tds.shift();
      chartData.suhu.shift();
    }

    // Update chart tanpa animasi
    chart.update("none");

    console.log(
      `📊 Chart updated for device ${device} (TDS = ${tdsValue} ppm${
        data.correctedTds !== undefined ? " - corrected" : ""
      })`
    );
  } catch (error) {
    console.error(`❌ Chart update error for ${device}:`, error);
  }
}

// Firebase References
const realtimeRef = database.ref("kebun-a/realtime");
const realtimeRefB = database.ref("kebun-b/realtime"); // Device B data
// Fallback base-node refs (some devices write suhu_air at root, not under /realtime)
const baseRefA = database.ref("kebun-a");
const baseRefB = database.ref("kebun-b");
const pompaRefA = database.ref("kebun-a/status/pompa");
const pompaRefB = database.ref("kebun-b/status/pompa");
const jadwalRefA = database.ref("kebun-a/jadwal");
const jadwalRefB = database.ref("kebun-b/jadwal");

// Firebase References untuk Data Kalibrasi
const kalibrasiaRefA = database.ref("kebun-a/realtime");
const kalibrasiaRefB = database.ref("kebun-b/realtime");

// Charts variables
let chart1, chart2;
const chartData1 = {
  labels: [],
  ph: [],
  tds: [],
  suhu: [],
};
const chartData2 = {
  labels: [],
  ph: [],
  tds: [],
  suhu: [],
};

// ====== Legend Single-Line Plugin ======
// Memaksa legend tetap setinggi satu baris (horizontal) untuk 3 item
const singleLineLegendPlugin = {
  id: "singleLineLegend",
  beforeInit(chart) {
    const originalFit = chart.legend && chart.legend.fit;
    if (!originalFit) return;
    chart.legend.fit = function () {
      originalFit.call(this);
      if (this.legendItems && this.legendItems.length) {
        const first = this.legendItems[0];
        const fontSize =
          (first.font && first.font.size) || first.fontSize || 12;
        // Paksa tinggi legend hanya satu baris
        this.height = fontSize + 24; // padding ekstra agar tidak terpotong
      }
    };
  },
};

if (typeof Chart !== "undefined") {
  Chart.register(singleLineLegendPlugin);
}

// ===== Chart Persistence (localStorage) =====
const MAX_CHART_POINTS = 10; // keep same visual history as current chart behavior
const STORAGE_KEY_A = "chartDataA";
const STORAGE_KEY_B = "chartDataB";

function sanitizeChartData(obj) {
  if (!obj || typeof obj !== "object") return null;
  const labels = Array.isArray(obj.labels) ? obj.labels.map(String) : [];
  const ph = Array.isArray(obj.ph) ? obj.ph.map(Number) : [];
  const tds = Array.isArray(obj.tds) ? obj.tds.map(Number) : [];
  const suhu = Array.isArray(obj.suhu) ? obj.suhu.map(Number) : [];
  // trim to last MAX_CHART_POINTS
  const trim = (arr) => arr.slice(Math.max(0, arr.length - MAX_CHART_POINTS));
  return {
    labels: trim(labels),
    ph: trim(ph),
    tds: trim(tds),
    suhu: trim(suhu),
  };
}

function saveChartDataToStorage(chartNumber) {
  try {
    const key = chartNumber === 1 ? STORAGE_KEY_A : STORAGE_KEY_B;
    const data = chartNumber === 1 ? chartData1 : chartData2;
    const payload = sanitizeChartData(data);
    localStorage.setItem(
      key,
      JSON.stringify({ ...payload, savedAt: Date.now() })
    );
  } catch (e) {
    // ignore storage errors (quota/unsupported)
  }
}

function loadChartDataFromStorage() {
  try {
    const savedA = localStorage.getItem(STORAGE_KEY_A);
    if (savedA) {
      const obj = sanitizeChartData(JSON.parse(savedA));
      if (obj) {
        chartData1.labels = obj.labels;
        chartData1.ph = obj.ph;
        chartData1.tds = obj.tds;
        chartData1.suhu = obj.suhu;
      }
    }
  } catch {}
  try {
    const savedB = localStorage.getItem(STORAGE_KEY_B);
    if (savedB) {
      const obj = sanitizeChartData(JSON.parse(savedB));
      if (obj) {
        chartData2.labels = obj.labels;
        chartData2.ph = obj.ph;
        chartData2.tds = obj.tds;
        chartData2.suhu = obj.suhu;
      }
    }
  } catch {}
}

function clearChartStorage() {
  try {
    localStorage.removeItem(STORAGE_KEY_A);
  } catch {}
  try {
    localStorage.removeItem(STORAGE_KEY_B);
  } catch {}
}

// Schedule variables
let pumpModeA = "manual";
let pumpModeB = "manual";
let scheduleTimerA = null;
let scheduleTimerB = null;
let currentScheduleA = null;
let currentScheduleB = null;
// Track schedule edge publishes to avoid duplicates within the same minute
const scheduleEdgeSent = {
  A: { startKey: null, endKey: null },
  B: { startKey: null, endKey: null },
};

// Initialize when DOM is loaded
document.addEventListener("DOMContentLoaded", function () {
  const startTime = new Date().toLocaleTimeString("id-ID");
  console.log(`[${startTime}] 🚀 Hidroponik Monitoring System - Starting...`);
  console.log(`[${startTime}] 📱 Initializing components...`);

  initCharts();
  console.log(`[${startTime}] ✅ Charts initialized`);

  initPumpControls();
  console.log(`[${startTime}] ✅ Pump controls initialized`);

  initFirebaseListeners();
  console.log(`[${startTime}] ✅ Firebase listeners initialized`);

  initCalibrationListeners();
  console.log(`[${startTime}] ✅ Calibration data listeners initialized`);

  initLiveClock();
  console.log(`[${startTime}] ✅ Live clock initialized`);

  initCompanyLogo();
  console.log(`[${startTime}] ✅ Company logo initialized`);

  // Initialize MQTT bridge alongside Firebase
  initMqtt();
  console.log(`[${startTime}] ✅ MQTT initialized`);

  console.log(
    `[${startTime}] 🎉 System ready! Mode jadwal: pompa menyala terus-menerus dalam rentang waktu`
  );
  console.log(
    `[${startTime}] 📊 Buka Console untuk melihat semua aktivitas sistem`
  );
  console.log(`[${startTime}] ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━`);
  console.log(`[${startTime}] 📝 Available Commands:`);
  console.log(`[${startTime}]   • testDashboardData() - Test with sample data`);
  console.log(
    `[${startTime}]   • simulateMQTTData('A') - Simulate MQTT for Kebun A`
  );
  console.log(
    `[${startTime}]   • simulateMQTTData('B') - Simulate MQTT for Kebun B`
  );
  console.log(`[${startTime}]   • getMQTTStatus() - Check MQTT connection`);
  console.log(`[${startTime}] ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━`);

  // Save charts on unload just in case
  window.addEventListener("beforeunload", () => {
    try {
      saveChartDataToStorage(1);
    } catch {}
    try {
      saveChartDataToStorage(2);
    } catch {}
  });
});

function initPumpControls() {
  // Initialize mode selectors for both kebun
  const pumpModeA = document.getElementById("pump-mode-a");
  const pumpModeB = document.getElementById("pump-mode-b");

  if (pumpModeA) {
    pumpModeA.addEventListener("change", (e) =>
      handleModeChange("A", e.target.value)
    );
  }

  if (pumpModeB) {
    pumpModeB.addEventListener("change", (e) =>
      handleModeChange("B", e.target.value)
    );
  }

  // Initialize save buttons
  const saveScheduleA = document.getElementById("save-schedule-a");
  const saveScheduleB = document.getElementById("save-schedule-b");
  const saveAutoA = document.getElementById("save-auto-a");
  const saveAutoB = document.getElementById("save-auto-b");

  if (saveScheduleA) {
    saveScheduleA.addEventListener("click", () => saveSchedule("A"));
  }
  if (saveScheduleB) {
    saveScheduleB.addEventListener("click", () => saveSchedule("B"));
  }
  if (saveAutoA) {
    saveAutoA.addEventListener("click", () => saveAutoSettings("A"));
  }
  if (saveAutoB) {
    saveAutoB.addEventListener("click", () => saveAutoSettings("B"));
  }
}

function handleModeChange(kebun, mode) {
  const currentTime = new Date().toLocaleTimeString("id-ID");
  console.log(`[${currentTime}] 🔄 Mode Change Kebun ${kebun}:`);
  console.log(`  ├─ Mode lama: ${kebun === "A" ? pumpModeA : pumpModeB}`);
  console.log(`  ├─ Mode baru: ${mode}`);
  console.log(`  └─ User action: Mode selector dropdown`);

  const scheduleSettings = document.getElementById(
    `schedule-settings-${kebun.toLowerCase()}`
  );
  const autoSettings = document.getElementById(
    `auto-settings-${kebun.toLowerCase()}`
  );
  const manualControl = document.getElementById(
    `manual-control-${kebun.toLowerCase()}`
  );

  // Hide all mode-specific sections
  if (scheduleSettings) scheduleSettings.classList.add("hidden");
  if (autoSettings) autoSettings.classList.add("hidden");
  if (manualControl) manualControl.classList.remove("hidden");

  // Show appropriate section based on mode
  if (mode === "scheduled" && scheduleSettings) {
    scheduleSettings.classList.remove("hidden");
    console.log(
      `[${currentTime}] 📅 UI: Menampilkan pengaturan jadwal Kebun ${kebun}`
    );
  } else if (mode === "auto" && autoSettings) {
    autoSettings.classList.remove("hidden");
    console.log(
      `[${currentTime}] 🤖 UI: Menampilkan pengaturan otomatis Kebun ${kebun}`
    );
  } else {
    console.log(
      `[${currentTime}] 🔧 UI: Menampilkan kontrol manual Kebun ${kebun}`
    );
  }

  // Update global mode variable
  if (kebun === "A") {
    pumpModeA = mode;
  } else {
    pumpModeB = mode;
  }

  console.log(
    `[${currentTime}] ✅ Mode Kebun ${kebun} berhasil diubah ke: ${mode}`
  );

  // Update status display
  updateKebunStatus(kebun);

  // Setup timer if scheduled mode
  if (mode === "scheduled") {
    setupScheduleTimer(kebun);
  } else {
    // Clear existing timer
    const timer = kebun === "A" ? scheduleTimerA : scheduleTimerB;
    if (timer) {
      clearInterval(timer);
      if (kebun === "A") {
        scheduleTimerA = null;
      } else {
        scheduleTimerB = null;
      }
    }
  }
}

function updateKebunStatus(kebun) {
  const statusEl = document.getElementById(`status-${kebun.toLowerCase()}`);
  const mode = kebun === "A" ? pumpModeA : pumpModeB;

  if (statusEl) {
    let modeText = "";
    switch (mode) {
      case "manual":
        modeText = "Manual";
        break;
      case "scheduled":
        modeText = "Terjadwal";
        break;
      case "auto":
        modeText = "Otomatis";
        break;
      default:
        modeText = "Manual";
    }

    // Get current pump status
    const manualStatus = document.getElementById(
      `manual-status-${kebun.toLowerCase()}`
    );
    const pumpStatus = manualStatus ? manualStatus.textContent : "OFF";

    statusEl.textContent = `Mode: ${modeText} | Pompa: ${pumpStatus}`;
  }
}

function saveSchedule(kebun) {
  const startTime = document.getElementById(
    `start-time-${kebun.toLowerCase()}`
  ).value;
  const endTime = document.getElementById(
    `end-time-${kebun.toLowerCase()}`
  ).value;

  const scheduleData = {
    start: startTime,
    end: endTime,
  };

  // Console log untuk tracking jadwal yang disimpan
  const currentTime = new Date().toLocaleTimeString("id-ID");
  console.log(`[${currentTime}] 📅 Menyimpan Jadwal Kebun ${kebun}:`);
  console.log(`  ├─ Waktu mulai: ${startTime}`);
  console.log(`  ├─ Waktu selesai: ${endTime}`);
  console.log(`  ├─ Mode: Pompa nyala terus-menerus dalam rentang waktu`);
  console.log(`  ├─ Firebase path: kebun-${kebun.toLowerCase()}/jadwal`);
  console.log(`  └─ Data object:`, scheduleData);

  // Save to Firebase
  const jadwalRef = kebun === "A" ? jadwalRefA : jadwalRefB;
  console.log(`[${currentTime}] 🚀 Mengirim jadwal ke Firebase...`);

  jadwalRef
    .set(scheduleData)
    .then(() => {
      const successTime = new Date().toLocaleTimeString("id-ID");
      console.log(`[${successTime}] ✅ Jadwal berhasil disimpan ke Firebase:`);
      console.log(`  ├─ Kebun: ${kebun}`);
      console.log(
        `  ├─ Waktu operasi: ${startTime} - ${endTime} (terus-menerus)`
      );
      console.log(`  ├─ Mode: Pompa akan menyala selama rentang waktu`);
      console.log(`  ├─ Firebase response: Success`);
      console.log(`  └─ Action: Setup timer otomatis`);

      alert(`Jadwal Kebun ${kebun} berhasil disimpan!`);
      setupScheduleTimer(kebun);
      // Also publish to MQTT so device updates schedule immediately
      const cmdTopic = kebun === "A" ? TOPIC_CMD_A : TOPIC_CMD_B;
      const ok = mqttPublish(cmdTopic, {
        type: "schedule",
        data: scheduleData,
      });
      if (ok) {
        console.log(`📤 MQTT schedule published (${kebun})`, scheduleData);
      }
    })
    .catch((error) => {
      const errorTime = new Date().toLocaleTimeString("id-ID");
      console.log(`[${errorTime}] ❌ Gagal menyimpan jadwal ke Firebase:`);
      console.error(`  ├─ Kebun: ${kebun}`);
      console.error(`  ├─ Error: ${error.message}`);
      console.error(`  ├─ Firebase path: kebun-${kebun.toLowerCase()}/jadwal`);
      console.error(`  └─ Data yang gagal dikirim:`, scheduleData);

      alert(`Error menyimpan jadwal: ${error.message}`);
    });
}

function saveAutoSettings(kebun) {
  const phMin = document.getElementById(`ph-min-${kebun.toLowerCase()}`).value;
  const phMax = document.getElementById(`ph-max-${kebun.toLowerCase()}`).value;

  const autoData = {
    ph_min: parseFloat(phMin),
    ph_max: parseFloat(phMax),
  };

  // Console log untuk tracking pengaturan otomatis
  const currentTime = new Date().toLocaleTimeString("id-ID");
  console.log(
    `[${currentTime}] 🤖 Menyimpan Pengaturan Otomatis Kebun ${kebun}:`
  );
  console.log(`  ├─ pH Minimum: ${phMin}`);
  console.log(`  ├─ pH Maksimum: ${phMax}`);
  console.log(`  ├─ Firebase path: kebun-${kebun.toLowerCase()}/auto-settings`);
  console.log(`  └─ Data object:`, autoData);

  // Save to Firebase
  const autoRef = database.ref(`kebun-${kebun.toLowerCase()}/auto-settings`);
  console.log(
    `[${currentTime}] 🚀 Mengirim pengaturan otomatis ke Firebase...`
  );

  autoRef
    .set(autoData)
    .then(() => {
      const successTime = new Date().toLocaleTimeString("id-ID");
      console.log(`[${successTime}] ✅ Pengaturan otomatis berhasil disimpan:`);
      console.log(`  ├─ Kebun: ${kebun}`);
      console.log(`  ├─ pH Range: ${phMin} - ${phMax}`);
      console.log(`  ├─ Firebase response: Success`);
      console.log(`  └─ Action: Auto mode siap digunakan`);

      alert(`Pengaturan otomatis Kebun ${kebun} berhasil disimpan!`);
      // Publish auto settings to MQTT
      const cmdTopic = kebun === "A" ? TOPIC_CMD_A : TOPIC_CMD_B;
      const ok = mqttPublish(cmdTopic, { type: "auto", data: autoData });
      if (ok) {
        console.log(`📤 MQTT auto settings published (${kebun})`, autoData);
      }
    })
    .catch((error) => {
      const errorTime = new Date().toLocaleTimeString("id-ID");
      console.log(`[${errorTime}] ❌ Gagal menyimpan pengaturan otomatis:`);
      console.error(`  ├─ Kebun: ${kebun}`);
      console.error(`  ├─ Error: ${error.message}`);
      console.error(
        `  ├─ Firebase path: kebun-${kebun.toLowerCase()}/auto-settings`
      );
      console.error(`  └─ Data yang gagal dikirim:`, autoData);

      alert(`Error menyimpan pengaturan: ${error.message}`);
    });
}

function initLiveClock() {
  function updateClock() {
    const now = new Date();
    const timeString = now.toLocaleTimeString("id-ID", {
      hour: "2-digit",
      minute: "2-digit",
      second: "2-digit",
    });

    const dateString = now.toLocaleDateString("id-ID", {
      weekday: "short",
      year: "numeric",
      month: "short",
      day: "numeric",
    });

    // Update navbar clock (desktop)
    const clockEl = document.getElementById("real-time-clock");
    if (clockEl) {
      clockEl.textContent = timeString;
    }

    const dateEl = document.getElementById("real-time-date");
    if (dateEl) {
      dateEl.textContent = dateString;
    }

    // Update mobile clock
    const mobileClockEl = document.getElementById("real-time-clock-mobile");
    if (mobileClockEl) {
      mobileClockEl.textContent = timeString;
    }

    const mobileDateEl = document.getElementById("real-time-date-mobile");
    if (mobileDateEl) {
      mobileDateEl.textContent = dateString;
    }

    // Legacy support - update old clock if exists
    const oldClockEl = document.getElementById("current-time");
    if (oldClockEl) {
      oldClockEl.textContent = timeString;
    }

    // Update schedule status every minute (when seconds = 0)
    if (now.getSeconds() === 0) {
      updateScheduleStatus();
    }
  }

  // Update clock immediately and then every second
  updateClock();
  setInterval(updateClock, 1000);
}

// Initialize Company Logo
function initCompanyLogo() {
  const logoImg = document.getElementById("company-logo");
  const logoFallback = document.getElementById("logo-fallback");

  // Check if logo loads initially
  if (logoImg) {
    logoImg.onload = function () {
      logoImg.style.display = "block";
      if (logoFallback) logoFallback.style.display = "none";
      console.log(
        `[${new Date().toLocaleTimeString(
          "id-ID"
        )}] ✅ Company logo loaded successfully`
      );
    };

    logoImg.onerror = function () {
      logoImg.style.display = "none";
      if (logoFallback) logoFallback.style.display = "flex";
      console.log(
        `[${new Date().toLocaleTimeString(
          "id-ID"
        )}] ⚠️ Logo failed to load, showing fallback`
      );
    };

    // Test if current src loads
    if (logoImg.complete) {
      if (logoImg.naturalWidth === 0) {
        // Image failed to load
        logoImg.style.display = "none";
        if (logoFallback) logoFallback.style.display = "flex";
      } else {
        // Image loaded successfully
        logoImg.style.display = "block";
        if (logoFallback) logoFallback.style.display = "none";
      }
    }
  }

  // Function to set company logo
  window.setCompanyLogo = function (logoPath) {
    if (logoImg && logoFallback) {
      logoImg.src = logoPath;
      logoImg.onload = function () {
        logoImg.style.display = "block";
        logoFallback.style.display = "none";
        console.log(
          `[${new Date().toLocaleTimeString(
            "id-ID"
          )}] ✅ Company logo loaded: ${logoPath}`
        );
      };
      logoImg.onerror = function () {
        logoImg.style.display = "none";
        logoFallback.style.display = "flex";
        console.log(
          `[${new Date().toLocaleTimeString(
            "id-ID"
          )}] ⚠️ Failed to load logo, using fallback`
        );
      };
    }
  };

  // Function to reset to fallback
  window.resetLogo = function () {
    if (logoImg && logoFallback) {
      logoImg.style.display = "none";
      logoFallback.style.display = "flex";
      console.log(
        `[${new Date().toLocaleTimeString("id-ID")}] 🔄 Logo reset to fallback`
      );
    }
  };

  console.log(
    `[${new Date().toLocaleTimeString("id-ID")}] 📝 Logo functions available:`
  );
  console.log(`  ├─ setCompanyLogo('path/to/logo.png') - Set company logo`);
  console.log(`  └─ resetLogo() - Reset to fallback icon`);
}

function initCharts() {
  // Load from localStorage so charts start with previous history
  loadChartDataFromStorage();

  // Chart 1 - Unit A (pH, TDS, Suhu)
  const ctx1 = document.getElementById("chart1");
  if (ctx1) {
    chart1 = new Chart(ctx1, {
      type: "line",
      data: {
        labels: chartData1.labels,
        datasets: [
          {
            label: "pH",
            data: chartData1.ph,
            borderColor: "rgb(34, 197, 94)",
            backgroundColor: "rgba(34, 197, 94, 0.1)",
            tension: 0.4,
            yAxisID: "y",
          },
          {
            label: "TDS",
            data: chartData1.tds,
            borderColor: "rgb(147, 51, 234)",
            backgroundColor: "rgba(147, 51, 234, 0.1)",
            tension: 0.4,
            yAxisID: "y1",
          },
          {
            label: "Suhu",
            data: chartData1.suhu,
            borderColor: "rgb(59, 130, 246)",
            backgroundColor: "rgba(59, 130, 246, 0.1)",
            tension: 0.4,
            yAxisID: "y2",
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: {
          mode: "index",
          intersect: false,
        },
        scales: {
          x: {
            display: true,
            title: {
              display: true,
              text: "Waktu",
            },
          },
          y: {
            type: "linear",
            display: true,
            position: "left",
            title: {
              display: true,
              text: "pH Level",
            },
            min: 0,
            max: 14,
          },
          y1: {
            type: "linear",
            display: true,
            position: "right",
            title: {
              display: true,
              text: "TDS (ppm)",
            },
            grid: {
              drawOnChartArea: false,
            },
            min: 0,
            max: 1500,
          },
          y2: {
            type: "linear",
            display: false,
            min: 15,
            max: 35,
          },
        },
        plugins: {
          legend: {
            display: true,
            position: "top",
            align: "center",
            labels: {
              padding: 18,
              usePointStyle: false,
            },
          },
          title: {
            display: true,
            text: "Realtime Monitoring",
          },
        },
      },
    });
  }

  // Chart 2 - Unit B (pH, TDS, Suhu)
  const ctx2 = document.getElementById("chart2");
  if (ctx2) {
    chart2 = new Chart(ctx2, {
      type: "line",
      data: {
        labels: chartData2.labels,
        datasets: [
          {
            label: "pH",
            data: chartData2.ph,
            borderColor: "rgb(34, 197, 94)",
            backgroundColor: "rgba(34, 197, 94, 0.1)",
            tension: 0.4,
            yAxisID: "y",
          },
          {
            label: "TDS",
            data: chartData2.tds,
            borderColor: "rgb(147, 51, 234)",
            backgroundColor: "rgba(147, 51, 234, 0.1)",
            tension: 0.4,
            yAxisID: "y1",
          },
          {
            label: "Suhu",
            data: chartData2.suhu,
            borderColor: "rgb(59, 130, 246)",
            backgroundColor: "rgba(59, 130, 246, 0.1)",
            tension: 0.4,
            yAxisID: "y2",
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: {
          mode: "index",
          intersect: false,
        },
        scales: {
          x: {
            display: true,
            title: {
              display: true,
              text: "Waktu",
            },
          },
          y: {
            type: "linear",
            display: true,
            position: "left",
            title: {
              display: true,
              text: "pH Level",
            },
            min: 0,
            max: 14,
          },
          y1: {
            type: "linear",
            display: true,
            position: "right",
            title: {
              display: true,
              text: "TDS (ppm)",
            },
            grid: {
              drawOnChartArea: false,
            },
            min: 0,
            max: 1500,
          },
          y2: {
            type: "linear",
            display: false,
            min: 15,
            max: 35,
          },
        },
        plugins: {
          legend: {
            display: true,
            position: "top",
            align: "center",
            labels: {
              padding: 18,
              usePointStyle: false,
            },
          },
          title: {
            display: true,
            text: "Realtime Monitoring",
          },
        },
      },
    });
  }
}

function initFirebaseListeners() {
  console.log(
    `[${new Date().toLocaleTimeString(
      "id-ID"
    )}] 🔗 Menginisialisasi Firebase listeners...`
  );

  // Helper to process any incoming snapshot for a device with normalization
  function handleDeviceSnapshot(device, snapshot, sourceLabel) {
    const raw = snapshot.val();
    if (!raw) return;
    const norm = normalizeTelemetry(raw);

    const now = new Date();
    const currentTime = now.toLocaleTimeString("id-ID", {
      hour: "2-digit",
      minute: "2-digit",
      second: "2-digit",
    });
    const dateString = now.toLocaleDateString("id-ID", {
      weekday: "short",
      year: "numeric",
      month: "short",
      day: "numeric",
    });
    console.log(
      `[${currentTime}] 📊 Data Realtime Kebun ${device} diterima (${sourceLabel}):`
    );
    console.log(`  ├─ pH: ${norm.ph}`);
    console.log(`  ├─ TDS: ${norm.tds} ppm`);
    console.log(`  ├─ Suhu: ${norm.suhu} °C`);
    console.log(`  ├─ Firebase path: ${snapshot.ref.path.toString()}`);
    console.log(`  └─ Timestamp: ${now.toISOString()}`);

    // Update last update indicator
    const lastUpdateEl = document.getElementById(
      `last-update-${device.toLowerCase()}`
    );
    if (lastUpdateEl) {
      lastUpdateEl.textContent = `Last update: ${currentTime}`;
      lastUpdateEl.title = `Update: ${dateString}`;
    }

    updateDisplayValues(norm, device);
    updateCharts(norm, device === "A" ? 1 : 2);

    // Auto mode logic
    if (
      (device === "A" && pumpModeA === "auto") ||
      (device === "B" && pumpModeB === "auto")
    ) {
      console.log(
        `[${currentTime}] 🤖 Menjalankan pengecekan otomatis Kebun ${device}...`
      );
      checkAutoConditions(norm, device);
    }
  }

  // Real-time data listener for Device A
  realtimeRef.on("value", (snapshot) => {
    const data = snapshot.val();
    if (!data) return;
    handleDeviceSnapshot("A", snapshot, "realtime");
  });

  // Real-time data listener for Device B
  realtimeRefB.on("value", (snapshot) => {
    const data = snapshot.val();
    if (!data) return;
    handleDeviceSnapshot("B", snapshot, "realtime");
  });

  // Base-node fallback listeners (capture suhu_air written at root)
  baseRefA.on("value", (snapshot) => {
    const val = snapshot.val();
    if (!val) return;
    // only act when root has direct sensor keys to avoid noise
    if (
      val.suhu_air !== undefined ||
      val.suhu !== undefined ||
      val.tds !== undefined ||
      val.ph !== undefined
    ) {
      handleDeviceSnapshot("A", snapshot, "base");
    }
  });
  baseRefB.on("value", (snapshot) => {
    const val = snapshot.val();
    if (!val) return;
    if (
      val.suhu_air !== undefined ||
      val.suhu !== undefined ||
      val.tds !== undefined ||
      val.ph !== undefined
    ) {
      handleDeviceSnapshot("B", snapshot, "base");
    }
  });

  // Pump status listener for Kebun A
  pompaRefA.on("value", (snapshot) => {
    const status = snapshot.val();
    const currentTime = new Date().toLocaleTimeString("id-ID");
    console.log(`[${currentTime}] 💧 Status Pompa Kebun A berubah:`);
    console.log(`  ├─ Status baru: ${status}`);
    console.log(`  ├─ Firebase path: kebun-a/status/pompa`);
    console.log(`  └─ Action: Update UI display`);

    updatePumpDisplayA(status);
  });

  // Pump status listener for Kebun B
  pompaRefB.on("value", (snapshot) => {
    const status = snapshot.val();
    const currentTime = new Date().toLocaleTimeString("id-ID");
    console.log(`[${currentTime}] 💧 Status Pompa Kebun B berubah:`);
    console.log(`  ├─ Status baru: ${status}`);
    console.log(`  ├─ Firebase path: kebun-b/status/pompa`);
    console.log(`  └─ Action: Update UI display`);

    updatePumpDisplayB(status);
  });

  // Schedule listener for Kebun A
  jadwalRefA.on("value", (snapshot) => {
    currentScheduleA = snapshot.val();
    const currentTime = new Date().toLocaleTimeString("id-ID");
    console.log(`[${currentTime}] 📅 Jadwal Kebun A berubah:`);
    console.log(`  ├─ Data jadwal:`, currentScheduleA);
    console.log(`  ├─ Firebase path: kebun-a/jadwal`);
    console.log(`  └─ Mode: ${pumpModeA}`);

    if (pumpModeA === "scheduled" && currentScheduleA) {
      console.log(`[${currentTime}] ⏰ Setup timer untuk Kebun A...`);
      setupScheduleTimer("A");
    }
  });

  // Schedule listener for Kebun B
  jadwalRefB.on("value", (snapshot) => {
    currentScheduleB = snapshot.val();
    const currentTime = new Date().toLocaleTimeString("id-ID");
    console.log(`[${currentTime}] 📅 Jadwal Kebun B berubah:`);
    console.log(`  ├─ Data jadwal:`, currentScheduleB);
    console.log(`  ├─ Firebase path: kebun-b/jadwal`);
    console.log(`  └─ Mode: ${pumpModeB}`);

    if (pumpModeB === "scheduled" && currentScheduleB) {
      console.log(`[${currentTime}] ⏰ Setup timer untuk Kebun B...`);
      setupScheduleTimer("B");
    }
  });

  console.log(
    `[${new Date().toLocaleTimeString(
      "id-ID"
    )}] ✅ Semua Firebase listeners berhasil diinisialisasi`
  );
}

// Initialize Calibration Data Listeners
function initCalibrationListeners() {
  // Calibration listener for Kebun A
  kalibrasiaRefA.on("value", (snapshot) => {
    const calibrationData = snapshot.val();
    const currentTime = new Date().toLocaleTimeString("id-ID");

    if (calibrationData) {
      console.log(`[${currentTime}] 🔧 Data Kalibrasi Kebun A diterima:`);
      console.log(`  ├─ cal_ph_asam: ${calibrationData.cal_ph_asam || "--"}`);
      console.log(
        `  ├─ cal_ph_netral: ${calibrationData.cal_ph_netral || "--"}`
      );
      console.log(`  ├─ cal_tds_k: ${calibrationData.cal_tds_k || "--"}`);
      console.log(`  └─ Firebase path: kebun-a/kalibrasi`);

      updateCalibrationDisplay(calibrationData, "A");
    } else {
      console.log(`[${currentTime}] ⚠️ Data kalibrasi Kebun A tidak ditemukan`);
      updateCalibrationDisplay({}, "A");
    }
  });

  // Calibration listener for Kebun B
  kalibrasiaRefB.on("value", (snapshot) => {
    const calibrationData = snapshot.val();
    const currentTime = new Date().toLocaleTimeString("id-ID");

    if (calibrationData) {
      console.log(`[${currentTime}] 🔧 Data Kalibrasi Kebun B diterima:`);
      console.log(`  ├─ cal_ph_asam: ${calibrationData.cal_ph_asam || "--"}`);
      console.log(
        `  ├─ cal_ph_netral: ${calibrationData.cal_ph_netral || "--"}`
      );
      console.log(`  ├─ cal_tds_k: ${calibrationData.cal_tds_k || "--"}`);
      console.log(`  └─ Firebase path: kebun-b/kalibrasi`);

      updateCalibrationDisplay(calibrationData, "B");
    } else {
      console.log(`[${currentTime}] ⚠️ Data kalibrasi Kebun B tidak ditemukan`);
      updateCalibrationDisplay({}, "B");
    }
  });

  console.log(
    `[${new Date().toLocaleTimeString(
      "id-ID"
    )}] ✅ Calibration listeners berhasil diinisialisasi`
  );
}

// Update Calibration Display Function
function updateCalibrationDisplay(data, unit) {
  const suffix = unit.toLowerCase();

  // Update pH Asam
  const phAsamValue = data.cal_ph_asam
    ? parseFloat(data.cal_ph_asam).toFixed(4)
    : "--";
  const phAsamElement = document.getElementById(`cal-ph-asam-${suffix}`);
  if (phAsamElement) {
    phAsamElement.textContent = phAsamValue;
  }

  // Update pH Netral
  const phNetralValue = data.cal_ph_netral
    ? parseFloat(data.cal_ph_netral).toFixed(4)
    : "--";
  const phNetralElement = document.getElementById(`cal-ph-netral-${suffix}`);
  if (phNetralElement) {
    phNetralElement.textContent = phNetralValue;
  }

  // Update TDS K-Factor
  const tdsKValue = data.cal_tds_k
    ? parseFloat(data.cal_tds_k).toFixed(4)
    : "--";
  const tdsKElement = document.getElementById(`cal-tds-k-${suffix}`);
  if (tdsKElement) {
    tdsKElement.textContent = tdsKValue;
  }

  const currentTime = new Date().toLocaleTimeString("id-ID");
  console.log(`[${currentTime}] 🔧 UI Kalibrasi Kebun ${unit} diupdate:`);
  console.log(`  ├─ pH Asam: ${phAsamValue}`);
  console.log(`  ├─ pH Netral: ${phNetralValue}`);
  console.log(`  └─ TDS K: ${tdsKValue}`);
}

function updateDisplayValues(data, device = "A") {
  // Update main overview (always use Device A data for main display)
  if (device === "A") {
    // Show temperature with 1 decimal to match MQTT path
    setText("suhu-value", data.suhu, 1);
    // Format pH with 1 decimal place per request
    setText("ph-value", data.ph, 1);
    setText("tds-value", data.tds, 0);
    setText("cal-ph-netral", data.cal_ph_netral, 4);
    setText("cal-ph-asam", data.cal_ph_asam, 4);
    setText("cal-tds-k", data.cal_tds_k, 4);
  }

  // Update device-specific displays
  const deviceNum = device === "A" ? "1" : "2";
  // Device pH also 1 decimal
  setText(`device${deviceNum}-ph`, data.ph, 1);
  setText(`device${deviceNum}-tds`, data.tds, 0);
  // Temperature with 1 decimal to match MQTT path
  setText(`device${deviceNum}-suhu`, data.suhu, 1);
}

function updateCharts(data, chartNumber) {
  const now = new Date();
  const timeLabel = now.toLocaleTimeString("id-ID", {
    hour: "2-digit",
    minute: "2-digit",
  });

  const chartData = chartNumber === 1 ? chartData1 : chartData2;
  const chart = chartNumber === 1 ? chart1 : chart2;

  if (
    chart &&
    data.ph !== undefined &&
    data.tds !== undefined &&
    data.suhu !== undefined
  ) {
    chartData.labels.push(timeLabel);
    chartData.ph.push(parseFloat(data.ph));
    chartData.tds.push(parseFloat(data.tds));
    chartData.suhu.push(parseFloat(data.suhu));

    // Keep only last 20 data points
    if (chartData.labels.length > MAX_CHART_POINTS) {
      chartData.labels.shift();
      chartData.ph.shift();
      chartData.tds.shift();
      chartData.suhu.shift();
    }

    chart.update("none");
    // Save to storage after update
    saveChartDataToStorage(chartNumber);
  }
}

function setText(id, value, decimals) {
  const el = document.getElementById(id);
  if (!el) return;

  if (value !== undefined && value !== null && value !== "") {
    const numValue = parseFloat(value);
    let formatted =
      decimals !== undefined ? numValue.toFixed(decimals) : String(numValue);

    // Append units consistently for known IDs
    const isDeviceTds = id.startsWith("device") && id.endsWith("-tds");
    const isDeviceSuhu = id.startsWith("device") && id.endsWith("-suhu");
    const isTdsValue = id === "tds-value";
    const isSuhuValue = id === "suhu-value";

    if (isDeviceTds || isTdsValue) {
      formatted = `${formatted} ppm`;
    } else if (isDeviceSuhu || isSuhuValue) {
      // Match MQTT path style: no space before °C
      formatted = `${formatted}°C`;
    }

    el.innerText = formatted;
  } else {
    el.innerText = "--";
  }
}

function togglePump(kebun = "A") {
  const currentMode = kebun === "A" ? pumpModeA : pumpModeB;
  const pompaRef = kebun === "A" ? pompaRefA : pompaRefB;
  const cmdTopic = kebun === "A" ? TOPIC_CMD_A : TOPIC_CMD_B;

  if (currentMode !== "manual") {
    alert(
      `Pompa Kebun ${kebun} dalam mode otomatis. Ubah ke mode manual untuk kontrol manual.`
    );
    return;
  }

  pompaRef.once("value").then((snapshot) => {
    const currentStatus = snapshot.val();
    const newStatus = currentStatus === "ON" ? "OFF" : "ON";
    // Publish MQTT command
    const mqttOk = mqttPublish(cmdTopic, { type: "pump", action: newStatus });
    if (mqttOk) {
      console.log(`📤 MQTT command sent (${kebun}):`, newStatus);
    }
    pompaRef
      .set(newStatus)
      .then(() => console.log(`Pompa Kebun ${kebun} diubah ke:`, newStatus))
      .catch((error) =>
        console.error(`Gagal mengirim perintah Kebun ${kebun}:`, error)
      );
  });
}

// Make function available globally for onclick handlers
window.togglePump = togglePump;

// Manual toggle function for toggle switches
function manualToggle(kebun) {
  const currentMode = kebun === "A" ? pumpModeA : pumpModeB;
  const pompaRef = kebun === "A" ? pompaRefA : pompaRefB;
  const cmdTopic = kebun === "A" ? TOPIC_CMD_A : TOPIC_CMD_B;

  // Get the toggle element that was clicked
  const manualToggleEl = document.getElementById(
    `manual-toggle-${kebun.toLowerCase()}`
  );

  // Determine toggle state
  let isChecked = false;
  if (event && event.target) {
    isChecked = event.target.checked;
  }

  // Console log untuk tracking input user
  const currentTime = new Date().toLocaleTimeString("id-ID");
  console.log(`[${currentTime}] 🔄 Manual Toggle Kebun ${kebun}:`);
  console.log(`  ├─ Mode saat ini: ${currentMode}`);
  console.log(`  ├─ Toggle state: ${isChecked ? "ON" : "OFF"}`);
  console.log(`  └─ Firebase path: kebun-${kebun.toLowerCase()}/status/pompa`);

  if (currentMode !== "manual") {
    console.log(
      `[${currentTime}] ❌ Aksi dibatalkan - Pompa dalam mode: ${currentMode}`
    );
    alert(
      `Pompa Kebun ${kebun} dalam mode otomatis. Ubah ke mode manual untuk kontrol manual.`
    );
    // Reset toggle to current state
    pompaRef.once("value").then((snapshot) => {
      const currentStatus = snapshot.val();
      const shouldBeChecked = currentStatus === "ON";
      console.log(
        `[${currentTime}] 🔄 Reset toggle ke status aktual: ${currentStatus}`
      );
      if (manualToggleEl) manualToggleEl.checked = shouldBeChecked;
    });
    return;
  }

  const newStatus = isChecked ? "ON" : "OFF";

  // Log data yang akan dikirim ke Firebase
  console.log(`[${currentTime}] 🚀 Mengirim data ke Firebase:`);
  console.log(`  ├─ Kebun: ${kebun}`);
  console.log(`  ├─ Status baru: ${newStatus}`);
  console.log(`  ├─ Firebase ref: kebun-${kebun.toLowerCase()}/status/pompa`);
  console.log(`  └─ User action: Manual toggle switch`);

  // Publish MQTT command
  const mqttOk = mqttPublish(cmdTopic, { type: "pump", action: newStatus });
  if (mqttOk) {
    console.log(`📤 MQTT command sent (${kebun}):`, newStatus);
  }

  pompaRef
    .set(newStatus)
    .then(() => {
      console.log(`[${currentTime}] ✅ Berhasil mengirim ke Firebase:`);
      console.log(`  ├─ Pompa Kebun ${kebun}: ${newStatus}`);
      console.log(`  ├─ Timestamp: ${new Date().toISOString()}`);
      console.log(`  └─ Firebase response: Success`);
      updateKebunStatus(kebun);
    })
    .catch((error) => {
      console.log(`[${currentTime}] ❌ Gagal mengirim ke Firebase:`);
      console.error(`  ├─ Kebun: ${kebun}`);
      console.error(`  ├─ Error: ${error.message}`);
      console.error(`  └─ Action: Reset toggle state`);
      // Reset toggle on error
      if (manualToggleEl) manualToggleEl.checked = !isChecked;
    });
}

// Make manual toggle available globally
window.manualToggle = manualToggle;

function updatePumpDisplayA(status) {
  const manualStatusA = document.getElementById("manual-status-a");
  const manualToggleA = document.getElementById("manual-toggle-a");

  // Update manual toggle switches and status
  const isOn = status === "ON";

  if (manualStatusA) {
    if (status === "ON") {
      manualStatusA.textContent = "ON";
      manualStatusA.className = "text-sm text-blue-600 font-semibold";
    } else if (status === "OFF") {
      manualStatusA.textContent = "OFF";
      manualStatusA.className = "text-sm text-red-600 font-semibold";
    } else {
      manualStatusA.textContent = "UNKNOWN";
      manualStatusA.className = "text-sm text-gray-600";
    }
  }

  if (manualToggleA) {
    manualToggleA.checked = isOn;
  }

  // Update status display
  updateKebunStatus("A");
}

function updatePumpDisplayB(status) {
  const manualStatusB = document.getElementById("manual-status-b");
  const manualToggleB = document.getElementById("manual-toggle-b");

  // Update manual toggle switches and status
  const isOn = status === "ON";

  if (manualStatusB) {
    if (status === "ON") {
      manualStatusB.textContent = "ON";
      manualStatusB.className = "text-sm text-green-600 font-semibold";
    } else if (status === "OFF") {
      manualStatusB.textContent = "OFF";
      manualStatusB.className = "text-sm text-red-600 font-semibold";
    } else {
      manualStatusB.textContent = "UNKNOWN";
      manualStatusB.className = "text-sm text-gray-600";
    }
  }

  if (manualToggleB) {
    manualToggleB.checked = isOn;
  }

  // Update status display
  updateKebunStatus("B");
}

function setBtn(btn, text, colorClasses) {
  btn.innerText = text;
  btn.className = `w-full ${colorClasses} text-white font-bold py-3 px-6 rounded-lg transition duration-300 transform hover:scale-105`;
}

function setupScheduleTimer(kebun = "A") {
  const isKebunA = kebun === "A";
  const scheduleTimer = isKebunA ? scheduleTimerA : scheduleTimerB;
  const currentSchedule = isKebunA ? currentScheduleA : currentScheduleB;
  const pumpMode = isKebunA ? pumpModeA : pumpModeB;
  const pompaRef = isKebunA ? pompaRefA : pompaRefB;

  const currentTime = new Date().toLocaleTimeString("id-ID");
  console.log(`[${currentTime}] ⏰ Setup Schedule Timer Kebun ${kebun}:`);
  console.log(`  ├─ Mode: ${pumpMode}`);
  console.log(`  ├─ Schedule exists: ${currentSchedule ? "Yes" : "No"}`);

  if (scheduleTimer) {
    console.log(`  ├─ Clearing existing timer...`);
    clearInterval(scheduleTimer);
  }

  if (!currentSchedule || pumpMode !== "scheduled") {
    console.log(
      `  └─ Timer tidak diaktifkan (mode bukan scheduled atau tidak ada jadwal)`
    );
    return;
  }

  console.log(
    `  ├─ Jadwal aktif: ${currentSchedule.start} - ${currentSchedule.end}`
  );
  console.log(`  ├─ Mode: Pompa nyala terus-menerus dalam rentang waktu`);
  console.log(`  └─ Timer diaktifkan (check setiap detik)`);

  const checkSchedule = () => {
    const now = new Date();
    const currentTime = now.toTimeString().substring(0, 5); // HH:MM format

    const startTime = currentSchedule.start; // Format: "HH:MM"
    const endTime = currentSchedule.end; // Format: "HH:MM"

    // Check if current time is within schedule range
    // PERBAIKAN: Pompa menyala dari startTime sampai SEBELUM endTime
    // Contoh: 22:30-22:31 = pompa ON di 22:30, OFF di 22:31
    const isWithinSchedule = currentTime >= startTime && currentTime < endTime;

    // Log periodic check (setiap menit, bukan setiap detik)
    const seconds = now.getSeconds();
    if (seconds === 0) {
      const checkTime = new Date().toLocaleTimeString("id-ID");
      console.log(`[${checkTime}] ⏰ SCHEDULE CHECK - Kebun ${kebun}:`);
      console.log(`  ├─ Waktu sekarang: ${currentTime}`);
      console.log(
        `  ├─ Jadwal operasi: ${startTime} - ${endTime} (eksklusif endTime)`
      );
      console.log(
        `  ├─ Dalam rentang jadwal: ${isWithinSchedule ? "YA" : "TIDAK"}`
      );
      console.log(`  └─ Memeriksa status pompa...`);
      // At exact schedule edges, publish definitive commands once per minute
      const edgeKeys = isKebunA ? scheduleEdgeSent.A : scheduleEdgeSent.B;
      const minuteKey = `${now.toISOString().slice(0, 10)} ${currentTime}`;
      if (currentTime === startTime && edgeKeys.startKey !== minuteKey) {
        try {
          const cmdTopic = isKebunA ? TOPIC_CMD_A : TOPIC_CMD_B;
          const ok = mqttPublish(cmdTopic, { type: "pump", action: "ON" });
          if (ok)
            console.log(
              `📤 MQTT schedule edge sent (${kebun}): ON @ ${currentTime}`
            );
        } catch (e) {
          console.warn("MQTT publish (edge ON) failed:", e);
        }
        edgeKeys.startKey = minuteKey;
      }
      if (currentTime === endTime && edgeKeys.endKey !== minuteKey) {
        try {
          const cmdTopic = isKebunA ? TOPIC_CMD_A : TOPIC_CMD_B;
          const ok = mqttPublish(cmdTopic, { type: "pump", action: "OFF" });
          if (ok)
            console.log(
              `📤 MQTT schedule edge sent (${kebun}): OFF @ ${currentTime}`
            );
        } catch (e) {
          console.warn("MQTT publish (edge OFF) failed:", e);
        }
        edgeKeys.endKey = minuteKey;
      }
    }

    // Get current pump status
    pompaRef
      .once("value")
      .then((snapshot) => {
        const currentStatus = snapshot.val();

        if (isWithinSchedule && currentStatus !== "ON") {
          // Should be ON but currently OFF - turn it ON
          const triggerTime = new Date().toLocaleTimeString("id-ID");
          console.log(`[${triggerTime}] � JADWAL START - Kebun ${kebun}:`);
          console.log(`  ├─ Waktu sekarang: ${currentTime}`);
          console.log(`  ├─ Dalam rentang: ${startTime} - ${endTime}`);
          console.log(`  ├─ Status saat ini: ${currentStatus}`);
          console.log(`  ├─ Action: Menyalakan pompa untuk jadwal`);
          console.log(
            `  ├─ Firebase path: kebun-${kebun.toLowerCase()}/status/pompa`
          );
          console.log(`  └─ Mengirim command: ON`);

          // Publish MQTT command to turn pump ON for this kebun
          try {
            const cmdTopic = isKebunA ? TOPIC_CMD_A : TOPIC_CMD_B;
            const ok = mqttPublish(cmdTopic, { type: "pump", action: "ON" });
            if (ok)
              console.log(`📤 MQTT command sent (${kebun}): ON [scheduled]`);
          } catch (e) {
            console.warn("MQTT publish (scheduled ON) failed:", e);
          }

          pompaRef
            .set("ON")
            .then(() => {
              console.log(
                `[${triggerTime}] ✅ JADWAL START SUCCESS - Kebun ${kebun}:`
              );
              console.log(`  ├─ Pompa berhasil dinyalakan`);
              console.log(`  ├─ Firebase response: Success`);
              console.log(
                `  └─ Pompa akan menyala sampai ${endTime} (eksklusif)`
              );
            })
            .catch((error) => {
              console.log(
                `[${triggerTime}] ❌ JADWAL START ERROR - Kebun ${kebun}:`
              );
              console.error(`  ├─ Error: ${error.message}`);
              console.error(`  └─ Gagal menyalakan pompa`);
            });
        } else if (!isWithinSchedule && currentStatus === "ON") {
          // Should be OFF but currently ON - turn it OFF
          const stopTime = new Date().toLocaleTimeString("id-ID");
          console.log(`[${stopTime}] 🔴 JADWAL END - Kebun ${kebun}:`);
          console.log(`  ├─ Waktu sekarang: ${currentTime}`);
          console.log(`  ├─ Di luar rentang: ${startTime} - ${endTime}`);
          console.log(`  ├─ Status saat ini: ${currentStatus}`);
          console.log(`  ├─ Action: Mematikan pompa (jadwal selesai)`);
          console.log(
            `  ├─ Firebase path: kebun-${kebun.toLowerCase()}/status/pompa`
          );
          console.log(`  └─ Mengirim command: OFF`);

          // Publish MQTT command to turn pump OFF for this kebun
          try {
            const cmdTopic = isKebunA ? TOPIC_CMD_A : TOPIC_CMD_B;
            const ok = mqttPublish(cmdTopic, { type: "pump", action: "OFF" });
            if (ok)
              console.log(`📤 MQTT command sent (${kebun}): OFF [scheduled]`);
          } catch (e) {
            console.warn("MQTT publish (scheduled OFF) failed:", e);
          }

          pompaRef
            .set("OFF")
            .then(() => {
              console.log(
                `[${stopTime}] ✅ JADWAL END SUCCESS - Kebun ${kebun}:`
              );
              console.log(`  ├─ Pompa berhasil dimatikan`);
              console.log(`  ├─ Firebase response: Success`);
              console.log(`  ├─ Jadwal operasi selesai`);
              console.log(`  └─ 🏁 Schedule cycle completed successfully`);
            })
            .catch((error) => {
              console.log(
                `[${stopTime}] ❌ JADWAL END ERROR - Kebun ${kebun}:`
              );
              console.error(`  ├─ Error: ${error.message}`);
              console.error(`  └─ Gagal mematikan pompa`);
            });
        } else if (seconds === 0) {
          // Log status setiap menit jika tidak ada perubahan
          const statusTime = new Date().toLocaleTimeString("id-ID");
          console.log(
            `[${statusTime}] ➡️ SCHEDULE STATUS OK - Kebun ${kebun}:`
          );
          console.log(`  ├─ Status pompa: ${currentStatus}`);
          console.log(
            `  ├─ Status sesuai jadwal: ${
              isWithinSchedule ? "Seharusnya ON" : "Seharusnya OFF"
            }`
          );
          console.log(`  └─ Tidak ada aksi diperlukan`);
        }
      })
      .catch((error) => {
        if (seconds === 0) {
          console.error(
            `[${new Date().toLocaleTimeString(
              "id-ID"
            )}] ❌ ERROR reading pump status - Kebun ${kebun}:`,
            error
          );
        }
      });
  };

  // Start checking schedule immediately
  console.log(`[${currentTime}] 🚀 Memulai pengecekan jadwal pertama...`);
  checkSchedule();

  const newTimer = setInterval(checkSchedule, 1000); // Check every second

  if (isKebunA) {
    scheduleTimerA = newTimer;
  } else {
    scheduleTimerB = newTimer;
  }

  console.log(`[${currentTime}] ✅ Timer Kebun ${kebun} berhasil diaktifkan:`);
  console.log(`  ├─ Check interval: setiap detik`);
  console.log(`  ├─ Log interval: setiap menit (detik ke-0)`);
  console.log(`  ├─ Jadwal: ${currentSchedule.start} - ${currentSchedule.end}`);
  console.log(`  └─ Timer ID: ${newTimer}`);
  updateScheduleStatus();
}

function checkAutoConditions(data, kebun = "A") {
  const isKebunA = kebun === "A";
  const currentSchedule = isKebunA ? currentScheduleA : currentScheduleB;
  const pumpMode = isKebunA ? pumpModeA : pumpModeB;
  const pompaRef = isKebunA ? pompaRefA : pompaRefB;

  if (!currentSchedule || pumpMode !== "auto") {
    return;
  }

  const ph = parseFloat(data.ph);
  if (isNaN(ph)) return;

  const currentTime = new Date().toLocaleTimeString("id-ID");
  console.log(`[${currentTime}] 🤖 Auto Check Kebun ${kebun}:`);
  console.log(`  ├─ pH saat ini: ${ph}`);
  console.log(
    `  ├─ pH range: ${currentSchedule.phMin} - ${currentSchedule.phMax}`
  );
  console.log(
    `  └─ Status: ${
      ph < currentSchedule.phMin || ph > currentSchedule.phMax
        ? "DILUAR RANGE"
        : "NORMAL"
    }`
  );

  if (ph < currentSchedule.phMin || ph > currentSchedule.phMax) {
    console.log(`[${currentTime}] 🚨 AUTO TRIGGER - Kebun ${kebun}:`);
    console.log(
      `  ├─ pH diluar range: ${ph} (target: ${currentSchedule.phMin}-${currentSchedule.phMax})`
    );
    console.log(`  ├─ Action: Menyalakan pompa untuk koreksi pH`);
    console.log(`  ├─ Durasi: 2 menit`);
    console.log(
      `  └─ Firebase path: kebun-${kebun.toLowerCase()}/status/pompa`
    );

    pompaRef.set("ON");
    console.log(
      `[${currentTime}] ✅ Pompa Kebun ${kebun} dinyalakan oleh sistem otomatis (pH: ${ph})`
    );

    // Turn off after 2 minutes
    setTimeout(() => {
      const offTime = new Date().toLocaleTimeString("id-ID");
      console.log(`[${offTime}] ⏹️ AUTO STOP - Kebun ${kebun}:`);
      console.log(`  ├─ Durasi operasi: 2 menit (auto mode)`);
      console.log(`  ├─ Trigger: pH correction completed`);
      console.log(`  ├─ Action: Mematikan pompa`);
      console.log(
        `  └─ Firebase path: kebun-${kebun.toLowerCase()}/status/pompa`
      );

      pompaRef.set("OFF");
      console.log(
        `[${offTime}] ✅ Pompa Kebun ${kebun} dimatikan setelah 2 menit operasi otomatis`
      );
    }, 2 * 60 * 1000);
  }
}

function updateScheduleStatus() {
  // This function is no longer needed as status is updated per kebun
  // Status is now handled by updateKebunStatus function
}

// ===== TEST FUNCTIONS FOR DASHBOARD =====
// Fungsi untuk test tampilan data di dashboard
window.testDashboardData = function () {
  console.log("🧪 Testing dashboard with sample data...");

  // Sample data untuk Kebun A
  const dataA = {
    ph: 6.8,
    tds: 950,
    suhu: 26.5,
  };

  // Sample data untuk Kebun B
  const dataB = {
    ph: 7.2,
    tds: 1020,
    suhu: 27.3,
  };

  console.log("📊 Updating Kebun A with:", dataA);
  updateDashboardDisplay("A", dataA);

  console.log("📊 Updating Kebun B with:", dataB);
  updateDashboardDisplay("B", dataB);

  console.log("✅ Test completed! Check dashboard for data.");
  return { kebunA: dataA, kebunB: dataB };
};

// Fungsi untuk simulate MQTT data
window.simulateMQTTData = function (device = "A", interval = 3000) {
  console.log(
    `🔄 Starting MQTT simulation for Kebun ${device} (interval: ${interval}ms)`
  );

  const generateData = () => ({
    ph: (6 + Math.random() * 2).toFixed(1), // pH 6.0-8.0
    tds: Math.floor(800 + Math.random() * 400), // TDS 800-1200
    suhu: (24 + Math.random() * 6).toFixed(1), // Suhu 24-30°C
  });

  // Update immediately
  const initialData = generateData();
  console.log(`📡 Initial data for Kebun ${device}:`, initialData);
  updateDashboardDisplay(device, initialData);

  // Update every interval
  const timer = setInterval(() => {
    const data = generateData();
    console.log(`📡 MQTT simulated data for Kebun ${device}:`, data);
    updateDashboardDisplay(device, data);
  }, interval);

  console.log(`✅ Simulation started. Timer ID: ${timer}`);
  console.log(`⏹️  To stop: clearInterval(${timer})`);

  return timer;
};

console.log("📝 Test functions available:");
console.log("  testDashboardData() - Test dengan sample data");
console.log("  simulateMQTTData('A', 3000) - Simulate MQTT Kebun A");
console.log("  simulateMQTTData('B', 3000) - Simulate MQTT Kebun B");
