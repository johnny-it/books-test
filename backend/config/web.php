<?php

$params = require __DIR__ . '/params.php';

return [
    'id' => 'book-catalog-api',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'controllerNamespace' => 'app\\controllers',
    'components' => [
        'request' => [
            'cookieValidationKey' => getenv('COOKIE_VALIDATION_KEY') ?: 'book-catalog-local-key',
            'enableCsrfValidation' => false,
            'parsers' => [
                'application/json' => yii\web\JsonParser::class,
            ],
        ],
        'response' => [
            'format' => yii\web\Response::FORMAT_JSON,
            'charset' => 'UTF-8',
        ],
        'db' => require __DIR__ . '/db.php',
        'cache' => [
            'class' => yii\caching\FileCache::class,
        ],
        'user' => [
            'identityClass' => app\models\User::class,
            'enableAutoLogin' => false,
            'enableSession' => false,
            'loginUrl' => null,
        ],
        'errorHandler' => [
            'class' => app\components\ApiErrorHandler::class,
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => yii\log\FileTarget::class,
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'enableStrictParsing' => true,
            'rules' => [
                'OPTIONS <path:.*>' => 'site/options',
                'GET api/v1/health' => 'site/health',
                'POST api/v1/auth/login' => 'auth/login',
                'POST api/v1/auth/refresh' => 'auth/refresh',
                'POST api/v1/auth/logout' => 'auth/logout',
                'GET api/v1/books' => 'book/index',
                'POST api/v1/books' => 'book/create',
                'GET api/v1/books/<id:\\d+>' => 'book/view',
                'PUT api/v1/books/<id:\\d+>' => 'book/update',
                'PATCH api/v1/books/<id:\\d+>' => 'book/update',
                'DELETE api/v1/books/<id:\\d+>' => 'book/delete',
                'GET api/v1/authors' => 'author/index',
                'POST api/v1/authors' => 'author/create',
                'GET api/v1/authors/<id:\\d+>' => 'author/view',
                'PUT api/v1/authors/<id:\\d+>' => 'author/update',
                'DELETE api/v1/authors/<id:\\d+>' => 'author/delete',
                'POST api/v1/authors/<id:\\d+>/subscriptions' => 'subscription/create',
                'GET api/v1/reports/top-authors' => 'report/top-authors',
            ],
        ],
    ],
    'params' => $params,
];
