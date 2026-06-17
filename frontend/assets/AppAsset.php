<?php

namespace frontend\assets;

use yii\web\AssetBundle;

/**
 * Main frontend application asset bundle.
 * @author Kartik Visweswaran <kartikv2@gmail.com>
 */
class AppAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web/frontend/assets';
    public $css = [
        ['css/plugins.css', 'appendTimestamp' => true],
        ['search/search.css', 'appendTimestamp' => true],
        'vendor/aos/aos.css',
        'vendor/bootstrap/css/bootstrap.min.css',
        'vendor/bootstrap-icons/bootstrap-icons.css',
        'vendor/boxicons/css/boxicons.min.css',
        'vendor/swiper/swiper-bundle.min.css',
        ['css/lazyload.css', 'appendTimestamp' => true],
        ['css/style.css', 'appendTimestamp' => true],
        ['css/mobile-menu.css', 'appendTimestamp' => true],
    ];

    public $js = [
        'vendor/bootstrap/js/bootstrap.bundle.min.js',
        ['js/lazyload.js', 'appendTimestamp' => true],
        'vendor/aos/aos.js',
        'vendor/swiper/swiper-bundle.min.js',
        'vendor/php-email-form/validate.js',
        ['js/main.js', 'appendTimestamp' => true],
    ];

    public $jsOptions = ['defer' => true];
    public $depends = [
        'yii\web\YiiAsset',
        //'yii\bootstrap\BootstrapAsset',
    ];
}
