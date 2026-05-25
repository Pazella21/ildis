<?php

namespace common\components;

use Yii;
use yii\web\NotFoundHttpException;

class SafeDownload
{
    private static $allowedBasePaths = [
        '@common/dokumen',
        '@common/uploads',
    ];

    private static $allowedExtensions = [
        'pdf', 'jpg', 'jpeg', 'png', 'gif', 'bmp', 'doc', 'docx',
        'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'rtf', 'odt', 'zip',
    ];

    public static function sendFile($baseAlias, $filename)
    {
        $basePath = Yii::getAlias($baseAlias);
        $realBasePath = realpath($basePath);

        if ($realBasePath === false) {
            throw new NotFoundHttpException('Direktori tidak ditemukan.');
        }

        $filename = basename($filename);

        if (empty($filename)) {
            throw new NotFoundHttpException('Nama file tidak valid.');
        }

        $fullPath = $realBasePath . DIRECTORY_SEPARATOR . $filename;
        $realFilePath = realpath($fullPath);

        if ($realFilePath === false || !file_exists($realFilePath)) {
            throw new NotFoundHttpException('File tidak ditemukan.');
        }

        if (strpos($realFilePath, $realBasePath) !== 0) {
            throw new NotFoundHttpException('Akses file ditolak.');
        }

        $ext = strtolower(pathinfo($realFilePath, PATHINFO_EXTENSION));
        if (!in_array($ext, self::$allowedExtensions, true)) {
            throw new NotFoundHttpException('Tipe file tidak diizinkan.');
        }

        return Yii::$app->response->sendFile($realFilePath);
    }
}