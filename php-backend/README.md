# PHP Backend Design: Authentication + Authorization

A clean, logical backend starter in plain PHP (no heavy framework), focused on secure auth flows and role-based access control.

## Features

- Register and login with hashed passwords
- JWT access token authentication
- Refresh token rotation (stored as SHA-256 hashes in DB)
- Role-based authorization (`user`, `manager`, `admin`)
- Protected routes with middleware
- SQLite default setup for fast local start
- Full relational database blueprint (identity, RBAC, security, audit, org/project/task)
- Attractive API home page at `/` for clean presentation
- Standardized JSON response envelope (`success`, `message`, `data/errors`, `meta`)

## Project Structure

```text
php-backend/
  public/index.php
  src/
    Auth/
    Controllers/
    Core/
    Middleware/
    Repositories/
    Config/
  database/schema.sql
  scripts/migrate.php
  .env.example
```

## Quick Start

1. Copy env file:
   - Windows PowerShell: `Copy-Item .env.example .env`
2. Set a strong `JWT_SECRET` in `.env`
3. Run migration:
   - `php scripts/migrate.php`
4. Start server:
   - `php -S localhost:8000 -t public`

## Default Admin

- Email: `admin@example.com`
- Password: `Admin@12345`
- Change this immediately after first login.

## API Endpoints

- `POST /api/auth/register`
- `POST /api/auth/login`
- `POST /api/auth/refresh`
- `GET /api` (JSON API overview)
- `GET /api/me` (requires bearer token)
- `GET /api/admin/dashboard` (requires `admin` role)

## Full Database Design

`database/schema.sql` now includes a complete backend-ready relational design:

- Identity: `users`, `user_profiles`
- RBAC: `roles`, `permissions`, `role_permissions`, `user_roles`
- Security: `refresh_tokens`, `password_resets`, `email_verifications`, `mfa_factors`, `mfa_challenges`, `api_keys`
- Operations: `login_attempts`, `audit_logs`, `notifications`
- Business core: `organizations`, `organization_members`, `projects`, `project_members`, `tasks`
- Seed data: default roles, permissions mapping, and admin user

This design is normalized, indexed for common lookups, and ready to scale from auth-only APIs to multi-tenant business workflows.

## Example Requests

Register:

```bash
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d "{\"name\":\"John\",\"email\":\"john@example.com\",\"password\":\"StrongPass123\"}"
```

Login:

```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d "{\"email\":\"admin@example.com\",\"password\":\"Admin@12345\"}"
```

Profile:

```bash
curl http://localhost:8000/api/me -H "Authorization: Bearer YOUR_ACCESS_TOKEN"
```
