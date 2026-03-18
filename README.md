# YukiMart Backend

> Laravel 12 + L5-Swagger REST API

## Tech Stack

- **Framework**: Laravel 12
- **Language**: PHP 8.2
- **Database**: MySQL 8.0
- **API Docs**: L5-Swagger (OpenAPI 3.0)
- **Authentication**: Laravel Sanctum

## Getting Started

```bash
# Install dependencies
composer install

# Configure environment
cp .env.example .env
php artisan key:generate

# Run migrations
php artisan migrate

# Seed database
php artisan db:seed

# Start development server
php artisan serve

# Generate API documentation
php artisan l5-swagger:generate
```

## Project Structure

```
app/
├── Http/
│   ├── Controllers/    # API Controllers
│   ├── Middleware/      # Request middleware
│   ├── Requests/        # Form validation
│   └── Resources/       # API response transformers
├── Models/             # Eloquent models
├── Services/           # Business logic layer
├── Repositories/       # Data access layer
└── Exceptions/         # Custom exception handlers

database/
├── migrations/         # Database schema
├── seeders/            # Test data
└── factories/          # Model factories

routes/
├── api.php             # API routes
└── web.php             # Web routes
```

## API Documentation

Swagger UI available at: `https://yukimart.io.vn/docs`

## Git Branching Strategy (Gitflow)

| Branch | Purpose | Deploy |
|--------|---------|--------|
| `prod` | Production-ready code | Auto deploy to Production |
| `staging` | QA testing and demo | Auto deploy to Staging |
| `dev` | Active development | Auto deploy to Dev |
| `feat/<name>` | New feature development | CI only |
| `hotfix/<name>` | Emergency production fix | Direct to prod |

### Workflow

```
feat/xxx  ──PR──>  dev  ──merge──>  staging  ──merge──>  prod
                    │                  │                    │
              Deploy DEV         Deploy STAGING      Deploy PRODUCTION
```

1. Create feature branch from `dev`: `git checkout -b feat/feature_name`
2. Develop and commit following conventions
3. Push and create Pull Request to `dev`
4. CI runs automatically (lint, test, security scan)
5. Code review and merge
6. Auto deploy to DEV environment
7. When stable, merge `dev` → `staging` for QA
8. After QA approval, merge `staging` → `prod` for production release

### Hotfix Process

1. Branch from `prod`: `git checkout -b hotfix/fix_name`
2. Fix and push
3. PR to `prod` → review → merge → auto deploy
4. Merge back: `prod` → `staging` → `dev`

## CI/CD Pipeline

### CI Pipeline (every push/PR)

```
Code Quality (PSR-12) ──> Run Tests (MySQL) ──> CI Summary
Security Audit ───────>   API Docs Generation ─>
```

### Deploy Pipeline

| Environment | Stages |
|-------------|--------|
| **DEV** | Deploy → Verify API Health → Report |
| **STAGING** | Security Audit → Deploy → Verify → Report |
| **PRODUCTION** | Quality Gate → Security Audit → Deploy → Smoke Test (API + Swagger) → Release Report |

## Commit Convention

```
<type>(scope): description
```

| Type | Description |
|------|-------------|
| `feat` | New feature |
| `fix` | Bug fix |
| `refactor` | Code restructuring |
| `docs` | Documentation changes |
| `chore` | Maintenance tasks |
| `style` | UI/CSS changes |
| `perf` | Performance improvement |
| `vendor` | Dependency updates |

### Examples

```bash
feat(product): add product search API
fix(order): resolve payment calculation error
refactor(auth): restructure middleware layer
docs(api): update Swagger annotations
```

## Environment Variables

Key variables in `.env`:

| Variable | Description |
|----------|-------------|
| `DB_CONNECTION` | Database driver (mysql) |
| `DB_HOST` | Database host |
| `DB_DATABASE` | Database name |
| `APP_URL` | Application URL |
| `L5_SWAGGER_GENERATE_ALWAYS` | Auto-generate Swagger docs |

## Deployment

Deployment is automated via GitHub Actions webhook.

- **Server**: Managed via aaPanel
- **Runtime**: PHP 8.2 + Composer
- **Database**: MySQL 8.0
- **Web Server**: Nginx (reverse proxy to Laravel)
- **SSL**: Cloudflare + Origin Certificate
- **Domain**: [yukimart.io.vn](https://yukimart.io.vn)

## License

Private - All rights reserved.
