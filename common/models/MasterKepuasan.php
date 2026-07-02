<?php

namespace common\models;

use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property string $keterangan
 */
class MasterKepuasan extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%master_kepuasan}}';
    }

    public function rules(): array
    {
        return [
            [['keterangan'], 'required'],
            [['keterangan'], 'string', 'max' => 255],
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function optionList(): array
    {
        return static::find()
            ->select(['keterangan', 'id'])
            ->indexBy('id')
            ->orderBy(['id' => SORT_ASC])
            ->column();
    }
}
