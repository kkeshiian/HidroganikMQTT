<?php

namespace App\Services;

use Kreait\Firebase\Factory;
use Kreait\Firebase\Exception\DatabaseException;

class FirebaseService
{
    protected $database;

    public function __construct()
    {
        // BENAR: Path ke file kredensial dibuat di dalam kode PHP,
        // menggunakan konstanta CodeIgniter WRITEPATH.
        $credentialsPath = WRITEPATH . '/keys/firebase-admin.json';

        // Inisialisasi Firebase Factory
        $factory = (new Factory)
            ->withServiceAccount($credentialsPath) // Menggunakan path yang sudah benar
            ->withDatabaseUri(getenv('FIREBASE_DATABASE_URL')); // Tetap membaca URL dari .env

        $this->database = $factory->createDatabase();
    }
    
    // ... sisa kode tidak perlu diubah ...
    
    /**
     * Mengambil data history dari Firebase dengan filter.
     * @param string $device 'a', 'b', or 'all'
     * @param string $startDate 'Y-m-d'
     * @param string $endDate 'Y-m-d'
     * @return array
     */
    public function getHistory($device, $startDate, $endDate)
    {
        $devices = ($device === 'all') ? ['a', 'b'] : [$device];
        $allData = [];
        $startMillis = strtotime($startDate . ' 00:00:00') * 1000;
        $endMillis = strtotime($endDate . ' 23:59:59') * 1000;

        foreach ($devices as $d) {
            try {
                $reference = $this->database->getReference("kebun-{$d}/history");
                $snapshot = $reference->orderByChild('timestamp')->getSnapshot();
                $historyData = $snapshot->getValue();

                if (is_array($historyData)) {
                    foreach ($historyData as $key => $data) {
                        if (!isset($data['timestamp'])) {
                            if (isset($data['date']) && isset($data['time'])) {
                                $data['timestamp'] = strtotime("{$data['date']} {$data['time']}") * 1000;
                            } else {
                                continue;
                            }
                        }
                        
                        if ($data['timestamp'] >= $startMillis && $data['timestamp'] <= $endMillis) {
                            $allData[] = [
                                'id' => $key,
                                'timestamp' => $data['timestamp'],
                                'device' => strtoupper($d),
                                'ph' => $data['ph'] ?? 0,
                                'tds' => $data['tds'] ?? 0,
                                'suhu' => $data['suhu'] ?? 0,
                                'cal_ph_asam' => $data['cal_ph_asam'] ?? 0,
                                'cal_ph_netral' => $data['cal_ph_netral'] ?? 0,
                                'cal_tds_k' => $data['cal_tds_k'] ?? 0,
                            ];
                        }
                    }
                }
            } catch (DatabaseException $e) {
                log_message('error', 'FirebaseService Error: ' . $e->getMessage());
            }
        }
        
        usort($allData, function($a, $b) {
            return $b['timestamp'] <=> $a['timestamp'];
        });

        return $allData;
    }

    public function setPumpStatus($kebun, $status)
    {
        try {
            $this->database->getReference("kebun-{$kebun}/status/pompa")->set($status);
            return true;
        } catch (DatabaseException $e) {
            log_message('error', 'FirebaseService Error (setPumpStatus): ' . $e->getMessage());
            return false;
        }
    }

    public function setSchedule($kebun, $scheduleData)
    {
        try {
            $this->database->getReference("kebun-{$kebun}/jadwal")->set($scheduleData);
            return true;
        } catch (DatabaseException $e) {
            log_message('error', 'FirebaseService Error (setSchedule): ' . $e->getMessage());
            return false;
        }
    }
}

