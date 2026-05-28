<?php

use yii\helpers\Html;
use yii\bootstrap\Nav;
use yii\bootstrap\NavBar;
use yii\helpers\Url;
use yii\widgets\Menu;
?>


<?php
$menuItems = [
    [
        'label' => 'Beranda',
        'url' => ['/site/index'],
    ],

    [
        'label' => 'Tentang Kami',
        'url' => '#',
        'options' => ['class' => 'dropdown'],
        'template' => '<span class="submenu-button"></span><a href="javascript:void(0)" class="href_class">{label}</a>',
        'items' => [
            ['label' => 'Sekilas Sejarah', 'url' => ['site/sekilas-sejarah']],
            ['label' => 'Dasar Hukum', 'url' => ['site/dasar-hukum']],
            ['label' => 'Visi ', 'url' => ['site/visi']],
            ['label' => 'Misi', 'url' => ['site/misi']],
            //['label' => 'Struktur Organisasi', 'url' => ['site/sto']],
            [
                'label' => 'Struktur Organisasi',
                'options' => ['class' => 'dropdown'],
                'template' => '<a href="" class="href_class">{label}</a>',
                'items' => [
                    ['label' => 'JDIH Instansi', 'url' => ['site/sto']],
                    ['label' => 'Biro/Bagian Hukum', 'url' => ['site/stoinstansi']],
                ]
            ],
            ['label' => 'SK Tim Pengelola', 'url' => ['site/pengelola']],
            ['label' => 'SOP', 'url' => ['site/sop']],

        ]
    ],


    [
        'label' => 'Jenis Dokumen',
        'url' => '#',
        'options' => ['class' => 'dropdown'],
        'activateItems' => true,
        'activeCssClass' => 'active',
        'template' => '<span class="submenu-button"></span><a href={url}>{label}</a>',

        'items' => [
            ['label' => 'Peraturan', 'url' => ['dokumen/peraturan']],
            ['label' => 'Monografi', 'url' => ['dokumen/monografi']],
            // ['label' => 'Penelitian Hukum', 'url' => ['dokumen/penelitian-hukum']],
            // ['label' => 'Pengkajian Hukum', 'url' => ['dokumen/pengkajian-hukum']],
            // ['label' => 'Pengkajian Konstitusi', 'url' => ['dokumen/pengkajian-konstitusi']],
            // ['label' => 'Naskah Akademik Kemenkumham', 'url' => ['dokumen/naskah-akademik']],
            // ['label' => 'Analisis Dan Evaluasi', 'url' => ['dokumen/analisis-dan-evaluasi']],
            // ['label' => 'Kompedium', 'url' => ['site/kompedium']],
            ['label' => 'Artikel/Majalah Hukum', 'url' => ['dokumen/artikel']],
            ['label' => 'Putusan', 'url' => ['dokumen/putusan']],
            // ['label' => 'Staatsblad', 'url' => ['dokumen/staatsblad']],
        ]
    ],
    [
        'label' => \common\components\DocumentGroup::label(
            \common\components\DocumentGroup::LEGISLATION_FORMATION
        ),
        'url' => Url::to(['/dokumen-pembentukan-puu']),
        'options' => ['class' => 'dropdown'],
        'activateItems' => true,
        'activeCssClass' => 'active',
        'template' => '<span class="submenu-button"></span><a href={url}>{label}</a>',
        'items' => array_map(static function (\common\models\DocumentType $t) {
            return [
                'label' => ucwords(strtolower($t->name)),
                'url' => Url::to(['/dokumen-pembentukan-puu/' . $t->slug]),
            ];
        }, \common\models\DocumentType::findByGroup(
            \common\components\DocumentGroup::LEGISLATION_FORMATION
        )),
    ],
    ['label' => 'Berita', 'url' => ['berita/index']],
    [
        'label' => 'Link Terkait',
        'url' => '#',
        'options' => ['class' => 'dropdown'],
        'activateItems' => true,
        'activeCssClass' => 'active',
        // 'template' => '<span class="submenu-button"></span><span class="submenu-button"></span><a href="{url}">{label}</a>',
        'template' => '<span class="submenu-button"></span><a href={url}>{label}</a>',

        'items' => [
            ['label' => 'jdihn.go.id', 'url' => Url::to('https://jdihn.go.id/')],
            ['label' => 'bphn.go.id', 'url' => Url::to('https://bphn.go.id/')],
        ]
    ],

];

//fitur layanan perpustakaan disable
if (Yii::$app->user->isGuest) {
    $menuItems[] =  ['label' => '', 'url' => ['site/login']];
} else {
    $menuItems[] = ['label' => 'Profile User', 'url' => ['/profile/index']];
    $menuItems[] = ['label' => 'Sign out', 'url' => ['/site/logout'],  ['data' => ['method' => 'post']]];
}
echo Menu::widget([
    'items' => $menuItems,
    'options' => [],
    'activateParents' => true,
    //'encodeLabels' => false,
    'activeCssClass' => 'current',
    //'submenuTemplate' => "\n<ul class='has-sub'>\n{items}\n</ul>\n",

]);
?>
