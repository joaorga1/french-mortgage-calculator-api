# 📖 API Documentation

Complete reference for the Mortgage Calculator API.

---

## 🚀 Interactive Testing

| Tool | Description | Link |
|------|-------------|------|
| **Swagger UI** | Interactive API explorer with live testing | (http://localhost/api/documentation) |
| **Postman Collection** | Pre-configured collection with all endpoints | (./Doutor%20Finanças.postman_collection.json) |

---

## Table of Contents

- [Authentication](#-authentication)
- [Mortgage Calculation](#-mortgage-calculation)
- [Simulation Management](#-simulation-management)
- [Export](#-export)
- [Health Check](#-health-check)
- [Error Responses](#-error-responses)

---

## 🔐 Authentication

All mortgage calculation and simulation endpoints require Bearer token authentication via Laravel Sanctum.

### Endpoints Overview

| Method | Endpoint | Description | Auth Required |
|--------|----------|-------------|---------------|
| `POST` | `/api/auth/register` | Create new user account | ❌ |
| `POST` | `/api/auth/login` | Authenticate and get token | ❌ |
| `POST` | `/api/auth/logout` | Revoke current token | ✅ |
| `GET` | `/api/auth/me` | Get authenticated user profile | ✅ |

---

### Register User

Create a new user account and receive an access token.

**Endpoint:** `POST /api/auth/register`

**Headers:**
```http
Content-Type: application/json
Accept: application/json
```

**Request Body:**
```json
{
  "name": "João Silva",
  "email": "joao@example.com",
  "password": "SecurePass123!",
  "password_confirmation": "SecurePass123!"
}
```

**Validation Rules:**
| Field | Type | Rules |
|-------|------|-------|
| `name` | string | Required, max 255 characters |
| `email` | string | Required, valid email, unique, max 255 characters |
| `password` | string | Required, confirmed, min 8 characters, must include letters & numbers |
| `password_confirmation` | string | Required, must match password |

**Success Response (201 Created):**
```json
{
  "message": "User registered successfully",
  "data": {
    "user": {
      "id": 1,
      "name": "João Silva",
      "email": "joao@example.com"
    },
    "access_token": "1|abc123xyz789...",
    "token_type": "Bearer"
  }
}
```

**cURL Example:**
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

---

### Login

Authenticate with email and password to receive an access token.

**Endpoint:** `POST /api/auth/login`

**Headers:**
```http
Content-Type: application/json
Accept: application/json
```

**Request Body:**
```json
{
  "email": "joao@example.com",
  "password": "SecurePass123!"
}
```

**Validation Rules:**
| Field | Type | Rules |
|-------|------|-------|
| `email` | string | Required, valid email format |
| `password` | string | Required |

**Success Response (200 OK):**
```json
{
  "message": "Login successful",
  "data": {
    "user": {
      "id": 1,
      "name": "João Silva",
      "email": "joao@example.com"
    },
    "access_token": "2|def456uvw012...",
    "token_type": "Bearer"
  }
}
```

**Error Response (401 Unauthorized):**
```json
{
  "message": "Invalid credentials"
}
```

**cURL Example:**
```bash
curl -X POST http://localhost/api/auth/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "email": "joao@example.com",
    "password": "SecurePass123!"
  }'
```

> 💡 **Important:** Save the `access_token` from the response. Include it in the `Authorization` header for all protected endpoints.

---

### Get Profile

Retrieve the authenticated user's profile information.

**Endpoint:** `GET /api/auth/me`

**Headers:**
```http
Accept: application/json
Authorization: Bearer {TOKEN}
```

**Success Response (200 OK):**
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

**cURL Example:**
```bash
curl -X GET http://localhost/api/auth/me \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {TOKEN}"
```

---

### Logout

Revoke the current access token.

**Endpoint:** `POST /api/auth/logout`

**Headers:**
```http
Accept: application/json
Authorization: Bearer {TOKEN}
```

**Success Response (200 OK):**
```json
{
  "message": "Logged out successfully"
}
```

**cURL Example:**
```bash
curl -X POST http://localhost/api/auth/logout \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {TOKEN}"
```

> 💡 **Note:** After logout, the token is permanently revoked and cannot be reused.

---

## 🏠 Mortgage Calculation

Calculate mortgage monthly payments using the French amortization method. All calculations are automatically saved to your simulation history.

**Endpoint:** `POST /api/mortgage/calculate`

**Authentication:** Required (Bearer token)

**Rate Limit:** 60 requests/minute

---

### Fixed Rate Calculation

Calculate mortgage with a constant interest rate.

**Headers:**
```http
Content-Type: application/json
Accept: application/json
Authorization: Bearer {TOKEN}
```

**Request Body:**
```json
{
  "loan_amount": 200000,
  "duration_months": 360,
  "type": "fixed",
  "rate": 3.5
}
```

**Validation Rules:**
| Field | Type | Rules |
|-------|------|-------|
| `loan_amount` | number | Required, between 5,000 and 10,000,000 |
| `duration_months` | integer | Required, between 60 and 480 (5-40 years) |
| `type` | string | Required, must be "fixed" |
| `rate` | number | Required, between 0 and 100 (annual percentage rate) |

**Success Response (201 Created):**
```json
{
  "data": {
    "id": 1,
    "monthly_payment": "898.09",
    "loan_amount": 200000,
    "duration_months": 360,
    "annual_rate": 3.5,
    "total_amount": "323312.40",
    "total_interest": "123312.40",
    "metadata": {
      "calculation_date": "2025-11-03T12:00:00+00:00",
      "formula": "M = P * [i(1 + i)^n] / [(1 + i)^n - 1]",
      "method": "french_amortization",
      "currency": "EUR"
    }
  }
}
```

**cURL Example:**
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

---

### Variable Rate Calculation

Calculate mortgage with Euribor (or other index) + Spread.

**Request Body:**
```json
{
  "loan_amount": 250000,
  "duration_months": 300,
  "type": "variable",
  "index_rate": 2.8,
  "spread": 1.3
}
```

**Validation Rules:**
| Field | Type | Rules |
|-------|------|-------|
| `loan_amount` | number | Required, between 5,000 and 10,000,000 |
| `duration_months` | integer | Required, between 60 and 480 (5-40 years) |
| `type` | string | Required, must be "variable" |
| `index_rate` | number | Required, between 0 and 100 (e.g., Euribor) |
| `spread` | number | Required, between 0 and 100 |

**Success Response (201 Created):**
```json
{
  "data": {
    "id": 2,
    "monthly_payment": "1186.19",
    "loan_amount": 250000,
    "duration_months": 300,
    "annual_rate": 4.1,
    "total_amount": "355857.00",
    "total_interest": "105857.00",
    "metadata": {
      "calculation_date": "2025-11-03T12:05:00+00:00",
      "formula": "M = P * [i(1 + i)^n] / [(1 + i)^n - 1]",
      "method": "french_amortization",
      "currency": "EUR"
    }
  }
}
```

> **Note:** Annual Rate = index_rate + spread = 2.8% + 1.3% = **4.1%**

**cURL Example:**
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

---

## 📊 Simulation Management

Manage your saved mortgage simulations.

### Endpoints Overview

| Method | Endpoint | Description | Auth Required | Rate Limit |
|--------|----------|-------------|---------------|------------|
| `GET` | `/api/simulations` | List simulations (paginated) | ✅ | 60/min |
| `GET` | `/api/simulations/{id}` | View simulation with amortization table | ✅ | 60/min |

---

### List Simulations

Retrieve all your saved simulations with pagination.

**Endpoint:** `GET /api/simulations`

**Headers:**
```http
Accept: application/json
Authorization: Bearer {TOKEN}
```

**Query Parameters:**
| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `page` | integer | 1 | Page number for pagination |

**Success Response (200 OK):**
```json
{
  "data": [
    {
      "id": 2,
      "user_id": 1,
      "loan_amount": "200000.00",
      "duration_months": 360,
      "rate_type": "fixed",
      "annual_rate": "3.50",
      "monthly_payment": "898.09",
      "total_amount": "323312.40",
      "total_interest": "123312.40",
      "created_at": "2025-11-03T10:30:00+00:00"
    },
    {
      "id": 1,
      "user_id": 1,
      "loan_amount": "150000.00",
      "duration_months": 240,
      "rate_type": "variable",
      "annual_rate": "4.10",
      "index_rate": "2.80",
      "spread": "1.30",
      "monthly_payment": "916.09",
      "total_amount": "219862.16",
      "total_interest": "69862.16",
      "created_at": "2025-11-03T09:15:00+00:00"
    }
  ],
  "links": {
    "first": "http://localhost/api/simulations?page=1",
    "last": "http://localhost/api/simulations?page=1",
    "prev": null,
    "next": null
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 1,
    "per_page": 10,
    "to": 2,
    "total": 2
  }
}
```

**cURL Example:**
```bash
# Get first page
curl -X GET http://localhost/api/simulations \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {TOKEN}"

# Get page 2
curl -X GET "http://localhost/api/simulations?page=2" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {TOKEN}"
```

> 💡 **Pagination:** Results are paginated with 10 items per page. Use the `links` and `meta` fields to navigate between pages.

---

### View Simulation Details

Get detailed information about a specific simulation, including the complete month-by-month amortization table.

**Endpoint:** `GET /api/simulations/{id}`

**Headers:**
```http
Accept: application/json
Authorization: Bearer {TOKEN}
```

**URL Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `id` | integer | Simulation ID |

**Success Response (200 OK):**
```json
{
  "id": 2,
  "user_id": 1,
  "loan_amount": "200000.00",
  "duration_months": 360,
  "rate_type": "fixed",
  "annual_rate": "3.50",
  "monthly_payment": "898.09",
  "total_amount": "323312.40",
  "total_interest": "123312.40",
  "created_at": "2025-11-03T10:30:00+00:00",
  "amortization_table": [
    {
      "month": 1,
      "payment": 898.09,
      "principal": 314.76,
      "interest": 583.33,
      "balance": 199685.24
    },
    {
      "month": 2,
      "payment": 898.09,
      "principal": 315.68,
      "interest": 582.41,
      "balance": 199369.56
    },
    ...
    {
      "month": 360,
      "payment": 898.09,
      "principal": 895.48,
      "interest": 2.61,
      "balance": 0
    }
  ]
}
```

**Error Response (403 Forbidden):**
```json
{
  "message": "Forbidden"
}
```

**cURL Example:**
```bash
curl -X GET http://localhost/api/simulations/2 \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {TOKEN}"
```

> 💡 **Amortization Table:** The table includes all monthly payments from month 1 to the final month. The `balance` reaches exactly **0.00** at the end due to precise floating-point calculations.

**Amortization Table Fields:**
| Field | Type | Description |
|-------|------|-------------|
| `month` | integer | Payment number (1 to duration_months) |
| `payment` | number | Monthly payment amount (constant) |
| `principal` | number | Amount applied to principal (increases over time) |
| `interest` | number | Interest portion (decreases over time) |
| `balance` | number | Remaining loan balance after payment |

---

## 📥 Export

Download amortization tables in CSV or Excel format.

**Endpoint:** `GET /api/simulations/{id}/export`

**Authentication:** Required (Bearer token)

**Rate Limit:** 60 requests/minute

---

### Export to CSV

Download the amortization table as a plain-text CSV file.

**Headers:**
```http
Authorization: Bearer {TOKEN}
```

**Query Parameters:**
| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `format` | string | csv | Export format: "csv" or "excel" |

**Success Response (200 OK):**
```
Headers:
  Content-Type: text/csv; charset=UTF-8
  Content-Disposition: attachment; filename=simulation_2.csv

Body:
Month,Payment,Principal,Interest,Balance
1,898.09,314.76,583.33,199685.24
2,898.09,315.68,582.41,199369.56
3,898.09,316.60,581.49,199052.96
...
360,898.09,895.48,2.61,0.00
```

**cURL Example:**
```bash
curl -X GET "http://localhost/api/simulations/2/export?format=csv" \
  -H "Authorization: Bearer {TOKEN}" \
  -o simulation_2.csv
```

---

### Export to Excel

Download the amortization table as a Microsoft Excel file (.xlsx).

**Query Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `format` | string | Must be "excel" |

**Success Response (200 OK):**
```
Headers:
  Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet
  Content-Disposition: attachment; filename=simulation_2.xlsx

Body: [Binary Excel file]
```

**cURL Example:**
```bash
curl -X GET "http://localhost/api/simulations/2/export?format=excel" \
  -H "Authorization: Bearer {TOKEN}" \
  -o simulation_2.xlsx
```

---

### Export Error Responses

**Invalid Format (400 Bad Request):**
```json
{
  "error": "Invalid format. Use csv or excel"
}
```

**Forbidden Access (403 Forbidden):**
```json
{
  "message": "Forbidden"
}
```

**Simulation Not Found (404 Not Found):**
```json
{
  "message": "No query results for model [App\\Models\\Simulation] {id}"
}
```

> 💡 **Default Format:** If the `format` parameter is omitted, the export defaults to CSV.

---

## 🏥 Health Check

Monitor API availability and dependencies.

### Endpoints Overview

| Method | Endpoint | Description | Auth Required |
|--------|----------|-------------|---------------|
| `GET` | `/api/health` | Custom health check (extensible) | ❌ |
| `GET` | `/up` | Laravel native health check | ❌ |

---

### Custom Health Check

Extensible endpoint for monitoring application-level dependencies.

**Endpoint:** `GET /api/health`

**Success Response (200 OK):**
```json
{
  "status": "ok",
  "service": "mortgage-calculator-api",
  "timestamp": "2025-11-03T12:00:00+00:00"
}
```

**cURL Example:**
```bash
curl http://localhost/api/health
```

> 💡 **Use Cases:**
> - Monitor database connectivity
> - Check cache service status
> - Verify third-party API availability
> - Custom business logic health metrics

---

### Laravel Native Health Check

Basic infrastructure-level health check.

**Endpoint:** `GET /up`

**Success Response (200 OK):**
```json
{
  "status": "ok"
}
```

**cURL Example:**
```bash
curl http://localhost/up
```

> 💡 **Use Cases:**
> - Kubernetes liveness/readiness probes
> - Docker container health checks
> - Load balancer health monitoring

---

## ❌ Error Responses

All errors follow a consistent JSON structure.

### Validation Error (422 Unprocessable Entity)

**Example Request:**
```json
{
  "loan_amount": 3000,
  "type": "fixed"
}
```

**Response:**
```json
{
  "message": "O valor do empréstimo tem de ser pelo menos 5000€ (and 1 more error)",
  "errors": {
    "loan_amount": [
      "O valor do empréstimo tem de ser pelo menos 5000€"
    ],
    "duration_months": [
      "A duração é obrigatória"
    ]
  }
}
```

---

### Authentication Error (401 Unauthorized)

**Response:**
```json
{
  "message": "Unauthenticated."
}
```

**Common Causes:**
- Missing `Authorization` header
- Invalid or expired token
- Token revoked via logout

---

### Authorization Error (403 Forbidden)

**Response:**
```json
{
  "message": "Forbidden"
}
```

**Common Causes:**
- Trying to access another user's simulation
- Insufficient permissions

---

### Not Found Error (404 Not Found)

**Response:**
```json
{
  "message": "No query results for model [App\\Models\\Simulation] 999"
}
```

**Common Causes:**
- Simulation ID doesn't exist
- Invalid endpoint URL

---

### Rate Limit Error (429 Too Many Requests)

**Response:**
```json
{
  "message": "Too Many Attempts."
}
```

**Common Causes:**
- Exceeded 60 requests/minute on protected endpoints

**Solution:** Wait 60 seconds before retrying.

---

### Server Error (500 Internal Server Error)

**Production Response:**
```json
{
  "message": "An error occurred",
  "error": "Internal server error"
}
```

**Development Response (APP_DEBUG=true):**
```json
{
  "message": "Failed to create simulation",
  "error": "SQLSTATE[HY000] [2002] Connection refused"
}
```

**Common Causes:**
- Database connection issues
- Application configuration errors
- Unexpected exceptions

> 💡 **Note:** Stack traces and detailed errors are only shown when `APP_DEBUG=true` in the `.env` file (development only).

---

## 📋 HTTP Status Codes

| Status Code | Meaning | When It Occurs |
|-------------|---------|----------------|
| `200 OK` | Success | GET requests successful |
| `201 Created` | Resource created | POST /calculate, /register successful |
| `400 Bad Request` | Invalid request | Invalid export format |
| `401 Unauthorized` | Authentication required | Missing or invalid token |
| `403 Forbidden` | Access denied | Trying to access other user's data |
| `404 Not Found` | Resource not found | Simulation ID doesn't exist |
| `422 Unprocessable Entity` | Validation failed | Invalid input data |
| `429 Too Many Requests` | Rate limit exceeded | More than 60 req/min |
| `500 Internal Server Error` | Server error | Unexpected application error |

---

## 🔗 Quick Reference

### Base URL
```
http://localhost
```

### All Endpoints

```
POST   /api/auth/register
POST   /api/auth/login
POST   /api/auth/logout
GET    /api/auth/me

POST   /api/mortgage/calculate
GET    /api/simulations
GET    /api/simulations/{id}
GET    /api/simulations/{id}/export

GET    /api/health
GET    /up
```

### Common Headers

```http
Content-Type: application/json
Accept: application/json
Authorization: Bearer {TOKEN}
```

---

**Need help?** Check the [README](./README.md) for installation and setup instructions.

