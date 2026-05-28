<?php

namespace console\migrations;

use yii\db\Migration;

class m260528_000003_insert_footer_menu extends Migration
{
    public function safeUp()
    {
        $this->insert('{{%menu}}', [
            'name' => 'Footer',
            'parent' => 1,
            'route' => null,
            'order' => 0,
            'data' => json_encode(['icon' => 'fa fa-columns']),
        ]);

        $parentId = $this->db->getLastInsertID();

        $this->insert('{{%menu}}', [
            'name' => 'Bagian Footer',
            'parent' => $parentId,
            'route' => '/footer-section/index',
            'order' => 1,
            'data' => json_encode(['icon' => 'fa fa-th-list']),
        ]);

        $this->insert('{{%menu}}', [
            'name' => 'Link Footer',
            'parent' => $parentId,
            'route' => '/footer-link/index',
            'order' => 2,
            'data' => json_encode(['icon' => 'fa fa-link']),
        ]);
    }

    public function safeDown()
    {
        $this->delete('{{%menu}}', ['route' => '/footer-link/index']);
        $this->delete('{{%menu}}', ['route' => '/footer-section/index']);

        $parentId = $this->db->createCommand(
            'SELECT id FROM {{%menu}} WHERE name = \'Footer\' AND route IS NULL AND parent = 1'
        )->queryScalar();

        if ($parentId) {
            $this->delete('{{%menu}}', ['id' => $parentId]);
        }
    }
}