# Production Deployment Guide: Single-Seller Digital Product Storefront

This guide documents production deployment procedures, required environment variables, queue worker management via Laravel Horizon, S3 bucket security policies, and Laravel scheduler configuration per **@research.md §§5.1, 6.1, 6.2, and 8.1**.

---

## 1. Required Production Environment Variables (`.env`)

Ensure the following variables are configured in your production environment:

```env
# Core Application Configuration
APP_NAME="DevStore Pro"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourstore.com
APP_KEY=base64:... # Generate via php artisan key:generate --show

# Primary Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=digital_storefront_prod
DB_USERNAME=storefront_user
DB_PASSWORD="<SECURE_STRONG_PASSWORD>"

# Queue & Cache (Redis recommended for production & Horizon)
QUEUE_CONNECTION=redis
CACHE_STORE=redis
SESSION_DRIVER=redis
REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=null

# Laravel Scout (Database driver for low/medium scale MVP per research §3.2)
SCOUT_DRIVER=database

# Private S3-Compatible Secure Storage Tier (@research.md §5.1)
AWS_ACCESS_KEY_ID="<IAM_ACCESS_KEY>"
AWS_SECRET_ACCESS_KEY="<IAM_SECRET_KEY>"
AWS_DEFAULT_REGION="us-east-1"
AWS_BUCKET_SECURE="devstore-releases-private"
AWS_USE_PATH_STYLE_ENDPOINT=false

# Local Payment Adapter: SSLCOMMERZ v4 (@research.md §6.1)
SSLCOMMERZ_STORE_ID="<STORE_ID>"
SSLCOMMERZ_STORE_PASSWORD="<STORE_PASSWORD>"
SSLCOMMERZ_SANDBOX=false # Set to false for live production transactions

# International Merchant of Record: Paddle Billing v2 (@research.md §6.2)
PADDLE_VENDOR_ID="<PADDLE_VENDOR_ID>"
PADDLE_API_KEY="<PADDLE_API_KEY>"
PADDLE_WEBHOOK_SECRET="<PADDLE_WEBHOOK_SECRET>" # Used for HMAC-SHA256 verification
PADDLE_SANDBOX=false

# Production Mail Delivery
MAIL_MAILER=smtp
MAIL_HOST=smtp.postmarkapp.com # Or SendGrid, SES, Mailgun
MAIL_PORT=587
MAIL_USERNAME="<POSTMARK_API_TOKEN>"
MAIL_PASSWORD="<POSTMARK_API_TOKEN>"
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="orders@yourstore.com"
MAIL_FROM_NAME="DevStore Pro Orders"
```

---

## 2. Private S3 Bucket Policy (Block All Public Access)

Master binary packages, product versions, and watermarked customer PDFs must **never** be publicly accessible. All access is brokered via ephemeral signed URLs (10-minute validity) generated server-side.

### 2.1 AWS S3 Public Access Block
Enable all four public access blocks on your releases bucket (`AWS_BUCKET_SECURE`):
- `BlockPublicAcls`: **TRUE**
- `IgnorePublicAcls`: **TRUE**
- `BlockPublicPolicy`: **TRUE**
- `RestrictPublicBuckets`: **TRUE**

### 2.2 S3 Bucket Policy Example
Restrict bucket access strictly to the application's dedicated IAM service user:

```json
{
  "Version": "2012-10-17",
  "Statement": [
    {
      "Sid": "AllowAppIAMUserOnly",
      "Effect": "Allow",
      "Principal": {
        "AWS": "arn:aws:iam::ACCOUNT_ID:user/devstore-app-uploader"
      },
      "Action": [
        "s3:GetObject",
        "s3:PutObject",
        "s3:DeleteObject"
      ],
      "Resource": "arn:aws:s3:::devstore-releases-private/*"
    }
  ]
}
```

### 2.3 S3 CORS Configuration (for Download Pre-signed URLs)
```json
[
  {
    "AllowedHeaders": ["*"],
    "AllowedMethods": ["GET", "HEAD"],
    "AllowedOrigins": ["https://yourstore.com"],
    "ExposeHeaders": ["ETag", "Content-Disposition"],
    "MaxAgeSeconds": 3600
  }
]
```

---

## 3. Queue Worker Setup (Laravel Horizon)

Asynchronous fulfillment (`GrantOrderEntitlements`, `OrderConfirmationMail`, `ScanProductFileJob`, and `WatermarkPdfProductJob`) requires a reliable queue supervisor.

### 3.1 Install & Configure Laravel Horizon
If running multi-process queues:
```sh
composer require laravel/horizon
php artisan horizon:install
```

### 3.2 Systemd Service (`/etc/systemd/system/horizon.service`)
```ini
[Unit]
Description=Laravel Horizon Queue Manager
After=network.target redis-server.service

[Service]
Type=simple
User=www-data
Group=www-data
Restart=always
RestartSec=3
WorkingDirectory=/var/www/digital-storefront
ExecStart=/usr/bin/php /var/www/digital-storefront/artisan horizon
ExecStop=/usr/bin/php /var/www/digital-storefront/artisan horizon:terminate

[Install]
WantedBy=multi-user.target
```

Enable and start the service:
```sh
sudo systemctl daemon-reload
sudo systemctl enable --now horizon.service
sudo systemctl status horizon.service
```

*(Alternative: Use `supervisor` running `php artisan queue:work redis --sleep=3 --tries=3 --max-time=3600`)*

---

## 4. Laravel Scheduler (Cron Job Configuration)

The scheduler executes periodic tasks (cart-abandonment sequences, expiring license cleanup, rate-limiter pruning, and weekly financial aggregates).

Add the standard Laravel cron entry to the `www-data` crontab:

```sh
sudo crontab -u www-data -e
```

Add the following line:
```cron
* * * * * cd /var/www/digital-storefront && php artisan schedule:run >> /dev/null 2>&1
```

---

## 5. Deployment Script / CI-CD Pipeline Steps

Execute on every deployment:

```sh
# 1. Enter maintenance mode
php artisan down --render="errors::503"

# 2. Pull code & install production dependencies
git pull origin main
composer install --no-dev --optimize-autoloader

# 3. Database migrations
php artisan migrate --force

# 4. Cache configurations, routes, and views
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan filament:optimize

# 5. Build frontend production assets
npm ci
npm run build

# 6. Synchronize Scout Search Indexes
php artisan scout:import "App\Models\Product"

# 7. Restart Horizon Queue Workers
php artisan horizon:terminate

# 8. Exit maintenance mode
php artisan up
```
