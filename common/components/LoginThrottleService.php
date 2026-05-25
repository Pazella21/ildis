<?php

namespace common\components;

use Yii;

class LoginThrottleService
{
    const MAX_ATTEMPTS = 3;
    const LOCKOUT_DURATION = 300;

    public static function getFailedAttempts($username)
    {
        $cacheKey = self::getCacheKey($username);
        return Yii::$app->cache->get($cacheKey) ?: 0;
    }

    public static function incrementFailedAttempt($username)
    {
        $cacheKey = self::getCacheKey($username);
        $attempts = self::getFailedAttempts($username) + 1;
        Yii::$app->cache->set($cacheKey, $attempts, self::LOCKOUT_DURATION);
        return $attempts;
    }

    public static function clearFailedAttempts($username)
    {
        $cacheKey = self::getCacheKey($username);
        Yii::$app->cache->delete($cacheKey);
    }

    public static function isLockedOut($username)
    {
        return self::getFailedAttempts($username) >= self::MAX_ATTEMPTS;
    }

    public static function getRemainingAttempts($username)
    {
        return max(0, self::MAX_ATTEMPTS - self::getFailedAttempts($username));
    }

    public static function getCacheKey($username)
    {
        return "failed_logins_{$username}";
    }
}