namespace _next;

use yii\db\Migration;

class m260507_000001_create_table_visitor_log extends Migration
{
    public function safeUp()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%visitor_log}}', [
            'id' => $this->primaryKey(),
            'visitor_fingerprint' => $this->string(64)->notNull(),
            'visitor_cookie_id' => $this->string(64)->notNull(),
            'document_id' => $this->string(100),
            'page_url' => $this->string(500)->notNull(),
            'visit_date' => $this->date()->notNull(),
            'visit_time' => $this->dateTime()->notNull(),
            'is_unique' => $this->tinyInteger(1)->notNull()->defaultValue(0),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
        ], $tableOptions);

        $this->createIndex(
            'idx_visitor_log_fingerprint_date',
            '{{%visitor_log}}',
            ['visitor_fingerprint', 'visit_date']
        );
        $this->createIndex(
            'idx_visitor_log_document_date',
            '{{%visitor_log}}',
            ['document_id', 'visit_date']
        );
        $this->createIndex(
            'idx_visitor_log_visit_time',
            '{{%visitor_log}}',
            'visit_time'
        );
    }

    public function safeDown()
    {
        $this->dropTable('{{%visitor_log}}');
    }
}
