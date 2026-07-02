<?php

namespace common\components;

use frontend\models\Dokumen;
use yii\db\Expression;

class DocumentPopularityService
{
    /**
     * @return Dokumen[]
     */
    public static function getPopularDocuments(int $limit = 10): array
    {
        return Dokumen::find()
            ->where(['is_publish' => 1])
            ->andWhere(['>', new Expression('COALESCE(hit_see, 0) + COALESCE(hit_download, 0)'), 0])
            ->orderBy(new Expression('COALESCE(hit_see, 0) + COALESCE(hit_download, 0) DESC'))
            ->limit($limit)
            ->all();
    }

    public static function getPopularityScore(Dokumen $model): int
    {
        return (int) ($model->hit_see ?? 0) + (int) ($model->hit_download ?? 0);
    }
}
