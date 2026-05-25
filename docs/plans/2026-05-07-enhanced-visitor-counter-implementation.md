# Enhanced Visitor Counter Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Build a production-grade visitor analytics system with cookie-based session tracking, per-page/document visit tracking, daily/weekly/monthly/yearly/all-time statistics aggregation, and a backend dashboard.

**Architecture:** Raw events stored in `visitor_log`; pre-aggregated counters in `visitor_stats`. A nightly console command rebuilds the last 7 days of stats from raw events. A Yii2 component (`VisitorCounter`) handles request-time deduplication via cookies.

**Tech Stack:** Yii 2 Advanced Template, MySQL/MariaDB, PHP 7.4+, Codeception, AdminLTE (backend UI)

---

## Prerequisites

Before starting, ensure you have reviewed the design document:
- `docs/plans/2026-05-07-enhanced-visitor-counter-design.md`

Verify project setup:
```bash
vendor/bin/codecept --version
```

---

### Task 1: Create `visitor_log` Table Migration

**Goal:** Create the raw event log table.

**Files:**
- Create: `console/migrations/_next/m260507_000001_create_table_visitor_log.php`

**Step 1: Write the migration**

```php
namespace _next;

use yii\db\Migration;

class m260507_000001_create_table_visitor_log extends Migration
{
    public function safeUp()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%visitor_log}}', [
            'id' => $this->primaryKey(),
            'visitor_fingerprint' => $this->string(64)->notNull(),
            'visitor_cookie_id' => $this->string(64)->notNull(),
            'document_id' => $this->string(100),
            'page_url' => $this->string(500)->notNull(),
            'visit_date' => $this->date()->notNull(),
            'visit_time' => $this->dateTime()->notNull(),
            'is_unique' => $this->tinyInteger(1)->notNull()->defaultValue(0),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
        ], $tableOptions);

        $this->createIndex(
            'idx_visitor_log_fingerprint_date',
            '{{%visitor_log}}',
            ['visitor_fingerprint', 'visit_date']
        );
        $this->createIndex(
            'idx_visitor_log_document_date',
            '{{%visitor_log}}',
            ['document_id', 'visit_date']
        );
        $this->createIndex(
            'idx_visitor_log_visit_time',
            '{{%visitor_log}}',
            'visit_time'
        );
    }

    public function safeDown()
    {
        $this->dropTable('{{%visitor_log}}');
    }
}
```

**Step 2: Run migration**

```bash
php yii migrate --migrationPath=console/migrations/_next --interactive=0
```

Expected: `Done` - both migrations applied successfully.

**Step 3: Commit**

```bash
git add console/migrations/_next/m260507_000001_create_table_visitor_log.php
git commit -m "feat(visitor-counter): add visitor_log migration"
```

---

### Task 2: Create `visitor_stats` Table Migration

**Goal:** Create the pre-aggregated statistics table.

**Files:**
- Create: `console/migrations/_next/m260507_000002_create_table_visitor_stats.php`

**Step 1: Write the migration**

```php
namespace _next;

use yii\db\Migration;

class m260507_000002_create_table_visitor_stats extends Migration
{
    public function safeUp()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%visitor_stats}}', [
            'id' => $this->primaryKey(),
            'stat_type' => "ENUM('daily','weekly','monthly','yearly','all_time') NOT NULL",
            'stat_date' => $this->date()->notNull(),
            'document_id' => $this->string(100),
            'total_visits' => $this->integer()->unsigned()->notNull()->defaultValue(0),
            'unique_visits' => $this->integer()->unsigned()->notNull()->defaultValue(0),
            'updated_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'),
        ], $tableOptions);

        $this->createIndex(
            'idx_visitor_stats_type_date_doc',
            '{{%visitor_stats}}',
            ['stat_type', 'stat_date', 'document_id'],
            true
        );
    }

    public function safeDown()
    {
        $this->dropTable('{{%visitor_stats}}');
    }
}
```

**Step 2: Run migration**

```bash
php yii migrate --migrationPath=console/migrations/_next --interactive=0
```

Expected: `Done` - visitor_stats table created.

**Step 3: Commit**

```bash
git add console/migrations/_next/m260507_000002_create_table_visitor_stats.php
git commit -m "feat(visitor-counter): add visitor_stats migration"
```

---

### Task 3: Create `VisitorLog` ActiveRecord Model

**Goal:** Yii2 AR model for `visitor_log`.

**Files:**
- Create: `common/models/VisitorLog.php`

**Step 1: Write the model**

```php
namespace common\models;

use Yii;
use yii\db\ActiveRecord;

class VisitorLog extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%visitor_log}}';
    }

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

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'visitor_fingerprint' => 'Visitor Fingerprint',
            'visitor_cookie_id' => 'Visitor Cookie ID',
            'document_id' => 'Document ID',
            'page_url' => 'Page URL',
            'visit_date' => 'Visit Date',
            'visit_time' => 'Visit Time',
            'is_unique' => 'Is Unique',
            'created_at' => 'Created At',
        ];
    }
}
```

**Step 2: Test instantiation**

```bash
php -r "require 'vendor/autoload.php'; new \common\models\VisitorLog(); echo 'OK' . PHP_EOL;"
```

Expected: `OK`

**Step 3: Commit**

```bash
git add common/models/VisitorLog.php
git commit -m "feat(visitor-counter): add VisitorLog AR model"
```

---

### Task 4: Create `VisitorStats` ActiveRecord Model

**Goal:** Yii2 AR model for `visitor_stats`.

**Files:**
- Create: `common/models/VisitorStats.php`

**Step 1: Write the model**

```php
namespace common\models;

use Yii;
use yii\db\ActiveRecord;

class VisitorStats extends ActiveRecord
{
    const TYPE_DAILY = 'daily';
    const TYPE_WEEKLY = 'weekly';
    const TYPE_MONTHLY = 'monthly';
    const TYPE_YEARLY = 'yearly';
    const TYPE_ALL_TIME = 'all_time';

    public static function tableName()
    {
        return '{{%visitor_stats}}';
    }

    public function rules()
    {
        return [
            [['stat_type', 'stat_date'], 'required'],
            [['total_visits', 'unique_visits'], 'integer'],
            [['stat_type'], 'in', 'range' => [self::TYPE_DAILY, self::TYPE_WEEKLY, self::TYPE_MONTHLY, self::TYPE_YEARLY, self::TYPE_ALL_TIME]],
            [['stat_date', 'updated_at'], 'safe'],
            [['document_id'], 'string', 'max' => 100],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'stat_type' => 'Stat Type',
            'stat_date' => 'Stat Date',
            'document_id' => 'Document ID',
            'total_visits' => 'Total Visits',
            'unique_visits' => 'Unique Visits',
            'updated_at' => 'Updated At',
        ];
    }
}
```

**Step 2: Test instantiation**

```bash
php -r "require 'vendor/autoload.php'; new \common\models\VisitorStats(); echo 'OK' . PHP_EOL;"
```

Expected: `OK`

**Step 3: Commit**

```bash
git add common/models/VisitorStats.php
git commit -m "feat(visitor-counter): add VisitorStats AR model"
```

---

### Task 5: Write Unit Test — Fingerprint Generation

**Goal:** Ensure `VisitorCounter` generates consistent fingerprints.

**Files:**
- Create: `common/tests/unit/components/VisitorCounterTest.php`

**Step 1: Write the failing test**

```php
namespace common\tests\unit\components;

use Codeception\Test\Unit;
use common\components\VisitorCounter;

class VisitorCounterTest extends Unit
{
    public function testGenerateFingerprint()
    {
        $counter = new VisitorCounter();
        $fingerprint = $counter->generateFingerprint('192.168.1.1', 'Mozilla/5.0');

        $this->assertEquals(32, strlen($fingerprint));
        $this->assertEquals($fingerprint, $counter->generateFingerprint('192.168.1.1', 'Mozilla/5.0'));
        $this->assertNotEquals($fingerprint, $counter->generateFingerprint('192.168.1.2', 'Mozilla/5.0'));
    }
}
```

**Step 2: Run test to verify it fails**

```bash
vendor/bin/codecept run unit common/tests/unit/components/VisitorCounterTest.php::testGenerateFingerprint -v
```

Expected: FAIL with `Class common\components\VisitorCounter not found`

**Step 3: Commit test**

```bash
git add common/tests/unit/components/VisitorCounterTest.php
git commit -m "test(visitor-counter): add fingerprint generation test"
```

---

### Task 6: Implement `VisitorCounter` Component — Fingerprint & Cookie

**Goal:** Implement the core visitor tracking component skeleton.

**Files:**
- Create: `common/components/VisitorCounter.php`

**Step 1: Write minimal implementation**

```php
namespace common\components;

use Yii;
use yii\base\Component;
use common\models\VisitorLog;
use common\models\VisitorStats;

class VisitorCounter extends Component
{
    public $deduplicateWindowMinutes = 30;
    public $cookieName = '__visitor_id';
    public $cookieExpiryDays = 180;

    /**
     * Generate a visitor fingerprint from IP and User Agent.
     */
    public function generateFingerprint($ip, $userAgent)
    {
        return md5($ip . '|' . $userAgent);
    }

    /**
     * Get or create the visitor cookie ID.
     */
    public function getVisitorCookieId()
    {
        $cookies = Yii::$app->request->cookies;
        $cookieId = $cookies->getValue($this->cookieName, null);

        if (!$cookieId) {
            $cookieId = $this->generateUuid();
            Yii::$app->response->cookies->add(new \yii\web\Cookie([
                'name' => $this->cookieName,
                'value' => $cookieId,
                'expire' => time() + 86400 * $this->cookieExpiryDays,
                'httpOnly' => true,
                'secure' => getenv('YII_ENV') === 'prod',
                'sameSite' => 'Lax',
            ]));
        }

        return $cookieId;
    }

    protected function generateUuid()
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
```

**Step 2: Run test to verify it passes**

```bash
vendor/bin/codecept run unit common/tests/unit/components/VisitorCounterTest.php::testGenerateFingerprint -v
```

Expected: PASS

**Step 3: Commit**

```bash
git add common/components/VisitorCounter.php
git commit -m "feat(visitor-counter): add VisitorCounter with fingerprint and cookie logic"
```

---

### Task 7: Write Unit Test — Deduplication Check

**Goal:** Test that repeated visits within the 30-minute window are flagged as non-unique.

**Files:**
- Modify: `common/tests/unit/components/VisitorCounterTest.php`
- Ensure test DB has `visitor_log` table (run migrate in test env if needed)

**Step 1: Write the failing test**

```php
public function testIsUniqueVisit()
{
    $counter = new VisitorCounter();
    $fingerprint = $counter->generateFingerprint('10.0.0.1', 'TestAgent/1.0');
    $documentId = 'peraturan_123';

    // First visit should be unique
    $this->assertTrue($counter->isUniqueVisit($fingerprint, $documentId));

    // Simulate an existing log entry within window
    $log = new VisitorLog();
    $log->visitor_fingerprint = $fingerprint;
    $log->visitor_cookie_id = 'test-cookie-id';
    $log->document_id = $documentId;
    $log->page_url = 'http://localhost/peraturan/view?id=123';
    $log->visit_date = date('Y-m-d');
    $log->visit_time = date('Y-m-d H:i:s');
    $log->is_unique = 1;
    $log->save();

    // Second visit within window should NOT be unique
    $this->assertFalse($counter->isUniqueVisit($fingerprint, $documentId));
}
```

**Step 2: Run test to verify it fails**

```bash
vendor/bin/codecept run unit common/tests/unit/components/VisitorCounterTest.php::testIsUniqueVisit -v
```

Expected: FAIL with `Method isUniqueVisit() does not exist`

**Step 3: Commit test**

```bash
git add common/tests/unit/components/VisitorCounterTest.php
git commit -m "test(visitor-counter): add deduplication check test"
```

---

### Task 8: Implement Deduplication & Visit Tracking

**Goal:** Add `isUniqueVisit()` and `trackVisit()` to `VisitorCounter`.

**Files:**
- Modify: `common/components/VisitorCounter.php`

**Step 1: Append methods to VisitorCounter**

```php
    /**
     * Check if this fingerprint/document combination is unique within the window.
     */
    public function isUniqueVisit($fingerprint, $documentId)
    {
        $since = date('Y-m-d H:i:s', strtotime('-' . $this->deduplicateWindowMinutes . ' minutes'));
        $today = date('Y-m-d');

        $query = VisitorLog::find()
            ->where(['visitor_fingerprint' => $fingerprint])
            ->andWhere(['visit_date' => $today])
            ->andWhere(['>=', 'visit_time', $since]);

        if ($documentId !== null) {
            $query->andWhere(['document_id' => $documentId]);
        } else {
            $query->andWhere(['document_id' => null]);
        }

        return !$query->exists();
    }

    /**
     * Record a visit and update stats.
     */
    public function trackVisit($documentId = null, $pageUrl = null)
    {
        $request = Yii::$app->request;
        $ip = $request->userIP ?: '127.0.0.1';
        $userAgent = $request->userAgent ?: 'Unknown';
        $fingerprint = $this->generateFingerprint($ip, $userAgent);
        $cookieId = $this->getVisitorCookieId();
        $pageUrl = $pageUrl ?: $request->absoluteUrl;
        $today = date('Y-m-d');
        $now = date('Y-m-d H:i:s');
        $isUnique = $this->isUniqueVisit($fingerprint, $documentId) ? 1 : 0;

        $log = new VisitorLog();
        $log->visitor_fingerprint = $fingerprint;
        $log->visitor_cookie_id = $cookieId;
        $log->document_id = $documentId;
        $log->page_url = $pageUrl;
        $log->visit_date = $today;
        $log->visit_time = $now;
        $log->is_unique = $isUnique;

        try {
            $log->save();
        } catch (\yii\db\Exception $e) {
            Yii::error('VisitorCounter insertion failed: ' . $e->getMessage());
            return false;
        }

        if ($isUnique) {
            $this->incrementStats($documentId);
        } else {
            $this->incrementTotalOnly($documentId);
        }

        return true;
    }

    protected function incrementStats($documentId)
    {
        $today = date('Y-m-d');
        $this->upsertStat(VisitorStats::TYPE_DAILY, $today, $documentId, 1, 1);
        $this->upsertStat(VisitorStats::TYPE_WEEKLY, date('Y-m-d', strtotime('monday this week')), $documentId, 1, 1);
        $this->upsertStat(VisitorStats::TYPE_MONTHLY, date('Y-m-01'), $documentId, 1, 1);
        $this->upsertStat(VisitorStats::TYPE_YEARLY, date('Y-01-01'), $documentId, 1, 1);
        $this->upsertStat(VisitorStats::TYPE_ALL_TIME, '1970-01-01', $documentId, 1, 1);
    }

    protected function incrementTotalOnly($documentId)
    {
        $today = date('Y-m-d');
        $this->upsertStat(VisitorStats::TYPE_DAILY, $today, $documentId, 1, 0);
        $this->upsertStat(VisitorStats::TYPE_WEEKLY, date('Y-m-d', strtotime('monday this week')), $documentId, 1, 0);
        $this->upsertStat(VisitorStats::TYPE_MONTHLY, date('Y-m-01'), $documentId, 1, 0);
        $this->upsertStat(VisitorStats::TYPE_YEARLY, date('Y-01-01'), $documentId, 1, 0);
        $this->upsertStat(VisitorStats::TYPE_ALL_TIME, '1970-01-01', $documentId, 1, 0);
    }

    protected function upsertStat($type, $statDate, $documentId, $totalDelta, $uniqueDelta)
    {
        $existing = VisitorStats::find()
            ->where([
                'stat_type' => $type,
                'stat_date' => $statDate,
                'document_id' => $documentId,
            ])
            ->one();

        if ($existing) {
            $existing->total_visits += $totalDelta;
            $existing->unique_visits += $uniqueDelta;
            $existing->save();
        } else {
            $stat = new VisitorStats();
            $stat->stat_type = $type;
            $stat->stat_date = $statDate;
            $stat->document_id = $documentId;
            $stat->total_visits = $totalDelta;
            $stat->unique_visits = $uniqueDelta;
            $stat->save();
        }
    }
```

**Step 2: Run tests**

```bash
vendor/bin/codecept run unit common/tests/unit/components/VisitorCounterTest.php -v
```

Expected: PASS

**Step 3: Commit**

```bash
git add common/components/VisitorCounter.php
git commit -m "feat(visitor-counter): add deduplication, trackVisit, and realtime stat updates"
```

---

### Task 9: Register `VisitorCounter` as Yii2 Component

**Goal:** Bootstrap the component on every frontend request.

**Files:**
- Modify: `frontend/config/main.php`

**Step 1: Add to bootstrap array**

Modify the `'bootstrap'` line from:
```php
'bootstrap' => ['log', 'userCounter'],
```
to:
```php
'bootstrap' => ['log', 'userCounter', 'visitorCounter'],
```

**Step 2: Add component configuration**

Add inside the `'components'` array:
```php
        'visitorCounter' => [
            'class' => 'common\components\VisitorCounter',
            'deduplicateWindowMinutes' => 30,
            'cookieName' => '__visitor_id',
            'cookieExpiryDays' => 180,
        ],
```

**Step 3: Verify syntax**

```bash
php -l frontend/config/main.php
```

Expected: `No syntax errors detected`

**Step 4: Commit**

```bash
git add frontend/config/main.php
git commit -m "feat(visitor-counter): register VisitorCounter in frontend config"
```

---

### Task 10: Write Console Aggregation Command

**Goal:** Recompute `visitor_stats` from `visitor_log` for the last 7 days.

**Files:**
- Create: `console/controllers/VisitorController.php`

**Step 1: Write the command**

```php
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
                'stat_type' => "'daily'",
                'stat_date' => 'visit_date',
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
                'stat_type' => "'daily'",
                'stat_date' => 'visit_date',
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
```

**Step 2: Run command locally**

```bash
php yii visitor/aggregate --days=1
```

Expected: `Aggregation complete. Inserted X stat rows.`

**Step 3: Commit**

```bash
git add console/controllers/VisitorController.php
git commit -m "feat(visitor-counter): add nightly aggregation console command"
```

---

### Task 11: Write Backend Dashboard Controller Skeleton

**Goal:** Create `VisitorReportController` with access control.

**Files:**
- Create: `backend/controllers/VisitorReportController.php`

**Step 1: Write the controller**

```php
namespace backend\controllers;

use Yii;
use yii\web\Controller;
use yii\filters\AccessControl;
use common\models\VisitorStats;

class VisitorReportController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['admin'],
                    ],
                ],
            ],
        ];
    }

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

    protected function getStat($type, $date)
    {
        $stat = VisitorStats::find()
            ->where(['stat_type' => $type, 'stat_date' => $date, 'document_id' => null])
            ->one();

        return $stat ?: ['total_visits' => 0, 'unique_visits' => 0];
    }
}
```

**Step 2: Commit**

```bash
git add backend/controllers/VisitorReportController.php
git commit -m "feat(visitor-counter): add VisitorReportController backend dashboard"
```

---

### Task 12: Write Dashboard Views

**Goal:** Render summary cards using AdminLTE styling.

**Files:**
- Create: `backend/views/visitor-report/index.php`
- Create: `backend/views/visitor-report/_stat_card.php`

**Step 1: Write index view**

```php
use yii\helpers\Html;

$this->title = 'Statistik Pengunjung';
$this->params['breadcrumbs'][] = $this->title;

$cardLabels = [
    'daily' => 'Hari Ini',
    'weekly' => 'Minggu Ini',
    'monthly' => 'Bulan Ini',
    'yearly' => 'Tahun Ini',
    'all_time' => 'Semua Waktu',
];
?>


    Statistik Pengunjung
    
        
            = Html::encode($label) ?>
            
                
                    = isset($stat['unique_visits']) ? Html::encode($stat['unique_visits']) : Html::encode($stat->unique_visits) ?>
                    Unique Visits
                
                
                    = isset($stat['total_visits']) ? Html::encode($stat['total_visits']) : Html::encode($stat->total_visits) ?>
                    Total Visits
                
            
        
    
    
    
        Bandingkan Periode
        
            
                
                    Periode
                    Saat Ini
                    Sebelumnya
                
            
            = Html::encode(isset($item['current']['unique_visits']) ? $item['current']['unique_visits'] : $item['current']->unique_visits) ?>
            = Html::encode(isset($item['previous']['unique_visits']) ? $item['previous']['unique_visits'] : $item['previous']->unique_visits) ?>
            = Html::encode(isset($item['current']['total_visits']) ? $item['current']['total_visits'] : $item['current']->total_visits) ?>
            = Html::encode(isset($item['previous']['total_visits']) ? $item['previous']['total_visits'] : $item['previous']->total_visits) ?>
        
    


```

**Step 2: Verify syntax**

```bash
php -l backend/views/visitor-report/index.php
```

Expected: `No syntax errors detected in backend/views/visitor-report/index.php`

**Step 3: Commit**

```bash
git add backend/views/visitor-report/
git commit -m "feat(visitor-counter): add dashboard views with summary cards and comparisons"
```

---

### Task 13: Add Backend Menu Entry

**Goal:** Add navigation link for the Visitor Report dashboard.

**Files:**
- Modify: Locate and update the backend menu configuration (e.g., `backend/views/layouts/main.php`, `backend/views/layouts/left.php`, or similar menu config file)

**Step 1: Add menu item**

Find the sidebar navigation array (commonly in `backend/views/layouts/left.php` or a widget configuration) and add:

```php
['label' => 'Statistik Pengunjung', 'icon' => 'chart-bar', 'url' => ['/visitor-report/index']],
```

**Step 2: Verify by accessing `/backend/visitor-report/index`**

Start the development server:
```bash
php yii serve --port=9000
```

Login as admin and navigate to the new menu item.

**Step 3: Commit**

```bash
git add backend/views/layouts/
git commit -m "feat(visitor-counter): add visitor report menu item to backend"
```

---

### Task 14: Write Functional Test — Dashboard Access

**Goal:** Ensure only admin users can access the dashboard.

**Files:**
- Create: `backend/tests/functional/VisitorReportCest.php`

**Step 1: Write the test**

```php
namespace backend\tests\functional;

use backend\tests\FunctionalTester;
use yii\helpers\Url;

class VisitorReportCest
{
    public function testGuestCannotAccess(FunctionalTester $I)
    {
        $I->amOnPage(Url::toRoute('/visitor-report/index'));
        $I->seeCurrentUrlEquals(Url::toRoute('/site/login'));
    }

    public function testAdminCanAccess(FunctionalTester $I)
    {
        // Assume a fixture or login method exists; adjust per project conventions
        $I->amLoggedInAs(['username' => 'admin', 'password' => 'admin']);
        $I->amOnPage(Url::toRoute('/visitor-report/index'));
        $I->see('Statistik Pengunjung');
    }
}
```

**Step 2: Run functional tests**

```bash
vendor/bin/codecept run functional VisitorReportCest -v
```

Expected: Tests pass if login fixtures are configured; otherwise note fixture requirements.

**Step 3: Commit**

```bash
git add backend/tests/functional/VisitorReportCest.php
git commit -m "test(visitor-counter): add dashboard access control functional tests"
```

---

### Task 15: Add Dashboard Chart AJAX Endpoint

**Goal:** Expose JSON endpoint for Chart.js line chart of last 30 days unique visits.

**Files:**
- Modify: `backend/controllers/VisitorReportController.php`
- Create: `backend/views/visitor-report/_chart.php`

**Step 1: Add action to controller**

```php
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
```

**Step 2: Create Chart.js partial**

```php

    
        
            
                Tren Kunjungan (30 Hari)
                
                    
                        Tanpa data
                    
                
            
        
    


// Instantiate chart via AJAX endpoint
```

**Step 3: Include partial in index view**

In `backend/views/visitor-report/index.php`, add:
```php
= $this->render('_chart') ?>
```

**Step 4: Commit**

```bash
git add backend/controllers/VisitorReportController.php backend/views/visitor-report/_chart.php backend/views/visitor-report/index.php
git commit -m "feat(visitor-counter): add Chart.js AJAX endpoint and trend chart partial"
```

---

### Task 16: Final Integration & Verification

**Goal:** Run all tests, verify end-to-end flow.

**Step 1: Run full test suite**

```bash
vendor/bin/codecept run unit common/tests/unit/components/VisitorCounterTest.php -v
vendor/bin/codecept run functional backend/tests/functional/VisitorReportCest.php -v
```

Expected: All tests pass.

**Step 2: Verify component bootstraps on frontend**

```bash
curl -I http://localhost:9000/
```

Expected: Response headers include `Set-Cookie: __visitor_id=...`

**Step 3: Verify aggregation command**

```bash
php yii visitor/aggregate --days=1
```

Expected: `Aggregation complete.` with no errors.

**Step 4: Final commit**

```bash
git status
git add -A
git commit -m "feat(visitor-counter): production-grade visitor counter system"
```

---

## Schedule the Aggregation Cron

Add to crontab on the production server:

```cron
# Visitor stats aggregation — run daily at 3:00 AM
0 3 * * * cd /path/to/project && php yii visitor/aggregate >> /var/log/visitor_aggregate.log 2>&1
```

---

## Post-Implementation Checklist

| # | Task | Status |
|---|------|--------|
| 1 | Migrations applied to production DB | Pending |
| 2 | `VisitorCounter` component bootstraps on every request | Pending |
| 3 | Cookie `__visitor_id` sets correctly in browser | Pending |
| 4 | Refreshing a page within 30 minutes only increments `total_visits` | Pending |
| 5 | Aggregation command populates `visitor_stats` correctly | Pending |
| 6 | Dashboard renders stats without errors | Pending |
| 7 | Only admin role can access dashboard | Pending |

---

## Rollback Plan

If issues arise, remove the component registration from `frontend/config/main.php`, drop the tables via migration rollback, and remove the backend controller route.

---

*Plan generated from approved design: `docs/plans/2026-05-07-enhanced-visitor-counter-design.md`*
