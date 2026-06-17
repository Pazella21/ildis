<?php

namespace common\behaviors;

use common\components\DocumentSlug;
use yii\base\Behavior;
use yii\db\ActiveRecord;

class DocumentSlugBehavior extends Behavior
{
    public function events()
    {
        return [
            ActiveRecord::EVENT_BEFORE_INSERT => 'ensureSlug',
            ActiveRecord::EVENT_BEFORE_UPDATE => 'ensureSlug',
        ];
    }

    public function ensureSlug(): void
    {
        $owner = $this->owner;

        if (empty($owner->slug)) {
            if (empty($owner->judul)) {
                return;
            }

            $owner->slug = DocumentSlug::fromDocument(
                (int) $owner->tipe_dokumen,
                $owner->judul,
                $owner->singkatan_jenis ?? null,
                $owner->nomor_peraturan ?? null,
                $owner->tahun_terbit ?? null
            );
            return;
        }

        $owner->slug = DocumentSlug::normalize($owner->slug);
    }
}
