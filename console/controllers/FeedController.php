<?php

namespace console\controllers;

use backend\models\DataLampiran;
use backend\models\DokumenJdih;
use backend\models\JenisPeraturan;
use common\components\FeedExportFilter;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\db\ActiveQuery;
use yii\helpers\ArrayHelper;
use yii\helpers\Console;
use yii\helpers\FileHelper;

class FeedController extends Controller
{
    public $tipe;
    public $typeId;
    public $dateField;
    public $from;
    public $to;
    public $output;
    public $nonInteractive = false;
    public $yes = false;

    public function options($actionID)
    {
        return array_merge(parent::options($actionID), [
            'tipe', 'typeId', 'dateField', 'from', 'to', 'output',
            'nonInteractive', 'yes',
        ]);
    }

    public function optionAliases()
    {
        return array_merge(parent::optionAliases(), [
            't' => 'tipe',
            'o' => 'output',
            'n' => 'nonInteractive',
            'y' => 'yes',
        ]);
    }

    public function actionGenerateDocument()
    {
        $filePath = \Yii::getAlias('@feed/document.json');

        try {
            $dokumen = $this->fetchDocuments($this->buildBaseQuery());

            if (empty($dokumen)) {
                echo "[feed] Peringatan: Tidak ada dokumen yang dipublikasikan. File tidak diperbarui.\n";
                return self::EXIT_CODE_ERROR;
            }

            $dokumen = $this->enrichRows($dokumen);

            $bytes = $this->writeJsonFile($filePath, $dokumen);

            echo "[feed] Berhasil: {$filePath} (" . count($dokumen) . " dokumen, {$bytes} bytes)\n";
            return self::EXIT_CODE_NORMAL;

        } catch (\Exception $e) {
            \Yii::error("[feed] Gagal generate document.json: " . $e->getMessage(), 'feed');
            echo "[feed] ERROR: " . $e->getMessage() . "\n";
            return self::EXIT_CODE_ERROR;
        }
    }

    public function actionExportDocument()
    {
        $filter = $this->nonInteractive
            ? $this->buildFilterFromFlags()
            : $this->buildFilterInteractively();

        if (!$filter->validate()) {
            foreach ($filter->getErrors() as $field => $messages) {
                Console::error("{$field}: " . implode(', ', $messages));
            }
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $query = $this->buildBaseQuery();
        FeedExportFilter::applyToQuery($query, $filter);

        $count = (int) $query->count();
        $outputPath = $filter->resolveOutputPath();

        if (!$this->yes && !$this->nonInteractive) {
            $this->printExportSummary($filter, $count, $outputPath);
            if (!$this->confirm('Lanjutkan export?')) {
                Console::output('Export dibatalkan.');
                return ExitCode::OK;
            }
        }

        if ($count === 0) {
            Console::error('[feed] Peringatan: Tidak ada dokumen yang cocok. File tidak ditulis.');
            return ExitCode::UNSPECIFIED_ERROR;
        }

        try {
            $dokumen = $this->enrichRows($this->fetchDocuments($query));
            $bytes = $this->writeJsonFile($outputPath, $dokumen);
            Console::output("[feed] Berhasil: {$outputPath} ({$count} dokumen, {$bytes} bytes)");
            return ExitCode::OK;
        } catch (\Exception $e) {
            \Yii::error('[feed] Gagal export: ' . $e->getMessage(), 'feed');
            Console::error('[feed] ERROR: ' . $e->getMessage());
            return ExitCode::UNSPECIFIED_ERROR;
        }
    }

    private function buildFilterFromFlags(): FeedExportFilter
    {
        return new FeedExportFilter([
            'tipe' => $this->tipe,
            'typeId' => $this->typeId,
            'dateField' => $this->dateField,
            'from' => $this->from,
            'to' => $this->to,
            'output' => $this->output,
        ]);
    }

    private function buildFilterInteractively(): FeedExportFilter
    {
        $tipeOptions = [
            '' => 'Semua',
            (string) DokumenJdih::TYPE_PERATURAN => 'Peraturan',
            (string) DokumenJdih::TYPE_MONOGRAFI => 'Monografi',
            (string) DokumenJdih::TYPE_ARTIKEL => 'Artikel',
            (string) DokumenJdih::TYPE_PUTUSAN => 'Putusan',
        ];
        $tipe = $this->select('Pilih tipe dokumen:', $tipeOptions);

        $typeId = null;
        if ($tipe === (string) DokumenJdih::TYPE_PERATURAN) {
            $types = JenisPeraturan::find()
                ->where(['parent_id' => 1])
                ->orderBy(['name' => SORT_ASC])
                ->all();
            $typeOptions = ['' => 'Semua'] + ArrayHelper::map($types, 'id', 'name');
            $selected = $this->select('Pilih jenis peraturan:', $typeOptions);
            $typeId = $selected !== '' ? (int) $selected : null;
        }

        $dateFieldOptions = [
            '' => 'Tanpa filter tanggal',
            'updated_at' => 'Tanggal diperbarui (updated_at)',
            'tanggal_pengundangan' => 'Tanggal pengundangan',
            'tanggal_penetapan' => 'Tanggal penetapan',
        ];
        $dateField = $this->select('Pilih field tanggal:', $dateFieldOptions);

        $from = null;
        $to = null;
        if ($dateField !== '') {
            $from = $this->prompt('Dari tanggal (Y-m-d, kosongkan untuk abaikan):', ['default' => '']);
            $to = $this->prompt('Sampai tanggal (Y-m-d, kosongkan untuk abaikan):', ['default' => '']);
            $from = $from !== '' ? $from : null;
            $to = $to !== '' ? $to : null;
        }

        return new FeedExportFilter([
            'tipe' => $tipe !== '' ? (int) $tipe : null,
            'typeId' => $typeId,
            'dateField' => $dateField !== '' ? $dateField : null,
            'from' => $from,
            'to' => $to,
            'output' => $this->output,
        ]);
    }

    private function printExportSummary(FeedExportFilter $filter, int $count, string $outputPath): void
    {
        Console::output('');
        Console::output('Ringkasan export:');
        Console::output('  Tipe       : ' . ($filter->tipe ?? 'Semua'));
        Console::output('  Type ID    : ' . ($filter->typeId ?? 'Semua'));
        Console::output('  Date field : ' . ($filter->dateField ?? '-'));
        Console::output('  From       : ' . ($filter->from ?? '-'));
        Console::output('  To         : ' . ($filter->to ?? '-'));
        Console::output('  Output     : ' . $outputPath);
        Console::output('  Dokumen    : ' . $count);
        Console::output('');
    }

    /**
     * @return string[]
     */
    private function getDocumentSelectColumns(): array
    {
        return [
            'd.id AS idData',
            'd.tahun_terbit AS tahun_pengundangan',
            'd.tanggal_penetapan',
            'd.tanggal_pengundangan',
            'd.jenis_peraturan AS jenis',
            'd.nomor_peraturan AS noPeraturan',
            'd.judul',
            'd.nomor_panggil AS noPanggil',
            'd.singkatan_jenis AS singkatanJenis',
            'd.tempat_terbit AS tempatTerbit',
            'd.penerbit',
            'd.deskripsi_fisik AS deskripsiFisik',
            'd.sumber',
            'd.isbn',
            'd.status',
            'd.bahasa',
            'd.bidang_hukum AS bidangHukum',
            'd.teu AS teuBadan',
            'd.nomor_induk_buku AS nomorIndukBuku',
            'd.abstrak',
            'd.updated_at AS last_updated',
        ];
    }

    private function buildBaseQuery(): ActiveQuery
    {
        return DokumenJdih::find()
            ->alias('d')
            ->select($this->getDocumentSelectColumns())
            ->where(['d.is_publish' => 1]);
    }

    private function fetchDocuments(ActiveQuery $query): array
    {
        return $query->asArray()->all();
    }

    private function enrichRows(array $dokumen): array
    {
        $baseUrl = \Yii::getAlias('@imageurl');

        $lampiranMap = [];
        $allLampiran = DataLampiran::find()
            ->select(['id_dokumen', 'dokumen_lampiran', 'url_lampiran'])
            ->where(['id_dokumen' => array_column($dokumen, 'idData')])
            ->orderBy(['urutan' => SORT_ASC, 'id' => SORT_ASC])
            ->asArray()
            ->all();

        foreach ($allLampiran as $lampiran) {
            $docId = $lampiran['id_dokumen'];
            if (!isset($lampiranMap[$docId]) && !empty($lampiran['dokumen_lampiran'])) {
                $lampiranMap[$docId] = $lampiran;
            }
        }

        foreach ($dokumen as &$row) {
            if (!empty($row['abstrak'])) {
                $row['urlAbstrak'] = rtrim($baseUrl, '/') . '/common/dokumen/' . $row['abstrak'];
            } else {
                $row['abstrak'] = '';
                $row['urlAbstrak'] = '';
            }

            $row['urlDetailPeraturan'] = \Yii::$app->urlManager->createAbsoluteUrl([
                'dokumen/view', 'id' => $row['idData']
            ]);

            $docId = $row['idData'];
            if (isset($lampiranMap[$docId])) {
                $row['fileDownload'] = $lampiranMap[$docId]['dokumen_lampiran'];
                if (!empty($lampiranMap[$docId]['url_lampiran'])) {
                    $row['urlDownload'] = $lampiranMap[$docId]['url_lampiran'];
                } else {
                    $row['urlDownload'] = rtrim($baseUrl, '/') . '/common/dokumen/' . $lampiranMap[$docId]['dokumen_lampiran'];
                }
            } else {
                $row['fileDownload'] = '-';
                $row['urlDownload'] = '-';
            }

            $row['subjek'] = '';
            $row['operasi'] = '4';
            $row['display'] = '1';
        }

        return $dokumen;
    }

    private function writeJsonFile(string $filePath, array $dokumen): int
    {
        $tempPath = $filePath . '.tmp.' . getmypid();

        try {
            FileHelper::createDirectory(dirname($filePath));

            $json = json_encode($dokumen, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            if ($json === false) {
                throw new \RuntimeException('Gagal encode JSON: ' . json_last_error_msg());
            }

            $bytes = file_put_contents($tempPath, $json);
            if ($bytes === false) {
                throw new \RuntimeException("Gagal menulis file temporer: {$tempPath}");
            }

            if (!rename($tempPath, $filePath)) {
                throw new \RuntimeException("Gagal rename {$tempPath} ke {$filePath}");
            }

            return $bytes;
        } catch (\Exception $e) {
            if (file_exists($tempPath)) {
                @unlink($tempPath);
            }
            throw $e;
        }
    }
}
