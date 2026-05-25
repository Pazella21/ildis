<?php

use yii\db\Migration;

class m260507_000003_insert_visitor_report_menu extends Migration
{
    public function safeUp()
    {
        $this->insert('{{%menu}}', [
            'name' => 'Statistik Pengunjung',
            'route' => '/visitor-report/index',
            'data' => json_encode(['icon' => 'chart-bar']),
            'order' => 999,
        ]);
    }

    public function safeDown()
    {
        $this->delete('{{%menu}}', ['route' => '/visitor-report/index']);
    }
}
