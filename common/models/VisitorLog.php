<?php

namespace common\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "{{%visitor_log}}".
 *
 * @property int $id
 * @property string $visitor_fingerprint
 * @property string $visitor_cookie_id
 * @property string|null $document_id
 * @property string $page_url
 * @property string $visit_date
 * @property string $visit_time
 * @property int $is_unique
 * @property string|null $created_at
 */
class VisitorLog extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%visitor_log}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['visitor_fingerprint', 'visitor_cookie_id', 'page_url', 'visit_date', 'visit_time'], 'required'],
            [['visitor_fingerprint', 'visitor_cookie_id'], 'string', 'max' => 64],
            [['document_id'], 'string', 'max' => 100],
            [['page_url'], 'string', 'max' => 500],
            [['is_unique'], 'integer'],
            [['visit_date', 'visit_time', 'created_at'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'visitor_fingerprint' => 'Visitor Fingerprint',
            'visitor_cookie_id' => 'Visitor Cookie ID',
            'document_id' => 'Document ID',
            'page_url' => 'Page URL',
            'is_unique' => 'Is Unique',
            'visit_date' => 'Visit Date',
            'visit_time' => 'Visit Time',
            'created_at' => 'Created At',
        ];
    }
}
