# Digital Product Storefront & Software Licensing Platform

[![Laravel](https://img.shields.io/badge/Laravel-12.x-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)](https://laravel.com)
[![Filament](https://img.shields.io/badge/Filament-5.x-F59E0B?style=for-the-badge&logo=filament&logoColor=white)](https://filamentphp.com)
[![TailwindCSS](https://img.shields.io/badge/Tailwind-3.4-38B2AC?style=for-the-badge&logo=tailwind-css&logoColor=white)](https://tailwindcss.com)
[![Pest Tests](https://img.shields.io/badge/Pest%20Tests-133%20Passed-10B981?style=for-the-badge&logo=pest&logoColor=white)](https://pestphp.com)
[![PHP](https://img.shields.io/badge/PHP-8.4%2B-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://php.net)

A robust, enterprise-grade digital product marketplace and software licensing platform built with **Laravel 12**, **Filament 5.x**, **Tailwind CSS**, and **Alpine.js**.

Designed specifically for developers, creators, and digital software vendors selling themes, plugins, developer tools, SaaS starter kits, and technical ebooks both locally in Bangladesh (BDT) and internationally (USD).

---

## 🚀 Key Features

### 🛒 High-Converting Developer Storefront
- **Modern Aesthetics**: High-contrast, dark developer-tool aesthetic (Inter & JetBrains Mono typography).
- **Interactive Catalog**: Real-time product filtering by categories, price tiers, and multi-version compatibility (e.g., PHP 8.4+, Laravel 12.x).
- **Search Engine**: Integrated with **Laravel Scout** (`database` driver) indexing titles, summaries, tags, and rich descriptions with a dedicated `/search?q=` results page.
- **Frictionless Checkout**: Single-page checkout with an Alpine.js slide-over cart drawer, instant coupon discount validation, and automatic customer provisioning.

### 💳 Dual Payment Gateway Architecture
- **Local Bangladesh Payments**: Direct integration with **SSLCOMMERZ** (Cards, Mobile Banking, bKash, Nagad, Rocket) with mandatory server-to-server Order Validation API checks and IPN processing.
- **International Payments (Merchant of Record)**: Official **Paddle** integration using `laravel/cashier-paddle`:
  - Checkout currency and gateway toggle (Local BDT vs International USD Card).
  - Secure webhook handler for `transaction.completed` with **HMAC-SHA256 signature verification** and a strict **5-second replay tolerance window**.
  - Idempotent transaction processing and unified entitlement fulfillment.

### 🔑 Cryptographic Software Licensing Engine
- **Key Format**: Cryptographically secure keys formatted as `PROD-XXXX-XXXX-XXXX-XXXX`.
- **Zero Raw Key Storage**: Stores only SHA-256 hashed keys (`license_key_hash`) and masked display strings (`PROD-XXXX-XXXX-XXXX-88E0`). Raw keys are shown only once post-purchase and cached transiently for 30 minutes.
- **Seat Allocation & Remote APIs**:
  - `/api/v1/licenses/activate`: Validates license, verifies seats, and activates hostnames/fingerprints.
  - `/api/v1/licenses/validate`: Heartbeat verification checking status and revocation.
  - `/api/v1/licenses/deactivate`: Graceful remote device deactivations.
- **State Machine**: Full lifecycle management (`Active`, `Suspended`, `Revoked`, `Expired`).

### 📦 Secure Delivery Pipeline & Automated PDF Watermarking
- **Private S3 Storage**: Encrypted bucket storage for digital release binaries with temporary pre-signed S3 download URLs.
- **Quota Enforced Delivery**: Atomic attempt counters and expiration enforcement.
- **Automated PDF Watermarking**: Queued background job powered by FPDI/FPDF that stamps buyer email, order number, and timestamp onto each page footer of ebook/PDF products before delivery.

### 👤 Customer Self-Service Dashboard (`/library`)
- Passwordless **Magic Link** authentication and traditional credential access.
- Self-service license seat management with one-click device deactivations.
- Order history with on-demand branded PDF invoice generation (`Barryvdh\DomPDF`).
- Integrated customer support ticket portal and verified customer review submission.

### 🛠️ Filament 5.x Administration Back-Office
- **Commerce & Finance**: Orders management, financial masking, one-click refunds with automated license & download revocation, gateway webhook audit logs with replay actions, and discount coupon CRUD.
- **Licensing & Delivery**: License key lifecycle management, device activation audit trail, download grant quotas, and file scanner telemetry.
- **Customer Support & Moderation**: Support ticket replies and customer review moderation queue (approve, reject, merchant reply).
- **Fine-Grained RBAC**: Spatie Permissions matrix with predefined roles:
  - `Super Admin`
  - `Finance Manager`
  - `Catalog Editor`
  - `Support Agent`
  - `Review Moderator`

---

## 🛡️ Security Hardening

- **Rate Limiting**:
  - License APIs: `60 req/min` per IP (`throttle:licenses`).
  - Checkout submissions: `10 req/min` per IP (`throttle:checkout`).
  - Auth/Login requests: `5 req/min` per IP (`throttle:login`).
- **Security Headers Middleware**: Enforces `X-Frame-Options: SAMEORIGIN`, `X-Content-Type-Options: nosniff`, `X-XSS-Protection: 1; mode=block`, `Referrer-Policy`, `Permissions-Policy`, and baseline CSP.
- **Zero Sensitive Data Logging**: File paths and raw license keys are completely scrubbed from all application and system logs.
- **Form Sanitization**: Dedicated FormRequest classes (`CheckoutRequest`, `StoreReviewRequest`, `StoreSupportTicketRequest`, `MagicLinkRequest`) with HTML tag stripping and input normalization.
- **Composer Security**: `0` vulnerabilities reported via `composer audit`.

---

## 📋 System Requirements

- **PHP**: 8.3 or 8.4+
- **Extensions**: `pdo_mysql`, `mbstring`, `openssl`, `curl`, `zip`, `gd` or `imagick`, `bcmath`
- **Composer**: 2.x
- **Node.js**: 20.x+ & npm
- **Database**: MySQL 8.0+ or MariaDB 10.4+
- **Cache / Queue**: Redis (recommended) or Database driver

---

## ⚙️ Installation & Local Setup

### 1. Clone the Repository
```bash
git clone https://github.com/rashedislamrion/Digital-product-website.git
cd Digital-product-website
```

### 2. Install Dependencies
```bash
composer install
npm install
```

### 3. Configure Environment
```bash
cp .env.example .env
php artisan key:generate
```
Edit your `.env` file to configure your database credentials, AWS S3/Spaces keys, and payment gateway sandbox credentials:
```ini
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=digital_storefront
DB_USERNAME=root
DB_PASSWORD=
```

### 4. Run Migrations & Seeders
```bash
php artisan migrate:fresh --seed
```
*This provisions default categories, test products, prices, versions, administrative roles, permissions, and demo users.*

### 5. Compile Frontend Assets
```bash
npm run build
```

### 6. Start the Local Server
```bash
php artisan serve
```
Your application will be live at `http://127.0.0.1:8000`.

---

## 🔐 Default Demo Accounts

All seeded accounts use the default password: **`password`**

| Role | Email | Panel Access |
| :--- | :--- | :--- |
| **Super Admin** | `admin@example.com` | Full Filament Admin Panel (`/admin`) |
| **Finance Manager** | `finance@example.com` | Orders, Invoices, Refunds, Webhooks |
| **Catalog Editor** | `catalog@example.com` | Products, Versions, Media |
| **Support Agent** | `support@example.com` | Tickets, Licenses, Quota Resets |
| **Review Moderator**| `reviewer@example.com` | Reviews Moderation Queue |
| **Demo Customer** | `customer@example.com`| Customer Library (`/library`) |

---

## 🧪 Running Tests

The application is covered by a suite of 133 automated Pest tests covering storefront operations, cart, checkout, SSLCOMMERZ callbacks, Paddle webhooks, licensing state machines, downloads, watermarking, security headers, and admin RBAC:

```bash
php artisan test
```

To run a specific test suite:
```bash
php artisan test tests/Feature/MvpAdjacentAndHardeningTest.php
php artisan test tests/Feature/EntitlementFulfillmentTest.php
php artisan test tests/Feature/AdminPanelResourcesTest.php
```

---

## 🚢 Production Deployment

For complete details on configuring production queue workers (Laravel Horizon), S3 private bucket policies, SSL certificates, cron schedulers, and zero-downtime deployment scripts, please refer to:

👉 **[DEPLOYMENT.md](DEPLOYMENT.md)**

---

## 📄 License

This software is proprietary and confidential. All rights reserved.
