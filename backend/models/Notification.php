<?php

namespace app\models;

use yii\db\ActiveRecord;

class Notification extends ActiveRecord
{
    public static function tableName(): string { return '{{%notification}}'; }

    public function getSubscription(): \yii\db\ActiveQuery
    {
        return $this->hasOne(Subscription::class, ['id' => 'subscription_id']);
    }

    public function getBook(): \yii\db\ActiveQuery
    {
        return $this->hasOne(Book::class, ['id' => 'book_id']);
    }
}

