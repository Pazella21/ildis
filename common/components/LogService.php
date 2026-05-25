<?php

namespace common\components;

use backend\models\LogPustakawan;
use Yii;

class LogService
{
    public static function log($dokumenId, $controller, $aksi)
    {
        $log = new LogPustakawan();
        $log->dokumen_id = $dokumenId;
        $log->controller = $controller;
        $log->aksi = $aksi;
        $log->keterangan = 'User ' . Yii::$app->user->identity->username
            . ' melakukan ' . strtolower($aksi) . ' pada '
            . DateHelper::formatIndonesian(date('Y-m-d H:i:s'));

        if (!$log->save()) {
            Yii::error('Failed to save log: ' . json_encode($log->getErrors()));
        }

        return $log;
    }
}