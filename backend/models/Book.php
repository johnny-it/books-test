<?php

namespace app\models;

use yii\db\ActiveRecord;

class Book extends ActiveRecord
{
    public array $author_ids = [];

    public static function tableName(): string { return '{{%book}}'; }

    public function behaviors(): array
    {
        return [[
            'class' => \yii\behaviors\TimestampBehavior::class,
            'value' => new \yii\db\Expression('NOW()'),
        ]];
    }

    public function rules(): array
    {
        return [
            [['title', 'year'], 'required'],
            ['title', 'string', 'min' => 1, 'max' => 255],
            ['year', 'integer', 'min' => 1000, 'max' => 2100],
            ['description', 'string', 'max' => 5000],
            ['isbn', 'string', 'max' => 20],
            ['isbn', 'match', 'pattern' => '/^(?:97[89][- ]?)?[0-9][- 0-9]{8,16}[0-9Xx]$/', 'skipOnEmpty' => true, 'message' => 'Укажите корректный ISBN.'],
            ['author_ids', 'required'],
            ['author_ids', 'each', 'rule' => ['integer']],
        ];
    }

    public function getAuthors(): \yii\db\ActiveQuery
    {
        return $this->hasMany(Author::class, ['id' => 'author_id'])
            ->viaTable('{{%book_author}}', ['book_id' => 'id']);
    }

    public function syncAuthors(array $authorIds): void
    {
        $ids = array_values(array_unique(array_map('intval', $authorIds)));
        $authors = Author::find()->where(['id' => $ids])->all();
        if (count($authors) !== count($ids)) {
            throw new \InvalidArgumentException('Один или несколько авторов не найдены.');
        }
        $this->unlinkAll('authors', true);
        foreach ($authors as $author) {
            $this->link('authors', $author);
        }
    }

    public function fields(): array
    {
        return [
            'id' => fn () => (int) $this->id,
            'title',
            'year' => fn () => (int) $this->year,
            'description',
            'isbn',
            'cover_url' => function () {
                if (!$this->cover_path) return null;
                $parts = parse_url(\Yii::$app->params['apiPublicUrl']);
                $origin = ($parts['scheme'] ?? 'http') . '://' . ($parts['host'] ?? 'localhost') . (isset($parts['port']) ? ':' . $parts['port'] : '');
                return $origin . '/uploads/covers/' . rawurlencode($this->cover_path);
            },
            'authors' => fn () => array_map(static fn (Author $author) => [
                'id' => (int) $author->id,
                'full_name' => $author->full_name,
            ], $this->authors),
        ];
    }
}
