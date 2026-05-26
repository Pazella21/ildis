<?php

namespace console\migrations;

use yii\db\Migration;

/**
 * Frontend OPAC reads from dokumen_data_subyek. Fresh Docker installs created an empty
 * denormalized table; production used a view over document + subyek + pengarang.
 */
class m260526_120000_recreate_dokumen_data_subyek_as_view extends Migration
{
    public function safeUp()
    {
        $this->dropTable('{{%dokumen_data_subyek}}');

        $sql = <<<'SQL'
CREATE VIEW {{%dokumen_data_subyek}} AS
SELECT
    d.id,
    d.tipe_dokumen,
    d.judul,
    d.teu,
    d.nomor_peraturan,
    d.nomor_panggil,
    d.dokumen_type_id,
    d.bentuk_peraturan,
    d.jenis_peraturan,
    d.singkatan_jenis,
    d.cetakan,
    d.tempat_terbit,
    d.penerbit,
    d.tanggal_penetapan,
    d.deskripsi_fisik,
    d.sumber,
    d.isbn,
    d.bahasa,
    d.bidang_hukum,
    d.nomor_induk_buku,
    d.singkatan_bentuk,
    d.tipe_koleksi_nomor_eksemplar,
    d.pola_nomor_eksemplar,
    d.jumlah_eksemplar,
    d.kala_terbit,
    d.tahun_terbit,
    d.tanggal_dibacakan,
    d.pernyataan_tanggung_jawab,
    d.edisi,
    d.gmd,
    d.judul_seri,
    d.klasifikasi,
    d.info_detil_spesifik,
    d.abstrak,
    d.gambar_sampul,
    d.label,
    d.sembunyikan_di_opac,
    d.promosikan_ke_beranda,
    d.status_terakhir,
    d.status,
    d.integrasi,
    d._created_by,
    d._updated_by,
    d.created_at,
    d.updated_at,
    d.inisiatif,
    d.pemrakarsa,
    d.tanggal_pengundangan,
    d.daerah,
    d.penandatanganan,
    d.lembaga_peradilan,
    d.pemohon,
    d.termohon,
    d.jenis_perkara,
    d.sub_klasifikasi,
    d.amar_status,
    d.berkekuatan_hukum_tetap,
    d.urusan_pemerintahan,
    d.catatan_status_peraturan,
    d.hit_see,
    d.hit_download,
    d.sumber_perolehan,
    d.is_publish,
    d.subjek_data,
    d.slug,
    COALESCE((
        SELECT GROUP_CONCAT(DISTINCT ds.subyek ORDER BY ds.subyek SEPARATOR ', ')
        FROM {{%data_subyek}} ds
        WHERE ds.id_dokumen = d.id
    ), '') AS subyek,
    COALESCE((
        SELECT GROUP_CONCAT(DISTINCT COALESCE(p.name, CAST(dp.nama_pengarang AS CHAR)) ORDER BY p.name SEPARATOR ', ')
        FROM {{%data_pengarang}} dp
        LEFT JOIN {{%pengarang}} p ON p.id = dp.nama_pengarang
        WHERE dp.id_dokumen = d.id
    ), '-') AS nama_pengarang
FROM {{%document}} d
SQL;

        $this->execute($sql);
    }

    public function safeDown()
    {
        $this->execute('DROP VIEW IF EXISTS {{%dokumen_data_subyek}}');

        $this->createTable(
            '{{%dokumen_data_subyek}}',
            [
                'id' => $this->integer()->notNull()->defaultValue(0),
                'tipe_dokumen' => $this->integer(),
                'judul' => $this->text()->notNull(),
                'teu' => $this->text(),
                'nomor_peraturan' => $this->string(),
                'nomor_panggil' => $this->string(),
                'dokumen_type_id' => $this->integer(),
                'bentuk_peraturan' => $this->text(),
                'jenis_peraturan' => $this->string()->defaultValue('-'),
                'singkatan_jenis' => $this->string()->defaultValue('-'),
                'cetakan' => $this->string(),
                'tempat_terbit' => $this->text(),
                'penerbit' => $this->text(),
                'tanggal_penetapan' => $this->date(),
                'deskripsi_fisik' => $this->string(),
                'sumber' => $this->text(),
                'isbn' => $this->string(),
                'bahasa' => $this->text(),
                'bidang_hukum' => $this->text(),
                'nomor_induk_buku' => $this->string(),
                'singkatan_bentuk' => $this->string(),
                'tipe_koleksi_nomor_eksemplar' => $this->string(),
                'pola_nomor_eksemplar' => $this->string(),
                'jumlah_eksemplar' => $this->string(),
                'kala_terbit' => $this->string(),
                'tahun_terbit' => $this->string(),
                'tanggal_dibacakan' => $this->date(),
                'pernyataan_tanggung_jawab' => $this->text(),
                'edisi' => $this->string(),
                'gmd' => $this->string(),
                'judul_seri' => $this->string(),
                'klasifikasi' => $this->string(),
                'info_detil_spesifik' => $this->string(),
                'abstrak' => $this->text(),
                'gambar_sampul' => $this->string(),
                'label' => $this->string(),
                'sembunyikan_di_opac' => $this->string(),
                'promosikan_ke_beranda' => $this->string()->defaultValue('Ya'),
                'status_terakhir' => $this->string(),
                'status' => $this->string()->defaultValue('Berlaku'),
                'integrasi' => $this->integer()->defaultValue(1),
                '_created_by' => $this->string(),
                '_updated_by' => $this->string(),
                'created_at' => $this->dateTime(),
                'updated_at' => $this->dateTime(),
                'inisiatif' => $this->string(),
                'pemrakarsa' => $this->string(),
                'tanggal_pengundangan' => $this->date(),
                'daerah' => $this->integer(),
                'penandatanganan' => $this->string(),
                'lembaga_peradilan' => $this->string(),
                'pemohon' => $this->string(),
                'termohon' => $this->string(),
                'jenis_perkara' => $this->string(),
                'sub_klasifikasi' => $this->string(),
                'amar_status' => $this->string(),
                'berkekuatan_hukum_tetap' => $this->string(),
                'urusan_pemerintahan' => $this->string(),
                'catatan_status_peraturan' => $this->string(),
                'hit_see' => $this->integer(),
                'hit_download' => $this->integer(),
                'sumber_perolehan' => $this->string(),
                'is_publish' => $this->integer()->defaultValue(0),
                'subjek_data' => $this->string(),
                'slug' => $this->string(),
                'subyek' => $this->string(),
                'nama_pengarang' => $this->string(),
            ]
        );
    }
}
