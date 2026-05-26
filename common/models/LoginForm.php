<?php
namespace common\models;

use Yii;
use yii\base\Model;
use common\components\LoginThrottleService;

class LoginForm extends Model
{
    const REMEMBER_ME_DURATION = 300;

    public $username;
    public $password;
    public $rememberMe = true;
    private $_user;
    public $reCaptcha;

    public function rules()
    {
        $rules = [
            [['username', 'password'], 'required'],
            ['rememberMe', 'boolean'],
            ['password', 'validatePassword'],
        ];

        if (!empty(Yii::$app->params['recaptcha.enabled'])) {
            $rules[] = [['reCaptcha'], 'required'];
            $rules[] = [
                ['reCaptcha'],
                \himiklab\yii2\recaptcha\ReCaptchaValidator3::class,
                'secret' => Yii::$app->params['recaptcha.secretKey'],
                'threshold' => 0.5,
                'action' => 'login',
            ];
        }

        return $rules;
    }

    public function validatePassword($attribute, $params)
    {
        if ($this->hasErrors()) {
            return;
        }

        $user = $this->getUser();
        $now = time();
        $throttleKey = LoginThrottleService::getCacheKey($this->username);

        if ($user) {
            if ($user->suspended_until && strtotime($user->suspended_until) > $now) {
                $remaining = strtotime($user->suspended_until) - $now;
                $minutesLeft = ceil($remaining / 60);
                $this->addError($attribute, "Akun ditangguhkan. Coba lagi dalam {$minutesLeft} menit.");
                return;
            }

            if (!$user->validatePassword($this->password)) {
                $failedLogins = LoginThrottleService::incrementFailedAttempt($this->username);

                if ($failedLogins >= LoginThrottleService::MAX_ATTEMPTS) {
                    $user->suspended_until = date('Y-m-d H:i:s', $now + LoginThrottleService::LOCKOUT_DURATION);
                    $user->save(false);
                    LoginThrottleService::clearFailedAttempts($this->username);
                    $this->addError($attribute, "Akun ditangguhkan selama 5 menit karena salah login 3x berturut-turut.");
                } else {
                    $this->addError($attribute, "Kesalahan username atau password.");
                }
            } else {
                LoginThrottleService::clearFailedAttempts($this->username);
            }
        } else {
            LoginThrottleService::incrementFailedAttempt($this->username);
            $this->addError($attribute, "Kesalahan username atau password.");
        }
    }

    public function login()
    {
        if ($this->validate()) {
            return Yii::$app->user->login($this->getUser(), $this->rememberMe ? self::REMEMBER_ME_DURATION : 0);
        }

        return false;
    }

    protected function getUser()
    {
        if ($this->_user === null) {
            $this->_user = User::findByUsername($this->username);
        }

        return $this->_user;
    }
}