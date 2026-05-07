<?php
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
}
