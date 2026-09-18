<?php

namespace app\commands;

use app\models\Notification;
use app\services\SmsPilotService;
use yii\console\Controller;
use yii\console\ExitCode;

class SmsController extends Controller
{
    public int $pollInterval = 5;
    public int $maxAttempts = 3;

    public function options($actionID): array
    {
        return array_merge(parent::options($actionID), ['pollInterval', 'maxAttempts']);
    }

    public function actionRun(): int
    {
        $service = new SmsPilotService();
        while (true) {
            $notification = Notification::find()
                ->with(['subscription', 'book'])
                ->where(['status' => ['pending', 'retry']])
                ->andWhere(['<=', 'available_at', date('Y-m-d H:i:s')])
                ->orderBy(['id' => SORT_ASC])
                ->one();
            if (!$notification) {
                sleep($this->pollInterval);
                continue;
            }

            $notification->status = 'processing';
            $notification->attempts++;
            $notification->save(false);
            try {
                $message = sprintf('Новая книга «%s» (%d) уже в каталоге.', $notification->book->title, $notification->book->year);
                $notification->provider_id = $service->send($notification->subscription->phone, $message);
                $notification->status = 'sent';
                $notification->sent_at = date('Y-m-d H:i:s');
                $notification->last_error = null;
            } catch (\Throwable $exception) {
                $notification->last_error = mb_substr($exception->getMessage(), 0, 1000);
                $notification->status = $notification->attempts >= $this->maxAttempts ? 'failed' : 'retry';
                $notification->available_at = date('Y-m-d H:i:s', time() + 60 * $notification->attempts);
                \Yii::warning($exception->getMessage(), 'sms');
            }
            $notification->save(false);
        }
        return ExitCode::OK;
    }
}

