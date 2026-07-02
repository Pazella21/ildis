<?php

namespace common\models;

use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int|null $tingkat_kepuasan
 * @property string|null $ip_address
 * @property string|null $created_at
 * @property string|null $updated_at
 * @property string|null $isi
 */
class SurveyKepuasan extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%survey_kepuasan}}';
    }

    public function rules(): array
    {
        return [
            [['tingkat_kepuasan'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['ip_address'], 'string', 'max' => 255],
            [['isi'], 'string', 'max' => 1000],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id' => 'ID',
            'tingkat_kepuasan' => 'Tingkat Kepuasan',
            'ip_address' => 'Alamat IP',
            'created_at' => 'Tanggal',
            'isi' => 'Masukan',
        ];
    }

    /**
     * @return array{
     *   total: int,
     *   average: float,
     *   ikmIndex: float,
     *   distribution: array<int, int>
     * }
     */
    public static function getAggregateStats(): array
    {
        $total = (int) static::find()->count();
        $average = (float) static::find()->average('tingkat_kepuasan');
        $distribution = [];

        for ($i = 1; $i <= 5; $i++) {
            $distribution[$i] = (int) static::find()->where(['tingkat_kepuasan' => $i])->count();
        }

        return [
            'total' => $total,
            'average' => round($average, 2),
            'ikmIndex' => round($average * 25, 2),
            'distribution' => $distribution,
        ];
    }

    public static function hasSubmittedToday(string $ipAddress): bool
    {
        return static::find()
            ->where(['ip_address' => $ipAddress])
            ->andWhere(['>=', 'created_at', date('Y-m-d 00:00:00')])
            ->exists();
    }
}
