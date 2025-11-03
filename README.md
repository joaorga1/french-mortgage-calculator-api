# 🏠 Mortgage Calculator API

REST API for mortgage simulation using the **French amortization method**. Built with Laravel 12 + PHP 8.4.

---

## 📋 Features

- ✅ **Fixed & Variable Rates**: Accurate mortgage calculations using French amortization
- ✅ **Simulation History**: Save and retrieve past simulations with complete details
- ✅ **Export Data**: Download amortization tables (CSV/Excel format)
- ✅ **Bearer Token Auth**: Secure API access with Laravel Sanctum
- ✅ **Interactive Docs**: Swagger/OpenAPI UI for live API testing
- ✅ **Health Monitoring**: Database connection checks for reliability
- ✅ **Rate Limiting**: 60 requests/minute protection against abuse
- ✅ **100% Tested**: 65 tests (13 unit + 52 feature) covering all functionality
- ✅ **CI/CD**: Automated testing with GitHub Actions on every push/PR

---

## 📖 Documentation

| Document | Description |
|----------|-------------|
| **[API.md](./API.md)** | Complete API documentation with all endpoints, examples, and validation rules |
| **[Swagger UI](http://localhost/api/documentation)** | Interactive API documentation with live testing (requires running server) |
| **[Postman Collection](./Doutor%20Finanças.postman_collection.json)** | Ready-to-use Postman collection with all endpoints and examples |

### Quick Navigation

- [🔐 Authentication](./API.md#-authentication) - Register, login, logout
- [🏠 Mortgage Calculation](./API.md#-mortgage-calculation) - Calculate payments
- [📊 Simulation Management](./API.md#-simulation-management) - List and view history
- [📥 Export](./API.md#-export) - Download CSV/Excel tables
- [📊 Swagger/OpenAPI](http://localhost/api/documentation) - Interactive API explorer
- [📮 Postman Collection](./Doutor%20Finanças.postman_collection.json) - Import into Postman/Insomnia

---

## 🧮 French Amortization Method

### Mathematical Formula

```
M = P × [i(1 + i)ⁿ] / [(1 + i)ⁿ - 1]
```

**Legend:**
- **M** = Monthly payment (€)
- **P** = Loan amount (€)
- **i** = Monthly interest rate = (APR ÷ 12 ÷ 100) (%)
- **n** = Number of months

### 💡 Practical Example

**Scenario:**
- Loan: **200 000€**
- Term: **30 years** (360 months)
- APR: **3.5%** (annual percentage rate)

**Calculation:**
```
i = 3.5 ÷ 12 ÷ 100 = 0.002917
M = 200,000 × [0.002917 × (1.002917)³⁶⁰] / [(1.002917)³⁶⁰ - 1]
M ≈ 898.09€/month
```

**Total paid:** 323,312€ (Principal: 200,000€ + Interest: 123,312€)

---

## 🛠️ Tech Stack

| Technology | Version | Purpose |
|------------|---------|---------|
| **PHP** | 8.4 | Strictly typed language |
| **Laravel** | 12.0 | RESTful API framework |
| **Laravel Sanctum** | 4.x | Bearer token authentication |
| **MySQL** | 8.0 | Relational database |
| **Maatwebsite/Excel** | 3.x | CSV/Excel export |
| **L5-Swagger** | 8.x | OpenAPI/Swagger documentation |
| **Docker** | via Sail | Development environment |
| **PHPUnit** | 11.5 | Automated testing |
| **PHPStan** | 2.1 (level 6) | Static code analysis |
| **PHP-CS-Fixer** | 3.89 | PSR-12 + Laravel formatting |

---

## 📦 Installation

### Prerequisites

- **Docker Desktop** (Windows/Mac) or **Docker Engine** (Linux)
- **Git**

### Step by Step

```bash
# 1. Clone the repository
git clone <repository-url>
cd api

# 2. Copy environment variables
cp .env.example .env

# 3. Install PHP dependencies
# Option A: Using Docker (recommended, no local PHP/Composer needed)
docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v "$(pwd):/var/www/html" \
    -w /var/www/html \
    laravelsail/php84-composer:latest \
    composer install --ignore-platform-reqs

# Option B: Using local Composer (if installed)
composer install

# 4. Configure Sail alias (optional but recommended)
echo "alias sail='./vendor/bin/sail'" >> ~/.bashrc
source ~/.bashrc
# Note: Use ~/.zshrc for zsh users

# 5. Start Docker containers (Laravel Sail)
sail up -d

# 6. Generate application key
sail artisan key:generate

# 7. Complete the .env file with credentials
DB_PASSWORD

# 8. Run database migrations
sail artisan migrate

# 9. Check application health
curl http://localhost/api/health
# Expected response: 200 OK
```

**Expected Response:**
```json
{
  "status": "ok",
  "service": "mortgage-calculator-api",
  "timestamp": "2025-11-03T12:00:00+00:00",
  "checks": {
    "database": {
      "status": "ok",
      "connection": "mysql"
    }
  }
}
```

---

## 🧪 Testing & Code Quality

### Run Tests

```bash
# Run all tests (unit + feature)
sail artisan test
```

### Code Quality Commands

```bash
# ✅ Run all quality checks at once
sail composer quality
# → Runs: cs-fix + phpstan + tests

# 🎨 Auto-format code (PSR-12 + Laravel standards)
sail composer cs-fix

# 🔍 Check formatting without modifying files
sail composer cs-check

# 📊 Static analysis (PHPStan level 6)
sail composer phpstan
```

### 🤖 Continuous Integration (CI/CD)

The project uses **GitHub Actions** to automatically run quality checks on every push and pull request:

**What runs automatically:**
- ✅ PHPStan (static analysis)
- ✅ PHP-CS-Fixer (code formatting check)
- ✅ PHPUnit tests (all 65 tests)
- ✅ Database migrations

**Workflow file:** `.github/workflows/tests.yml`

**When it runs:**
- Push to `main` branch
- Pull requests to `main` (from any branch)

[📖 View workflow details](.github/workflows/README.md)

---

## 🔒 Security

### Implemented Measures

| Measure | Implementation |
|---------|----------------|
| **Authentication** | Bearer Token via Laravel Sanctum |
| **Password Security** | Bcrypt hashing with 12 rounds |
| **Token Management** | Revocable tokens on logout |
| **Rate Limiting** | 60 requests/minute on protected endpoints |
| **Input Validation** | 15+ validation rules via Form Requests |
| **Type Safety** | PHP 8.4 strict types in all classes |
| **Error Handling** | Sanitized errors in production (no stack traces) |
| **CORS** | Configured for specified origins |
| **JSON Enforcement** | Middleware ensures consistent JSON responses |

---

### 🤖 Continuous Integration (CI/CD)

The project uses **GitHub Actions** to automatically run quality checks on every push and pull request:

**What runs automatically:**
- ✅ PHPStan (static analysis)
- ✅ PHP-CS-Fixer (code formatting check)
- ✅ PHPUnit tests (all 65 tests)
- ✅ Database migrations

**Workflow file:** `.github/workflows/tests.yml`

**When it runs:**
- Push to `main` branch
- Pull requests to `main` (from any branch)

---

## 📁 Project Structure

```
api/
├── app/
│   ├── Exports/
│   │   └── AmortizationExport.php              # Excel export formatting
│   ├── Http/
│   │   ├── Controllers/Api/
│   │   │   ├── AuthController.php              # Authentication endpoints
│   │   │   └── MortgageController.php          # Mortgage & simulations
│   │   ├── Middleware/
│   │   │   └── ForceJsonResponse.php           # Force JSON responses
│   │   ├── Requests/
│   │   │   ├── CalculateMortgageRequest.php    # Mortgage validation rules
│   │   │   ├── RegisterRequest.php             # Registration validation
│   │   │   └── LoginRequest.php                # Login validation
│   │   └── Resources/
│   │       ├── MortgageCalculationResource.php # Calculation JSON formatting
│   │       └── SimulationResource.php          # Simulation JSON formatting
│   ├── Models/
│   │   ├── User.php                            # User model with Sanctum
│   │   └── Simulation.php                      # Simulation model
│   └── Services/
│       ├── MortgageCalculatorService.php       # Mortgage calculation logic
│       ├── SimulationService.php               # Simulation creation
│       ├── AmortizationTableService.php        # Payment breakdown calculation
│       └── SimulationExportService.php         # CSV/Excel export logic
├── database/
│   ├── factories/
│   │   ├── UserFactory.php                     # User test data
│   │   └── SimulationFactory.php               # Simulation test data
│   └── migrations/
│       ├── create_users_table.php              # Users table schema
│       ├── create_personal_access_tokens_table.php  # Sanctum tokens
│       └── create_simulations_table.php        # Simulations history
├── tests/
│   ├── Unit/                                   # 13 unit tests
│   │   ├── MortgageCalculatorServiceTest.php   # Calculation logic tests
│   │   └── AmortizationTableServiceTest.php    # Table generation tests
│   └── Feature/                                # 52 feature tests
│       ├── AuthenticationTest.php              # Auth endpoint tests
│       ├── MortgageCalculationTest.php         # Mortgage endpoint tests
│       ├── SimulationTest.php                  # Simulation CRUD tests
│       └── SimulationExportTest.php            # Export functionality tests
├── .php-cs-fixer.php                           # Code formatting rules
├── phpstan.neon                                # Static analysis config
├── compose.yaml                                # Docker Sail configuration
├── README.md                                   # This file
└── API.md                                      # Complete API documentation
```

---

## 💻 Development

### Useful Commands

```bash
# Container management
sail up -d                  # Start containers in background
sail down                   # Stop containers
sail restart                # Restart all services
sail logs -f                # View real-time logs

# Laravel commands
sail artisan migrate        # Run database migrations
sail artisan migrate:fresh  # Reset database and re-run migrations
sail artisan tinker         # Interactive console

# Cache management
sail artisan cache:clear    # Clear application cache
sail artisan config:clear   # Clear config cache
sail artisan route:clear    # Clear route cache

# Database
sail mysql                  # Access MySQL CLI
sail artisan db:seed        # Run database seeders

# Debugging
sail shell                  # Enter PHP container
```

---

## 🎯 Health Check Endpoints

The API provides two health check endpoints for monitoring:

| Endpoint | Purpose | Status Codes | Checks |
|----------|---------|--------------|--------|
| `/up` | Laravel native health check | `200` OK | Basic container/infrastructure status |
| `/api/health` | Custom health check with dependency monitoring | `200` OK / `503` Error | Database connection, extensible for cache/APIs |

Both are useful for different needs:
- **`/up`** → Kubernetes liveness/readiness probes
- **`/api/health`** → Application-level monitoring, alerting and integration with third-party software

---

## 👨‍💻 Author

Developed by **João Alves** as part of a technical challenge.

**Stack:** Laravel · PHP · Docker · MySQL
