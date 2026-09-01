# Borz33

Онлайн магазин с разделени части: сайт с плановете на проекта, JSON API, бъдещ PHP магазин за клиентите и React/Redux админ панел.

## Какво има към момента

- **Сайт с плановете** — `plans/`. Това е представянето на проекта (етапи, страници, подготовка), не магазинът за клиенти. PHP страниците са в `plans/public/`, изгледите в `plans/views/`.
- **Магазин за клиенти** — `web/`. PHP SSR за витрината. Стиловете са Tailwind CSS + Font Awesome, Alpine.js е през npm (Vite build в `web/public/build/`). Рутерът е същият `App\Core\Router`, маршрутите са в `web/routes/web.php`. Eloquent е включен, за да ползва същите модели като API-то, когато има каталог.
- **API** — `api/`. MVC + services, отговорите са JSON. Рутерът е в `api/app/Core/Router.php`, маршрутите в `api/routes/api.php`. Готов endpoint за проверка: `GET /health`.
- **Админ панел** — `admin/`. Vite + React + Redux Toolkit. Има маршрути за вход и забравена парола (без форми още) и няма регистрация. API заявките минават през Vite proxy към JSON API.
- **Docker** — `docker-compose.yml` в корена. Оттам се пускат всички услуги в една мрежа: MySQL, phpMyAdmin, Mailpit (локални имейли), PHP-FPM, Nginx (планове + API + магазин), Vite за админа и Vite watch за стиловете на магазина (`store`).

Планирано разделение:

| Част | Технология | Директория | Състояние |
|---|---|---|---|
| Сайт с плановете | PHP | `plans/` | Готов за преглед |
| Магазин (клиенти) | PHP SSR, Tailwind, Alpine.js | `web/` | Начална конфигурация |
| API | PHP, MVC + services, JSON | `api/` | Начална структура |
| Админ панел | React / Redux | `admin/` | Вход и табло |
| База данни | MySQL 8.4 + phpMyAdmin + Phinx | `database/` | Работи през Docker |
| Имейли (локално) | Mailpit + Symfony Mailer | — | Работи през Docker |

## Docker

Изисква Docker Compose. Конфигурацията е в `.env` (шаблон: `.env.example`).

### Адреси

| Услуга | Адрес |
|---|---|
| Сайт с плановете | http://localhost:2000 |
| API | http://localhost:5000 |
| API health (Postman) | http://localhost:5000/health |
| Магазин (клиенти) | http://localhost:4000 |
| phpMyAdmin | http://localhost:8081 |
| Mailpit (имейли) | http://localhost:8026 |
| Админ панел | http://localhost:3000 |
| MySQL от хоста | `localhost:3307` |
| MySQL между контейнерите | хост `mysql`, порт `3306` |

MySQL към хоста е на **3307**, защото 3306 често вече е зает. Вътре в Docker мрежата портът остава 3306.

### Автопрезареждане при разработка

В `.env` е активирано `DEV_RELOAD_ENABLED=true`. При промяна на PHP шаблони, API код или файловете на сайта с плановете, отворените локални страници се презареждат автоматично. Админ панелът и клиентският магазин запазват Vite HMR за JavaScript, TypeScript, Vue и CSS промени. PHP OPcache е изключен само в Docker Compose development режима, затова API промените влизат в сила при следващата заявка без рестартиране на контейнера.

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

Забравена парола (само админ): `POST /auth/admin/password/forgot` и `POST /auth/admin/password/reset`. Линкът в писмото води към `ADMIN_PUBLIC_URL` (по подразбиране http://localhost:3000/reset-password).

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

### Продукти (схема)

Магазинът е за тениски и подобни артикули. Таблиците са в `database/migrations/20260828170600_create_product_tables.php`, моделите в `api/app/Models/Product*.php`.

- **products** — име, slug, базов SKU, описание, цена „от“, активност.
- **product_parameters** — информационни характеристики (материя, грамаж), не се купуват отделно.
- **product_options** / **product_option_values** — избор като размер и цвят (`hex_color` за мостри).
- **product_variants** — конкретна комбинация с SKU, цена и наличност.
- **product_variant_values** — връзка вариант ↔ опция (по една стойност на опция).
- **product_images** — едно предно (`role=front`) и неограничена галерия (`role=gallery`). Файлове в `api/public/uploads/products/{id}/`.
- **Персонализация** — `personalization_enabled` плюс етикет и описание върху продукта; допълнителни полета в `product_personalization_fields`. Ако няма полета, `Product::personalizationInputs()` връща едно поле от етикета и описанието на продукта.

Админ (Bearer, роля `admin`):

| Метод | Път | Действие |
|---|---|---|
| GET | `/admin/products` | Списък. Query: `q`, `status` (`all`/`active`/`inactive`/`deleted`), `page`, `per_page` (до 100) |
| POST | `/admin/products` | Нов продукт с параметри, опции, варианти и персонализация |
| GET | `/admin/products/{id}` | Детайли, включително изтрит продукт |
| PATCH / PUT | `/admin/products/{id}` | Редакция |
| DELETE | `/admin/products/{id}` | Меко изтриване. `?purge_images=1` трие и файловете на изображенията |
| POST | `/admin/products/{id}/restore` | Възстановяване |
| POST | `/admin/products/{id}/images/front` | Предно изображение (`multipart`: `image`, по желание `alt`). Заменя старото |
| POST | `/admin/products/{id}/images` | Допълнителни изображения (`image` или `images[]`) |
| PATCH | `/admin/products/{id}/images/{imageId}` | `alt`, `sort_order` |
| POST | `/admin/products/{id}/images/{imageId}/front` | Прави галерийно изображение предно (старото предно отива в галерията) |
| DELETE | `/admin/products/{id}/images/{imageId}` | Изтрива изображение и файла |

JPEG, PNG и WebP, до 8 MB. Файловете са в `api/public/uploads/products/{id}/` и се сервират като `/uploads/products/{id}/...`. Без `purge_images` мекото изтриване пази файловете, за да може продуктът да се възстанови със снимките.

Ако в PATCH се подадат `parameters`, `options`, `variants` или `personalization_fields` (дори празен масив), колекцията се заменя. Ако ключът липсва, съществуващите записи остават. `slug` се генерира от името, ако не е подаден.

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

**Seeds** (тестови данни): фабрики в `api/app/Database/Factories/`, seed файлове в `database/seeds/`.

Потребителите се пълнят през `UserFactory` (български имена, обща парола `password`, bulk insert) и `UserSeeder`. По подразбиране се създават около 80 записа с клиенти, админи, неактивни и изтрити. Броят се сменя с `USER_SEED_COUNT`. Повторно пускане изтрива само имейлите `@seed.borz33.local` и ги създава наново — реалният админ от `.env` (`admin@borz33.local`) не се пипа. Seed админите влизат с парола `password`.

Продуктите се пълнят през `ProductFactory` и `ProductSeeder` — тениски с размери, цветове, наличности и част с персонализация, **без изображения**. Повторно пускане трие само SKU `SEED-*`. Броят е `PRODUCT_SEED_COUNT` (по подразбиране 24).

```bash
./bin/phinx seed:run
./bin/phinx seed:run -s UserSeeder
./bin/phinx seed:run -s ProductSeeder
USER_SEED_COUNT=200 ./bin/phinx seed:run -s UserSeeder
PRODUCT_SEED_COUNT=40 ./bin/phinx seed:run -s ProductSeeder
```

Или през Composer в PHP контейнера:

```bash
docker compose exec php composer seed
docker compose exec php composer seed:users
docker compose exec php composer seed:products
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

Първото пускане билдва PHP образа и при нужда инсталира Composer зависимостите. След това магазинът е на `http://localhost:4000`, API-то на `http://localhost:5000`, администрацията на `http://localhost:3000`, сайтът с плановете на `:2000`, phpMyAdmin на `:8081`, а Mailpit на `:8026`.

Статус:

```bash
docker compose ps
```

Логове (всички или само избрани услуги — `nginx`, `php`, `mysql`):

```bash
docker compose logs -f
docker compose logs -f nginx php
```

### Публична development среда с Cloudflare Tunnel

Cloudflare Tunnel е допълнителен Compose profile. Обикновеното
`docker compose up -d` не го стартира и текущата локална среда остава
непроменена. Връзката е изходяща от `cloudflared` към Cloudflare — не
пренасочвайте и не отваряйте портове на рутера.

#### 1. Създаване на tunnel и domain routes

1. Добавете домейна си в Cloudflare и отворете **Zero Trust → Networks → Tunnels**.
2. Създайте remotely-managed tunnel, например `borz33-dev`, и копирайте connector token-а.
3. В **Routes → Published application** добавете:
   - `dev.example.com` → `http://nginx:8082`;
   - `api-dev.example.com` → `http://nginx:8080` (само ако API-то трябва да бъде достъпно отделно).
4. Cloudflare създава proxy DNS записите за тези hostnames и обслужва HTTPS сертификатите на edge-а. Origin връзката остава вътрешна в Docker мрежата.

Копирайте `.env.example` като `.env` и заменете примерните стойности:

```dotenv
CLOUDFLARE_TUNNEL_TOKEN=<connector-token-from-cloudflare>
CLOUDFLARE_WEB_HOST=dev.example.com
CLOUDFLARE_API_HOST=api-dev.example.com
STORE_VITE_PUBLIC_ORIGIN=https://dev.example.com
WEB_PUBLIC_URL=https://dev.example.com
CORS_ALLOWED_ORIGINS=http://localhost:3000,http://localhost:4000,https://dev.example.com
```

`CLOUDFLARE_TUNNEL_TOKEN` е секрет: не го commit-вайте. API CORS допуска само
точните origin-и от `CORS_ALLOWED_ORIGINS`; не използвайте `*` за development
tunnel с вход в акаунт. Vite модулите и HMR WebSocket-ът минават през същия
публичен HTTPS/WSS hostname, без отделен публичен порт.

#### 2. Стартиране

Цялата development среда плюс tunnel-а се стартира с една команда:

```bash
docker compose --profile public up -d --build
```

Проверка на връзката и логовете:

```bash
docker compose --profile public ps
docker compose logs -f cloudflared
```

Локалните адреси продължават да работят едновременно с публичните:

- `http://localhost:4000` — магазин;
- `http://localhost:5000` — API;
- `http://localhost:3000` — администрация.

#### 3. Ограничаване с Cloudflare Access

За development hostname е препоръчително да включите **Zero Trust → Access
controls → Applications → Self-hosted** и да добавите `dev.example.com` и,
ако се използва, `api-dev.example.com`. Създайте Allow policy само за
разрешените имейли, група или identity provider. Създайте Access правилата
преди да споделяте hostname-а — без тях всеки, който знае URL адреса, може да
достъпи development приложението.

При автоматизирани клиенти към защитеното API използвайте Cloudflare Access
service token вместо публична Bypass policy.

#### 4. Спиране

Спиране и премахване на публичната и локалната среда, без изтриване на MySQL
тома:

```bash
docker compose --profile public down
```

Само tunnel-ът може да бъде спрян с:

```bash
docker compose stop cloudflared
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

Колекцията ползва `baseUrl` = `http://localhost:5000`. Docker стекът трябва да е пуснат. Първата заявка за тест е **Health → Health check** (`GET /health`).

### React админ

Vite приложението е в `admin/` (React 19, Redux Toolkit, React Router). Стартира се с останалия стек:

```bash
docker compose up -d
```

#### Econt Demo / Production

Активната Econt среда се управлява от **Настройки → Econt** в администрацията.
Demo credentials и двата набора endpoints се подават през `.env`, а избраната
среда и криптираните Production credentials се пазят в `site_settings`.

Преди първо запазване на Production password генерирайте постоянен ключ:

```bash
openssl rand -base64 32
```

Запишете резултата само в `.env` като `APP_ENCRYPTION_KEY=base64:<резултат>` и
не сменяйте ключа след записване на credentials. Production операциите остават
блокирани, докато бутонът **Тествай връзката** не потвърди credentials. Паролата
не се връща от API и в администрацията се показва само маскиран placeholder.

Локалните конфигурационни тестове се пускат без заявка към Econt:

```bash
docker compose exec php php tests/econt_configuration_test.php
```

Адрес: http://localhost:3000. Браузърът говори само с Vite; `/auth` и `/health` се проксират към API (`http://nginx:8080` в Docker, или `http://127.0.0.1:5000` при `npm run dev` на хоста).

Има работещ вход, забравена парола, табло и пълно управление на потребителите. Регистрация в админ панела няма.

Локално без Docker:

```bash
cd admin
npm install
npm run dev
```

Портовете се сменят в `.env` (`PLANS_PORT`, `API_PORT`, `WEB_PORT`, `PHPMYADMIN_PORT`, `MAILPIT_UI_PORT`, `MYSQL_PORT`, `ADMIN_PORT`).
