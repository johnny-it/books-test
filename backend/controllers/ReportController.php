<?php

namespace app\controllers;

use app\models\Author;
use yii\db\Expression;

class ReportController extends ApiController
{
    public function actionTopAuthors(): array
    {
        $year = (int) \Yii::$app->request->get('year', 0);
        if ($year < 1000 || $year > 2100) return $this->fail('Укажите корректный параметр year.', 'year', 400);

        $rows = Author::find()
            ->alias('a')
            ->select(['author_id' => 'a.id', 'a.full_name', 'books_count' => new Expression('COUNT(DISTINCT b.id)')])
            ->innerJoin('{{%book_author}} ba', 'ba.author_id = a.id')
            ->innerJoin('{{%book}} b', 'b.id = ba.book_id AND b.year = :year', [':year' => $year])
            ->groupBy(['a.id', 'a.full_name'])
            ->orderBy(['books_count' => SORT_DESC, 'a.full_name' => SORT_ASC])
            ->limit(10)
            ->asArray()
            ->all();

        $items = [];
        foreach ($rows as $index => $row) {
            $items[] = [
                'rank' => $index + 1,
                'author_id' => (int) $row['author_id'],
                'full_name' => $row['full_name'],
                'books_count' => (int) $row['books_count'],
            ];
        }
        return $this->ok(['year' => $year, 'items' => $items]);
    }
}

