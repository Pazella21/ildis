<?php

namespace frontend\controllers;

use Yii;
use yii\web\Controller;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use common\models\MemberForm;
use common\models\VisitorStats;
use frontend\models\ContactForm;
use frontend\models\DokumenSearch;


class SiteController extends Controller
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'only' => ['logout', 'signup', 'profile'],
                'rules' => [
                    [
                        'actions' => ['signup'],
                        'allow' => true,
                        'roles' => ['?'],
                    ],
                    [
                        'actions' => ['logout', 'profile'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }

    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
        ];
    }

    public function actionIndex()
    {
        $berita = \frontend\models\Berita::find()
            ->where(['status' => 1])
            ->orderBy(['created_at' => SORT_DESC])
            ->limit(3)
            ->all();

        return $this->render('index', [
            'berita'       => $berita,
        ]);
    }


    public function actionSekilasSejarah()
    {
        return $this->render('sekilas-sejarah');
    }

    public function actionDasarHukum()
    {
        return $this->render('dasar-hukum');
    }

    public function actionVisi()
    {
        return $this->render('visi');
    }

    public function actionMisi()
    {
        return $this->render('misi');
    }

    public function actionSto()
    {
        return $this->render('struktur-organisasi');
    }

     public function actionStoinstansi()
    {
        return $this->render('struktur-organisasi-jdihinstansi');
    }

    public function actionSop()
    {
        return $this->render('sop');
    }

    public function actionModalikm()
    {
        return $this->render('modal');
    }

     public function actionVisitor()
    {
        $today = date('Y-m-d');
        $thisWeek = date('Y-m-d', strtotime('monday this week'));
        $thisMonth = date('Y-m-01');
        $thisYear = date('Y-01-01');

        $todayStat = VisitorStats::find()->where(['stat_type' => VisitorStats::TYPE_DAILY, 'stat_date' => $today, 'document_id' => null])->one();
        $weekStat = VisitorStats::find()->where(['stat_type' => VisitorStats::TYPE_WEEKLY, 'stat_date' => $thisWeek, 'document_id' => null])->one();
        $monthStat = VisitorStats::find()->where(['stat_type' => VisitorStats::TYPE_MONTHLY, 'stat_date' => $thisMonth, 'document_id' => null])->one();
        $yearStat = VisitorStats::find()->where(['stat_type' => VisitorStats::TYPE_YEARLY, 'stat_date' => $thisYear, 'document_id' => null])->one();
        $allTimeStat = VisitorStats::find()->where(['stat_type' => VisitorStats::TYPE_ALL_TIME, 'stat_date' => '1970-01-01', 'document_id' => null])->one();

        $yesterday = date('Y-m-d', strtotime('yesterday'));
        $lastWeekStart = date('Y-m-d', strtotime('monday last week'));
        $lastMonthStart = date('Y-m-01', strtotime('first day of last month'));
        $lastYearStart = date('Y-01-01', strtotime('first day of January last year'));

        $yesterdayStat = VisitorStats::find()->where(['stat_type' => VisitorStats::TYPE_DAILY, 'stat_date' => $yesterday, 'document_id' => null])->one();
        $lastWeekStat = VisitorStats::find()->where(['stat_type' => VisitorStats::TYPE_WEEKLY, 'stat_date' => $lastWeekStart, 'document_id' => null])->one();
        $lastMonthStat = VisitorStats::find()->where(['stat_type' => VisitorStats::TYPE_MONTHLY, 'stat_date' => $lastMonthStart, 'document_id' => null])->one();
        $lastYearStat = VisitorStats::find()->where(['stat_type' => VisitorStats::TYPE_YEARLY, 'stat_date' => $lastYearStart, 'document_id' => null])->one();

        $stats = [
            'today' => [
                'unique' => $todayStat ? (int)$todayStat->unique_visits : 0,
                'total' => $todayStat ? (int)$todayStat->total_visits : 0,
            ],
            'yesterday' => [
                'unique' => $yesterdayStat ? (int)$yesterdayStat->unique_visits : 0,
                'total' => $yesterdayStat ? (int)$yesterdayStat->total_visits : 0,
            ],
            'week' => [
                'unique' => $weekStat ? (int)$weekStat->unique_visits : 0,
                'total' => $weekStat ? (int)$weekStat->total_visits : 0,
            ],
            'lastWeek' => [
                'unique' => $lastWeekStat ? (int)$lastWeekStat->unique_visits : 0,
                'total' => $lastWeekStat ? (int)$lastWeekStat->total_visits : 0,
            ],
            'month' => [
                'unique' => $monthStat ? (int)$monthStat->unique_visits : 0,
                'total' => $monthStat ? (int)$monthStat->total_visits : 0,
            ],
            'lastMonth' => [
                'unique' => $lastMonthStat ? (int)$lastMonthStat->unique_visits : 0,
                'total' => $lastMonthStat ? (int)$lastMonthStat->total_visits : 0,
            ],
            'year' => [
                'unique' => $yearStat ? (int)$yearStat->unique_visits : 0,
                'total' => $yearStat ? (int)$yearStat->total_visits : 0,
            ],
            'lastYear' => [
                'unique' => $lastYearStat ? (int)$lastYearStat->unique_visits : 0,
                'total' => $lastYearStat ? (int)$lastYearStat->total_visits : 0,
            ],
            'allTime' => [
                'unique' => $allTimeStat ? (int)$allTimeStat->unique_visits : 0,
                'total' => $allTimeStat ? (int)$allTimeStat->total_visits : 0,
            ],
        ];

        return $this->render('visitor', ['stats' => $stats]);
    }

    public function actionPengelola()
    {
        return $this->render('pengelola');
    }



    public function actionLogin()
    {
        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $model = new MemberForm();
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            Yii::$app->session->setFlash('success', 'Selamat Datang Di Website JDIH');
            return $this->render('/profile/index', ['model' => $model]);
        } else {
            return $this->render('login', [
                'model' => $model,
            ]);
        }
    }

    public function actionLogout()
    {
        Yii::$app->user->logout();
        Yii::$app->session->setFlash('warning', 'anda telah logout dari web aplikasi kami');
        return $this->goHome();
    }

    public function actionKontaks()
    {
        $model = new ContactForm();
        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            if ($model->sendEmail(Yii::$app->params['adminEmail'])) {
                Yii::$app->session->setFlash('success', 'Thank you for contacting us. We will respond to you as soon as possible.');
            } else {
                Yii::$app->session->setFlash('error', 'There was an error sending your message.');
            }

            return $this->refresh();
        } else {
            return $this->render('kontak', [
                'model' => $model,
            ]);
        }
    }

    public function actionAbout()
    {
        return $this->render('about');
    }

    public function actionKontak()
    {
        return $this->render('kontak');
    }
}
