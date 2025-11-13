<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\TelemetryModel;

class Telemetry extends BaseController
{
    /**
     * Ingest telemetry JSON from MQTT bridge.
     * Security: require X-INGEST-TOKEN header matching env('INGEST_TOKEN')
     * Body JSON example:
     * {
     *   "kebun": "kebun-a", // or kebun-b
     *   "ph": 6.5, "tds": 850, "suhu": 25.2,
     *   "cal_ph_asam": 4.0100, "cal_ph_netral": 6.8600, "cal_tds_k": 0.5000,
     *   "timestamp": 1730325600000 // ms since epoch (optional)
     * }
     */
    public function ingest()
    {
        $tokenHeader = $this->request->getHeaderLine('X-INGEST-TOKEN');
        $expected = env('INGEST_TOKEN');
        if (!$expected || $tokenHeader !== $expected) {
            return $this->response->setStatusCode(401)->setJSON([
                'status' => 'error',
                'message' => 'Unauthorized'
            ]);
        }

        $payload = $this->request->getJSON(true) ?? [];

        // Determine kebun and device
        $kebun = strtolower(trim($payload['kebun'] ?? ($payload['kebun_id'] ?? '')));
        if (!$kebun && isset($payload['topic'])) {
            // Optional: derive from topic hidroganik/kebun-a/telemetry
            if (preg_match('~hidroganik/(kebun-[^/]+)/telemetry~i', $payload['topic'], $m)) {
                $kebun = strtolower($m[1]);
            }
        }
        if ($kebun !== 'kebun-a' && $kebun !== 'kebun-b') {
            return $this->response->setStatusCode(422)->setJSON([
                'status' => 'error',
                'message' => 'Invalid kebun (expected kebun-a or kebun-b)'
            ]);
        }
        $device = ($kebun === 'kebun-b') ? 'B' : 'A';

        // Normalize numeric fields
        $ph  = isset($payload['ph']) ? (float) $payload['ph'] : null;
        $tds = isset($payload['tds']) ? (int) $payload['tds'] : null;
        $suhu = isset($payload['suhu']) ? (float) $payload['suhu'] : null;
        $calAsam = isset($payload['cal_ph_asam']) ? (float) $payload['cal_ph_asam'] : null;
        $calNetral = isset($payload['cal_ph_netral']) ? (float) $payload['cal_ph_netral'] : null;
        $calTdsK = isset($payload['cal_tds_k']) ? (float) $payload['cal_tds_k'] : null;

        // Timestamp ms (prefer explicit, else from date+time, else now)
        $tsMs = null;
        if (isset($payload['timestamp'])) {
            $tsMs = (int) $payload['timestamp'];
        } elseif (!empty($payload['date']) && !empty($payload['time'])) {
            $dt = strtotime($payload['date'] . ' ' . $payload['time']);
            if ($dt !== false) $tsMs = $dt * 1000;
        }
        if (!$tsMs) $tsMs = (int) (microtime(true) * 1000);

        $data = [
            'kebun' => $kebun,
            'device' => $device,
            'ph' => $ph,
            'tds' => $tds,
            'suhu' => $suhu,
            'cal_ph_asam' => $calAsam,
            'cal_ph_netral' => $calNetral,
            'cal_tds_k' => $calTdsK,
            'timestamp_ms' => $tsMs,
            'created_at' => date('Y-m-d H:i:s'),
        ];

        try {
            $model = new TelemetryModel();
            $model->insert($data);
        } catch (\Throwable $e) {
            log_message('error', 'Telemetry ingest failed: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'status' => 'error',
                'message' => 'DB error'
            ]);
        }

        return $this->response->setJSON(['status' => 'ok']);
    }

    /**
     * Query telemetry logs with filters and pagination.
     * Security: requires login (route under auth filter)
     * GET params:
     * - start: YYYY-MM-DD (optional)
     * - end: YYYY-MM-DD (optional)
     * - device: A|B|all (default all)
     * - page: integer (default 1)
     * - perPage: integer (default 25, max 100)
     * - sort: asc|desc on timestamp_ms (default desc)
     */
    public function query()
    {
        $start = trim((string) $this->request->getGet('start'));
        $end = trim((string) $this->request->getGet('end'));
        $device = strtoupper(trim((string) $this->request->getGet('device')));
        if (!in_array($device, ['A', 'B'], true)) {
            $device = 'ALL';
        }
        $page = (int) ($this->request->getGet('page') ?? 1);
        if ($page < 1) $page = 1;
        $perPage = (int) ($this->request->getGet('perPage') ?? 25);
        if ($perPage < 1) $perPage = 25;
        if ($perPage > 100) $perPage = 100;
        $sort = strtolower((string) ($this->request->getGet('sort') ?? 'desc')) === 'asc' ? 'asc' : 'desc';

        $model = new TelemetryModel();
        $builder = $model->builder();
        $builder->from($model->getTable());

        // Filters
        if ($device !== 'ALL') {
            $builder->where('device', $device);
        }
        // Convert start/end dates to timestamp range if provided
        $startTs = null; $endTs = null;
        if ($start) {
            $dt = strtotime($start . ' 00:00:00');
            if ($dt !== false) $startTs = $dt * 1000;
        }
        if ($end) {
            $dt = strtotime($end . ' 23:59:59');
            if ($dt !== false) $endTs = $dt * 1000;
        }
        if ($startTs !== null) {
            $builder->where('timestamp_ms >=', $startTs);
        }
        if ($endTs !== null) {
            $builder->where('timestamp_ms <=', $endTs);
        }

        // Clone for count and stats before applying limit/offset
        $countBuilder = clone $builder;
        $statsBuilder = clone $builder;

        // Total count
        $countBuilder->select('COUNT(*) AS c');
        $total = (int) ($countBuilder->get()->getRow('c') ?? 0);

        // Stats
        $statsBuilder->select('AVG(ph) AS avg_ph, AVG(tds) AS avg_tds, AVG(suhu) AS avg_suhu');
        $statsRow = $statsBuilder->get()->getRowArray() ?? [];
        $stats = [
            'avg_ph' => isset($statsRow['avg_ph']) ? round((float)$statsRow['avg_ph'], 2) : null,
            'avg_tds' => isset($statsRow['avg_tds']) ? round((float)$statsRow['avg_tds']) : null,
            'avg_suhu' => isset($statsRow['avg_suhu']) ? round((float)$statsRow['avg_suhu'], 2) : null,
        ];

        // Data query
        $offset = ($page - 1) * $perPage;
        $builder->orderBy('timestamp_ms', $sort);
        $builder->limit($perPage, $offset);
        $builder->select('id, kebun, device, ph, tds, suhu, cal_ph_asam, cal_ph_netral, cal_tds_k, timestamp_ms, created_at');
        $rows = $builder->get()->getResultArray();

        // Format output
        $items = array_map(static function(array $r){
            return [
                'id' => (int)$r['id'],
                'kebun' => $r['kebun'],
                'device' => $r['device'],
                'ph' => is_null($r['ph']) ? null : (float)$r['ph'],
                'tds' => is_null($r['tds']) ? null : (int)$r['tds'],
                'suhu' => is_null($r['suhu']) ? null : (float)$r['suhu'],
                'cal_ph_asam' => is_null($r['cal_ph_asam']) ? null : (float)$r['cal_ph_asam'],
                'cal_ph_netral' => is_null($r['cal_ph_netral']) ? null : (float)$r['cal_ph_netral'],
                'cal_tds_k' => is_null($r['cal_tds_k']) ? null : (float)$r['cal_tds_k'],
                'timestamp_ms' => is_null($r['timestamp_ms']) ? null : (int)$r['timestamp_ms'],
                'created_at' => $r['created_at'],
            ];
        }, $rows);

        return $this->response->setJSON([
            'status' => 'ok',
            'page' => $page,
            'perPage' => $perPage,
            'total' => $total,
            'items' => $items,
            'stats' => $stats,
            'sort' => $sort,
            'device' => $device,
            'start' => $start ?: null,
            'end' => $end ?: null,
        ]);
    }
}
