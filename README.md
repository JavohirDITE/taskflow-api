# TaskFlow API

<p align="center">
  <strong>Enterprise-grade Team Task Management REST API built with Laravel 11 + PostgreSQL</strong>
</p>

<p align="center">
  <img src="https://img.shields.io/badge/PHP-8.3-blue?style=for-the-badge&logo=php" alt="PHP 8.3">
  <img src="https://img.shields.io/badge/Laravel-11-red?style=for-the-badge&logo=laravel" alt="Laravel 11">
  <img src="https://img.shields.io/badge/PostgreSQL-16-blue?style=for-the-badge&logo=postgresql" alt="PostgreSQL">
  <img src="https://img.shields.io/badge/Redis-7-red?style=for-the-badge&logo=redis" alt="Redis">
  <img src="https://img.shields.io/badge/Docker-ready-blue?style=for-the-badge&logo=docker" alt="Docker">
  <img src="https://img.shields.io/github/actions/workflow/status/yourusername/taskflow-api/ci.yml?style=for-the-badge&label=CI" alt="CI">
</p>

---

## Overview

TaskFlow is a production-ready REST API for team task management — think of it as a lightweight Jira clone backend. It demonstrates modern PHP/Laravel architecture patterns used in real commercial projects.

## Key Features

- 🔐 **Token-based Auth** — Laravel Sanctum with expiring tokens
- 👥 **RBAC** — Role-based access: Owner / Admin / Member / Viewer
- 📋 **Task Management** — Full CRUD with status, priority, assignee, and due dates
- 📎 **File Attachments** — Secure upload with MIME validation and UUID filenames
- 💬 **Comments** — Nested threaded comments on tasks
- 📊 **Audit Logging** — Automatic tracking of all task changes via Model Observer
- 📨 **Queue Jobs** — Email notifications dispatched asynchronously via Redis
- 📚 **OpenAPI 3.0** — Full Swagger documentation at `/api/documentation`
- 🧪 **Pest Tests** — Feature + Unit tests with >80% coverage
- 🐳 **Docker** — One-command local setup

## Architecture

```
Repository Pattern    — Decoupled data access layer (Interface → Implementation)
Service Layer         — Business logic isolated from HTTP controllers
Form Requests         — Input validation with security-first allowlists
API Resources         — Controlled response transformation (no sensitive data leaks)
Model Observers       — Automatic audit logging via Eloquent events
Queue Workers         — Redis-backed async job processing
```

## Tech Stack

| Layer | Technology |
|-------|-----------|
| Language | PHP 8.3 |
| Framework | Laravel 11 |
| Database | PostgreSQL 16 |
| Cache & Queue | Redis 7 |
| Auth | Laravel Sanctum |
| Testing | Pest PHP |
| Docs | l5-swagger (OpenAPI 3.0) |
| Container | Docker + Docker Compose |
| CI/CD | GitHub Actions |

## Getting Started

### Prerequisites

- Docker Desktop
- Git

### Installation

```bash
# 1. Clone the repository
git clone https://github.com/yourusername/taskflow-api.git
cd taskflow-api

# 2. Copy environment file
cp .env.example .env

# 3. Start all services and run setup
make setup
```

The API will be available at: **http://localhost:8000**  
Swagger docs: **http://localhost:8000/api/documentation**

### Available Commands

```bash
make up          # Start containers
make down        # Stop containers
make test        # Run all tests with coverage
make migrate     # Run database migrations
make seed        # Seed demo data
make shell       # Enter PHP container
make logs        # Follow application logs
make lint        # Fix code style
make swagger     # Regenerate OpenAPI docs
```

## API Endpoints

### Authentication
| Method | Endpoint | Description |
|--------|----------|-------------|
| `POST` | `/api/v1/auth/register` | Register new user |
| `POST` | `/api/v1/auth/login` | Login, receive token |
| `POST` | `/api/v1/auth/logout` | Revoke current token |
| `GET`  | `/api/v1/auth/me` | Get current user |
| `POST` | `/api/v1/auth/change-password` | Change password |

### Teams
| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET`  | `/api/v1/teams` | List user's teams |
| `POST` | `/api/v1/teams` | Create team |
| `GET`  | `/api/v1/teams/{id}` | Team details |
| `PUT`  | `/api/v1/teams/{id}` | Update team |
| `POST` | `/api/v1/teams/{id}/members` | Invite member |
| `DELETE` | `/api/v1/teams/{id}/members/{userId}` | Remove member |

### Tasks
| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET`  | `/api/v1/projects/{id}/tasks` | List tasks (with filters) |
| `POST` | `/api/v1/projects/{id}/tasks` | Create task |
| `GET`  | `/api/v1/tasks/{id}` | Task details with audit log |
| `PUT`  | `/api/v1/tasks/{id}` | Update task |
| `PATCH`| `/api/v1/tasks/{id}/status` | Update status only |
| `DELETE` | `/api/v1/tasks/{id}` | Soft delete task |

### Task Filters
```
GET /api/v1/projects/1/tasks?status=todo&priority=high&assignee_id=2&overdue=true&sort=due_date&direction=asc
```

## Database Schema

```
users
  └── team_user (pivot: role)
        └── teams
              └── projects
                    └── tasks
                          ├── comments
                          ├── attachments
                          └── audit_logs
```

## Security Highlights

- ✅ Parameterized queries via Eloquent ORM (SQL Injection prevention)
- ✅ Input validation allowlists on all endpoints (Form Requests)
- ✅ File uploads: MIME-type validation, UUID filenames, storage outside web root
- ✅ Sort field allowlist in Repository (no user-controlled ORDER BY)
- ✅ Password policy: min 8 chars, mixed case, numbers, HaveIBeenPwned check
- ✅ Token expiration configured via environment variable
- ✅ Secrets loaded from environment, never hardcoded
- ✅ Security headers via Nginx config (CSP, X-Frame-Options, etc.)
- ✅ CORS policy configured per environment
- ✅ Rate limiting on all API routes (stricter on auth endpoints)

## Testing

```bash
# Run all tests
make test

# Feature tests only
make test-feature

# Unit tests only
make test-unit
```

Test coverage: **>80%** across Feature and Unit suites.

## Project Structure

```
app/
├── Enums/           # TaskStatus, Priority, TeamRole
├── Http/
│   ├── Controllers/ # Thin controllers, delegate to Services
│   ├── Requests/    # Input validation (Form Requests)
│   └── Resources/   # API response transformation
├── Interfaces/      # Repository contracts (DI bindings)
├── Models/          # Eloquent models with scopes
├── Observers/       # TaskObserver — automatic audit logging
├── Repositories/    # Data access layer
├── Services/        # Business logic (TaskService, AuthService)
└── Jobs/            # Queue jobs (email notifications)
```

## License

MIT License — see [LICENSE](LICENSE)

---

<p align="center">Built with ❤️ using Laravel 11 · PHP 8.3 · PostgreSQL 16</p>
