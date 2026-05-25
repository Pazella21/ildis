<?php

namespace common\models;

use Yii;
use yii\base\Model;
use common\components\LoginThrottleService;

class MemberForm extends Model
{
    const MEMBER_SESSION_DURATION = 2592000; // 3600 * 24 * 30 = 30 days

    public $username;
    public $password;
    public $rememberMe = true;

    private $_user;

    public function rules()
    {
        return [
            [['username', 'password'], 'required'],
            ['rememberMe', 'boolean'],
            ['password', 'validatePassword'],
        ];
    }

    public function validatePassword($attribute, $params)
    {
        if ($this->hasErrors()) {
            return;
        }

        $user = $this->getUser();
        $now = time();
        $throttleKey = 'member_' . LoginThrottleService::getCacheKey($this->username);

        if ($user) {
            if ($user->suspended_until && strtotime($user->suspended_until) > $now) {
                $remaining = strtotime($user->suspended_until) - $now;
                $minutesLeft = ceil($remaining / 60);
                $this->addError($attribute, "Akun ditangguhkan. Coba lagi dalam {$minutesLeft} menit.");
                return;
            }

            if (!$user->validatePassword($this->password)) {
                $failedLogins = Yii::$app->cache->get($throttleKey) ?: 0;
                $failedLogins++;
                Yii::$app->cache->set($throttleKey, $failedLogins, LoginThrottleService::LOCKOUT_DURATION);

                if ($failedLogins >= LoginThrottleService::MAX_ATTEMPTS) {
                    $user->suspended_until = date('Y-m-d H:i:s', $now + LoginThrottleService::LOCKOUT_DURATION);
                    $user->save(false);
                    Yii::$app->cache->delete($throttleKey);
                    $this->addError($attribute, "Akun ditangguhkan selama 5 menit karena salah login 3x berturut-turut.");
                } else {
                    $this->addError($attribute, "Kesalahan username atau password.");
                }
            } else {
                Yii::$app->cache->delete($throttleKey);
            }
        } else {
            $failedLogins = Yii::$app->cache->get($throttleKey) ?: 0;
            $failedLogins++;
            Yii::$app->cache->set($throttleKey, $failedLogins, LoginThrottleService::LOCKOUT_DURATION);
            $this->addError($attribute, "Kesalahan username atau password.");
        }
    }

    public function login()
    {
        if ($this->validate()) {
            return Yii::$app->user->login($this->getUser(), $this->rememberMe ? self::MEMBER_SESSION_DURATION : 0);
        }

        return false;
    }

    protected function getUser()
    {
        if ($this->_user === null) {
            $this->_user = Member::findByUsername($this->username);
        }

        return $this->_user;
    }
}