<?php

namespace common\components;

use Yii;
use yii\web\UploadedFile;
use backend\web\components\FileHelper;

class FileUploadService
{
    public static function handleUpload($model, $attribute, UploadedFile $file, $oldValue = null, $preserveOnEmpty = true)
    {
        $model->$attribute = FileHelper::sanitizeFilename($file->name);
        $path = Yii::getAlias('@common') . '/dokumen/' . $model->$attribute;

        if (!$file->saveAs($path)) {
            Yii::error("Failed to save uploaded file for attribute {$attribute}");
            return false;
        }

        return true;
    }

    public static function handleUploadWithOld($model, $attribute, $oldValue)
    {
        $file = UploadedFile::getInstance($model, $attribute);

        if (!empty($file)) {
            $model->$attribute = FileHelper::sanitizeFilename($file->name);
            $path = Yii::getAlias('@common') . '/dokumen/' . $model->$attribute;

            if (!$file->saveAs($path)) {
                Yii::error("Failed to save uploaded file for attribute {$attribute}");
                return false;
            }
        } else {
            $model->$attribute = $oldValue;
        }

        return true;
    }
}