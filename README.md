# Borz33

Онлайн магазин с разделени части: сайт с плановете на проекта, JSON API, бъдещ PHP магазин за клиентите и React/Redux админ панел.

## Какво има към момента

- **Сайт с плановете** — `plans/`. Това е представянето на проекта (етапи, страници, подготовка), не магазинът за клиенти. PHP страниците са в `plans/public/`, изгледите в `plans/views/`.
- **Магазин за клиенти** — `web/`. Тук ще бъде истинският PHP SSR сайт (MVC + services). Папката е отделена нарочно, за да не се смесва с плановете.
- **API** — `api/`. MVC + services, отговорите са JSON. Рутерът е в `api/app/Core/Router.php`, маршрутите в `api/routes/api.php`. Готов endpoint за проверка: `GET /health`.
- **Админ панел** — `admin/`. Vite + React + Redux Toolkit. Има маршрути за вход и забравена парола (без форми още) и няма регистрация. API заявките минават през Vite proxy към JSON API.
- **Docker** — `docker-compose.yml` в корена. Оттам се пускат всички услуги в една мрежа: MySQL, phpMyAdmin, Mailpit (локални имейли), PHP-FPM, Nginx (планове + API) и Vite за админа.

Планирано разделение:

| Част | Технология | Директория | Състояние |
|---|---|---|---|
| Сайт с плановете | PHP | `plans/` | Готов за преглед |
| Магазин (клиенти) | PHP SSR, MVC + services | `web/` | Предстои |
| API | PHP, MVC + services, JSON | `api/` | Начална структура |
| Админ панел | React / Redux | `admin/` | Вход и табло |
| База данни | MySQL 8.4 + phpMyAdmin + Phinx | `database/` | Работи през Docker |
| Имейли (локално) | Mailpit + Symfony Mailer | — | Работи през Docker |

## Docker

Изисква Docker Compose. Конфигурацията е в `.env` (шаблон: `.env.example`).

### Адреси

| Услуга | Адрес |
|---|---|
| Сайт с плановете | http://localhost:8000 |
| API | http://localhost:8080 |
| API health (Postman) | http://localhost:8080/health |
| phpMyAdmin | http://localhost:8081 |
| Mailpit (имейли) | http://localhost:8026 |
| Админ панел | http://localhost:5173 |
| MySQL от хоста | `localhost:3307` |
| MySQL между контейнерите | хост `mysql`, порт `3306` |

MySQL към хоста е на **3307**, защото 3306 често вече е зает. Вътре в Docker мрежата портът остава 3306.

### Вход в MySQL

Стекът трябва да е пуснат (`docker compose up -d`). Данните са в `.env`:

| | Стойност |
|---|---|
| База | `borz33` |
| Потребител | `borz33` |
| Парола | `borz33` |
| Root парола | `root` |

**phpMyAdmin (преглед в браузър):** http://localhost:8081

Сървърът е вече избран (`mysql`). Влез с `borz33` / `borz33` или като root с `root` / `root`. Оттам се гледат бази, таблици и SQL, без терминал.

**От терминал, през Docker** (по желание):

```bash
docker compose exec mysql mysql -u borz33 -pborz33 borz33
```

Като root:

```bash
docker compose exec mysql mysql -u root -proot borz33
```

Изход от клиента: `exit` или `Ctrl+D`.

**От хоста** (TablePlus, DBeaver, MySQL Workbench, `mysql` CLI):

| Поле | Стойност |
|---|---|
| Host | `127.0.0.1` |
| Port | `3307` |
| User | `borz33` |
| Password | `borz33` |
| Database | `borz33` |

```bash
mysql -h 127.0.0.1 -P 3307 -u borz33 -p borz33
```

**От PHP / API контейнера** ползвай хост `mysql` и порт `3306` (вътрешната Docker мрежа), не `localhost:3307`.

### Имейли (Mailpit)

Локално писмата не излизат в интернет. API-то ги праща по SMTP към Mailpit (`mailpit:1025`), а ги четеш в браузъра: http://localhost:8026

Изпращане от PHP:

```php
$mailer = new \App\Services\Mail\MailService();
$mailer->sendTemplate('user@example.com', 'Заглавие', 'verify-registration', $data, 'текстов вариант');
```

Всички писма минават през `resources/emails/layout.php` (фирма, ЕИК, адрес, GDPR текстове, линкове към поверителност и условия). Съдържанието е отделен шаблон в същата папка.

Потвърждение на регистрация: след `POST /auth/register` клиентът получава 6-цифрен код. Въвежда се с `POST /auth/verify-email` (`email` + `code`). Нов код: `POST /auth/verify-email/resend`. Админският потребител от `.env` не получава писмо. Кодът е валиден `MAIL_VERIFICATION_TTL_MINUTES` минути (по подразбиране 15).

Фирмените данни в писмата се настройват с `COMPANY_*` в `.env`.

### Вход

`POST /auth/login` с `email`, `password`, `device_uuid` (UUID) и по желание `device_name`.

- Имейлът трябва да е потвърден.
- Неуспешните опити се броят по имейл и IP. След 5 грешни пароли за 15 минути входът се заключва (429).
- Устройството от регистрацията е доверено и влиза директно с Bearer token (30 дни).
- Ново устройство получава 6-цифрен код по имейл. Потвърждение: `POST /auth/login/device`. Нов код: `POST /auth/login/device/resend`.
- Грешната парола не издава дали имейлът съществува.

Админ панелът ползва отделни маршрути: `POST /auth/admin/login`, `POST /auth/admin/login/device`, `POST /auth/admin/login/device/resend`. Влизат само потребители с роля `admin`. Ако админ още няма, първият админски вход го създава от `ADMIN_*` в `.env`. Клиентски профил получава същия отговор като грешна парола.

Сесия: `GET /auth/me` и `POST /auth/logout` с `Authorization: Bearer`.

Забравена парола (само админ): `POST /auth/admin/password/forgot` и `POST /auth/admin/password/reset`. Линкът в писмото води към `ADMIN_PUBLIC_URL` (по подразбиране http://localhost:5173/reset-password).

### Потребители (админ)

Всички маршрути изискват Bearer token на активен администратор.

| Метод | Път | Действие |
|---|---|---|
| GET | `/admin/users` | Списък. Query: `q`, `role` (`admin`/`customer`), `status` (`all`/`active`/`inactive`/`deleted`), `page`, `per_page` (до 50) |
| POST | `/admin/users` | Нов профил (потвърден имейл, зададена парола, роля и активност) |
| GET | `/admin/users/{id}` | Детайли, включително изтрит профил |
| PATCH / PUT | `/admin/users/{id}` | Редакция. Паролата е по желание; при смяна или деактивиране сесиите се прекратяват |
| DELETE | `/admin/users/{id}` | Меко изтриване |
| POST | `/admin/users/{id}/restore` | Възстановяване |

Не може да изтриете, деактивирате или свалите ролята на собствения си профил. Винаги остава поне един активен администратор.

### Миграции (Phinx)

Миграциите са общи за API и сайта. Файловете са в `database/migrations/`, конфигурацията в `phinx.php`. Командите се пускат **вътре в PHP контейнера** (там има PDO и връзка към `mysql`).

Първо стекът трябва да е вдигнат. При липса на `vendor/` контейнерът сам прави `composer install` при старт.

```bash
docker compose up -d --build
```

**Създаване на миграция:**

```bash
./bin/phinx create CreateUsersTable
```

Името е PascalCase, без интервали. Phinx ще създаде файл в `database/migrations/`. В `up()` пишеш промените, в `down()` — връщането им.

**Прилагане:**

```bash
./bin/phinx migrate
```

**Статус:**

```bash
./bin/phinx status
```

**Връщане на последната миграция:**

```bash
./bin/phinx rollback
```

Връщане с няколко стъпки: `./bin/phinx rollback -t 0` (всички) или `./bin/phinx rollback -t YYYYMMDDHHMMSS` до конкретна версия.

**Seeds** (тестови данни): файлове в `database/seeds/`.

```bash
./bin/phinx seed:create UserSeeder
./bin/phinx seed:run
```

Еквивалент без `./bin/phinx`:

```bash
docker compose exec php vendor/bin/phinx migrate
docker compose exec php composer migrate
```

Таблицата `phinxlog` държи кои миграции са приложени. Вижда се в phpMyAdmin.

### Стартиране

```bash
docker compose up -d
```

Първото пускане билдва PHP образа и при нужда инсталира Composer зависимостите. След това сайтът с плановете е на `:8000`, API-то на `:8080`, phpMyAdmin на `:8081`, Mailpit на `:8026`.

Статус:

```bash
docker compose ps
```

Логове (всички или само избрани услуги — `nginx`, `php`, `mysql`):

```bash
docker compose logs -f
docker compose logs -f nginx php
```

### Спиране

Спира контейнерите, запазва MySQL данните:

```bash
docker compose stop
```

Спира и премахва контейнерите (томът с MySQL остава):

```bash
docker compose down
```

Пълно изчистване, включително базата:

```bash
docker compose down -v
```

### Postman

Импорт: **File → Import** и избери `postman/Borz33-API.postman_collection.json`.

Колекцията ползва `baseUrl` = `http://localhost:8080`. Docker стекът трябва да е пуснат. Първата заявка за тест е **Health → Health check** (`GET /health`).

### React админ

Vite приложението е в `admin/` (React 19, Redux Toolkit, React Router). Стартира се с останалия стек:

```bash
docker compose up -d
```

Адрес: http://localhost:5173. Браузърът говори само с Vite; `/auth` и `/health` се проксират към API (`http://nginx:8080` в Docker, или `http://127.0.0.1:8080` при `npm run dev` на хоста).

Има работещ вход, забравена парола, табло и пълно управление на потребителите. Регистрация в админ панела няма.

Локално без Docker:

```bash
cd admin
npm install
npm run dev
```

Портовете се сменят в `.env` (`PLANS_PORT`, `API_PORT`, `PHPMYADMIN_PORT`, `MAILPIT_UI_PORT`, `MYSQL_PORT`, `ADMIN_PORT`).
