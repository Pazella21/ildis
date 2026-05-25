<?php

namespace console\migrations;

use yii\db\Migration;

class m250514_121343_create_table_schema_migration extends Migration
{
    public function safeUp()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable(
            '{{%schema_migration}}',
            [
                'id' => $this->primaryKey()->unsigned(),
                'migration' => $this->string()->notNull(),
                'batch' => $this->integer()->notNull(),
            ],
            $tableOptions
        );
    }

    public function safeDown()
    {
        $this->dropTable('{{%schema_migration}}');
    }
}
