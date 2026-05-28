<?php

namespace common\models;

use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property string $second_id
 * @property int $parent_id
 * @property string $name
 * @property string $singkatan
 * @property string|null $document_group_label
 * @property string|null $slug
 * @property string|null $status
 * @property int|null $integrasi
 */
class DocumentType extends ActiveRecord
{
    public static function tableName(): string
    {
        return 'document_type';
    }

    public function rules(): array
    {
        return [
            [['parent_id', 'integrasi', 'created_by', 'updated_by'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['second_id', 'name', 'singkatan', 'status'], 'string', 'max' => 255],
            [['document_group_label'], 'string', 'max' => 64],
            [['slug'], 'string', 'max' => 128],
            [['slug'], 'match', 'pattern' => '/^[\w-]+$/'],
            [['slug'], 'unique'],
        ];
    }

    /**
     * @return self[]
     */
    public static function findByGroup(string $groupSlug): array
    {
        return static::find()
            ->where(['document_group_label' => $groupSlug])
            ->orderBy(['name' => SORT_ASC])
            ->all();
    }

    public static function findBySlugInGroup(string $slug, string $groupSlug): ?self
    {
        return static::findOne([
            'slug' => $slug,
            'document_group_label' => $groupSlug,
        ]);
    }

    /**
     * @return int[]
     */
    public function descendantTypeIds(): array
    {
        $ids = [$this->id];
        $children = static::find()->where(['parent_id' => $this->id])->all();
        foreach ($children as $child) {
            $ids = array_merge($ids, $child->descendantTypeIds());
        }

        return $ids;
    }

    /**
     * @return string[]
     */
    public function descendantTypeNames(): array
    {
        $names = [$this->name];
        $children = static::find()->where(['parent_id' => $this->id])->all();
        foreach ($children as $child) {
            $names = array_merge($names, $child->descendantTypeNames());
        }

        return $names;
    }

    /**
     * @return string[]
     */
    public static function groupTypeNames(string $groupSlug): array
    {
        $names = [];
        foreach (self::findByGroup($groupSlug) as $root) {
            $names = array_merge($names, $root->descendantTypeNames());
        }

        return array_values(array_unique($names));
    }
}
