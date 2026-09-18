<?php

namespace app\services;

use app\models\Book;
use app\models\Notification;
use app\models\Subscription;

final class NotificationService
{
    public static function enqueueForBook(Book $book, array $authorIds): void
    {
        $subscriptions = Subscription::find()->where(['author_id' => $authorIds])->all();
        foreach ($subscriptions as $subscription) {
            $notification = new Notification();
            $notification->book_id = $book->id;
            $notification->subscription_id = $subscription->id;
            $notification->status = 'pending';
            $notification->attempts = 0;
            $notification->available_at = date('Y-m-d H:i:s');
            $notification->save(false);
        }
    }
}

