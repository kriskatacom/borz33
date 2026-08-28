# Borz33

Онлайн магазин с разделени части: сайт с плановете на проекта, JSON API, бъдещ PHP магазин за клиентите и (по-късно) React админ панел.

## Какво има към момента

- **Сайт с плановете** — `plans/`. Това е представянето на проекта (етапи, страници, подготовка), не магазинът за клиенти. PHP страниците са в `plans/public/`, изгледите в `plans/views/`.
- **Магазин за клиенти** — `web/`. Тук ще бъде истинският PHP SSR сайт (MVC + services). Папката е отделена нарочно, за да не се смесва с плановете.
- **API** — `api/`. MVC + services, отговорите са JSON. Рутерът е в `api/app/Core/Router.php`, маршрутите в `api/routes/api.php`. Готов endpoint за проверка: `GET /health`.
- **Docker** — `docker-compose.yml` в корена. Оттам се пускат всички услуги в една мрежа: MySQL, phpMyAdmin, PHP-FPM, Nginx (планове + API). React админът е предвиден, но още не се стартира по подразбиране.

Планирано разделение:

| Част | Технология | Директория | Състояние |
|---|---|---|---|
| Сайт с плановете | PHP | `plans/` | Готов за преглед |
| Магазин (клиенти) | PHP SSR, MVC + services | `web/` | Предстои |
| API | PHP, MVC + services, JSON | `api/` | Начална структура |
| Админ панел | React / Redux | `admin/` | Предстои |
| База данни | MySQL 8.4 + phpMyAdmin | — | Работи през Docker |

## Docker

Изисква Docker Compose. Конфигурацията е в `.env` (шаблон: `.env.example`).

### Адреси

| Услуга | Адрес |
|---|---|
| Сайт с плановете | http://localhost:8000 |
| API | http://localhost:8080 |
| API health (Postman) | http://localhost:8080/health |
| phpMyAdmin | http://localhost:8081 |
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

### Стартиране

```bash
docker compose up -d
```

Първото пускане билдва PHP образа. След това сайтът с плановете е на `:8000`, API-то на `:8080`, phpMyAdmin на `:8081`.

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

### React админ (още не е готов)

Услугата `admin` е в Compose с профил `admin`, за да не се пуска, докато няма папка `admin/`. Когато панелът съществува:

```bash
docker compose --profile admin up -d
```

По подразбиране ще е на http://localhost:5173.

Портовете се сменят в `.env` (`PLANS_PORT`, `API_PORT`, `PHPMYADMIN_PORT`, `MYSQL_PORT`, `ADMIN_PORT`).
