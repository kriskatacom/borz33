# Borz33

Онлайн магазин с разделени части: сайт с плановете на проекта, JSON API, бъдещ PHP магазин за клиентите и (по-късно) React админ панел.

## Какво има към момента

- **Сайт с плановете** — `plans/`. Това е представянето на проекта (етапи, страници, подготовка), не магазинът за клиенти. PHP страниците са в `plans/public/`, изгледите в `plans/views/`.
- **Магазин за клиенти** — `web/`. Тук ще бъде истинският PHP SSR сайт (MVC + services). Папката е отделена нарочно, за да не се смесва с плановете.
- **API** — `api/`. MVC + services, отговорите са JSON. Рутерът е в `api/app/Core/Router.php`, маршрутите в `api/routes/api.php`. Готов endpoint за проверка: `GET /health`.
- **Docker** — `docker-compose.yml` в корена. Оттам се пускат всички услуги в една мрежа: MySQL, phpMyAdmin, Mailpit (локални имейли), PHP-FPM, Nginx (планове + API). React админът е предвиден, но още не се стартира по подразбиране.

Планирано разделение:

| Част | Технология | Директория | Състояние |
|---|---|---|---|
| Сайт с плановете | PHP | `plans/` | Готов за преглед |
| Магазин (клиенти) | PHP SSR, MVC + services | `web/` | Предстои |
| API | PHP, MVC + services, JSON | `api/` | Начална структура |
| Админ панел | React / Redux | `admin/` | Предстои |
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
$mailer->send('user@example.com', 'Заглавие', '<p>HTML съдържание</p>', 'Текстово съдържание');
```

Настройките са в `.env` (`MAIL_DSN`, `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME`). Потвърждение с код още не е вързано — само транспортът.

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

### React админ (още не е готов)

Услугата `admin` е в Compose с профил `admin`, за да не се пуска, докато няма папка `admin/`. Когато панелът съществува:

```bash
docker compose --profile admin up -d
```

По подразбиране ще е на http://localhost:5173.

Портовете се сменят в `.env` (`PLANS_PORT`, `API_PORT`, `PHPMYADMIN_PORT`, `MAILPIT_UI_PORT`, `MYSQL_PORT`, `ADMIN_PORT`).
