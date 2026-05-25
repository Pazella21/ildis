<?php

namespace backend\controllers;

use Yii;
use yii\web\Controller;
use yii\filters\AccessControl;
use common\models\VisitorStats;

/**
 * VisitorReportController displays visitor statistics for the admin dashboard.
 */
class VisitorReportController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['superadmin'],
                    ],
                ],
            ],
        ];
    }

    /**
     * Displays visitor statistics cards and comparisons.
     *
     * @return string
     */
    public function actionIndex()
    {
        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $thisWeekStart = date('Y-m-d', strtotime('monday this week'));
        $lastWeekStart = date('Y-m-d', strtotime('monday last week'));
        $thisMonthStart = date('Y-m-01');
        $lastMonthStart = date('Y-m-01', strtotime('first day of last month'));
        $thisYearStart = date('Y-01-01');

        $cards = [
            'daily' => $this->getStat(VisitorStats::TYPE_DAILY, $today),
            'weekly' => $this->getStat(VisitorStats::TYPE_WEEKLY, $thisWeekStart),
            'monthly' => $this->getStat(VisitorStats::TYPE_MONTHLY, $thisMonthStart),
            'yearly' => $this->getStat(VisitorStats::TYPE_YEARLY, $thisYearStart),
            'all_time' => $this->getStat(VisitorStats::TYPE_ALL_TIME, '1970-01-01'),
        ];

        $comparisons = [
            'today_vs_yesterday' => [
                'current' => $cards['daily'],
                'previous' => $this->getStat(VisitorStats::TYPE_DAILY, $yesterday),
            ],
            'this_week_vs_last_week' => [
                'current' => $cards['weekly'],
                'previous' => $this->getStat(VisitorStats::TYPE_WEEKLY, $lastWeekStart),
            ],
            'this_month_vs_last_month' => [
                'current' => $cards['monthly'],
                'previous' => $this->getStat(VisitorStats::TYPE_MONTHLY, $lastMonthStart),
            ],
        ];

        return $this->render('index', [
            'cards' => $cards,
            'comparisons' => $comparisons,
        ]);
    }

    /**
     * Returns JSON data for Chart.js showing last 30 days of unique visits.
     *
     * @return array
     */
    public function actionAjaxChart()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $startDate = date('Y-m-d', strtotime('-29 days'));

        $stats = VisitorStats::find()
            ->select(['stat_date', 'unique_visits'])
            ->where(['stat_type' => VisitorStats::TYPE_DAILY])
            ->andWhere(['>=', 'stat_date', $startDate])
            ->andWhere(['document_id' => null])
            ->orderBy('stat_date ASC')
            ->asArray()
            ->all();

        $labels = [];
        $data = [];

        foreach ($stats as $stat) {
            $labels[] = Yii::$app->formatter->asDate($stat['stat_date'], 'dd MMM');
            $data[] = (int) $stat['unique_visits'];
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Kunjungan Unik',
                    'data' => $data,
                    'borderColor' => '#007bff',
                    'fill' => false,
                ],
            ],
        ];
    }

    /**
     * Finds a stat by type and date with document_id = null.
     *
     * @param string $type the stat_type constant
     * @param string $date the stat_date value
     * @return VisitorStats|array the model or a default zero array
     */
    protected function getStat($type, $date)
    {
        $stat = VisitorStats::find()
            ->where(['stat_type' => $type, 'stat_date' => $date, 'document_id' => null])
            ->one();

        return $stat ?: ['total_visits' => 0, 'unique_visits' => 0];
    }
}
