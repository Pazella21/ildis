namespace _next;

use yii\db\Migration;

class m260507_000002_create_table_visitor_stats extends Migration
{
    public function safeUp()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%visitor_stats}}', [
            'id' => $this->primaryKey(),
            'stat_type' => "ENUM('daily','weekly','monthly','yearly','all_time') NOT NULL",
            'stat_date' => $this->date()->notNull(),
            'document_id' => $this->string(100),
            'total_visits' => $this->integer()->unsigned()->notNull()->defaultValue(0),
            'unique_visits' => $this->integer()->unsigned()->notNull()->defaultValue(0),
            'updated_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'),
        ], $tableOptions);

        $this->createIndex(
            'idx_visitor_stats_type_date_doc',
            '{{%visitor_stats}}',
            ['stat_type', 'stat_date', 'document_id'],
            true
        );
    }

    public function safeDown()
    {
        $this->dropTable('{{%visitor_stats}}');
    }
}
