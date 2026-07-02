<?php
namespace common\components;

use Yii;
use yii\base\Component;
use yii\base\BootstrapInterface;
use common\models\VisitorLog;
use common\models\VisitorStats;

class VisitorCounter extends Component implements BootstrapInterface
{
    public $deduplicateWindowMinutes = 30;
    public $cookieName = '__visitor_id';
    public $cookieExpiryDays = 180;

    public function bootstrap($app)
    {
        // Track only on web frontend requests
        if ($app instanceof \yii\web\Application && $app->id === 'app-frontend') {
            if ($this->shouldSkipBootstrapTracking($app)) {
                return;
            }
            $this->trackVisit();
        }
    }

    /**
     * Document detail and download pages are tracked in DokumenController with document_id.
     */
    protected function shouldSkipBootstrapTracking($app): bool
    {
        $path = $app->request->pathInfo;
        if ($path === '') {
            return false;
        }

        if (strpos($path, 'dokumen/download') === 0) {
            return true;
        }

        return (bool) preg_match('#^dokumen/\d+#', $path);
    }

    public function generateFingerprint($ip, $userAgent)
    {
        return md5($ip . '|' . $userAgent);
    }

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
}
