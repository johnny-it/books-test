<?php

return [
    'id' => 'book-catalog-console',
    'basePath' => dirname(__DIR__),
    'controllerNamespace' => 'app\\commands',
    'components' => [
        'db' => require __DIR__ . '/db.php',
        'log' => [
            'targets' => [[
                'class' => yii\log\FileTarget::class,
                'levels' => ['error', 'warning', 'info'],
            ]],
        ],
    ],
    'params' => require __DIR__ . '/params.php',
];

