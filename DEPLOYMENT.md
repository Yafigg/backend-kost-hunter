# 🚀 Gajayana Kost Backend - Deployment Guide

## 📋 Overview

Backend API untuk aplikasi pencarian dan booking kos/kost dengan Laravel 12 dan Supabase PostgreSQL.

## 🎯 Target Domain

-   **Production**: `https://backend-gajayana-kost.throoner.my.id`
-   **API Base URL**: `https://backend-gajayana-kost.throoner.my.id/api`

## 🗄️ Database Configuration

Menggunakan **Supabase PostgreSQL** dengan connection string:

```
postgresql://postgres:Socrates36@db.licatwuqqoddjhgjyrvb.supabase.co:5432/postgres
```

### ⚠️ IPv6 Issue Resolution

Jika mengalami masalah koneksi IPv6, gunakan salah satu solusi:

1. **VPS dengan IPv6 Support**
2. **Supabase IPv4 Add-on** (dari dashboard Supabase)
3. **Connection Pooler** dengan format yang benar

## 🛠️ Deployment Options

### Option 1: VPS Ubuntu (Recommended)

#### Prerequisites

-   Ubuntu 22.04+ VPS
-   Domain `throoner.my.id` dengan akses DNS
-   SSH access ke VPS

#### Quick Deployment

```bash
# 1. Clone repository ke VPS
git clone <your-repo> /var/www/gajayana-backend
cd /var/www/gajayana-backend

# 2. Run deployment script
./deploy.sh production

# 3. Setup DNS
# A record: backend-gajayana-kost.throoner.my.id -> VPS_IP
```

#### Manual Deployment Steps

```bash
# 1. Install dependencies
sudo apt update && sudo apt upgrade -y
sudo apt install -y nginx php8.3-fpm php8.3-{pgsql,mbstring,xml,gd,curl,zip,bcmath} \
  certbot python3-certbot-nginx git unzip composer

# 2. Setup application
sudo mkdir -p /var/www/gajayana-backend
sudo chown -R $USER:$USER /var/www/gajayana-backend
cp -r . /var/www/gajayana-backend/
cd /var/www/gajayana-backend

# 3. Install Composer dependencies
composer install --no-dev --optimize-autoloader

# 4. Configure environment
# Edit .env dengan konfigurasi production

# 5. Setup database
php artisan key:generate --force
php artisan migrate --force
php artisan storage:link

# 6. Optimize
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 7. Set permissions
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache

# 8. Configure Nginx (lihat file deploy.sh untuk konfigurasi lengkap)
# 9. Setup SSL dengan Certbot
# 10. Configure firewall
```

### Option 2: PaaS (Render/Railway/Fly.io)

#### Environment Variables

```env
APP_NAME="Gajayana Kost"
APP_ENV=production
APP_KEY=base64:...
APP_URL=https://backend-gajayana-kost.throoner.my.id
APP_DEBUG=false

DB_CONNECTION=pgsql
DB_HOST=db.licatwuqqoddjhgjyrvb.supabase.co
DB_PORT=5432
DB_DATABASE=postgres
DB_USERNAME=postgres
DB_PASSWORD=Socrates36
DB_SSLMODE=require

SESSION_DOMAIN=.throoner.my.id
SANCTUM_STATEFUL_DOMAINS=*.throoner.my.id
```

#### Build Commands

```bash
composer install --no-dev --optimize-autoloader
php artisan key:generate --force
php artisan migrate --force
php artisan optimize
```

#### Start Commands

-   **Apache**: `heroku-php-apache2 public/`
-   **Nginx**: Sesuaikan dengan platform

## 🌐 DNS Configuration

### A Record (untuk VPS)

```
Name: backend-gajayana-kost
Type: A
Value: YOUR_VPS_IP
TTL: 300
```

### CNAME Record (untuk PaaS)

```
Name: backend-gajayana-kost
Type: CNAME
Value: your-service.onrender.com
TTL: 300
```

## 🔧 Environment Configuration

### Production .env

```env
APP_NAME="Gajayana Kost"
APP_ENV=production
APP_KEY=base64:...
APP_URL=https://backend-gajayana-kost.throoner.my.id
APP_DEBUG=false

LOG_CHANNEL=stack
LOG_LEVEL=error

DB_CONNECTION=pgsql
DB_HOST=db.licatwuqqoddjhgjyrvb.supabase.co
DB_PORT=5432
DB_DATABASE=postgres
DB_USERNAME=postgres
DB_PASSWORD=Socrates36
DB_SSLMODE=require

SESSION_DOMAIN=.throoner.my.id
SANCTUM_STATEFUL_DOMAINS=*.throoner.my.id

CACHE_DRIVER=file
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
```

## 🔒 Security Considerations

### SSL/TLS

-   Otomatis dengan Let's Encrypt (VPS)
-   Platform-managed (PaaS)

### Headers

```nginx
add_header X-Frame-Options "SAMEORIGIN" always;
add_header X-XSS-Protection "1; mode=block" always;
add_header X-Content-Type-Options "nosniff" always;
add_header Referrer-Policy "no-referrer-when-downgrade" always;
```

### File Access

```nginx
location ~ /(vendor|storage|bootstrap|config|database|resources|routes|tests) {
    deny all;
}
```

## 📊 Monitoring & Maintenance

### Health Check

```bash
curl -I https://backend-gajayana-kost.throoner.my.id/api/kos
```

### Logs

```bash
# Laravel logs
tail -f storage/logs/laravel.log

# Nginx logs
sudo tail -f /var/log/nginx/access.log
sudo tail -f /var/log/nginx/error.log
```

### Database Connection Test

```bash
php artisan tinker
>>> DB::connection()->getPdo();
```

## 🚨 Troubleshooting

### IPv6 Connection Issues

```bash
# Test IPv6 connectivity
ping6 db.licatwuqqoddjhgjyrvb.supabase.co

# Alternative: Use IPv4 add-on dari Supabase dashboard
```

### Database Connection

```bash
# Test connection
php artisan db:show

# Check PostgreSQL driver
php -m | grep pgsql
```

### Permission Issues

```bash
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

## 📚 API Endpoints

### Public Endpoints

-   `GET /api/kos` - List semua kos
-   `GET /api/kos/{id}` - Detail kos
-   `POST /api/auth/register` - Register user
-   `POST /api/auth/login` - Login user

### Protected Endpoints (Auth Required)

-   `GET /api/auth/user` - User profile
-   `POST /api/auth/profile` - Update profile
-   `GET /api/bookings` - User bookings
-   `POST /api/bookings` - Create booking
-   `GET /api/favorites` - User favorites

### Owner Endpoints (Owner Role Required)

-   `GET /api/owner/kos` - Owner's kos list
-   `POST /api/owner/kos` - Create kos
-   `PUT /api/owner/kos/{id}` - Update kos
-   `DELETE /api/owner/kos/{id}` - Delete kos

## 🎉 Success Indicators

✅ **Deployment Successful** jika:

-   `curl -I https://backend-gajayana-kost.throoner.my.id/api/kos` returns 200
-   Database connection berhasil
-   SSL certificate aktif
-   API endpoints berfungsi

## 📞 Support

Jika mengalami masalah:

1. Cek logs: `tail -f storage/logs/laravel.log`
2. Test database connection
3. Verify DNS propagation
4. Check firewall settings
