<?php

namespace console\migrations;

use yii\db\Migration;

class m250515_000001_seed_base_data extends Migration
{
    public function safeUp()
    {
        $seedFile = __DIR__ . '/seed_data.sql';
        if (!file_exists($seedFile)) {
            echo "    > Seed file not found: {$seedFile}. Skipping seed data.\n";
            return true;
        }

        $sql = file_get_contents($seedFile);
        if ($sql === false) {
            echo "    > Could not read seed file: {$seedFile}\n";
            return false;
        }

        $statements = array_filter(
            array_map('trim', explode(";\n", $sql)),
            function ($s) {
                return !empty($s) && !preg_match('/^(--|\/\*|#)/', $s);
            }
        );

        foreach ($statements as $statement) {
            $statement = rtrim($statement, ';');
            if (empty($statement)) {
                continue;
            }
            try {
                $this->execute($statement);
            } catch (\Exception $e) {
                echo "    > Warning: Seeding failed for statement: " . substr($statement, 0, 80) . "...\n";
                echo "    > Error: " . $e->getMessage() . "\n";
            }
        }

        echo "    > Seed data loaded.\n";
        return true;
    }

    public function safeDown()
    {
        echo "    > Seed data cannot be reverted automatically.\n";
        return false;
    }
}