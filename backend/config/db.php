<?php

return [
    'class' => yii\db\Connection::class,
    'dsn' => getenv('DB_DSN') ?: 'mysql:host=127.0.0.1;dbname=books',
    'username' => getenv('DB_USER') ?: 'books',
    'password' => getenv('DB_PASSWORD') ?: 'books',
    'charset' => 'utf8mb4',
    'enableSchemaCache' => !YII_DEBUG,
];

