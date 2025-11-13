// Simple MQTT → HTTP ingester
// Requires: Node.js >= 16
// Install deps:
//   npm init -y
//   npm i mqtt axios
// Run:
//   node ingest.js

const mqtt = require("mqtt");
const axios = require("axios");

// --- CONFIG ---
const MQTT_URL = process.env.MQTT_URL || "wss://broker.emqx.io:8084/mqtt";
const MQTT_OPTIONS = {
  clean: true,
  connectTimeout: 4000,
  clientId: `srv-ingest-${Math.random().toString(16).slice(2)}`,
};
const TOPIC = process.env.MQTT_TOPIC || "hidroganik/+/telemetry";

// Point to your local CI4 server
const INGEST_URL =
  process.env.INGEST_URL || "http://localhost/hidroganik/api/telemetry/ingest";
const INGEST_TOKEN = process.env.INGEST_TOKEN || "change-me"; // must match .env INGEST_TOKEN on server
// ---------------

function kebunFromTopic(topic) {
  const m = topic.match(/hidroganik\/(kebun-[^/]+)\/telemetry/i);
  return m && m[1] ? m[1].toLowerCase() : "";
}

(async function main() {
  console.log(`[ingest] Connecting to MQTT: ${MQTT_URL}`);
  const client = mqtt.connect(MQTT_URL, MQTT_OPTIONS);

  client.on("connect", () => {
    console.log(`[ingest] MQTT connected. Subscribing ${TOPIC}`);
    client.subscribe(TOPIC, (err) => {
      if (err) console.error("[ingest] subscribe error:", err.message);
    });
  });

  client.on("message", async (topic, payload) => {
    const kebun = kebunFromTopic(topic);
    let data = null;
    try {
      data = JSON.parse(payload.toString());
    } catch (e) {
      console.warn("[ingest] Non-JSON payload ignored:", topic);
      return;
    }
    // Build body for ingest
    const body = {
      kebun,
      ...data,
      topic,
    };
    try {
      const res = await axios.post(INGEST_URL, body, {
        headers: {
          "X-INGEST-TOKEN": INGEST_TOKEN,
          "Content-Type": "application/json",
        },
        timeout: 5000,
      });
      if (res.status === 200 && res.data && res.data.status === "ok") {
        console.log(
          `[ingest] Stored: ${kebun} ts=${
            body.timestamp || body.date + " " + body.time || "now"
          }`
        );
      } else {
        console.warn("[ingest] Store not-ok:", res.status, res.data);
      }
    } catch (e) {
      console.error("[ingest] HTTP error:", e.message);
    }
  });

  client.on("error", (e) => console.error("[ingest] MQTT error:", e.message));
  client.on("reconnect", () => console.log("[ingest] MQTT reconnecting..."));
})();
