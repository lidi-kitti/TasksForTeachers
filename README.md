# Платформа для размещения заданий преподавателей

Мини-приложение на **Symfony 6.4**, позволяющее преподавателям создавать и управлять своими заданиями, а также просматривать задания коллег.

## Стек технологий

- PHP 8.1+ (в ТЗ: 8.2+)
- Symfony 6.4
- Doctrine ORM + Migrations
- PostgreSQL (поддерживается MySQL при пересоздании миграций)
- Twig + Bootstrap 5

## Реализованный функционал

### Регистрация и аутентификация

- Регистрация преподавателя: email, пароль, имя (`/register`)
- Вход по email и паролю (`/login`)
- Неавторизованные пользователи не имеют доступа к заданиям — редирект на `/login`
- Пароли хранятся в хешированном виде (`UserPasswordHasher`)

### Управление заданиями

Сущность `Task` содержит поля:

| Поле | Тип | Описание |
|------|-----|----------|
| `title` | string | Название |
| `description` | text | Описание |
| `dueDate` | date | Срок выполнения |
| `maxScore` | int | Максимальный балл |
| `status` | enum | `active` / `completed` (Активно / Завершено) |
| `author` | User | Автор задания |

Преподаватель может:

- создать задание (`/tasks/new`) — все поля обязательны, валидация через Symfony Validation;
- просмотреть список **всех** заданий всех преподавателей (`/tasks`) с пагинацией (10 на страницу);
- открыть карточку любого задания (`/tasks/{id}`);
- редактировать только свои задания (`/tasks/{id}/edit`);
- удалить только свои задания (`/tasks/{id}/delete`, POST + CSRF).

### Права доступа

- Список заданий доступен всем авторизованным преподавателям, отображается автор.
- Попытка редактировать или удалить чужое задание → **HTTP 403 Forbidden** (`AccessDeniedException`).

### Дополнительно

- **Пагинация** — `KnpPaginatorBundle`
- **Messenger** — flash-уведомление при редактировании задания
- **Фикстуры** — демо-пользователи и 5 заданий
- **PHPUnit-тесты** — создание задания, проверка прав доступа, запрет гостям

## Архитектура

```
Браузер → Controller → Service → Repository → Entity → Twig
```

| Слой | Назначение |
|------|------------|
| **Controller** | Принимает HTTP-запрос, вызывает сервис, возвращает Response |
| **Service** | Бизнес-логика: CRUD, проверка владельца (`TaskService`, `UserRegistrationService`) |
| **Repository** | Запросы к БД через QueryBuilder |
| **Entity** | `User`, `Task`, `TaskStatus` (связь User 1 → N Task) |
| **Twig** | Шаблоны списка, формы, карточки задания |

Маршруты заданы PHP-атрибутами `#[Route]`.

## Требования

- PHP 8.1+ с расширениями `ctype`, `iconv`, `pdo_pgsql` (или `pdo_mysql`)
- Composer 2.x
- PostgreSQL 14+ (или MySQL 8+)

## Установка и запуск

### 1. Зависимости

```bash
git clone <url-репозитория> TasksForTeachers
cd TasksForTeachers
composer install
```

### 2. Окружение

Скопируйте шаблон и укажите свои параметры:

```bash
cp .env.local.example .env
```

В `.env` замените `!ChangeMe!` на пароль PostgreSQL и `APP_SECRET` на случайную строку (32+ символа).

> Файл `.env` **не коммитится** в git (содержит пароли).

### 3. База данных и миграции

```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate --no-interaction
```

Миграции в репозитории написаны под PostgreSQL. Для MySQL пересоздайте миграцию после настройки `DATABASE_URL`.

### 4. Демо-данные (фикстуры)

```bash
php bin/console doctrine:fixtures:load --no-interaction
```

### 5. Запуск сервера

```bash
php -S localhost:8002 -t public
```

Откройте http://localhost:8002 — после входа откроется список заданий `/tasks`.

## Тестовые пользователи

После `doctrine:fixtures:load`:

| Email | Пароль | Имя |
|-------|--------|-----|
| `teacher@example.com` | `pass123` | Анна Преподаватель |
| `colleague@example.com` | `pass123` | Пётр Коллега |

## Маршруты

| URL | Имя | Описание |
|-----|-----|----------|
| `/` | `app_home` | Редирект на `/tasks` или `/login` |
| `/register` | `app_register` | Регистрация |
| `/login` | `app_login` | Вход |
| `/logout` | `app_logout` | Выход |
| `/tasks` | `app_task_index` | Список заданий |
| `/tasks/new` | `app_task_new` | Создание |
| `/tasks/{id}` | `app_task_show` | Просмотр |
| `/tasks/{id}/edit` | `app_task_edit` | Редактирование (только автор) |
| `/tasks/{id}/delete` | `app_task_delete` | Удаление POST (только автор) |

## Безопасность

- Хеширование паролей — `UserPasswordHasher`
- CSRF — формы входа, регистрации, удаления задания
- Валидация — `NotBlank`, `Email`, `Positive`, `Length` на сущностях и формах
- SQL-инъекции — параметризованные запросы Doctrine ORM
- Контроль доступа — `security.yaml` + `TaskService::assertOwner()`

## Тесты

### Подготовка (один раз)

```bash
cp .env.test.local.example .env.test.local
# укажите те же credentials, что в .env

php bin/console doctrine:database:create --env=test --if-not-exists
php bin/console doctrine:migrations:migrate --env=test --no-interaction
```

Фикстуры для веб-тестов загружаются автоматически перед запуском `TaskControllerTest`.

### Запуск

```bash
php bin/phpunit
```

Покрытие:

- гость не видит `/tasks`;
- преподаватель создаёт задание;
- редактирование чужого задания → 403;
- удаление своего задания;
- `TaskService::assertOwner()` для чужого пользователя.

## Структура проекта

```
src/
├── Controller/         # Home, Security, Registration, Task
├── Entity/               # User, Task, TaskStatus
├── Form/                 # RegistrationFormType, TaskType
├── Repository/           # UserRepository, TaskRepository
├── Service/              # TaskService, UserRegistrationService
├── Exception/            # AccessDeniedException
├── Message/              # TaskUpdatedNotification
├── MessageHandler/       # TaskUpdatedNotificationHandler
└── DataFixtures/         # AppFixtures
templates/
├── task/                 # index, show, new, edit, _form
└── security/             # login, register
migrations/               # Version20260608072110 (user), Version20260608142506 (task)
tests/                    # TaskControllerTest, TaskServiceTest
config/packages/          # doctrine, security, messenger, twig
```

## Полезные команды

```bash
php bin/console about                  # информация о проекте
php bin/console debug:router           # список маршрутов
php bin/console doctrine:schema:validate
php bin/console cache:clear
php bin/console doctrine:fixtures:load # демо-данные
php bin/phpunit                        # тесты
```

## Документация Symfony

https://symfony.com/doc/current/index.html
