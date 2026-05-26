<?php

namespace common\components;

use Yii;

/**
 * Debug-only authentication diagnostics (never logs passwords).
 */
class AuthDebugLogger
{
    /**
     * @param string $event Short event name, e.g. backend_login_validation_failed
     * @param array<string, mixed> $context
     */
    public static function log(string $event, array $context = []): void
    {
        if (!self::isDebugEnabled()) {
            return;
        }

        unset($context['password'], $context['password_hash'], $context['password_reset_token']);

        $payload = array_merge([
            'event' => $event,
            'app' => Yii::$app->id ?? null,
            'identity_class' => Yii::$app->has('user') ? Yii::$app->user->identityClass : null,
            'remote_addr' => Yii::$app->request->userIP ?? null,
        ], $context);

        Yii::warning($payload, 'auth');
    }

    private static function isDebugEnabled(): bool
    {
        if (defined('YII_DEBUG') && YII_DEBUG) {
            return true;
        }

        $env = getenv('YII_DEBUG');
        if ($env === false || $env === '') {
            return false;
        }

        return filter_var($env, FILTER_VALIDATE_BOOLEAN);
    }
}
