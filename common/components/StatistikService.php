<?php

namespace common\components;

use common\models\DocumentType;
use common\models\VisitorLog;
use common\models\VisitorStats;
use frontend\models\Dokumen;
use Yii;
use yii\db\Expression;

class StatistikService
{
    public static function getSummary(): array
    {
        return [
            'totalDokumen' => self::getTotalPublishedDocuments(),
            'byType' => self::getTotalsByType(),
            'totalPuu' => self::getPuuCollectionCount(),
            'peraturanByStatus' => self::getPeraturanByStatus(),
            'peraturanByJenis' => self::getPeraturanByJenis(),
            'documentVisitors' => self::getDocumentVisitorCount(),
            'siteVisitors' => self::getSiteVisitorCount(),
        ];
    }

    public static function getTotalPublishedDocuments(): int
    {
        return (int) Dokumen::find()->where(['is_publish' => 1])->count();
    }

    /**
     * @return array<string, int>
     */
    public static function getTotalsByType(): array
    {
        return [
            'peraturan' => (int) Dokumen::find()->where(['is_publish' => 1, 'tipe_dokumen' => Dokumen::TYPE_PERATURAN])->count(),
            'monografi' => (int) Dokumen::find()->where(['is_publish' => 1, 'tipe_dokumen' => Dokumen::TYPE_MONOGRAFI])->count(),
            'artikel' => (int) Dokumen::find()->where(['is_publish' => 1, 'tipe_dokumen' => Dokumen::TYPE_ARTIKEL])->count(),
            'putusan' => (int) Dokumen::find()->where(['is_publish' => 1, 'tipe_dokumen' => Dokumen::TYPE_PUTUSAN])->count(),
        ];
    }

    public static function getPuuCollectionCount(): int
    {
        $puuTypes = DocumentType::groupTypeNames(DocumentGroup::LEGISLATION_FORMATION);
        if (empty($puuTypes)) {
            return 0;
        }

        return (int) Dokumen::find()
            ->where(['is_publish' => 1, 'tipe_dokumen' => Dokumen::TYPE_MONOGRAFI])
            ->andWhere(['jenis_peraturan' => $puuTypes])
            ->count();
    }

    /**
     * @return array<string, int>
     */
    public static function getPeraturanByStatus(): array
    {
        $rows = Dokumen::find()
            ->select(['status', new Expression('COUNT(*) AS total')])
            ->where(['is_publish' => 1, 'tipe_dokumen' => Dokumen::TYPE_PERATURAN])
            ->andWhere(['not', ['status' => null]])
            ->andWhere(['<>', 'status', ''])
            ->groupBy('status')
            ->orderBy(['total' => SORT_DESC])
            ->asArray()
            ->all();

        $result = [];
        foreach ($rows as $row) {
            $result[$row['status']] = (int) $row['total'];
        }

        return $result;
    }

    /**
     * @return array<string, int>
     */
    public static function getPeraturanByJenis(): array
    {
        $rows = Dokumen::find()
            ->select(['jenis_peraturan', new Expression('COUNT(*) AS total')])
            ->where(['is_publish' => 1, 'tipe_dokumen' => Dokumen::TYPE_PERATURAN])
            ->andWhere(['not', ['jenis_peraturan' => null]])
            ->andWhere(['<>', 'jenis_peraturan', ''])
            ->groupBy('jenis_peraturan')
            ->orderBy(['total' => SORT_DESC])
            ->limit(15)
            ->asArray()
            ->all();

        $result = [];
        foreach ($rows as $row) {
            $result[$row['jenis_peraturan']] = (int) $row['total'];
        }

        return $result;
    }

    public static function getDocumentVisitorCount(): int
    {
        $fromStats = (int) VisitorStats::find()
            ->where([
                'stat_type' => VisitorStats::TYPE_ALL_TIME,
                'stat_date' => '1970-01-01',
            ])
            ->andWhere(['not', ['document_id' => null]])
            ->sum('unique_visits');

        if ($fromStats > 0) {
            return $fromStats;
        }

        return (int) VisitorLog::find()
            ->where(['not', ['document_id' => null]])
            ->count();
    }

    public static function getSiteVisitorCount(): int
    {
        $stat = VisitorStats::find()
            ->where([
                'stat_type' => VisitorStats::TYPE_ALL_TIME,
                'stat_date' => '1970-01-01',
                'document_id' => null,
            ])
            ->one();

        return $stat ? (int) $stat->unique_visits : 0;
    }
}
