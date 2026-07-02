<?php

namespace console\migrations;

use yii\db\Migration;

class m260626_120000_insert_survey_kepuasan_menu extends Migration
{
    public function safeUp()
    {
        $this->insert('{{%menu}}', [
            'name' => 'Survey Kepuasan',
            'route' => '/survey-kepuasan/index',
            'data' => json_encode(['icon' => 'smile-o']),
            'order' => 1000,
        ]);
    }

    public function safeDown()
    {
        $this->delete('{{%menu}}', ['route' => '/survey-kepuasan/index']);
    }
}
