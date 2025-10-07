// Calibration page specific logic extracted from original kalibrasi.html
(function () {
  if (!window.firebase || !firebase.apps.length) {
    const firebaseConfig = {
      apiKey: "AIzaSyCFx2ZlJRGZfD-P6I84a53yc8D_cyFqvgs",
      authDomain: "hidroganik-monitoring.firebaseapp.com",
      databaseURL: "https://hidroganik-monitoring-default-rtdb.asia-southeast1.firebasedatabase.app",
      projectId: "hidroganik-monitoring",
      storageBucket: "hidroganik-monitoring.firebasestorage.app",
      messagingSenderId: "103705402081",
      appId: "1:103705402081:web:babc15ad263749e80535a0"
    };
    try {
      firebase.initializeApp(firebaseConfig);
    } catch (e) {
      console.warn("Firebase init skipped:", e.message);
    }
  }
  const db = firebase.database();

  const CMD_PATHS = { A: "kebun-a/commands", B: "kebun-b/commands" };
  function nowStr() {
    return new Date().toLocaleTimeString("id-ID");
  }
  function showToast(message, type = "info", timeout = 3500) {
    if (!window.Toastify) {
      console.log("[Toast]", type, message);
      return;
    }
    const bgMap = {
      success: "linear-gradient(to right,#16a34a,#22c55e)",
      error: "linear-gradient(to right,#dc2626,#ef4444)",
      warning: "linear-gradient(to right,#d97706,#f59e0b)",
      info: "linear-gradient(to right,#2563eb,#3b82f6)",
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
    if (!el) return;
    const p = document.createElement("div");
    p.className =
      "flex items-center space-x-2 py-1 px-2 rounded hover:bg-gray-200 transition-colors";
    let icon = "📝";
    if (msg.includes("CMD dikirim")) icon = "📤";
    if (msg.includes("Gagal")) icon = "❌";
    if (msg.includes("siap")) icon = "✅";
    p.innerHTML = `<span class="text-gray-500">${icon}</span><span class="text-gray-600">[${nowStr()}]</span><span class="text-gray-800">${msg}</span>`;
    el.prepend(p);
    while (el.children.length > 30) {
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
    el.textContent = dec !== undefined ? n.toFixed(dec) : n;
  }
  function setBadgeState(unit, text, style = "success") {
    const el = document.getElementById(`state-${unit.toLowerCase()}`);
    if (!el) return;
    el.className = "badge badge-lg";
    if (style === "info") el.className += " badge-info status-active";
    else if (style === "warning") el.className += " badge-warning";
    else if (style === "success") el.className += " badge-success";
    else el.className += " badge-ghost";
    el.textContent = text;
    if (text.includes("...")) el.classList.add("animate-pulse");
    else el.classList.remove("animate-pulse");
  }
  function initRealtime() {
    [
      { unit: "A", ref: db.ref("kebun-a/realtime") },
      { unit: "B", ref: db.ref("kebun-b/realtime") },
    ].forEach(({ unit, ref }) => {
      ref.on("value", (snap) => {
        const d = snap.val() || {};
        setText(`${unit.toLowerCase()}-ph`, d.ph, 2);
        setText(`${unit.toLowerCase()}-tds`, d.tds, 0);
        setText(`${unit.toLowerCase()}-suhu`, d.suhu, 1);
        setText(`${unit.toLowerCase()}-cal-ph-asam`, d.cal_ph_asam, 4);
        setText(`${unit.toLowerCase()}-cal-ph-netral`, d.cal_ph_netral, 4);
        setText(`${unit.toLowerCase()}-cal-tds-k`, d.cal_tds_k, 4);
      });
    });
  }
  function sendCommand(unit, text) {
    const path = unit === "A" ? CMD_PATHS.A : CMD_PATHS.B;
    const ref = db.ref(path);
    const payload = {
      text,
      source: "web",
      createdAt: firebase.database.ServerValue.TIMESTAMP,
    };
    return ref
      .push(payload)
      .then((snap) => {
        log(`(${unit}) CMD dikirim: ${text}`);
        const mirrorPath = path.replace("/commands", "/last_command");
        return db.ref(mirrorPath).set({ ...payload, key: snap.key });
      })
      .catch((err) => {
        log(`(${unit}) Gagal kirim CMD: ${err.message}`);
        showToast(`(${unit}) Gagal: ${err.message}`, "error", 5000);
      });
  }
  function startPh(unit) {
    setBadgeState(unit, "MULAI pH", "info");
    const a = document.getElementById(`btn-${unit.toLowerCase()}-asam`);
    const n = document.getElementById(`btn-${unit.toLowerCase()}-netral`);
    if (a) a.disabled = false;
    if (n) n.disabled = true;
    sendCommand(unit, "kalibrasi ph");
    showToast(`(${unit}) Kalibrasi pH dimulai`, "info");
  }
  function recordPhAsam(unit) {
    setBadgeState(unit, "ASAM...", "warning");
    const a = document.getElementById(`btn-${unit.toLowerCase()}-asam`);
    const n = document.getElementById(`btn-${unit.toLowerCase()}-netral`);
    if (a) a.disabled = true;
    if (n) n.disabled = false;
    sendCommand(unit, "record asam 4.01");
    showToast(`(${unit}) Nilai pH Asam direkam`, "warning");
  }
  function recordPhNetral(unit) {
    setBadgeState(unit, "NETRAL...", "success");
    const n = document.getElementById(`btn-${unit.toLowerCase()}-netral`);
    if (n) n.disabled = true;
    sendCommand(unit, "record netral 6.86").then(() =>
      setBadgeState(unit, "SELESAI", "success")
    );
    showToast(`(${unit}) Nilai pH Netral direkam`, "success");
  }
  function calibrateTds(unit) {
    const input = document.getElementById(`${unit.toLowerCase()}-ppm`);
    const val = parseFloat(input && input.value);
    if (!val || val <= 0) {
      showToast("Masukkan nilai ppm valid", "warning");
      if (input) input.focus();
      return;
    }
    setBadgeState(unit, "KALIBRASI TDS...", "info");
    sendCommand(unit, `kalibrasi tds ${val}`).then(() =>
      setBadgeState(unit, "SELESAI", "success")
    );
    showToast(`(${unit}) Kalibrasi TDS (${val}) dikirim`, "info");
  }
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
        showToast(`(${unit}) Error: ${err.message}`, "error");
      });
  }
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
      if (clock) clock.textContent = t;
      const date = document.getElementById("real-time-date");
      if (date) date.textContent = d;
      const mc = document.getElementById("real-time-clock-mobile");
      if (mc) mc.textContent = t;
      const md = document.getElementById("real-time-date-mobile");
      if (md) md.textContent = d;
    }
    tick();
    setInterval(tick, 1000);
  }
  function initCompanyLogo() {
    const logoImg = document.getElementById("company-logo");
    const logoFallback = document.getElementById("logo-fallback");
    if (!logoImg) return;
    const sources = [
      "assets/logo.png",
      "assets/logo.jpg",
      "assets/logo.svg",
      "logo.png",
      "logo.jpg",
    ];
    let loaded = false;
    sources.forEach((src) => {
      if (loaded) return;
      const test = new Image();
      test.onload = () => {
        if (!loaded) {
          logoImg.src = src;
          logoImg.classList.remove("hidden");
          if (logoFallback) logoFallback.style.display = "none";
          loaded = true;
        }
      };
      test.src = src;
    });
  }
  function heartbeatTest() {
    const hbRef = db.ref("calibration-page/heartbeat");
    return hbRef.set({
      ts: firebase.database.ServerValue.TIMESTAMP,
      page: "kalibrasi",
    });
  }
  // expose
  window.startPh = startPh;
  window.recordPhAsam = recordPhAsam;
  window.recordPhNetral = recordPhNetral;
  window.calibrateTds = calibrateTds;
  window.finishCalibration = finishCalibration;

  document.addEventListener("DOMContentLoaded", () => {
    initClock();
    initCompanyLogo();
    initRealtime();
    heartbeatTest();
    log("Halaman kalibrasi siap");
  });
})();
