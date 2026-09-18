<?php

namespace app\models;

use yii\db\ActiveRecord;

class Subscription extends ActiveRecord
{
    public static function tableName(): string { return '{{%subscription}}'; }

    public function rules(): array
    {
        return [
            [['author_id', 'phone'], 'required'],
            ['author_id', 'integer'],
            ['author_id', 'exist', 'targetClass' => Author::class, 'targetAttribute' => 'id'],
            ['phone', 'match', 'pattern' => '/^7\d{10}$/', 'message' => 'Введите российский номер телефона.'],
            [['author_id', 'phone'], 'unique', 'targetAttribute' => ['author_id', 'phone']],
        ];
    }

    public static function normalizePhone(string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', $phone);
        if (strlen($digits) === 11 && $digits[0] === '8') $digits[0] = '7';
        if (strlen($digits) === 10) $digits = '7' . $digits;
        return preg_match('/^7\d{10}$/', $digits) ? $digits : null;
    }
}

