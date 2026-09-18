<?php

namespace app\services;

use yii\httpclient\Client;

final class SmsPilotService
{
    public function send(string $phone, string $message): string
    {
        $apiKey = \Yii::$app->params['smsPilotApiKey'];
        if ($apiKey === '') {
            throw new \RuntimeException('SMSPILOT_API_KEY не настроен.');
        }
        $data = [
            'send' => $message,
            'to' => $phone,
            'apikey' => $apiKey,
            'format' => 'json',
        ];
        if (\Yii::$app->params['smsPilotSender'] !== '') {
            $data['from'] = \Yii::$app->params['smsPilotSender'];
        }
        $response = (new Client())
            ->get('https://smspilot.ru/api.php', $data)
            ->setOptions(['timeout' => 10])
            ->send();
        if (!$response->isOk) {
            throw new \RuntimeException('SMS Pilot недоступен: HTTP ' . $response->statusCode);
        }
        $payload = $response->data;
        if (isset($payload['error'])) {
            throw new \RuntimeException($payload['error']['description_ru'] ?? $payload['error']['description'] ?? 'Ошибка SMS Pilot.');
        }
        return (string) ($payload['send'][0]['server_id'] ?? 'accepted');
    }
}
