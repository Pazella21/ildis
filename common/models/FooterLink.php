<?php

namespace common\models;

use Yii;
use yii\db\ActiveRecord;
use yii\db\Expression;

class FooterLink extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%footer_link}}';
    }

    public function rules()
    {
        return [
            [['section_id', 'label'], 'required'],
            [['section_id'], 'integer'],
            [['label'], 'string', 'max' => 255],
            [['url'], 'string', 'max' => 500],
            [['url'], 'default', 'value' => '#'],
            [['icon_class'], 'string', 'max' => 100],
            [['sort_order'], 'integer'],
            [['sort_order'], 'default', 'value' => 0],
            [['status'], 'integer'],
            [['status'], 'default', 'value' => 1],
            [['open_in_new_tab'], 'integer'],
            [['open_in_new_tab'], 'default', 'value' => 0],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'section_id' => 'Bagian',
            'label' => 'Label',
            'url' => 'URL',
            'icon_class' => 'Ikon',
            'sort_order' => 'Urutan',
            'status' => 'Status',
            'open_in_new_tab' => 'Buka di Tab Baru',
            'created_at' => 'Dibuat Pada',
            'updated_at' => 'Diubah Pada',
        ];
    }

    public function behaviors()
    {
        return [
            'timestamp' => [
                'class' => 'yii\behaviors\TimestampBehavior',
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => ['created_at', 'updated_at'],
                    ActiveRecord::EVENT_BEFORE_UPDATE => ['updated_at'],
                ],
                'value' => new Expression('NOW()'),
            ],
        ];
    }

    public function getSection()
    {
        return $this->hasOne(FooterSection::class, ['id' => 'section_id']);
    }

    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);
        Yii::$app->cache->delete('footer_sections_active');
    }

    public function afterDelete()
    {
        parent::afterDelete();
        Yii::$app->cache->delete('footer_sections_active');
    }
}