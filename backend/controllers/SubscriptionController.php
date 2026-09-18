<?php

namespace app\controllers;

use app\models\Author;
use app\models\Subscription;
use yii\web\NotFoundHttpException;

class SubscriptionController extends ApiController
{
    public function actionCreate(int $id): array
    {
        if (!Author::find()->where(['id' => $id])->exists()) throw new NotFoundHttpException('Автор не найден.');
        $phone = Subscription::normalizePhone((string) \Yii::$app->request->getBodyParam('phone', ''));
        if (!$phone) return $this->fail('Введите российский номер телефона.', 'phone');

        $subscription = Subscription::findOne(['author_id' => $id, 'phone' => $phone]);
        if ($subscription) {
            return $this->ok(['id' => (int) $subscription->id, 'phone' => '+' . $phone, 'already_subscribed' => true]);
        }

        $subscription = new Subscription(['author_id' => $id, 'phone' => $phone]);
        if (!$subscription->save()) return $this->validationErrors($subscription);
        return $this->ok(['id' => (int) $subscription->id, 'phone' => '+' . $phone, 'already_subscribed' => false], 201);
    }
}

