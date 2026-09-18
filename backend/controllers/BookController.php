<?php

namespace app\controllers;

use app\models\Book;
use app\services\NotificationService;
use yii\data\Pagination;
use yii\db\Expression;
use yii\web\NotFoundHttpException;
use yii\web\UploadedFile;

class BookController extends ApiController
{
    protected array $protectedActions = ['create', 'update', 'delete'];

    public function actionIndex(): array
    {
        $request = \Yii::$app->request;
        $query = Book::find()->with('authors')->distinct();
        if ($search = trim((string) $request->get('search', ''))) {
            $query->andWhere(['or',
                ['like', 'title', $search],
                ['like', 'description', $search],
                ['like', 'isbn', $search],
            ]);
        }
        if ($year = $request->get('year')) {
            $query->andWhere(['year' => (int) $year]);
        }
        if ($authorId = $request->get('author_id')) {
            $query->joinWith('authors')->andWhere(['author.id' => (int) $authorId]);
        }

        $page = max(1, (int) $request->get('page', 1));
        $perPage = min(100, max(1, (int) $request->get('per-page', 12)));
        $total = (int) (clone $query)->count();
        $items = $query->orderBy(['book.id' => SORT_DESC])
            ->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->all();

        return $this->ok([
            'items' => $items,
            'pagination' => [
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'total_pages' => (int) ceil($total / $perPage),
            ],
        ]);
    }

    public function actionView(int $id): array
    {
        return $this->ok($this->findBook($id));
    }

    public function actionCreate(): array
    {
        return $this->saveBook(new Book(), true);
    }

    public function actionUpdate(int $id): array
    {
        return $this->saveBook($this->findBook($id), false);
    }

    public function actionDelete(int $id): array
    {
        $book = $this->findBook($id);
        $cover = $book->cover_path;
        if (!$book->delete()) return $this->fail('Не удалось удалить книгу.', null, 500);
        if ($cover) {
            $path = \Yii::getAlias('@webroot/uploads/covers/') . $cover;
            if (is_file($path)) @unlink($path);
        }
        \Yii::$app->response->statusCode = 204;
        return [];
    }

    private function saveBook(Book $book, bool $isNew): array
    {
        $request = \Yii::$app->request;
        $payload = $request->bodyParams ?: $request->post();
        foreach (['title', 'year', 'description', 'isbn'] as $attribute) {
            if (array_key_exists($attribute, $payload)) $book->$attribute = $payload[$attribute];
        }
        $authorIds = $payload['author_ids'] ?? $book->getAuthors()->select('id')->column();
        if (is_string($authorIds)) $authorIds = array_filter(explode(',', $authorIds));
        $book->author_ids = array_values((array) $authorIds);
        $cover = UploadedFile::getInstanceByName('cover');

        if ($isNew && !$cover) {
            $book->addError('cover', 'Добавьте изображение обложки.');
        }
        if ($cover) {
            if (!in_array(strtolower($cover->extension), ['jpg', 'jpeg', 'png', 'webp'], true)) {
                $book->addError('cover', 'Поддерживаются JPG, PNG и WebP.');
            }
            if ($cover->size > 5 * 1024 * 1024) {
                $book->addError('cover', 'Размер обложки не должен превышать 5 МБ.');
            }
        }
        if (!$book->validate() || $book->hasErrors()) return $this->validationErrors($book);

        $transaction = \Yii::$app->db->beginTransaction();
        $oldCover = $book->cover_path;
        $newCover = null;
        try {
            if ($cover) {
                $newCover = bin2hex(random_bytes(12)) . '.' . strtolower($cover->extension);
                $directory = \Yii::getAlias('@webroot/uploads/covers');
                if (!is_dir($directory)) mkdir($directory, 0775, true);
                if (!$cover->saveAs($directory . DIRECTORY_SEPARATOR . $newCover)) {
                    throw new \RuntimeException('Не удалось сохранить обложку.');
                }
                $book->cover_path = $newCover;
            }
            $book->save(false);
            $book->syncAuthors($book->author_ids);
            if ($isNew) NotificationService::enqueueForBook($book, $book->author_ids);
            $transaction->commit();

            if ($newCover && $oldCover) {
                $oldPath = \Yii::getAlias('@webroot/uploads/covers/') . $oldCover;
                if (is_file($oldPath)) @unlink($oldPath);
            }
            $book->refresh();
            $book->populateRelation('authors', $book->getAuthors()->all());
            return $this->ok($book, $isNew ? 201 : 200);
        } catch (\InvalidArgumentException $exception) {
            $transaction->rollBack();
            if ($newCover) @unlink(\Yii::getAlias('@webroot/uploads/covers/') . $newCover);
            return $this->fail($exception->getMessage(), 'author_ids');
        } catch (\Throwable $exception) {
            $transaction->rollBack();
            if ($newCover) @unlink(\Yii::getAlias('@webroot/uploads/covers/') . $newCover);
            \Yii::error($exception);
            return $this->fail('Не удалось сохранить книгу.', null, 500);
        }
    }

    private function findBook(int $id): Book
    {
        $book = Book::find()->with('authors')->where(['id' => $id])->one();
        if (!$book) throw new NotFoundHttpException('Книга не найдена.');
        return $book;
    }
}
