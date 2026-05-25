<?php

namespace common\components;

class DateHelper
{
    private static $bulanIndo = [
        '', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];

    public static function formatIndonesian($tanggal)
    {
        if (empty($tanggal)) {
            return '';
        }

        $separator = '-';
        $parts = explode($separator, $tanggal);

        if (count($parts) < 3) {
            return $tanggal;
        }

        $tgl = substr($tanggal, 8, 2);
        $bulan = substr($tanggal, 5, 2);
        $tahun = substr($tanggal, 0, 4);

        return $tgl . ' ' . self::$bulanIndo[(int)$bulan] . ' ' . $tahun;
    }
}