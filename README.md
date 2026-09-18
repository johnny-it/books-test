# Книжная полка

Полнофункциональное решение тестового задания: адаптивный клиент на Vue 3 Composition API и REST API на Yii2 с MySQL, JWT, загрузкой обложек, подписками и фоновой отправкой SMS.

## Что реализовано

- публичный каталог книг с поиском, фильтрами и серверной пагинацией;
- страницы книг и авторов, публичный TOP-10 за выбранный год;
- JWT-вход и защищённый CRUD книг и авторов;
- связь книги с несколькими авторами;
- загрузка обложки до 5 МБ в JPG, PNG или WebP;
- гостевая подписка по российскому номеру телефона;
- очередь уведомлений и worker для SMS Pilot;
- единый адаптивный интерфейс без UI-библиотеки;
- актуализированный контракт в `openapi/book.yaml`.

## Запуск

Требуется Docker Desktop с Docker Compose.

```bash
copy .env.example .env
docker compose up --build -d
```

После первого запуска автоматически применяются миграции и создаются демонстрационные данные: пользователь `demo`, 10 авторов и 30 книг. Среди книг по 12 изданий за 2025 и 2026 годы, включая книги с несколькими авторами.

- Frontend: http://localhost:5173
- API: http://localhost:8080/api/v1
- Health check: http://localhost:8080/api/v1/health

Демонстрационный пользователь:

```text
login: demo
password: demo1234
```

Остановка:

```bash
docker compose down
```

Повторная загрузка тестовых данных:

> Команды ниже удаляют все текущие записи из базы данных и создают демонстрационные данные заново. Загруженные файлы обложек при этом не удаляются.

```bash
docker compose up --build -d
docker compose stop worker
docker compose exec api php yii migrate/down all --interactive=0
docker compose exec api php yii migrate --interactive=0
docker compose start worker
```

Полный сброс базы и загруженных обложек:

```bash
docker compose down -v
```

## Конфигурация

Переменные описаны в `.env.example`:

- `MYSQL_*` — база и пользователь MySQL;
- `JWT_SECRET` — HMAC-секрет длиной не менее 32 байт;
- `JWT_TTL` — время жизни токена в секундах;
- `REFRESH_TOKEN_TTL` — абсолютное время жизни refresh-сессии в секундах; локальное значение `604800` соответствует семи дням;
- `REFRESH_COOKIE_NAME` — имя HttpOnly cookie с refresh token; по умолчанию `book_catalog_refresh`;
- `COOKIE_SECURE` — добавляет cookie атрибут `Secure`; для локального HTTP используется `false`;
- `COOKIE_DOMAIN` — необязательный домен cookie; локально оставляется пустым, чтобы атрибут `Domain` не устанавливался;
- `FRONTEND_ORIGIN` — единственный разрешённый CORS-origin;
- `API_PUBLIC_URL` — публичный URL API для клиента и ссылок на обложки;
- `SMSPILOT_API_KEY` — ключ SMS Pilot; в примере указан ключ-эмулятор;
- `SMSPILOT_SENDER` — необязательное зарегистрированное имя отправителя.

Секреты из `.env.example` предназначены только для локальной разработки. Перед публикацией замените пароли и `JWT_SECRET`. В production приложение должно работать по HTTPS, а `COOKIE_SECURE` должно быть установлено в `true`.

## Покрытие исходного API во фронтенде

| Раздел | Методы из `book.yaml` | Пользовательский сценарий |
| --- | --- | --- |
| Авторизация | `POST /auth/login` | Форма входа |
| Книги | `GET /books`, `GET /books/{id}` | Каталог и страница книги |
| Книги | `POST /books` | Создание книги с обложкой |
| Книги | `PUT /books/{id}` | Полное multipart-обновление при замене обложки; передаётся как `POST` с `_method=PUT` для совместимости с Yii/PHP |
| Книги | `PATCH /books/{id}` | Обновление только изменённых текстовых полей и авторов без новой обложки |
| Книги | `DELETE /books/{id}` | Удаление из каталога или со страницы книги |
| Авторы | `GET /authors`, `GET /authors/{id}` | Список и страница автора |
| Авторы | `POST /authors`, `PUT /authors/{id}`, `DELETE /authors/{id}` | Создание, редактирование и удаление автора |
| Отчёты | `GET /reports/top-authors` | Публичный рейтинг за выбранный год |

Расширенный API проекта дополнительно использует `POST /auth/refresh`, `POST /auth/logout`, `GET /health` и `POST /authors/{id}/subscriptions`. Они обеспечивают восстановление и завершение сессии, проверку доступности API и гостевую SMS-подписку из тестового задания.

## Архитектура

```text
Browser :5173 ──► Vue 3 + nginx
      │
      └─────────► Yii2 REST API :8080 ──► MySQL 8
                               │
                               └──────► notification table ──► SMS worker ──► SMS Pilot
```

`api` и `worker` собираются из одного PHP-образа. API выполняет миграции при старте; worker читает таблицу `notification`, делает до трёх попыток и сохраняет статус и ошибку провайдера. Обложки лежат в именованном Docker volume `covers`.

## API

Основные методы:

```text
POST   /api/v1/auth/login
POST   /api/v1/auth/refresh
POST   /api/v1/auth/logout
GET    /api/v1/books
POST   /api/v1/books
GET    /api/v1/books/{id}
PUT    /api/v1/books/{id}
PATCH  /api/v1/books/{id}
DELETE /api/v1/books/{id}
GET    /api/v1/authors
POST   /api/v1/authors
GET    /api/v1/authors/{id}
PUT    /api/v1/authors/{id}
DELETE /api/v1/authors/{id}
POST   /api/v1/authors/{id}/subscriptions
GET    /api/v1/reports/top-authors?year=2023
```

Для изменения книги с новой обложкой браузер отправляет обычный multipart POST с полем `_method=PUT`. Yii2 преобразует его в PUT; это обходит ограничение PHP на разбор multipart-тела настоящего PUT-запроса.

Пример входа:

```bash
curl -X POST http://localhost:8080/api/v1/auth/login \
  -H "Origin: http://localhost:5173" \
  -H "Content-Type: application/json" \
  -c refresh.cookies \
  -d '{"username":"demo","password":"demo1234"}'
```

Access JWT возвращается в теле ответа и хранится только в памяти frontend. Refresh token недоступен JavaScript: API устанавливает его в HttpOnly cookie `book_catalog_refresh` с `SameSite=Lax` и `Path=/api/v1/auth`. Браузерные запросы login, refresh и logout отправляются с credentials.

Refresh-сессия имеет абсолютный срок жизни семь дней (`604800` секунд). Каждый успешный refresh отзывает использованный token и ротирует cookie, но не продлевает исходный срок сессии. Повторное использование уже заменённого token считается replay-атакой: API отзывает активную цепочку сессии и возвращает `401`. Logout отзывает текущую refresh-сессию, удаляет cookie и допускает безопасный повторный вызов.

Cookie-endpoints `/auth/refresh` и `/auth/logout` принимают только запросы с точным заголовком `Origin`, равным `FRONTEND_ORIGIN`; отсутствующий или другой Origin возвращает `403`. Для локального HTTP используются `COOKIE_SECURE=false` и пустой `COOKIE_DOMAIN`. В production обязательны HTTPS и `COOKIE_SECURE=true`; `COOKIE_DOMAIN` задаётся только при необходимости общего домена.

Пример подписки:

```bash
curl -X POST http://localhost:8080/api/v1/authors/1/subscriptions \
  -H "Content-Type: application/json" \
  -d '{"phone":"+7 999 123-45-67"}'
```

## Разработка frontend

```bash
cd frontend
npm install
npm run dev
npm run lint
npm run build
```

Клиент написан на JavaScript без TypeScript и автоматических тестов, как было оговорено. Access JWT и данные пользователя хранятся только в памяти Pinia. После перезагрузки страницы клиент восстанавливает вход через HttpOnly refresh cookie; при `401` выполняет один refresh, повторяет исходный запрос и завершает локальную сессию, если обновление не удалось.

## Ручная проверка перед сдачей

- открыть каталог гостем, проверить поиск, автора, год и пагинацию;
- открыть книгу, автора и отчёт для года с данными и без данных;
- оформить подписку и повторить её для проверки идемпотентности;
- войти как `demo`, создать автора и книгу с обложкой;
- изменить книгу без новой обложки и убедиться, что старая сохранилась;
- удалить книгу, затем автора;
- проверить, что запросы CRUD без Bearer-токена возвращают `401`;
- проверить desktop и mobile layout и отсутствие ошибок в консоли;
- проверить `docker compose logs worker` и статус записи в `notification`.

## Границы задания

Регистрация, восстановление пароля, администрирование пользователей и отписка не добавлялись. Исходников заявленного Yii2-бэкенда не было, поэтому API создан заново по предоставленной спецификации. Обязательная часть — каталог, CRUD и отчёт; подписка, очередь и SMS Pilot относятся к бонусной части и выводят общий объём за исходный лимит в 8 часов.

## Скриншоты
<img width="3840" height="3642" alt="FireShot Capture 020 - Книжная полка - localhost" src="https://github.com/user-attachments/assets/71626e63-191c-4638-bd7f-6e9ed20360eb" />
<img width="3840" height="2135" alt="FireShot Capture 021 - Книжная полка - localhost" src="https://github.com/user-attachments/assets/ada5f0ee-a3b8-4d80-980e-1adf4335fd9a" />
<img width="3840" height="3276" alt="FireShot Capture 022 - Книжная полка - localhost" src="https://github.com/user-attachments/assets/8338ccfa-346e-4989-92a5-0515fef90faf" />
<img width="3840" height="2201" alt="FireShot Capture 023 - Книжная полка - localhost" src="https://github.com/user-attachments/assets/5ae25f70-cf7e-4464-8c74-f22f407af6e1" />
<img width="3840" height="3189" alt="FireShot Capture 024 - Книжная полка - localhost" src="https://github.com/user-attachments/assets/dad7efb9-2624-470b-8567-f2e489a09df8" />
<img width="3840" height="3159" alt="FireShot Capture 025 - Книжная полка - localhost" src="https://github.com/user-attachments/assets/ce340d1e-7f08-40bb-9d6d-2c076d4340e7" />
