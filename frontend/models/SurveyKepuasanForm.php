<?php

namespace frontend\models;

use common\models\SurveyKepuasan;
use Yii;
use yii\base\Model;

class SurveyKepuasanForm extends Model
{
    public $tingkat_kepuasan;
    public $isi;

    public function rules(): array
    {
        return [
            [['tingkat_kepuasan'], 'required'],
            [['tingkat_kepuasan'], 'integer', 'min' => 1, 'max' => 5],
            [['isi'], 'string', 'max' => 1000],
            [['tingkat_kepuasan'], 'validateDailyLimit'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'tingkat_kepuasan' => 'Tingkat Kepuasan',
            'isi' => 'Masukan / Saran',
        ];
    }

    public function validateDailyLimit($attribute): void
    {
        $ip = Yii::$app->request->userIP ?: '127.0.0.1';
        if (SurveyKepuasan::hasSubmittedToday($ip)) {
            $this->addError($attribute, 'Anda sudah mengisi survey hari ini. Terima kasih.');
        }
    }

    public function save(): bool
    {
        if (!$this->validate()) {
            return false;
        }

        $model = new SurveyKepuasan();
        $model->tingkat_kepuasan = (int) $this->tingkat_kepuasan;
        $model->isi = $this->isi;
        $model->ip_address = Yii::$app->request->userIP ?: '127.0.0.1';
        $model->created_at = date('Y-m-d H:i:s');
        $model->updated_at = date('Y-m-d H:i:s');

        return $model->save();
    }
}
