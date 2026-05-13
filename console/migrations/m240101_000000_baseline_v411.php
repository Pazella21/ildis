<?php

use yii\db\Migration;

class m240101_000000_baseline_v411 extends Migration
{
    public function safeUp()
    {
        echo "    > Baseline migration for ILDIS v4.1.1 — marked as applied.\n";
        return true;
    }

    public function safeDown()
    {
        echo "    > Cannot revert baseline migration.\n";
        return false;
    }
}