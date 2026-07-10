# API-контракт «Фокус»

Base URL для локального запуска:

```text
http://localhost:8000/api
```

API принимает и возвращает JSON. Для всех запросов рекомендуется заголовок:

```http
Accept: application/json
```

Защищённые endpoint дополнительно требуют:

```http
Authorization: Bearer <token>
```

## Авторизация

### Вход

`POST /auth/login`

```json
{
  "email": "user@example.com",
  "password": "password"
}
```

Успешный ответ `200 OK`:

```json
{
  "token": "1|long-sanctum-token",
  "token_type": "Bearer",
  "user": {
    "id": 2,
    "name": "Алексей Смирнов",
    "email": "user@example.com",
    "role": "user",
    "created_at": "2026-07-10T12:00:00.000000Z"
  }
}
```

Неверные credentials возвращают `401`:

```json
{
  "message": "The provided credentials are incorrect."
}
```

Для login действует ограничение частоты: не более 10 попыток в минуту с одного источника.

### Текущий пользователь

`GET /user` — `200 OK` и объект пользователя без дополнительной обёртки.

### Выход

`POST /auth/logout` удаляет только токен текущего запроса.

```json
{
  "message": "Logged out successfully."
}
```

## Ресурс задачи

```json
{
  "id": 12,
  "user_id": 2,
  "title": "Созвон с командой",
  "description": "Обсудить прогресс, риски и план до конца недели.",
  "due_date": "2026-07-13",
  "status": "in_progress",
  "created_at": "2026-07-10T12:00:00.000000Z",
  "updated_at": "2026-07-10T12:00:00.000000Z",
  "user": {
    "id": 2,
    "name": "Алексей Смирнов",
    "email": "user@example.com"
  },
  "can": {
    "update": true,
    "delete": true
  }
}
```

`can` рассчитан backend-политикой для текущего пользователя. Обычный пользователь получает только собственные задачи; администратор видит все и может изменять любую.

## Список задач

`GET /tasks`

| Параметр | Тип | Допустимые значения | По умолчанию |
|---|---|---|---|
| `search` | string | до 255 символов | — |
| `status` | enum | `pending`, `in_progress`, `completed` | — |
| `sort` | enum | `due_date`, `status`, `created_at` | `created_at` |
| `direction` | enum | `asc`, `desc` | `desc` |
| `page` | integer | `>= 1` | `1` |
| `per_page` | integer | `1..100` | `15` |

При сортировке по статусу порядок `asc` семантический: `pending` → `in_progress` → `completed`. Задачи без дедлайна при сортировке по `due_date` всегда располагаются после задач с датой.

Пример:

```http
GET /api/tasks?status=in_progress&sort=due_date&direction=asc&page=1&per_page=10
```

Ответ использует стандартную пагинацию Laravel Resources:

```json
{
  "data": [
    {
      "id": 12,
      "user_id": 2,
      "title": "Созвон с командой",
      "description": "Обсудить прогресс, риски и план до конца недели.",
      "due_date": "2026-07-13",
      "status": "in_progress",
      "created_at": "2026-07-10T12:00:00.000000Z",
      "updated_at": "2026-07-10T12:00:00.000000Z",
      "user": {
        "id": 2,
        "name": "Алексей Смирнов",
        "email": "user@example.com"
      },
      "can": {
        "update": true,
        "delete": true
      }
    }
  ],
  "links": {
    "first": "http://localhost:8000/api/tasks?status=in_progress&sort=due_date&direction=asc&per_page=10&page=1",
    "last": "http://localhost:8000/api/tasks?status=in_progress&sort=due_date&direction=asc&per_page=10&page=1",
    "prev": null,
    "next": null
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 1,
    "path": "http://localhost:8000/api/tasks",
    "per_page": 10,
    "to": 1,
    "total": 1
  },
  "summary": {
    "total": 5,
    "pending": 3,
    "in_progress": 1,
    "completed": 1,
    "overdue": 0
  }
}
```

Фактический объект `meta` также содержит массив ссылок страниц, формируемый Laravel.
`summary` считается по всем задачам, доступным текущему пользователю, до применения фильтров и пагинации. Поэтому карточки статистики не меняются при переходе между страницами или выборе статуса.

## Создание задачи

`POST /tasks`

```json
{
  "title": "Подготовить заметки к релизу",
  "description": "Кратко описать изменения следующей версии.",
  "due_date": "2026-07-20",
  "status": "pending"
}
```

Правила:

- `title` — обязателен, строка от 3 до 255 символов;
- `description` — nullable string;
- `due_date` — nullable date в формате, распознаваемом Laravel; frontend отправляет `YYYY-MM-DD`;
- `status` — один из трёх статусов; при отсутствии используется `pending`.

Ответ `201 Created` содержит задачу в обёртке `data`.

## Получение задачи

`GET /tasks/{id}` — `200 OK`, ресурс в обёртке `data`.

Обычный пользователь не может получить чужую задачу и получает `403`. Администратор имеет доступ к любой задаче.

## Обновление задачи

`PUT /tasks/{id}` или `PATCH /tasks/{id}`.

Все поля опциональны для частичного обновления:

```json
{
  "status": "completed"
}
```

Для переданного `title` по-прежнему действуют ограничения 3–255 символов. Ответ `200 OK` содержит обновлённую задачу в `data`.

## Удаление задачи

`DELETE /tasks/{id}` — `204 No Content` без тела ответа.

Обновление и удаление разрешены владельцу задачи и администратору.

## Ошибки

| HTTP | Когда возвращается | Формат |
|---|---|---|
| `401` | Нет действующего Bearer token | `{"message":"Unauthenticated."}` |
| `403` | Политика запрещает действие | `{"message":"This action is unauthorized."}` |
| `404` | Ресурс или маршрут отсутствует | `{"message":"Resource not found."}` |
| `422` | Ошибка Form Request | `{"message":"...","errors":{"field":["..."]}}` |
| `429` | Превышен rate limit login | JSON с сообщением Laravel |
| `500` | Непредвиденная ошибка | `{"message":"Server error."}` при выключенном debug |

## cURL: полный сценарий

Получить токен:

```bash
curl --request POST 'http://localhost:8000/api/auth/login' \
  --header 'Accept: application/json' \
  --header 'Content-Type: application/json' \
  --data '{"email":"user@example.com","password":"password"}'
```

Скопировать значение `token` из ответа:

```bash
TOKEN='1|replace-with-token-from-login'
```

Получить задачи:

```bash
curl 'http://localhost:8000/api/tasks?sort=due_date&direction=asc' \
  --header 'Accept: application/json' \
  --header "Authorization: Bearer ${TOKEN}"
```

Создать задачу:

```bash
curl --request POST 'http://localhost:8000/api/tasks' \
  --header 'Accept: application/json' \
  --header 'Content-Type: application/json' \
  --header "Authorization: Bearer ${TOKEN}" \
  --data '{"title":"Проверить API-контракт","status":"pending","due_date":"2026-07-20"}'
```

Завершить сессию:

```bash
curl --request POST 'http://localhost:8000/api/auth/logout' \
  --header 'Accept: application/json' \
  --header "Authorization: Bearer ${TOKEN}"
```
