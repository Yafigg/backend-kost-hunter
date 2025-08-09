# 🚀 Gajayana Kost Backend - Deployment Guide

## 📋 Overview
Backend API untuk aplikasi pencarian dan booking kos/kost dengan Laravel 12 dan Supabase PostgreSQL.

## 🎯 Target Domain
- **Production**: `https://backend-gajayana-kost.throoner.my.id`
- **API Base URL**: `https://backend-gajayana-kost.throoner.my.id/api`

## 🗄️ Database Configuration
Menggunakan **Supabase PostgreSQL**:
```
postgresql://postgres:Socrates36@db.licatwuqqoddjhgjyrvb.supabase.co:5432/postgres
```

## 🛠️ Deployment Options

### Option 1: Render (Recommended - Free & Easy)

#### Langkah-langkah:
1. **Buat akun di [Render.com](https://render.com)**
2. **Connect GitHub repository**
3. **Create New Web Service**
4. **Pilih repository backend**
5. **Konfigurasi:**
   - **Name**: `gajayana-kost-backend`
   - **Environment**: `PHP`
   - **Build Command**: `composer install --no-dev --optimize-autoloader && php artisan key:generate --force && php artisan migrate --force`
   - **Start Command**: `php artisan serve --host=0.0.0.0 --port=$PORT`

#### Environment Variables di Render Dashboard:
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://gajayana-kost-backend.onrender.com
DB_CONNECTION=pgsql
DB_HOST=db.licatwuqqoddjhgjyrvb.supabase.co
DB_PORT=5432
DB_DATABASE=postgres
DB_USERNAME=postgres
DB_PASSWORD=Socrates36
DB_SSLMODE=require
CACHE_DRIVER=file
SESSION_DRIVER=file
QUEUE_CONNECTION=sync
LOG_CHANNEL=stack
LOG_LEVEL=error
```

#### Setup Custom Domain:
1. Di Render dashboard, pilih service
2. Settings → Custom Domains
3. Add domain: `backend-gajayana-kost.throoner.my.id`
4. Setup DNS CNAME record:
   ```
   Name: backend-gajayana-kost
   Type: CNAME
   Value: gajayana-kost-backend.onrender.com
   ```

### Option 2: Railway (Alternative)

#### Langkah-langkah:
1. **Buat akun di [Railway.app](https://railway.app)**
2. **Connect GitHub repository**
3. **Deploy from GitHub**
4. **Konfigurasi environment variables** (sama seperti Render)

### Option 3: Fly.io (Alternative)

#### Langkah-langkah:
1. **Install Fly CLI**: `curl -L https://fly.io/install.sh | sh`
2. **Login**: `fly auth login`
3. **Deploy**: `fly launch`
4. **Setup environment variables**

## 🌐 DNS Configuration

### CNAME Record (untuk PaaS)
```
Name: backend-gajayana-kost
Type: CNAME
Value: your-service-url.onrender.com
TTL: 300
```

## 🔧 Environment Variables

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

CACHE_DRIVER=file
SESSION_DRIVER=file
QUEUE_CONNECTION=sync
```

## 📊 Monitoring & Health Check

### Health Check URL
```
https://backend-gajayana-kost.throoner.my.id/api/kos
```

### Expected Response
```json
{
  "data": [],
  "message": "Kos retrieved successfully"
}
```

## 🚨 Troubleshooting

### Database Connection Issues
```bash
# Test dari local
curl -X GET "https://backend-gajayana-kost.throoner.my.id/api/kos" \
  -H "Accept: application/json"
```

### Common Issues:
1. **IPv6 Connection**: Supabase memerlukan IPv6 atau IPv4 add-on
2. **Environment Variables**: Pastikan semua env vars sudah diset
3. **Database Migration**: Jalankan migration di production
4. **SSL Certificate**: Otomatis dengan PaaS

## 📚 API Endpoints

### Public Endpoints
- `GET /api/kos` - List semua kos
- `GET /api/kos/{id}` - Detail kos
- `POST /api/auth/register` - Register user
- `POST /api/auth/login` - Login user

### Protected Endpoints (Auth Required)
- `GET /api/auth/user` - User profile
- `POST /api/auth/profile` - Update profile
- `GET /api/bookings` - User bookings
- `POST /api/bookings` - Create booking
- `GET /api/favorites` - User favorites

### Owner Endpoints (Owner Role Required)
- `GET /api/owner/kos` - Owner's kos list
- `POST /api/owner/kos` - Create kos
- `PUT /api/owner/kos/{id}` - Update kos
- `DELETE /api/owner/kos/{id}` - Delete kos

## 🎉 Success Indicators

✅ **Deployment Successful** jika:
- `curl -I https://backend-gajayana-kost.throoner.my.id/api/kos` returns 200
- Database connection berhasil
- SSL certificate aktif
- API endpoints berfungsi

## 📞 Quick Start Commands

### Render Deployment:
```bash
# 1. Push ke GitHub
git add .
git commit -m "Ready for deployment"
git push origin main

# 2. Deploy via Render dashboard
# 3. Setup custom domain
# 4. Test API
curl https://backend-gajayana-kost.throoner.my.id/api/kos
```

### Railway Deployment:
```bash
# 1. Install Railway CLI
npm i -g @railway/cli

# 2. Login dan deploy
railway login
railway init
railway up
```

## 🔒 Security Notes

- **Environment Variables**: Jangan commit password ke repository
- **Database**: Supabase sudah aman dengan SSL
- **API Keys**: Generate APP_KEY baru untuk production
- **CORS**: Setup jika frontend berbeda domain

## 📈 Performance Tips

- **Caching**: Enable Laravel caching
- **Database**: Supabase sudah optimized
- **CDN**: PaaS biasanya sudah include CDN
- **Monitoring**: Gunakan PaaS built-in monitoring
