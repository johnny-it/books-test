<?php

namespace app\models;

use yii\db\ActiveRecord;

class Author extends ActiveRecord
{
    public static function tableName(): string { return '{{%author}}'; }

    public function behaviors(): array
    {
        return [[
            'class' => \yii\behaviors\TimestampBehavior::class,
            'createdAtAttribute' => 'created_at',
            'updatedAtAttribute' => false,
            'value' => new \yii\db\Expression('NOW()'),
        ]];
    }

    public function rules(): array
    {
        return [
            ['full_name', 'required'],
            ['full_name', 'string', 'min' => 2, 'max' => 160],
            ['full_name', 'trim'],
            ['full_name', 'unique'],
        ];
    }

    public function getBooks(): \yii\db\ActiveQuery
    {
        return $this->hasMany(Book::class, ['id' => 'book_id'])
            ->viaTable('{{%book_author}}', ['author_id' => 'id']);
    }

    public function fields(): array
    {
        return ['id' => fn () => (int) $this->id, 'full_name'];
    }

    public function extraFields(): array { return ['books']; }
}
