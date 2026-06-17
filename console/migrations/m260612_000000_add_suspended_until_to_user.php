<?php

namespace console\migrations;

use yii\db\Migration;

/**
 * Adds login lockout column for existing databases where `user` predates migrations.
 */
class m260612_000000_add_suspended_until_to_user extends Migration
{
    public function safeUp()
    {
        $table = '{{%user}}';
        $schema = $this->db->getTableSchema($table, true);

        if ($schema === null) {
            return;
        }

        if (!isset($schema->columns['suspended_until'])) {
            $this->addColumn($table, 'suspended_until', $this->dateTime()->null()->after('status'));
        }
    }

    public function safeDown()
    {
        $table = '{{%user}}';
        $schema = $this->db->getTableSchema($table, true);

        if ($schema !== null && isset($schema->columns['suspended_until'])) {
            $this->dropColumn($table, 'suspended_until');
        }
    }
}
