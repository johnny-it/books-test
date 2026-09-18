<?php

namespace app\components;

use yii\web\HttpException;
use yii\web\Response;

class ApiErrorHandler extends \yii\web\ErrorHandler
{
    protected function renderException($exception): void
    {
        if (YII_DEBUG) {
            parent::renderException($exception);
            return;
        }

        $response = \Yii::$app->response;
        $response->format = Response::FORMAT_JSON;
        $response->statusCode = $exception instanceof HttpException ? $exception->statusCode : 500;
        $response->data = [
            'success' => false,
            'errors' => [[
                'field' => null,
                'message' => $response->statusCode >= 500 ? 'Внутренняя ошибка сервера.' : $exception->getMessage(),
            ]],
        ];
        $response->send();
    }
}

