<?php

namespace app\controllers;

use app\models\Author;
use yii\db\IntegrityException;
use yii\web\NotFoundHttpException;

class AuthorController extends ApiController
{
    protected array $protectedActions = ['create', 'update', 'delete'];

    public function actionIndex(): array
    {
        $request = \Yii::$app->request;
        $query = Author::find();
        if ($search = trim((string) $request->get('search', ''))) {
            $query->andWhere(['like', 'full_name', $search]);
        }
        $page = max(1, (int) $request->get('page', 1));
        $perPage = min(100, max(1, (int) $request->get('per-page', 20)));
        $total = (int) (clone $query)->count();
        $items = $query->orderBy(['full_name' => SORT_ASC])
            ->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->all();
        return $this->ok([
            'items' => $items,
            'pagination' => ['total' => $total, 'page' => $page, 'per_page' => $perPage, 'total_pages' => (int) ceil($total / $perPage)],
        ]);
    }

    public function actionView(int $id): array
    {
        $author = $this->findAuthor($id);
        $books = array_map(static fn ($book) => ['id' => (int) $book->id, 'title' => $book->title, 'year' => (int) $book->year], $author->books);
        return $this->ok(['id' => (int) $author->id, 'full_name' => $author->full_name, 'books' => $books]);
    }

    public function actionCreate(): array
    {
        return $this->saveAuthor(new Author(), 201);
    }

    public function actionUpdate(int $id): array
    {
        return $this->saveAuthor($this->findAuthor($id), 200);
    }

    public function actionDelete(int $id): array
    {
        $author = $this->findAuthor($id);
        if ($author->getBooks()->exists()) return $this->fail('Нельзя удалить автора, у которого есть книги.', null, 409);
        try {
            $author->delete();
            \Yii::$app->response->statusCode = 204;
            return [];
        } catch (IntegrityException) {
            return $this->fail('Нельзя удалить автора с зависимыми данными.', null, 409);
        }
    }

    private function saveAuthor(Author $author, int $status): array
    {
        $author->load(\Yii::$app->request->bodyParams, '');
        if (!$author->save()) return $this->validationErrors($author);
        return $this->ok($author, $status);
    }

    private function findAuthor(int $id): Author
    {
        $author = Author::find()->with('books')->where(['id' => $id])->one();
        if (!$author) throw new NotFoundHttpException('Автор не найден.');
        return $author;
    }
}

