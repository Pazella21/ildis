<?php

namespace common\components;

use frontend\models\DataLampiran;
use frontend\models\Dokumen;
use Yii;
use yii\db\Expression;
use yii\web\BadRequestHttpException;

class DocumentCounter
{
    public static function recordView(int $documentId): void
    {
        Dokumen::updateAll(
            ['hit_see' => new Expression('COALESCE(hit_see, 0) + 1')],
            ['id' => $documentId]
        );

        if (Yii::$app->has('visitorCounter')) {
            Yii::$app->visitorCounter->trackVisit((string) $documentId);
        }
    }

    public static function recordDownload(int $documentId): void
    {
        Dokumen::updateAll(
            ['hit_download' => new Expression('COALESCE(hit_download, 0) + 1')],
            ['id' => $documentId]
        );
    }

    /**
     * @return array{views: int, downloads: int}
     */
    public static function getCounts(int $documentId): array
    {
        $model = Dokumen::find()
            ->select(['hit_see', 'hit_download'])
            ->where(['id' => $documentId])
            ->one();

        return [
            'views' => (int) ($model->hit_see ?? 0),
            'downloads' => (int) ($model->hit_download ?? 0),
        ];
    }

    public static function fileBelongsToDocument(int $documentId, string $filename): bool
    {
        $doc = Dokumen::findOne($documentId);
        if ($doc === null) {
            return false;
        }

        $filename = basename($filename);
        if ($filename === '') {
            return false;
        }

        if (!empty($doc->abstrak) && basename($doc->abstrak) === $filename) {
            return true;
        }

        return DataLampiran::find()
            ->where(['id_dokumen' => $documentId, 'dokumen_lampiran' => $filename])
            ->exists();
    }

    public static function assertFileBelongsToDocument(int $documentId, string $filename): void
    {
        if (!self::fileBelongsToDocument($documentId, $filename)) {
            throw new BadRequestHttpException('File tidak terkait dengan dokumen ini.');
        }
    }
}
