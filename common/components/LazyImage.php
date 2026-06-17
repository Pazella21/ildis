<?php

namespace common\components;

use yii\helpers\Html;

/**
 * Helpers for performant image delivery (lazy loading, LCP hints).
 */
class LazyImage
{
    /**
     * Renders an img tag with sensible loading defaults.
     *
     * @param string $src
     * @param array $options HTML attributes
     * @param bool $lazy When false, marks image as high-priority (LCP candidates).
     */
    public static function img(string $src, array $options = [], bool $lazy = true): string
    {
        if ($lazy) {
            $options['loading'] = $options['loading'] ?? 'lazy';
            $options['decoding'] = $options['decoding'] ?? 'async';
        } else {
            $options['loading'] = $options['loading'] ?? 'eager';
            $options['fetchpriority'] = $options['fetchpriority'] ?? 'high';
            $options['decoding'] = $options['decoding'] ?? 'async';
        }

        return Html::img($src, $options);
    }

    /**
     * Renders a cover background section using a lazy-loaded inline img (better than CSS url()).
     *
     * @param string $src Image URL
     * @param string $content Inner HTML
     * @param array $options Section attributes; pass eager=true for above-the-fold banners
     */
    public static function coverSection(string $src, string $content, array $options = []): string
    {
        $eager = !empty($options['eager']);
        unset($options['eager']);

        $sectionClass = trim('lazy-cover ' . ($options['class'] ?? ''));
        unset($options['class']);

        $imgOptions = [
            'class' => 'lazy-cover__img',
            'alt' => $options['alt'] ?? '',
            'decoding' => 'async',
        ];
        unset($options['alt']);

        if ($eager) {
            $imgOptions['loading'] = 'eager';
            $imgOptions['fetchpriority'] = 'high';
        } else {
            $imgOptions['loading'] = 'lazy';
        }

        $img = Html::img($src, $imgOptions);

        return Html::tag('section', $img . $content, array_merge($options, ['class' => $sectionClass]));
    }
}
