<?php
namespace common\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "{{%visitor_stats}}".
 *
 * @property int $id
 * @property string $stat_type
 * @property string|null $stat_date
 * @property int|null $total_visits
 * @property int|null $unique_visits
 * @property string|null $document_id
 * @property string|null $updated_at
 */
class VisitorStats extends ActiveRecord
{
    public const TYPE_DAILY = 'daily';
    public const TYPE_WEEKLY = 'weekly';
    public const TYPE_MONTHLY = 'monthly';
    public const TYPE_YEARLY = 'yearly';
    public const TYPE_ALL_TIME = 'all_time';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%visitor_stats}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['stat_type'], 'required'],
            [['stat_type'], 'in', 'range' => [
                self::TYPE_DAILY,
                self::TYPE_WEEKLY,
                self::TYPE_MONTHLY,
                self::TYPE_YEARLY,
                self::TYPE_ALL_TIME,
            ]],
            [['stat_date'], 'required'],
            [['total_visits', 'unique_visits'], 'integer'],
            [['document_id'], 'string', 'max' => 100],
            [['stat_date', 'updated_at'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'stat_type' => 'Stat Type',
            'stat_date' => 'Stat Date',
            'total_visits' => 'Total Visits',
            'unique_visits' => 'Unique Visits',
            'document_id' => 'Document ID',
            'updated_at' => 'Updated At',
        ];
    }
}
