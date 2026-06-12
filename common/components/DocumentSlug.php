<?php

namespace common\components;

use Yii;
use yii\helpers\Inflector;

class DocumentSlug
{
    private const MAX_LENGTH = 60;
    private const TYPE_PERATURAN = 1;

    public static function fromDocument(
        int $tipeDokumen,
        string $judul,
        ?string $singkatanJenis = null,
        ?string $nomorPeraturan = null,
        ?string $tahunTerbit = null
    ): string {
        if ($tipeDokumen === self::TYPE_PERATURAN
            && self::hasValue($singkatanJenis)
            && self::hasValue($nomorPeraturan)
            && self::hasValue($tahunTerbit)
        ) {
            return self::normalize(
                Inflector::slug($singkatanJenis . '-' . $nomorPeraturan . '-' . $tahunTerbit)
            );
        }

        return self::fromJudul($judul);
    }

    public static function fromJudul(string $judul): string
    {
        $slug = Inflector::slug($judul);
        if ($slug === '') {
            $slug = 'dokumen';
        }

        return self::normalize($slug);
    }

    public static function normalize(string $slug): string
    {
        $slug = trim($slug);
        if ($slug === '') {
            return 'dokumen';
        }

        if (strlen($slug) > self::MAX_LENGTH) {
            $slug = rtrim(substr($slug, 0, self::MAX_LENGTH), '-');
        }

        return $slug !== '' ? $slug : 'dokumen';
    }

    /**
     * Resolve slug for URL generation (normalized DB slug, or derived from document fields).
     */
    public static function resolve(int $id, ?string $judul = null): string
    {
        static $cache = [];

        if (isset($cache[$id])) {
            return $cache[$id];
        }

        $row = Yii::$app->db->createCommand(
            'SELECT slug, judul, tipe_dokumen, singkatan_jenis, nomor_peraturan, tahun_terbit
             FROM {{%document}} WHERE id = :id LIMIT 1',
            [':id' => $id]
        )->queryOne();

        if ($row === false) {
            return $cache[$id] = self::fromJudul($judul ?? 'dokumen');
        }

        if (!empty($row['slug'])) {
            return $cache[$id] = self::normalize($row['slug']);
        }

        return $cache[$id] = self::fromDocument(
            (int) $row['tipe_dokumen'],
            $judul ?? ($row['judul'] ?? ''),
            $row['singkatan_jenis'] ?? null,
            $row['nomor_peraturan'] ?? null,
            $row['tahun_terbit'] ?? null
        );
    }

    private static function hasValue(?string $value): bool
    {
        return $value !== null && trim($value) !== '';
    }
}
