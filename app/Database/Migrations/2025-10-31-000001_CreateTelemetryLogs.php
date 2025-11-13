<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTelemetryLogs extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'kebun' => [
                'type' => 'VARCHAR',
                'constraint' => 20, // kebun-a / kebun-b
            ],
            'device' => [
                'type' => 'CHAR',
                'constraint' => 1, // A or B
                'null' => true,
            ],
            'ph' => [
                'type' => 'DECIMAL',
                'constraint' => '5,2',
                'null' => true,
            ],
            'tds' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
            ],
            'suhu' => [
                'type' => 'DECIMAL',
                'constraint' => '5,2',
                'null' => true,
            ],
            'cal_ph_asam' => [
                'type' => 'DECIMAL',
                'constraint' => '10,4',
                'null' => true,
            ],
            'cal_ph_netral' => [
                'type' => 'DECIMAL',
                'constraint' => '10,4',
                'null' => true,
            ],
            'cal_tds_k' => [
                'type' => 'DECIMAL',
                'constraint' => '10,4',
                'null' => true,
            ],
            'timestamp_ms' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'null' => true,
                'comment' => 'Original timestamp in ms from device/message',
            ],
            'created_at' => [
                'type' => 'TIMESTAMP',
                'null' => false,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['kebun', 'timestamp_ms']);
        $this->forge->createTable('telemetry_logs', true);
    }

    public function down()
    {
        $this->forge->dropTable('telemetry_logs', true);
    }
}
