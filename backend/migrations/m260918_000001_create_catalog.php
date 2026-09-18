<?php

use yii\db\Migration;

class m260918_000001_create_catalog extends Migration
{
    public function safeUp(): bool
    {
        $options = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
        $this->createTable('{{%user}}', [
            'id' => $this->primaryKey(),
            'username' => $this->string(100)->notNull()->unique(),
            'password_hash' => $this->string()->notNull(),
            'role' => $this->string(32)->notNull()->defaultValue('user'),
            'created_at' => $this->dateTime()->notNull(),
        ], $options);
        $this->createTable('{{%author}}', [
            'id' => $this->primaryKey(),
            'full_name' => $this->string(160)->notNull()->unique(),
            'created_at' => $this->dateTime()->notNull(),
        ], $options);
        $this->createTable('{{%book}}', [
            'id' => $this->primaryKey(),
            'title' => $this->string()->notNull(),
            'year' => $this->integer()->notNull(),
            'description' => $this->text(),
            'isbn' => $this->string(20),
            'cover_path' => $this->string(),
            'created_at' => $this->dateTime()->notNull(),
            'updated_at' => $this->dateTime()->notNull(),
        ], $options);
        $this->createIndex('idx-book-year', '{{%book}}', 'year');
        $this->createIndex('idx-book-title', '{{%book}}', 'title');
        $this->createTable('{{%book_author}}', [
            'book_id' => $this->integer()->notNull(),
            'author_id' => $this->integer()->notNull(),
            'PRIMARY KEY(book_id, author_id)',
        ], $options);
        $this->addForeignKey('fk-ba-book', '{{%book_author}}', 'book_id', '{{%book}}', 'id', 'CASCADE');
        $this->addForeignKey('fk-ba-author', '{{%book_author}}', 'author_id', '{{%author}}', 'id', 'RESTRICT');
        $this->createTable('{{%subscription}}', [
            'id' => $this->primaryKey(),
            'author_id' => $this->integer()->notNull(),
            'phone' => $this->string(11)->notNull(),
            'created_at' => $this->dateTime()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
        ], $options);
        $this->createIndex('uq-subscription-author-phone', '{{%subscription}}', ['author_id', 'phone'], true);
        $this->addForeignKey('fk-subscription-author', '{{%subscription}}', 'author_id', '{{%author}}', 'id', 'CASCADE');
        $this->createTable('{{%notification}}', [
            'id' => $this->primaryKey(),
            'book_id' => $this->integer()->notNull(),
            'subscription_id' => $this->integer()->notNull(),
            'status' => $this->string(20)->notNull()->defaultValue('pending'),
            'attempts' => $this->integer()->notNull()->defaultValue(0),
            'available_at' => $this->dateTime()->notNull(),
            'provider_id' => $this->string(100),
            'last_error' => $this->text(),
            'sent_at' => $this->dateTime(),
            'created_at' => $this->dateTime()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
        ], $options);
        $this->createIndex('idx-notification-status-available', '{{%notification}}', ['status', 'available_at']);
        $this->createIndex('uq-notification-book-subscription', '{{%notification}}', ['book_id', 'subscription_id'], true);
        $this->addForeignKey('fk-notification-book', '{{%notification}}', 'book_id', '{{%book}}', 'id', 'CASCADE');
        $this->addForeignKey('fk-notification-subscription', '{{%notification}}', 'subscription_id', '{{%subscription}}', 'id', 'CASCADE');

        $now = date('Y-m-d H:i:s');
        $this->insert('{{%user}}', [
            'username' => 'demo',
            'password_hash' => Yii::$app->security->generatePasswordHash('demo1234'),
            'role' => 'user',
            'created_at' => $now,
        ]);
        foreach (['Анна Соколова', 'Елена Морозова', 'Дмитрий Крылов', 'Мария Львова', 'Илья Новиков', 'Нина Власова', 'Константин Беляев', 'Ольга Романова', 'Виктор Соловьёв', 'Полина Кузнецова'] as $name) {
            $this->insert('{{%author}}', ['full_name' => $name, 'created_at' => $now]);
        }
        $books = [
            ['Тихие горизонты', 2021, 'История о дороге к себе среди северных гор и долгих рассветов.', '978-5-17-123450-5', [1]],
            ['Письма к океану', 2022, 'Письма, путешествия и море, соединяющее далёкие берега.', '978-5-17-123452-9', [3]],
            ['Когда цветут камни', 2023, 'Тонкая история о стойкости, дружбе и невозможных садах.', '978-5-17-123454-3', [5]],
            ['Люди и острова', 2021, 'Наблюдения о людях, море и расстояниях между ними.', '978-5-17-123456-7', [7]],
            ['Зимний сад', 2022, 'Тихий роман о доме, пережившем много зим.', '978-5-17-123457-4', [8]],
            ['Там, где свет', 2023, 'Сборник рассказов о маленьких решениях, меняющих большие жизни.', '978-5-17-123459-8', [10]],
            ['Над крышами августа', 2025, 'История одного лета, которое изменило привычный ритм большого города.', '978-5-17-123460-4', [1]],
            ['Архив дождя', 2025, 'Семейная тайна раскрывается в письмах, найденных после долгого осеннего дождя.', '978-5-17-123461-1', [2]],
            ['Последний маяк', 2025, 'Два исследователя отправляются к заброшенному маяку на краю северного моря.', '978-5-17-123462-8', [3, 7]],
            ['Северный экспресс', 2025, 'Пассажиры ночного поезда оказываются связаны одной забытой историей.', '978-5-17-123463-5', [4]],
            ['Дом с зелёными окнами', 2025, 'Роман о возвращении в старый дом и попытке заново собрать семью.', '978-5-17-123464-2', [5]],
            ['Карта забытых рек', 2025, 'Путешествие по исчезнувшим руслам превращается в поиски собственного прошлого.', '978-5-17-123465-9', [6, 9]],
            ['Шёпот старого театра', 2025, 'За кулисами закрытого театра оживают истории его прежних артистов.', '978-5-17-123466-6', [7]],
            ['Время бумажных птиц', 2025, 'Тихая повесть о письмах, мечтах и случайных встречах на вокзале.', '978-5-17-123467-3', [8]],
            ['Линия прибоя', 2025, 'Жители приморского города учатся принимать перемены после сильного шторма.', '978-5-17-123468-0', [9]],
            ['Пока звучит музыка', 2025, 'Три автора рассказывают одну историю с разных сторон и в разных ритмах.', '978-5-17-123469-7', [10, 2, 5]],
            ['Стеклянный полдень', 2025, 'Городская драма о выборе, который нельзя отложить до вечера.', '978-5-17-123470-3', [1]],
            ['Семь дней до весны', 2025, 'Семь коротких историй о людях, ожидающих первых тёплых дней.', '978-5-17-123471-0', [4]],
            ['Орбита тишины', 2026, 'Научная экспедиция пытается понять сигнал, пришедший из безмолвного сектора космоса.', '978-5-17-123472-7', [1, 6]],
            ['Библиотека у моря', 2026, 'Хранительница маленькой библиотеки спасает книги и воспоминания прибрежного города.', '978-5-17-123473-4', [2]],
            ['Невидимый мост', 2026, 'Две семьи по разные стороны реки ищут путь друг к другу.', '978-5-17-123474-1', [3]],
            ['Созвездие фонарей', 2026, 'Ночные прогулки по городу складываются в карту человеческих судеб.', '978-5-17-123475-8', [4, 8]],
            ['Пятое время года', 2026, 'Необычная зима нарушает привычный порядок жизни небольшого посёлка.', '978-5-17-123476-5', [5]],
            ['Хранители мая', 2026, 'Друзья возвращаются в родной город, чтобы сохранить старый яблоневый сад.', '978-5-17-123477-2', [6]],
            ['Пассажиры полуночи', 2026, 'Случайная встреча в последнем автобусе меняет планы нескольких незнакомцев.', '978-5-17-123478-9', [7]],
            ['Берег будущего', 2026, 'Три взгляда на город, который готовится сделать решающий шаг в будущее.', '978-5-17-123479-6', [8, 9, 10]],
            ['Механика надежды', 2026, 'Инженер восстанавливает старые часы и отношения с близкими людьми.', '978-5-17-123480-2', [9]],
            ['Снег в июле', 2026, 'Неожиданное природное явление заставляет героев пересмотреть свои решения.', '978-5-17-123481-9', [10]],
            ['Комната для ветра', 2026, 'Архитектор проектирует дом, открытый переменам и новым историям.', '978-5-17-123482-6', [1]],
            ['После последней страницы', 2026, 'Читатели книжного клуба продолжают историю, которую автор оставил незавершённой.', '978-5-17-123483-3', [3]],
        ];
        foreach ($books as [$title, $year, $description, $isbn, $authorIds]) {
            $this->insert('{{%book}}', compact('title', 'year', 'description', 'isbn') + ['cover_path' => null, 'created_at' => $now, 'updated_at' => $now]);
            $bookId = $this->db->getLastInsertID();
            foreach ($authorIds as $authorId) {
                $this->insert('{{%book_author}}', ['book_id' => $bookId, 'author_id' => $authorId]);
            }
        }
        return true;
    }

    public function safeDown(): bool
    {
        $this->dropTable('{{%notification}}');
        $this->dropTable('{{%subscription}}');
        $this->dropTable('{{%book_author}}');
        $this->dropTable('{{%book}}');
        $this->dropTable('{{%author}}');
        $this->dropTable('{{%user}}');
        return true;
    }
}
