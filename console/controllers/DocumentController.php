<?php

namespace console\controllers;

use common\components\DocumentSlug;
use yii\console\Controller;
use yii\db\Expression;
use yii\db\Query;
use yii\helpers\Console;

class DocumentController extends Controller
{
    /**
     * Backfill empty document.slug values from document metadata.
     */
    public function actionBackfillSlugs(): int
    {
        $rows = (new Query())
            ->from('{{%document}}')
            ->select([
                'id',
                'judul',
                'slug',
                'tipe_dokumen',
                'singkatan_jenis',
                'nomor_peraturan',
                'tahun_terbit',
            ])
            ->where(['or', ['slug' => null], ['slug' => '']])
            ->orderBy(['id' => SORT_ASC])
            ->all();

        if (empty($rows)) {
            $this->stdout("No documents need slug backfill.\n", Console::FG_GREEN);
            return self::EXIT_CODE_NORMAL;
        }

        $updated = $this->updateSlugs($rows, false);

        $this->stdout("Backfilled slug for {$updated} document(s).\n", Console::FG_GREEN);

        return self::EXIT_CODE_NORMAL;
    }

    /**
     * Regenerate slugs that are missing or exceed MAX_LENGTH (WAF safety backfill).
     */
    public function actionNormalizeSlugs(): int
    {
        $rows = (new Query())
            ->from('{{%document}}')
            ->select([
                'id',
                'judul',
                'slug',
                'tipe_dokumen',
                'singkatan_jenis',
                'nomor_peraturan',
                'tahun_terbit',
            ])
            ->where([
                'or',
                ['slug' => null],
                ['slug' => ''],
                ['>', new Expression('CHAR_LENGTH([[slug]])'), 60],
            ])
            ->orderBy(['id' => SORT_ASC])
            ->all();

        if (empty($rows)) {
            $this->stdout("No documents need slug normalization.\n", Console::FG_GREEN);
            return self::EXIT_CODE_NORMAL;
        }

        $updated = $this->updateSlugs($rows, true);

        $this->stdout("Normalized slug for {$updated} document(s).\n", Console::FG_GREEN);

        return self::EXIT_CODE_NORMAL;
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     */
    private function updateSlugs(array $rows, bool $logSamples): int
    {
        $updated = 0;
        $samples = 0;

        foreach ($rows as $row) {
            $oldSlug = $row['slug'] ?? '';
            $newSlug = DocumentSlug::fromDocument(
                (int) $row['tipe_dokumen'],
                $row['judul'] ?? '',
                $row['singkatan_jenis'] ?? null,
                $row['nomor_peraturan'] ?? null,
                $row['tahun_terbit'] ?? null
            );

            if ($newSlug === $oldSlug) {
                continue;
            }

            \Yii::$app->db->createCommand()->update(
                '{{%document}}',
                ['slug' => $newSlug],
                ['id' => $row['id']]
            )->execute();

            if ($logSamples && $samples < 5) {
                $this->stdout(
                    "  id {$row['id']}: \"{$oldSlug}\" → \"{$newSlug}\"\n",
                    Console::FG_YELLOW
                );
                $samples++;
            }

            $updated++;
        }

        return $updated;
    }
}
