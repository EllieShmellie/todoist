# Todo API

Laravel 12 / PHP 8.2+ REST API with SQLite and Laravel Sanctum Bearer tokens.

## Local setup

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
php artisan serve
```

The API is available at `http://localhost:8000/api`.

Seeded accounts (both use password `password`):

- `admin@example.com` — sees and manages every task;
- `user@example.com` — sees and manages only owned tasks.

## Endpoints

```text
POST   /api/auth/login
POST   /api/auth/logout
GET    /api/user
GET    /api/tasks
POST   /api/tasks
GET    /api/tasks/{task}
PUT    /api/tasks/{task}
PATCH  /api/tasks/{task}
DELETE /api/tasks/{task}
```

Except for login, endpoints require `Authorization: Bearer <token>`.

Task list parameters are `search`, `status`, `sort`, `direction`, `page`, and
`per_page`. Allowed status values are `pending`, `in_progress`, and
`completed`; allowed sort fields are `due_date`, `status`, and `created_at`.
The response contains Laravel pagination metadata plus an unfiltered `summary`
for all tasks visible to the authenticated user.

## Quality checks

```bash
composer test
vendor/bin/pint --test
```
