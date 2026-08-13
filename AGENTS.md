# Job Candys - AI Agent Instructions

## Project Overview

**Job Candys** is a modern recruitment management platform built with **Symfony 8.1** and **PHP 8.4**. It enables companies to manage recruitment workflows, collect applications, verify contacts and websites, and manage user profiles through a comprehensive web application.

**Key purpose:** Recruitment workflow automation with contact verification, website detection, and candidate application management.

## Technical Stack

- **PHP:** 8.4
- **Framework:** Symfony 8.1
- **Database:** MySQL 8.0 (Doctrine ORM + Migrations)
- **Frontend:** Twig templates, Stimulus JS, Asset Mapper, UX Turbo
- **External APIs:** Hunter (email verification), INSEE (French business data)
- **Email:** Symfony Mailer with MailHog (local) / SMTP (production)
- **Testing:** PHPUnit
- **Infrastructure:** Docker Compose (PHP, MySQL, phpMyAdmin, MailHog)
- **Security:** Symfony Security with custom authenticator, password hashing

## Project Structure

```
app/
├── src/
│   ├── Controller/          # HTTP request handlers (routing via #[Route])
│   ├── Entity/             # Doctrine ORM entities
│   ├── Service/            # Business logic layer
│   ├── Repository/         # Doctrine query layer
│   ├── Form/               # Symfony form types
│   ├── Security/           # Security authenticators & logic
│   ├── Kernel.php          # Application kernel
│
├── config/
│   ├── packages/           # Service configuration (doctrine, mailer, etc.)
│   ├── services.yaml       # Service definitions & DI parameters
│   ├── routes.yaml         # Route imports
│   ├── bundles.php         # Bundle configuration
│
├── templates/              # Twig templates (organized by feature)
├── public/                 # Webroot (index.php)
├── tests/                  # PHPUnit tests
├── migrations/             # Doctrine migrations
├── var/                    # Cache, logs
├── vendor/                 # Composer dependencies
```

## Entity Naming Convention

**⚠️ Important:** Entities use **plural names** (non-standard):
- `Users` (user entity)
- `Profils` (user profiles)
- `Companies` (company data)
- `CompanyContacts` (company contacts)
- `Applications` (job applications)
- `Notifications`

This differs from Symfony conventions; always refer to actual entity class names in code.

## Service Layer (Key Services)

| Service | Purpose |
|---------|---------|
| `WebsiteFinderService` | Detects company websites by domain name variants |
| `EmailService` | Sends templated emails with attachments (uses MailerInterface) |
| `UserCreatorService` | Creates users with unique Job-Candys sender email addresses |
| `HunterEmailVerify` | Verifies email validity via Hunter API |
| `ImapService` | IMAP email integration for inbox reading |
| `TechnologyDetectorService` | Detects tech stack from website HTML |
| `InseeApiService` | Queries French INSEE business registry API |
| `FileUploader` | Manages file uploads (profiles, temp) with validation |
| `WebsiteContactFinderService` | Extracts contact info from websites |

**Pattern:** All services use constructor dependency injection with autowiring (`_defaults: autowire: true`).

## Controllers Overview

| Controller | Routes | Purpose |
|------------|--------|---------|
| `MainController` | `/`, `/about`, `/contact` | Public pages |
| `RegistrationController` | `/register` | User registration with email verification |
| `SecurityController` | `/login`, `/logout` | Authentication |
| `ResetPasswordController` | Reset password workflow | Password recovery |
| `FindContactController` | Contact search form | Search company contacts |
| `FindWebController` | Website finder | Detect company websites |
| `FreelanceApplicationController` | Freelance applications | Handle freelance submissions |
| `SpontaneousApplicationController` | Spontaneous applications | Handle unsolicited applications |
| `AdminController` | Admin routes | Backend management |
| `ProfilesController` | Profile routes | User profiles |

Controllers use `#[Route]` PHP attributes for routing and return Twig-rendered responses.

## Services Layer Improvements (Aug 2026)

### WebsiteFinderService & FindWebController

**Refactored from curl to modern Symfony patterns:**

- **Dependency Injection**: Constructor injection of `HttpClientInterface` and `LoggerInterface`
- **HTTP Client**: Replaced `curl_init()` with `Symfony\Contracts\HttpClient\HttpClientInterface`
- **Logging**: Structured PSR-3 logging with context (INFO, DEBUG, WARNING levels)
- **Validation**: Input validation with max length checks
- **Error Handling**: Comprehensive try/catch with graceful error messages
- **Route Testing**: Independent test controller at `/test/website-finder/{company}`

**Key Files**:
- [app/src/Service/WebsiteFinderService.php](app/src/Service/WebsiteFinderService.php) - Core service logic
- [app/src/Controller/FindWebController.php](app/src/Controller/FindWebController.php) - Main route handler
- [app/src/Controller/TestWebsiteFinderController.php](app/src/Controller/TestWebsiteFinderController.php) - Test endpoint

### WebsiteContactFinderService & FindContactController

**Refactored to match WebsiteFinderService patterns:**

- **HTTP Client**: Migrated from `curl_init()` to `HttpClientInterface`
- **Logging**: Full PSR-3 logging with structured context (INFO, DEBUG, WARNING, ERROR)
- **Validation**: URL format validation using `filter_var()`
- **Error Handling**: Try/catch at global and per-page level
- **Architecture**: Symmetric with WebsiteFinderService for consistency
- **Testing**: Independent test controller at `/test/contact-finder?website=URL`

**Key Files**:
- [app/src/Service/WebsiteContactFinderService.php](app/src/Service/WebsiteContactFinderService.php) - Refactored service
- [app/src/Controller/FindContactController.php](app/src/Controller/FindContactController.php) - Enhanced controller with error handling
- [app/src/Controller/TestContactFinderController.php](app/src/Controller/TestContactFinderController.php) - Test endpoint (NEW)
- [app/templates/test_contact_finder.html.twig](app/templates/test_contact_finder.html.twig) - Test UI (NEW)

### Common Improvements Across Both Services

✅ **HTTP Configuration** (consistent across both services):
- Timeout: 10 seconds
- Max Redirects: 5
- User-Agent: `Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36`

✅ **Validation**:
- Empty/null checks
- Length validation (max 255 chars for names, URL format validation)
- Throws `InvalidArgumentException` for validation errors

✅ **Logging Pattern**:
```php
// INFO: Main flow events
$this->logger->info("Starting website search for company", ['name' => $name]);
$this->logger->info("Website found", ['company' => $name, 'url' => $website]);

// DEBUG: Detailed tracing
$this->logger->debug("Analyzing page", ['page' => $link]);
$this->logger->debug("Error checking domain", ['domain' => $domain, 'error' => $message]);

// WARNING: Non-fatal issues
$this->logger->warning("Failed to check domain", ['error' => $message]);

// ERROR: Fatal issues
$this->logger->error("Error during contact search", ['website' => $website, 'error' => $message]);
```

✅ **Error Handling in Controllers**:
- Outer try/catch for external API errors (INSEE, etc.)
- Inner try/catch for service validation errors
- User-friendly error messages in templates
- No 500 errors exposed to users

✅ **Template Updates**:
- Alert boxes for error display (`alert alert-danger`)
- Null-safe variable checks
- Consistent styling across both services

## Development Workflow

### Starting the Project

```bash
# Start all services
docker compose up -d --build

# Enter PHP container
docker exec -it job_candys_php /bin/bash

# Inside container:
cd /var/www/html

# Install dependencies
composer install

# Run migrations
php bin/console doctrine:migrations:migrate

# Clear cache
php bin/console cache:clear
```

### Access Points

| Service | URL | Credentials |
|---------|-----|-------------|
| Application | `http://localhost:8080` | - |
| phpMyAdmin | `http://localhost:8081` | root / `admin77911` |
| MailHog | `http://localhost:8025` | (local email testing) |

### Database

- **Container:** `job_candy_mysql`
- **Database:** `job_candys`
- **User:** `admin` / `admin7791` (dev)
- **Root:** `root` / `admin77911`
- **URL:** `mysql://admin:admin7791@database:3306/job_candys`

### Running Tests

```bash
cd app
php bin/phpunit

# Or in Docker:
docker exec job_candys_php php bin/phpunit
```

## Environment Variables (Key)

**`.env.dev` (development):**
```
APP_ENV=dev
APP_SECRET=[symfony-secret]
DATABASE_URL=mysql://admin:admin7791@database:3306/job_candys?serverVersion=8.4.6
MAILER_DSN=smtp://mailhog:1025
INSEE_API_KEY=[api-key]
HUNTER_API_KEY=[api-key]
IMAP_HOST, IMAP_PORT, IMAP_ENCRYPTION, IMAP_USERNAME, IMAP_PASSWORD
```

> **Note:** `.env.local` is Git-ignored; never commit sensitive keys.

## Authentication & Security

- **Provider:** `app_user_provider` (loads Users by email)
- **Authenticator:** `App\Security\LoginAuthenticator` (custom form-based auth)
- **Firewall:** Main firewall with remember-me cookie (7 days)
- **Password Hashing:** `auto` (Symfony's best practice)
- **User Entity:** `Users` implements `UserInterface`, `PasswordAuthenticatedUserInterface`

## Common Development Tasks

### Add New Entity
```bash
php bin/console make:entity EntityName
php bin/console make:migration
php bin/console doctrine:migrations:migrate
```

### Create Migration from Changes
```bash
php bin/console doctrine:migrations:diff
php bin/console doctrine:migrations:migrate
```

### Clear Cache (after config changes)
```bash
php bin/console cache:clear
```

### List All Routes
```bash
php bin/console debug:router
```

### Check Services & Configuration
```bash
php bin/console debug:container
```

## Code Conventions & Patterns

1. **Dependency Injection:** Always use constructor injection, never manual instantiation
2. **Entity Relations:** Use Doctrine attributes (`#[ORM\OneToOne]`, `#[ORM\ManyToOne]`)
3. **Validation:** Use Symfony constraints in entity attributes
4. **Form Handling:** Use FormBuilder in AbstractType classes
5. **Routing:** Use `#[Route]` PHP attributes (not YAML)
6. **Responses:** Controllers return `Response` objects (usually via `$this->render()`)
7. **Services:** Implement business logic, not controllers
8. **Repository Queries:** Use QueryBuilder for complex queries

## French/English Language Note

- Code comments and entity properties: **Mixed French/English**
- Labels and UI strings: **French-oriented** (Prénom, Nom, etc.)
- When adding features, follow existing language patterns

## API Integrations

### Hunter Email Verification
- **Service:** `HunterEmailVerify`
- **API Key:** `HUNTER_API_KEY` environment variable
- **Use case:** Verify email validity during registration
- **Pattern:** Injected into RegistrationController

### INSEE API (French Business Registry)
- **Service:** `InseeApiService`
- **API Key:** `INSEE_API_KEY` environment variable
- **Use case:** Query business information

### IMAP Email Reading
- **Service:** `ImapService`
- **Config:** IMAP_HOST, IMAP_PORT, IMAP_ENCRYPTION, IMAP_USERNAME, IMAP_PASSWORD
- **Purpose:** Inbox integration

## File Uploads

- **Upload Directory:** `public/uploads/profiles/`
- **Temp Directory:** `public/uploads/temp/`
- **Service:** `FileUploader` (validates and moves files)
- **Configuration:** Defined in `services.yaml` parameters

## Database Migrations

- **Location:** `app/migrations/`
- **Execute:** `php bin/console doctrine:migrations:migrate`
- **Create new:** `php bin/console doctrine:migrations:diff`
- **All migrations run on deployment**

## Performance & Production Notes

- **Caching:** Configure cache adapters in `config/packages/cache.yaml`
- **Database Optimization:** Lazy-loaded firewall (`lazy: true`)
- **Static Assets:** Asset Mapper handles CSS/JS bundling
- **Email Production:** Use real SMTP in `.env` (override `MAILER_DSN`)

## Debugging & Troubleshooting

**Web Profiler** (development only):
- Automatically available at bottom of pages
- Check performance, database queries, events

**Symfony Console:**
```bash
php bin/console debug:router           # See all routes
php bin/console debug:container        # See all services
php bin/console doctrine:query:sql     # Raw SQL queries
```

**Logs:** `var/log/` (check for errors in dev/prod)

## Git & Contribution

1. Create a feature branch
2. Make changes (follow conventions above)
3. Run tests: `php bin/phpunit`
4. Clear cache: `php bin/console cache:clear`
5. Test in browser (check MailHog for emails)
6. Commit with clear message
7. Push and create pull request with description

---

## Quick Reference: When to Use Each Layer

| Task | Use This |
|------|----------|
| Handle HTTP request | `Controller` |
| Business logic (calculations, API calls, data processing) | `Service` |
| Database queries | `Repository` |
| Data validation rules | `Entity` (Symfony constraints) |
| Form building & rendering | `Form` (FormType class) |
| User login flow | `Security/LoginAuthenticator` |
| Send email | `EmailService` |
| Verify company website | `WebsiteFinderService` |
| Verify email address | `HunterEmailVerify` |

---

**Last updated:** 2026-08-13 | Symfony 8.1 | PHP 8.4 | MySQL 8.0
