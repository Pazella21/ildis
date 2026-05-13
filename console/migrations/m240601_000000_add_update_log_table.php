<?php

use yii\db\Migration;

class m240601_000000_add_update_log_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%update_log}}', [
            'id' => $this->primaryKey(),
            'version_from' => $this->string(20)->notNull(),
            'version_to' => $this->string(20)->notNull(),
            'status' => $this->string(20)->notNull()->defaultValue('pending'),
            'backup_file' => $this->string(255)->null(),
            'started_at' => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
            'completed_at' => $this->timestamp()->null(),
            'notes' => $this->text()->null(),
        ]);

        $this->createIndex('idx-update_log-status', '{{%update_log}}', 'status');
        $this->createIndex('idx-update_log-version_to', '{{%update_log}}', 'version_to');
    }

    public function safeDown()
    {
        $this->dropIndex('idx-update_log-version_to', '{{%update_log}}');
        $this->dropIndex('idx-update_log-status', '{{%update_log}}');
        $this->dropTable('{{%update_log}}');
    }
}