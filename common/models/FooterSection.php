<?php

namespace common\models;

use Yii;
use yii\db\ActiveRecord;
use yii\db\Expression;

class FooterSection extends ActiveRecord
{
    const TYPE_NAV = 'nav';
    const TYPE_SOCIAL = 'social';

    public static function tableName()
    {
        return '{{%footer_section}}';
    }

    public function rules()
    {
        return [
            [['title', 'type'], 'required'],
            [['title'], 'string', 'max' => 255],
            [['type'], 'in', 'range' => [self::TYPE_NAV, self::TYPE_SOCIAL]],
            [['sort_order'], 'integer'],
            [['sort_order'], 'default', 'value' => 0],
            [['status'], 'integer'],
            [['status'], 'default', 'value' => 1],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'title' => 'Judul',
            'type' => 'Tipe',
            'sort_order' => 'Urutan',
            'status' => 'Status',
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

    public function getLinks()
    {
        return $this->hasMany(FooterLink::class, ['section_id' => 'id'])
            ->orderBy(['sort_order' => SORT_ASC]);
    }

    public function getActiveLinks()
    {
        return $this->getLinks()->andOnCondition(['footer_link.status' => 1]);
    }

    public static function getActiveSections()
    {
        $db = self::getDb();
        $duration = 3600;

        return $db->cache(function ($db) {
            return self::find()
                ->where(['status' => 1])
                ->orderBy(['sort_order' => SORT_ASC])
                ->with('activeLinks')
                ->all();
        }, $duration);
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