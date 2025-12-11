# Wallet Service API with Role-Based Authentication

A production-ready Financial API built with Laravel 11. This system implements a complete digital wallet solution featuring secure JWT authentication, API Key management with granular permissions, and a simulated payment gateway integration (Paystack).

## 🚀 Key Features

### 🔐 Advanced Authentication & Security
- **Dual Auth System**: 
  - **User Access**: JWT (JSON Web Tokens) for secure, stateless user sessions.
  - **Service Access**: Hashed API Keys (`X-API-KEY`) for server-to-server communication.
- **Granular Permissions**: API Keys are scoped with specific capabilities (`wallet.read`, `wallet.fund`, `wallet.transfer`).
- **Secure Storage**: API keys are hashed using SHA-256 before database storage; raw keys are shown only once upon creation.

### 💰 Digital Wallet System
- **Wallet Creation**: Automatically generates a unique 14-digit NUBAN-style wallet number for every user.
- **Deposits**: Integrated with Paystack (Simulated) to initialize payments and handle webhooks for wallet funding.
- **P2P Transfers**: Atomic, ACID-compliant money transfers between wallets with integrity checks and duplicate transaction prevention.
- **Ledger System**: Double-entry recording for all transactions (Credits and Debits).

### 🛠 Developer Experience
- **Swagger/OpenAPI**: Fully interactive API documentation available at `/api/documentation`.
- **Automated Testing**: Custom PowerShell E2E test suite that simulates full user flows including webhook callbacks.
- **Docker Ready**: (Optional) optimized for containerized deployments.

---

## 📦 Installation & Setup

### Prerequisites
- PHP 8.2 or higher
- Composer
- SQLite (default) or MySQL

### Step-by-Step Setup

1.  **Clone the Repository**
    ```bash
    git clone <repository-url>
    cd api-proj
    ```

2.  **Install Dependencies**
    ```bash
    composer install
    ```

3.  **Environment Configuration**
    ```bash
    cp .env.example .env
    php artisan key:generate
    php artisan jwt:secret
    ```
    *Ensure `DB_CONNECTION=sqlite` is set in `.env` for quick startup, or configure MySQL details.*

4.  **Database Migration & Seeding**
    This command sets up the tables and creates test users with predefined API keys.
    ```bash
    php artisan migrate:fresh --seed
    ```

5.  **Serve the Application**
    ```bash
    php artisan serve
    ```
    API will be available at `http://localhost:8000`.

---

## 🧪 Testing

### Automated End-to-End (E2E) Test
We have a robust PowerShell script that tests the entire flow: Authentication -> Deposit -> Webhook Simulation -> Transfer -> Verification.

**Run the Test:**
```powershell
./tests/e2e_test.ps1
```

**What it does:**
1.  Resets and Seeds the Database.
2.  Captures API keys for Sender and Receiver.
3.  **Funds the Wallet**: Simulates a Paystack payment initialization and **manually triggers the webhook** with a valid HMAC-SHA512 signature to fund the wallet locally.
4.  **Transfers Money**: Moves funds from Sender to Receiver.
5.  **Verifies**: Checks if final balances match expected values.

---

## 📚 API Documentation (Swagger)

A full interactive API reference is built-in.

1.  Start the server: `php artisan serve`
2.  Visit: **[http://localhost:8000/api/documentation](http://localhost:8000/api/documentation)**

Use the **Authorize** button in Swagger to authenticate:
- **BearerAuth**: Enter your JWT token (get it from `/api/auth/login`).
- **ApiKey**: Enter your API Key (get it from the Seeder output or `/api/keys`).

---

## 🏗 Architecture Highlights

### Wallet Funding Flow
1.  **Initialize**: User requests deposit → Server calls Paystack → Returns Auth URL.
2.  **Payment**: User pays on Paystack (Simulated in tests).
3.  **Webhook**: Paystack calls `/api/wallet/paystack/webhook`.
4.  **Verification**: Server validates signature (`x-paystack-signature`), checks lock (`FOR UPDATE`), and credits wallet.

### Atomic Transfers
Transfers use database transactions to ensure data integrity:
1.  **Lock**: Both Sender and Receiver wallet rows are locked.
2.  **Validate**: Check balance and self-transfer rules.
3.  **Debit**: Deduct from Sender (Transaction `_OUT`).
4.  **Credit**: Add to Receiver (Transaction `_IN`).
5.  **Record**: Create Transfer record linking both transactions.
6.  **Commit**: Save all changes at once.

---

## 👥 Default Test Users (from Seeder)

| Role | Email | Password | Permissions |
|------|-------|----------|-------------|
| **Sender** | `sender@example.com` | `password` | `read`, `fund`, `transfer` |
| **Receiver** | `receiver@example.com` | `password` | `read`, `fund`, `transfer` |
| **Admin** | `admin@example.com` | `password` | All |
| **ReadOnly** | `readonly@example.com` | `password` | `read` only |
| **Investor** | `investor@example.com` | `password` | `read`, `fund` |

---
