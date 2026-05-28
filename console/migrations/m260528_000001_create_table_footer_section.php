<?php

namespace console\migrations;

use Yii;
use yii\db\Migration;

class m260528_000001_create_table_footer_section extends Migration
{
    public function safeUp()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%footer_section}}', [
            'id' => $this->primaryKey(),
            'title' => $this->string(255)->notNull(),
            'type' => $this->string(20)->notNull()->defaultValue('nav'),
            'sort_order' => $this->integer()->notNull()->defaultValue(0),
            'status' => $this->tinyInteger(1)->notNull()->defaultValue(1),
            'created_at' => $this->dateTime(),
            'updated_at' => $this->dateTime(),
        ], $tableOptions);

        $this->insert('{{%footer_section}}', [
            'title' => 'LAYANAN',
            'type' => 'nav',
            'sort_order' => 1,
            'status' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->insert('{{%footer_section}}', [
            'title' => 'TENTANG',
            'type' => 'nav',
            'sort_order' => 2,
            'status' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->insert('{{%footer_section}}', [
            'title' => 'MEDIA SOSIAL',
            'type' => 'social',
            'sort_order' => 3,
            'status' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function safeDown()
    {
        $this->dropTable('{{%footer_section}}');
    }
}