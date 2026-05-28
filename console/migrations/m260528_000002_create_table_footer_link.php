<?php

namespace console\migrations;

use Yii;
use yii\db\Migration;

class m260528_000002_create_table_footer_link extends Migration
{
    public function safeUp()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%footer_link}}', [
            'id' => $this->primaryKey(),
            'section_id' => $this->integer()->notNull(),
            'label' => $this->string(255)->notNull(),
            'url' => $this->string(500)->notNull()->defaultValue('#'),
            'icon_class' => $this->string(100),
            'sort_order' => $this->integer()->notNull()->defaultValue(0),
            'status' => $this->tinyInteger(1)->notNull()->defaultValue(1),
            'open_in_new_tab' => $this->tinyInteger(1)->notNull()->defaultValue(0),
            'created_at' => $this->dateTime(),
            'updated_at' => $this->dateTime(),
        ], $tableOptions);

        $this->createIndex('idx-footer_link-section_id', '{{%footer_link}}', 'section_id');

        // Seed LAYANAN links (section id = 1)
        $this->insert('{{%footer_link}}', [
            'section_id' => 1,
            'label' => 'Pengaduan',
            'url' => '#',
            'sort_order' => 1,
            'status' => 1,
            'open_in_new_tab' => 0,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->insert('{{%footer_link}}', [
            'section_id' => 1,
            'label' => 'Penilaian',
            'url' => '#',
            'sort_order' => 2,
            'status' => 1,
            'open_in_new_tab' => 0,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        // Seed TENTANG links (section id = 2)
        $this->insert('{{%footer_link}}', [
            'section_id' => 2,
            'label' => 'Beranda',
            'url' => '/',
            'sort_order' => 1,
            'status' => 1,
            'open_in_new_tab' => 0,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->insert('{{%footer_link}}', [
            'section_id' => 2,
            'label' => 'FAQ',
            'url' => '#',
            'sort_order' => 2,
            'status' => 1,
            'open_in_new_tab' => 0,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->insert('{{%footer_link}}', [
            'section_id' => 2,
            'label' => 'Kontak Kami',
            'url' => '#',
            'sort_order' => 3,
            'status' => 1,
            'open_in_new_tab' => 0,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        // Seed MEDIA SOSIAL links (section id = 3)
        // Migrate from frontend_config IDs 13 (facebook), 14 (youtube), 15 (instagram)
        $fb = $this->db->createCommand('SELECT isi_konfig FROM {{%frontend_config}} WHERE id = 13')->queryScalar();
        $yt = $this->db->createCommand('SELECT isi_konfig FROM {{%frontend_config}} WHERE id = 14')->queryScalar();
        $ig = $this->db->createCommand('SELECT isi_konfig FROM {{%frontend_config}} WHERE id = 15')->queryScalar();

        $this->insert('{{%footer_link}}', [
            'section_id' => 3,
            'label' => 'Facebook',
            'url' => $fb ? strip_tags($fb) : '#',
            'icon_class' => 'bi bi-facebook',
            'sort_order' => 1,
            'status' => 1,
            'open_in_new_tab' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->insert('{{%footer_link}}', [
            'section_id' => 3,
            'label' => 'Instagram',
            'url' => $ig ? strip_tags($ig) : '#',
            'icon_class' => 'bi bi-instagram',
            'sort_order' => 2,
            'status' => 1,
            'open_in_new_tab' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->insert('{{%footer_link}}', [
            'section_id' => 3,
            'label' => 'Twitter/X',
            'url' => '#',
            'icon_class' => 'bi bi-twitter-x',
            'sort_order' => 3,
            'status' => 1,
            'open_in_new_tab' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->insert('{{%footer_link}}', [
            'section_id' => 3,
            'label' => 'YouTube',
            'url' => $yt ? strip_tags($yt) : '#',
            'icon_class' => 'bi bi-youtube',
            'sort_order' => 4,
            'status' => 1,
            'open_in_new_tab' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function safeDown()
    {
        $this->dropTable('{{%footer_link}}');
    }
}