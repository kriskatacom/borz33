# Borz33 Project Instructions

## General workflow

All source-code changes must be made locally in:

~/projects/borz33

Do not edit production source files directly unless explicitly requested.

After changes are completed, deploy with:

deploy

If `.env.production` was changed locally, deploy with:

deploy --env

---

## Production server

SSH access:

ssh almalinux@79.98.111.188

Production project path:

/home/almalinux/borz33

SSH key authentication is already configured. Do not ask for or store the SSH password.

---

## Docker

Production uses Docker Compose.

Run Docker commands from:

/home/almalinux/borz33

Compose command:

docker compose --env-file .env.production -f docker-compose.production.yml

Example:

ssh almalinux@79.98.111.188 \
  "cd /home/almalinux/borz33 && \
   docker compose --env-file .env.production -f docker-compose.production.yml ps"

Main services include:

- nginx
- php
- mysql
- admin-build
- store-build

---

## Production domains

Storefront:

https://borz33.com

Admin:

https://borz33.com/admin/

API:

https://api.borz33.com

Do not use `bors33.com`.
The correct domain is `borz33.com`.

---

## Database

Production database runs inside the Docker MySQL service.

Do not expose MySQL port 3306 publicly.

Access the database only through SSH and Docker.

Before making database changes:

1. Inspect the current schema.
2. Prefer Phinx migrations for structural changes.
3. Never manually modify production schema if a migration should be created.
4. Never DROP databases or tables without explicit user approval.
5. Never TRUNCATE production tables without explicit user approval.
6. Never delete production user/order/product data without explicit approval.

Run migrations with:

ssh almalinux@79.98.111.188 \
  "cd /home/almalinux/borz33 && \
   docker compose --env-file .env.production -f docker-compose.production.yml \
   exec -T php ./vendor/bin/phinx migrate"

---

## Environment and secrets

Local production environment file:

.env.production

Production environment file:

/home/almalinux/borz33/.env.production

Never print the complete contents of `.env.production`.

Never expose:

- passwords
- API keys
- SMTP credentials
- database credentials
- secret keys
- access tokens

When checking an environment variable, prefer checking whether it exists rather than printing its value.

Example:

grep '^MAIL_DSN=' .env.production >/dev/null && echo "MAIL_DSN exists"

`.env.production` must not be committed to Git.

---

## Deployment

Normal deployment:

deploy

Deployment including `.env.production`:

deploy --env

The deploy script:

- uploads source files
- builds the admin frontend
- builds the storefront assets
- recreates PHP when needed
- runs Phinx migrations
- recreates Nginx
- verifies storefront and admin

If deployment fails, inspect the actual error before changing unrelated code.

Do not run destructive commands as part of deployment.

---

## Production debugging

It is allowed to inspect production using SSH.

Useful commands:

### Containers

ssh almalinux@79.98.111.188 \
  "cd /home/almalinux/borz33 && \
   docker compose --env-file .env.production -f docker-compose.production.yml ps"

### PHP logs

ssh almalinux@79.98.111.188 \
  "cd /home/almalinux/borz33 && \
   docker compose --env-file .env.production -f docker-compose.production.yml logs --tail=200 php"

### Nginx logs

ssh almalinux@79.98.111.188 \
  "cd /home/almalinux/borz33 && \
   docker compose --env-file .env.production -f docker-compose.production.yml logs --tail=200 nginx"

### MySQL logs

ssh almalinux@79.98.111.188 \
  "cd /home/almalinux/borz33 && \
   docker compose --env-file .env.production -f docker-compose.production.yml logs --tail=200 mysql"

Do not continuously follow logs unless needed.

---

## Safety rules

Production is a live environment.

Before any destructive or irreversible action, ask for explicit confirmation.

Explicit approval is required before:

- deleting production data
- dropping tables/databases
- resetting the database
- removing Docker volumes
- deleting uploaded files
- deleting user accounts
- changing firewall rules
- changing SSH access
- changing DNS
- replacing SSL certificates
- modifying production secrets

Read-only inspection does not require confirmation.

Non-destructive application fixes may be implemented locally and deployed normally.

---

## Git

Do not commit:

- `.env`
- `.env.production`
- credentials
- generated secrets
- SSH private keys

Before committing, check the diff for secrets.

Prefer small, focused commits.

---

## Application architecture

Backend:
PHP 8.4

Database:
MySQL 8.4

Migrations:
Phinx

Storefront:
PHP templates + Vite + Vue 3 + Alpine.js + Tailwind CSS

Admin:
React 19 + TypeScript + Vite

Reverse proxy:
Host Nginx -> Docker Nginx

Production routing:

borz33.com -> 127.0.0.1:8080

api.borz33.com -> 127.0.0.1:8081

---

## Preferred problem-solving workflow

When asked to fix a production issue:

1. Reproduce or inspect the issue.
2. Inspect relevant production logs if necessary.
3. Find the root cause.
4. Make the fix locally.
5. Run relevant tests/builds locally when practical.
6. Deploy.
7. Verify the affected production URL.
8. Report exactly what changed.

Avoid speculative changes.
Do not modify unrelated functionality.
