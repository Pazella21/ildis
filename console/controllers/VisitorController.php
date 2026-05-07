<?php
namespace console\controllers;

use Yii;
use yii\console\Controller;
use common\models\VisitorLog;
use common\models\VisitorStats;

class VisitorController extends Controller
{
    public function actionAggregate($days = 7)
    {
        $this->acquireLock();
        $this->stdout("Starting aggregation for last {$days} days...\n");

        $startDate = date('Y-m-d', strtotime("-{$days} days"));
        $endDate = date('Y-m-d', strtotime('+1 day'));

        VisitorStats::deleteAll(['>=', 'stat_date', $startDate]);

        // Compute total visits per period (unique + non-unique)
        $totals = (new \yii\db\Query())
            ->select([
                'COUNT(*) AS total_visits',
                "'daily' AS stat_type",
                'visit_date AS stat_date',
                'document_id',
            ])
            ->from('{{%visitor_log}}')
            ->where(['>=', 'visit_date', $startDate])
            ->groupBy(['visit_date', 'document_id'])
            ->all();

        // Compute unique visits per period
        $uniques = (new \yii\db\Query())
            ->select([
                'COUNT(*) AS unique_visits',
                "'daily' AS stat_type",
                'visit_date AS stat_date',
                'document_id',
            ])
            ->from('{{%visitor_log}}')
            ->where(['>=', 'visit_date', $startDate])
            ->andWhere(['is_unique' => 1])
            ->groupBy(['visit_date', 'document_id'])
            ->all();

        // Build combined aggregate rows
        $aggregates = [];

        foreach ($totals as $row) {
            $key = "{$row['stat_type']}:{$row['stat_date']}:" . ($row['document_id'] ?: 'site');
            $aggregates[$key] = [
                'stat_type' => $row['stat_type'],
                'stat_date' => $row['stat_date'],
                'document_id' => $row['document_id'],
                'total_visits' => (int) $row['total_visits'],
                'unique_visits' => 0,
            ];
        }

        foreach ($uniques as $row) {
            $key = "{$row['stat_type']}:{$row['stat_date']}:" . ($row['document_id'] ?: 'site');
            if (isset($aggregates[$key])) {
                $aggregates[$key]['unique_visits'] = (int) $row['unique_visits'];
            }
        }

        $this->insertAggregates($aggregates);

        $this->stdout("Aggregation complete. Inserted " . count($aggregates) . " stat rows.\n");
        $this->releaseLock();
    }

    protected function insertAggregates($aggregates)
    {
        if (empty($aggregates)) {
            return;
        }

        $columns = ['stat_type', 'stat_date', 'document_id', 'total_visits', 'unique_visits'];
        $values = [];

        foreach ($aggregates as $row) {
            $values[] = [
                $row['stat_type'],
                $row['stat_date'],
                $row['document_id'],
                $row['total_visits'],
                $row['unique_visits'],
            ];
        }

        Yii::$app->db->createCommand()->batchInsert(VisitorStats::tableName(), $columns, $values)->execute();
    }

    protected function acquireLock()
    {
        $result = Yii::$app->db->createCommand("SELECT GET_LOCK('visitor_aggregate', 60)")->queryScalar();
        if (!$result) {
            throw new \Exception("Could not acquire aggregation lock. Another process may be running.");
        }
    }

    protected function releaseLock()
    {
        Yii::$app->db->createCommand("SELECT RELEASE_LOCK('visitor_aggregate')")->execute();
    }
}
