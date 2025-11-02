# 🏠 Mortgage Calculator API

REST API for mortgage simulation using the **French amortization method**. Built with Laravel 12 + PHP 8.4.

---

## 📋 About the Project

This API accurately calculates the **monthly payment** for a mortgage, supporting:

- ✅ **Fixed Rate**: Constant interest rate throughout the period
- ✅ **Variable Rate**: Euribor (or other index) + Spread
- ✅ **Bearer Token Authentication**: Secure API access with Laravel Sanctum
- ✅ **User Management**: Register, login, logout, and profile endpoints
- ✅ **Strict Validations**: 15+ validation rules for data integrity
- ✅ **Rate Limiting**: Protection against abuse (60 req/min)
- ✅ **100% Tested**: 39 tests (unit + feature)

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
# For zsh users, use ~/.zshrc instead of ~/.bashrc

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

### Optional Alias (Recommended)

```bash
# Add to ~/.bashrc or ~/.zshrc
alias sail='./vendor/bin/sail'

# Now you can use:
sail up -d
sail artisan test
```

---

## 🔐 Authentication

This API uses **Bearer Token authentication** powered by Laravel Sanctum. All mortgage calculation endpoints require authentication.

### 🔑 Authentication Endpoints

| Method | Endpoint | Description | Protected |
|--------|----------|-------------|-----------|
| `POST` | `/api/auth/register` | Create new user account | ❌ Public |
| `POST` | `/api/auth/login` | Authenticate and get token | ❌ Public |
| `POST` | `/api/auth/logout` | Revoke current token | ✅ Requires token |
| `GET` | `/api/auth/me` | Get authenticated user profile | ✅ Requires token |

---

### 📝 Example: Register User

**Request:**
```bash
curl -X POST http://localhost/api/auth/register \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name": "João Silva",
    "email": "joao@example.com",
    "password": "SecurePass123!",
    "password_confirmation": "SecurePass123!"
  }'
```

**Response (201 Created):**
```json
{
  "message": "User registered successfully",
  "data": {
    "user": {
      "id": 1,
      "name": "João Silva",
      "email": "joao@example.com"
    },
    "access_token": "{TOKEN}",
    "token_type": "Bearer"
  }
}
```

---

### 🔓 Example: Login

**Request:**
```bash
curl -X POST http://localhost/api/auth/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "email": "joao@example.com",
    "password": "SecurePass123!"
  }'
```

**Response (200 OK):**
```json
{
  "message": "Login successful",
  "data": {
    "user": {
      "id": 1,
      "name": "João Silva",
      "email": "joao@example.com"
    },
    "access_token": "{TOKEN}",
    "token_type": "Bearer"
  }
}
```

> 💡 **Important:** Save the `access_token` from the response. You'll need it for authenticated requests.

---

### 👤 Example: Get Profile

**Request:**
```bash
curl -X GET http://localhost/api/auth/me \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {TOKEN}"
```

**Response (200 OK):**
```json
{
  "data": {
    "user": {
      "id": 1,
      "name": "João Silva",
      "email": "joao@example.com",
      "created_at": "2025-11-02T18:30:00+00:00"
    }
  }
}
```

---

### 🚪 Example: Logout

**Request:**
```bash
curl -X POST http://localhost/api/auth/logout \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {TOKEN}"
```

**Response (200 OK):**
```json
{
  "message": "Logged out successfully"
}
```

> 💡 **Note:** After logout, the token is revoked and can no longer be used.

---

## 🚀 API Usage (Mortgage Calculation)

### Endpoint: Calculate Monthly Payment

```http
POST /api/mortgage/calculate
Content-Type: application/json
Accept: application/json
Authorization: Bearer {TOKEN}
```

> ⚠️ **Authentication Required:** You must include a valid Bearer token in the Authorization header.

---

### 📘 Example 1: Fixed Rate

**Request:**
```bash
curl -X POST http://localhost/api/mortgage/calculate \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {TOKEN}" \
  -d '{
    "loan_amount": 200000,
    "duration_months": 360,
    "type": "fixed",
    "rate": 3.5
  }'
```

**Response (200 OK):**
```json
{
  "data": {
    "monthly_payment": 898.09,
    "loan_amount": 200000,
    "duration_months": 360,
    "annual_rate": 3.5,
    "method": "french_amortization",
    "currency": "EUR",
    "metadata": {
      "calculation_date": "2025-11-02T15:30:00+00:00",
      "formula": "M = P * [i(1 + i)^n] / [(1 + i)^n - 1]"
    }
  }
}
```

---

### 📗 Example 2: Variable Rate (Euribor + Spread)

**Request:**
```bash
curl -X POST http://localhost/api/mortgage/calculate \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {TOKEN}" \
  -d '{
    "loan_amount": 250000,
    "duration_months": 300,
    "type": "variable",
    "index_rate": 2.8,
    "spread": 1.3
  }'
```

**Response (200 OK):**
```json
{
  "data": {
    "monthly_payment": 1186.19,
    "loan_amount": 250000,
    "duration_months": 300,
    "annual_rate": 4.1,
    "method": "french_amortization",
    "currency": "EUR",
    "metadata": {
      "calculation_date": "2025-11-02T15:35:00+00:00",
      "formula": "M = P * [i(1 + i)^n] / [(1 + i)^n - 1]"
    }
  }
}
```

> **Note:** APR = index_rate + spread = 2.8% + 1.3% = **4.1%**

---

### ❌ Example 3: Validation (Error 422)

**Invalid request:**
```bash
curl -X POST http://localhost/api/mortgage/calculate \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {TOKEN}" \
  -d '{
    "loan_amount": 3000,
    "type": "fixed"
  }'
```

**Response (422 Unprocessable Entity):**
```json
{
  "message": "Loan amount must be greater than 5000€ (and 1 more error)",
  "errors": {
    "loan_amount": [
      "Loan amount must be greater than 5000€"
    ],
    "duration_months": [
      "Duration is required"
    ]
  }
}
```

---

## 📖 API Documentation

### Available Endpoints

#### Authentication Endpoints

| Method | Endpoint | Description | Protected | Rate Limit |
|--------|----------|-------------|-----------|------------|
| `POST` | `/api/auth/register` | Create new user account | ❌ | - |
| `POST` | `/api/auth/login` | Authenticate and get token | ❌ | - |
| `POST` | `/api/auth/logout` | Revoke current token | ✅ | - |
| `GET` | `/api/auth/me` | Get authenticated user profile | ✅ | - |

#### Mortgage Calculation Endpoints

| Method | Endpoint | Description | Protected | Rate Limit |
|--------|----------|-------------|-----------|------------|
| `POST` | `/api/mortgage/calculate` | Calculate monthly payment | ✅ | 60/min |

#### Health Check Endpoints

| Method | Endpoint | Description | Protected | Rate Limit |
|--------|----------|-------------|-----------|------------|
| `GET` | `/api/health` | Custom health check (API + dependencies) | ❌ | - |
| `GET` | `/up` | Laravel native health check | ❌ | - |

> **💡 About the Health Check Endpoints:**  
> Laravel provides `/up` by default for **basic infrastructure checks** (container/pod liveness). However, the custom `/api/health` endpoint was added as a **best practice** to go beyond basic status:
>
> **`/up` (Laravel native):**
> - Basic application availability
> - Infrastructure-level health (Docker, Kubernetes liveness probes)
> 
> **`/api/health` (Custom):**
> - Can be extended to monitor **application-specific dependencies**:
>   - Database connections
>   - Cache services (Redis, Memcached)
>   - Third-party API availability
>   - Custom business logic health metrics
>
> This approach enables **comprehensive monitoring** beyond simple container status, allowing early detection of application-level issues before they impact users.

---

### Request Parameters

#### **Fixed Rate** (`type: "fixed"`)

| Field | Type | Required | Validation | Example |
|-------|------|----------|------------|---------|
| `loan_amount` | number | ✅ | 5 000 - 10 000 000 | 200 000 |
| `duration_months` | integer | ✅ | 60 - 480 (5-40 years) | 360 |
| `type` | string | ✅ | "fixed" | "fixed" |
| `rate` | number | ✅ | 0 - 100 | 3.5 |

#### **Variable Rate** (`type: "variable"`)

| Field | Type | Required | Validation | Example |
|-------|------|----------|------------|---------|
| `loan_amount` | number | ✅ | 5 000 - 10 000 000 | 180000 |
| `duration_months` | integer | ✅ | 60 - 480 (5-40 years) | 300 |
| `type` | string | ✅ | "variable" | "variable" |
| `index_rate` | number | ✅ | 0 - 100 (Euribor) | 2.5 |
| `spread` | number | ✅ | 0 - 100 | 1.5 |

---

### Response Structure

```json
{
  "data": {
    "monthly_payment": number,      // Monthly payment in EUR
    "loan_amount": number,          // Loan amount
    "duration_months": integer,     // Term in months
    "annual_rate": number,          // Applied APR
    "method": "french_amortization",
    "currency": "EUR",
    "metadata": {
      "calculation_date": string,   // Date of simulation
      "formula": string             // Formula used
    }
  }
}
```

---

## 📁 Project Structure

```
api/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── Api/
│   │   │       ├── AuthController.php           # Authentication endpoints
│   │   │       └── MortgageController.php       # Mortgage calculation
│   │   ├── Middleware/
│   │   │   └── ForceJsonResponse.php            # Force JSON responses
│   │   ├── Requests/
│   │   │   ├── CalculateMortgageRequest.php     # Mortgage validations
│   │   │   ├── RegisterRequest.php              # Registration validations
│   │   │   └── LoginRequest.php                 # Login validations
│   │   └── Resources/
│   │       └── MortgageCalculationResource.php  # JSON formatting
│   ├── Models/
│   │   └── User.php                             # User model with Sanctum
│   ├── Providers/
│   │   └── AppServiceProvider.php               # Register services
│   └── Services/
│       └── MortgageCalculatorService.php        # Business logic
├── config/
│   ├── auth.php                                 # Authentication configuration
│   ├── cors.php                                 # CORS configuration
│   └── sanctum.php                              # Sanctum configuration
├── database/
│   └── migrations/
│       ├── create_users_table.php               # Users table
│       └── create_personal_access_tokens_table.php  # Sanctum tokens
├── routes/
│   └── api.php                                  # API routes
├── tests/
│   ├── Unit/
│   │   └── MortgageCalculatorServiceTest.php   # 8 unit tests
│   └── Feature/
│       ├── AuthenticationTest.php               # 17 authentication tests
│       └── MortgageCalculationTest.php          # 17 mortgage tests
├── .php-cs-fixer.php                            # Formatting config
├── phpstan.neon                                 # Static analysis config
├── compose.yaml                                 # Docker (Laravel Sail)
└── README.md                                    # This file
```

---

## 🧪 Testing

## 🔧 Composer Scripts

```bash
# ✅ Run all quality checks
sail composer quality
# → Formatting + Static analysis + Tests

# 🎨 Format code automatically (PSR-12 + Laravel)
sail composer cs-fix

# 🔍 Check formatting without changing files
sail composer cs-check

# 📊 Static analysis with PHPStan (level 6)
sail composer phpstan

# 🧪 Run tests (clears cache first)
sail composer test
```

---

## 🔒 Security

### Implemented Measures

| Measure | Description |
|---------|-------------|
| **Bearer Token Authentication** | Laravel Sanctum for secure API access |
| **Password Hashing** | Bcrypt with configurable rounds (default: 12) |
| **Token Revocation** | Logout immediately invalidates tokens |
| **Rate Limiting** | 60 requests/minute on `/calculate` endpoint |
| **Input Validation** | Laravel Form Request with 15+ validation rules |
| **Type Safety** | PHP 8.4 strict types in all classes |
| **Defensive Programming** | Service throws exceptions for impossible values |
| **No Stack Trace** | Sanitized errors in production (via Laravel Handler) |
| **CORS** | Configured for localhost (production) |
| **Force JSON** | Middleware ensures consistent JSON responses |

---

## 💻 Development

### Useful Commands

```bash
# Start containers
sail up -d

# View logs in real-time
sail logs -f

# Enter PHP container
sail shell

# Stop containers
sail down

# Clear Laravel cache
sail artisan cache:clear
sail artisan config:clear
sail artisan route:clear

# Rebuild containers
sail build --no-cache
```
---

## 👨‍💻 Author

Developed by **João Alves** as part of a technical challenge.

**Stack:** Laravel · PHP · Docker

