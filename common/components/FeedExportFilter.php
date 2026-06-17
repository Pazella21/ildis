<?php

namespace common\components;

use backend\models\DokumenJdih;
use common\models\DocumentType;
use yii\base\Model;
use yii\db\ActiveQuery;

class FeedExportFilter extends Model
{
    public const ALLOWED_DATE_FIELDS = [
        'updated_at',
        'tanggal_pengundangan',
        'tanggal_penetapan',
    ];

    public const TIPE_SLUGS = [
        DokumenJdih::TYPE_PERATURAN => 'peraturan',
        DokumenJdih::TYPE_MONOGRAFI => 'monografi',
        DokumenJdih::TYPE_ARTIKEL => 'artikel',
        DokumenJdih::TYPE_PUTUSAN => 'putusan',
    ];

    public const DATE_FIELD_SLUGS = [
        'updated_at' => 'updated',
        'tanggal_pengundangan' => 'pengundangan',
        'tanggal_penetapan' => 'penetapan',
    ];

    public $tipe;
    public $typeId;
    public $dateField;
    public $from;
    public $to;
    public $output;

    public function rules(): array
    {
        return [
            [['tipe', 'typeId'], 'integer'],
            [['dateField', 'from', 'to', 'output'], 'string'],
        ];
    }

    public function validate($attributeNames = null, $clearErrors = true): bool
    {
        parent::validate($attributeNames, $clearErrors);
        $this->validateBusinessRules();
        return !$this->hasErrors();
    }

    private function validateBusinessRules(): void
    {
        if ($this->tipe !== null && $this->tipe !== '') {
            $allowed = array_keys(self::TIPE_SLUGS);
            if (!in_array((int) $this->tipe, $allowed, true)) {
                $this->addError('tipe', 'tipe must be 1–4.');
            }
        }

        if ($this->typeId !== null && $this->typeId !== '') {
            if (DocumentType::findOne((int) $this->typeId) === null) {
                $this->addError('typeId', 'document_type not found.');
            }
        }

        if ($this->dateField !== null && $this->dateField !== '') {
            if (!in_array($this->dateField, self::ALLOWED_DATE_FIELDS, true)) {
                $this->addError('dateField', 'Invalid date field.');
            }
        }

        if (($this->from || $this->to) && empty($this->dateField)) {
            $this->addError('dateField', 'dateField is required when from/to is set.');
        }

        if ($this->from && !$this->isValidDate($this->from)) {
            $this->addError('from', 'from must be Y-m-d.');
        }

        if ($this->to && !$this->isValidDate($this->to)) {
            $this->addError('to', 'to must be Y-m-d.');
        }

        if ($this->from && $this->to && $this->from > $this->to) {
            $this->addError('to', 'to must be on or after from.');
        }
    }

    private function isValidDate(string $value): bool
    {
        $dt = \DateTime::createFromFormat('Y-m-d', $value);
        return $dt && $dt->format('Y-m-d') === $value;
    }

    public static function applyToQuery(ActiveQuery $query, self $filter): void
    {
        if ($filter->tipe !== null && $filter->tipe !== '') {
            $query->andWhere(['d.tipe_dokumen' => (int) $filter->tipe]);
        }

        if ($filter->typeId !== null && $filter->typeId !== '') {
            $type = DocumentType::findOne((int) $filter->typeId);
            if ($type === null) {
                throw new \InvalidArgumentException('document_type not found.');
            }
            $query->andWhere(['d.dokumen_type_id' => $type->descendantTypeIds()]);
        }

        if ($filter->dateField && $filter->from) {
            $query->andWhere(['>=', 'd.' . $filter->dateField, $filter->from]);
        }

        if ($filter->dateField && $filter->to) {
            $toValue = $filter->dateField === 'updated_at'
                ? $filter->to . ' 23:59:59'
                : $filter->to;
            $query->andWhere(['<=', 'd.' . $filter->dateField, $toValue]);
        }
    }

    public function resolveOutputPath(): string
    {
        $exportDir = \Yii::getAlias('@feed/export');

        if ($this->output !== null && $this->output !== '') {
            $basename = basename($this->output);
            if ($basename !== $this->output || strpos($this->output, '..') !== false) {
                throw new \InvalidArgumentException('Unsafe output path.');
            }
            if (substr(strtolower($basename), -5) !== '.json') {
                $basename .= '.json';
            }
            return $exportDir . '/' . $basename;
        }

        $parts = [];
        if ($this->tipe !== null && $this->tipe !== '') {
            $parts[] = self::TIPE_SLUGS[(int) $this->tipe] ?? 'dokumen';
        } else {
            $parts[] = 'semua';
        }

        if ($this->typeId !== null && $this->typeId !== '') {
            $type = DocumentType::findOne((int) $this->typeId);
            if ($type !== null) {
                $parts[] = self::slugify($type->slug ?: $type->name);
            }
        }

        if ($this->dateField && ($this->from || $this->to)) {
            $parts[] = self::DATE_FIELD_SLUGS[$this->dateField] ?? $this->dateField;
        }

        if ($this->from && $this->to) {
            $parts[] = $this->from . '_' . $this->to;
        } elseif ($this->from) {
            $parts[] = 'from-' . $this->from;
        } elseif ($this->to) {
            $parts[] = 'to-' . $this->to;
        }

        return $exportDir . '/' . implode('-', $parts) . '.json';
    }

    public static function slugify(string $value): string
    {
        $slug = strtolower(trim($value));
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        return trim($slug, '-') ?: 'dokumen';
    }
}
