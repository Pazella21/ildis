<?php
namespace common\models;

use Yii;
use yii\base\Model;
use common\components\AuthDebugLogger;
use common\components\LoginThrottleService;

class LoginForm extends Model
{
    const REMEMBER_ME_DURATION = 300;

    public $username;
    public $password;
    public $rememberMe = true;
    private $_user;
    /** @var string|null */
    private $_authFailureReason;
    /** @var array<string, mixed> */
    private $_authFailureContext = [];
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

        if ($user) {
            if ($user->suspended_until && strtotime($user->suspended_until) > $now) {
                $remaining = strtotime($user->suspended_until) - $now;
                $minutesLeft = ceil($remaining / 60);
                $this->addError($attribute, "Akun ditangguhkan. Coba lagi dalam {$minutesLeft} menit.");
                $this->noteAuthFailure('account_suspended');
                return;
            }

            if (!$user->validatePassword($this->password)) {
                $failedLogins = LoginThrottleService::incrementFailedAttempt($this->username);

                if ($failedLogins >= LoginThrottleService::MAX_ATTEMPTS) {
                    $user->suspended_until = date('Y-m-d H:i:s', $now + LoginThrottleService::LOCKOUT_DURATION);
                    $user->save(false);
                    LoginThrottleService::clearFailedAttempts($this->username);
                    $this->addError($attribute, "Akun ditangguhkan selama 5 menit karena salah login 3x berturut-turut.");
                    $this->noteAuthFailure('account_locked_after_failed_attempts', [
                        'failed_attempts' => $failedLogins,
                    ]);
                } else {
                    $this->addError($attribute, "Kesalahan username atau password.");
                    $this->noteAuthFailure('invalid_password', [
                        'failed_attempts' => $failedLogins,
                    ]);
                }
            } else {
                LoginThrottleService::clearFailedAttempts($this->username);
            }
        } else {
            LoginThrottleService::incrementFailedAttempt($this->username);
            $this->addError($attribute, "Kesalahan username atau password.");
            $this->noteAuthFailure('user_not_found');
        }
    }

    public function login()
    {
        if ($this->validate()) {
            $duration = $this->rememberMe ? self::REMEMBER_ME_DURATION : 0;
            if (Yii::$app->user->login($this->getUser(), $duration)) {
                return true;
            }

            $this->noteAuthFailure('session_login_failed', [
                'user_id' => $this->getUser()->id ?? null,
            ]);
            $this->flushAuthFailureLog();

            return false;
        }

        if ($this->_authFailureReason === null) {
            $this->noteAuthFailure('validation_failed');
        }
        $this->flushAuthFailureLog();

        return false;
    }

    /**
     * Records failure reason; written to auth.log once per login attempt when YII_DEBUG is on.
     */
    protected function noteAuthFailure(string $reason, array $extra = []): void
    {
        $this->_authFailureReason = $reason;
        $this->_authFailureContext = array_merge($this->_authFailureContext, $extra);
    }

    /**
     * Writes auth diagnostics when YII_DEBUG is enabled.
     */
    protected function flushAuthFailureLog(): void
    {
        if ($this->_authFailureReason === null) {
            return;
        }

        $user = $this->getUser();

        AuthDebugLogger::log('backend_login_failed', array_merge([
            'reason' => $this->_authFailureReason,
            'username' => $this->username,
            'errors' => $this->getErrors(),
            'recaptcha_enabled' => !empty(Yii::$app->params['recaptcha.enabled']),
            'user_found' => $user !== null,
            'user_id' => $user->id ?? null,
            'user_status' => $user->status ?? null,
            'user_suspended_until' => $user->suspended_until ?? null,
            'session_cookie_secure' => Yii::$app->session->cookieParams['secure'] ?? null,
            'request_is_secure' => Yii::$app->request->isSecureConnection,
        ], $this->_authFailureContext));

        $this->_authFailureReason = null;
        $this->_authFailureContext = [];
    }

    protected function getUser()
    {
        if ($this->_user === null) {
            $this->_user = User::findByUsername($this->username);
        }

        return $this->_user;
    }
}