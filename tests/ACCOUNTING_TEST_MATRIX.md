# Автоматизирани тестове за „Счетоводство"

Тестовете са създадени, но не са стартирани автоматично.

## Покритие

- `tests/accounting_full_test.php` — integration тестове за статистики, знаци, ДДС, периоди, кредитни известия, повторно генериране, филтри, всички справки, CSV/XLSX, празни и смесени ZIP пакети, Econt защита и rollback.
- `tests/accounting_exports_test.php` — минимален smoke тест за PDF/ZIP export.
- `tests/e2e/specs/accounting.spec.ts` — E2E проверки за филтри, справки, празни резултати, условни ДДС/Econt секции, приключване, audit log и API заявки.

## Ръчно изпълнение

```bash
docker compose exec -T php php tests/accounting_full_test.php
docker compose exec -T php php tests/accounting_exports_test.php
```

Production-like конфигурационна проверка без стартиране на услугите:

```bash
docker compose --env-file .env.production -f docker-compose.production.yml config --quiet
```

E2E тестове:

```bash
cd ~/projects/borz33
npm install
npx playwright install chromium
set -a; source .env; set +a
cd tests/e2e
ADMIN_STORAGE_STATE=/home/kristian/projects/borz33/tests/e2e/.auth/admin.json npm test
```

`ADMIN_STORAGE_STATE` трябва да сочи към съществуващ JSON файл с предварително създадена authenticated Playwright сесия. Не използвайте буквално `/реалния/път/...`. Алтернативно може да се подаде временен `ADMIN_AUTH_TOKEN`. Не записвайте токени и пароли в Git.

Тестовете от `tests/` използват rollback и почистват генерираните архиви. Част от проверките умишлено ще се провалят, докато съответният проблем от `Problems.md` не бъде коригиран.
