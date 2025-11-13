<?php

namespace App\Models;

use CodeIgniter\Model;

class TelemetryModel extends Model
{
    protected $table = 'telemetry_logs';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'kebun', 'device', 'ph', 'tds', 'suhu', 'cal_ph_asam', 'cal_ph_netral', 'cal_tds_k', 'timestamp_ms', 'created_at'
    ];
    protected $useTimestamps = false;
}
