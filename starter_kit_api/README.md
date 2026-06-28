# API Based Project Starter Kit (Advanced)

A Laravel 13 API-based starter kit running on PHP 8.4, PostgreSQL 15, and Nginx — fully containerized with Docker.

## Tech Stack

- PHP 8.4 (FPM)
- Laravel 13
- PostgreSQL 15
- Nginx (Alpine)
- Laravel Sanctum (token-based auth)
- Laravel Boost

## Prerequisites

- Docker
- Docker Compose
- Git

You do **not** need PHP, Composer, or PostgreSQL installed on your host — everything runs inside containers.

---

## Step-by-Step: Running the Project Locally with Docker

### 1. Clone the repository

```bash
git clone <repository-url>
cd api-based-project-start-ki-advanced
```

### 2. Create the `.env` file

Copy the example environment file:

```bash
cp .env.example .env
```

### 3. Set user/group variables in `.env`

The Dockerfile builds the PHP container using your host UID/GID so file permissions stay correct. Append these to your `.env` (replace values with the output of `id -u`, `id -g`, `whoami`):

```bash
echo "USER_ID=$(id -u)" >> .env
echo "GROUP_ID=$(id -g)" >> .env
echo "USER_NAME=$(whoami)" >> .env
echo "GROUP_NAME=$(id -gn)" >> .env
```

### 4. Create the external shared Docker network

The `docker-compose.yml` references an external network named `api_starter_shared_network`. Create it once:

```bash
docker network create api_starter_shared_network
```

### 5. Build and start the containers

```bash
docker compose up -d --build
```

This brings up three containers:

| Container | Purpose | Port |
|---|---|---|
| `api_starter_laravel_nginx` | Web server | `8080` (host) → `80` |
| `api_starter_laravel_php` | PHP-FPM app | `9000` (internal) |
| `api_starter_laravel_postgres` | PostgreSQL 15 | `5432` (host) → `5432` |

### 6. Install Composer dependencies (if needed)

Dependencies are installed during the image build. If you need to re-install or update them:

```bash
docker exec api_starter_laravel_php sh -lc 'cd /var/www/html && composer install'
```

### 7. Generate the application key (if not already set)

```bash
docker exec api_starter_laravel_php sh -lc 'cd /var/www/html && php artisan key:generate'
```

### 8. Run database migrations

```bash
docker exec api_starter_laravel_php sh -lc 'cd /var/www/html && php artisan migrate'
```

To wipe the database and re-run all migrations from scratch:

```bash
docker exec api_starter_laravel_php sh -lc 'cd /var/www/html && php artisan migrate:fresh'
```

### 9. Seed the database

Run all seeders defined in `DatabaseSeeder`:

```bash
docker exec api_starter_laravel_php sh -lc 'cd /var/www/html && php artisan db:seed'
```

Or migrate and seed in one step:

```bash
docker exec api_starter_laravel_php sh -lc 'cd /var/www/html && php artisan migrate:fresh --seed'
```

Available seeders include: `PermissionSeeder`, `RoleSeeder`, `CountrySeeder`, `AdminLevelSeeder`, `AdminAreaSeeder`, `CountryAdminStructureSeeder`, `LocationDataSeeder`.

To run a specific seeder:

```bash
docker exec api_starter_laravel_php sh -lc 'cd /var/www/html && php artisan db:seed --class=PermissionSeeder'
```

### 10. Access the application

The API is now available at:

```
http://localhost:8080
```

(or whatever you set `APP_EXTERNAL_PORT` to in `.env`).

---

## Common Commands

All Artisan / Composer / Pint commands must run inside the PHP container.

**Open a shell in the PHP container:**

```bash
docker exec -it api_starter_laravel_php sh
cd /var/www/html
```

**Run tests:**

```bash
docker exec api_starter_laravel_php sh -lc 'cd /var/www/html && php artisan test --compact'
```

**Format code with Pint:**

```bash
docker exec api_starter_laravel_php sh -lc 'cd /var/www/html && vendor/bin/pint --dirty --format agent'
```

**Tail Laravel logs:**

```bash
docker exec api_starter_laravel_php sh -lc 'cd /var/www/html && php artisan pail'
```

**List routes:**

```bash
docker exec api_starter_laravel_php sh -lc 'cd /var/www/html && php artisan route:list'
```

**Clear caches:**

```bash
docker exec api_starter_laravel_php sh -lc 'cd /var/www/html && php artisan optimize:clear'
```

---

## Stopping / Restarting

**Stop containers (keep data):**

```bash
docker compose down
```

**Stop and remove volumes (destroys database):**

```bash
docker compose down -v
```

**Restart containers:**

```bash
docker compose restart
```

**View logs:**

```bash
docker compose logs -f
```

---

## Troubleshooting

- **Port `8080` already in use** — change `APP_EXTERNAL_PORT` in `.env` to a free port and run `docker compose up -d`.
- **Port `5432` already in use** — change `DB_EXTERNAL_PORT` in `.env`. The internal `DB_PORT` should stay `5432`.
- **Permission errors on `storage/` or `bootstrap/cache/`** — make sure `USER_ID`, `GROUP_ID`, `USER_NAME`, `GROUP_NAME` in `.env` match your host user, then rebuild: `docker compose up -d --build`.
- **`network api_starter_shared_network not found`** — run `docker network create api_starter_shared_network`.
- **Database connection refused** — wait a few seconds after `docker compose up` for PostgreSQL to finish initializing, then re-run the migrate command.
