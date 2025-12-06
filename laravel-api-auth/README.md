# Laravel API Authentication System

A complete, production-ready API authentication system built with Laravel 11.

## Features

- **Dual Authentication**: Support for both JWT (User Login) and API Keys (Service Access).
- **Secure Hashing**: API keys are hashed using SHA-256 before storage.
- **Usage Tracking**: Tracks every API key usage with IP, User Agent, and Status Code.
- **Role Separation**: 
  - Users authenticate via Email/Password to get JWT.
  - Services authenticate via `X-API-KEY` header.

## Installation

1. **Clone & Install Dependencies**
   ```bash
   composer install
   ```

2. **Environment Setup**
   ```bash
   cp .env.example .env
   php artisan key:generate
   php artisan jwt:secret
   ```
   *Configure your database credentials in `.env`*

3. **Database Setup**
   ```bash
   php artisan migrate
   php artisan db:seed
   ```
   *This creates a test user: `test@example.com` / `password` and a demo API Key.*

## Usage

### Authentication (JWT)

**Register**
`POST /api/register`
```json
{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password"
}
```

**Login**
`POST /api/login`
```json
{
    "email": "john@example.com",
    "password": "password"
}
```
*Returns `access_token`.*

### API Key Management (Protected by JWT)

**Create Key**
`POST /api/api-keys`
Headers: `Authorization: Bearer <token>`
```json
{
    "name": "Mobile App Key"
}
```
*Returns the plain text key ONLY ONCE.*

**List Keys**
`GET /api/api-keys`

**Delete Key**
`DELETE /api/api-keys/{id}`

### Service Access (Protected by API Key)

**Access Protected Service**
`GET /api/service`
Headers: `X-API-KEY: <your-api-key>`

## Testing

**Run Automated Tests**
```bash
php artisan test
```

**Run Manual Test Script**
```bash
# Requires jq installed
./test-api.sh
```

**Postman**
Import `Laravel-API-Auth.postman_collection.json` into Postman.
