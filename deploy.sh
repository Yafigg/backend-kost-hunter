#!/bin/bash

# Gajayana Kost Backend Deployment Script
# Usage: ./deploy.sh [production|staging]

set -e

ENVIRONMENT=${1:-production}
DOMAIN="backend-gajayana-kost.throoner.my.id"

echo "🚀 Deploying Gajayana Kost Backend to $ENVIRONMENT..."

# Update system
echo "📦 Updating system packages..."
sudo apt update && sudo apt upgrade -y

# Install dependencies
echo "🔧 Installing dependencies..."
sudo apt install -y nginx php8.3-fpm php8.3-{pgsql,mbstring,xml,gd,curl,zip,bcmath} \
  certbot python3-certbot-nginx git unzip composer

# Create application directory
echo "📁 Setting up application directory..."
sudo mkdir -p /var/www/gajayana-backend
sudo chown -R $USER:$USER /var/www/gajayana-backend

# Clone/copy application
echo "📋 Copying application files..."
cp -r . /var/www/gajayana-backend/
cd /var/www/gajayana-backend

# Install Composer dependencies
echo "📚 Installing Composer dependencies..."
composer install --no-dev --optimize-autoloader

# Set up environment
echo "⚙️ Configuring environment..."
if [ "$ENVIRONMENT" = "production" ]; then
    # Production environment
    cat > .env << EOF
APP_NAME="Gajayana Kost"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://$DOMAIN

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=error

DB_CONNECTION=pgsql
DB_HOST=db.licatwuqqoddjhgjyrvb.supabase.co
DB_PORT=5432
DB_DATABASE=postgres
DB_USERNAME=postgres
DB_PASSWORD=Socrates36
DB_SSLMODE=require

BROADCAST_DRIVER=log
CACHE_DRIVER=file
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
SESSION_LIFETIME=120
SESSION_DOMAIN=.throoner.my.id

MEMCACHED_HOST=127.0.0.1

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="noreply@$DOMAIN"
MAIL_FROM_NAME="${APP_NAME}"

SANCTUM_STATEFUL_DOMAINS=*.throoner.my.id
EOF
else
    # Staging environment
    cp .env.example .env
fi

# Generate application key
echo "🔑 Generating application key..."
php artisan key:generate --force

# Run migrations
echo "🗄️ Running database migrations..."
php artisan migrate --force

# Create storage link
echo "📁 Creating storage link..."
php artisan storage:link

# Optimize application
echo "⚡ Optimizing application..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Set permissions
echo "🔐 Setting permissions..."
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache

# Configure Nginx
echo "🌐 Configuring Nginx..."
sudo tee /etc/nginx/sites-available/gajayana-backend << EOF
server {
    listen 80;
    server_name $DOMAIN;
    root /var/www/gajayana-backend/public;
    index index.php;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;
    add_header Content-Security-Policy "default-src 'self' http: https: data: blob: 'unsafe-inline'" always;

    # Handle Laravel routes
    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    # Handle PHP files
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
    }

    # Cache static files
    location ~* \.(jpg|jpeg|png|gif|svg|css|js|ico|woff2?)$ {
        expires 7d;
        access_log off;
        add_header Cache-Control "public, immutable";
    }

    # Deny access to sensitive files
    location ~ /\. {
        deny all;
    }

    location ~ /(vendor|storage|bootstrap|config|database|resources|routes|tests) {
        deny all;
    }
}
EOF

# Enable site
echo "🔗 Enabling Nginx site..."
sudo ln -sf /etc/nginx/sites-available/gajayana-backend /etc/nginx/sites-enabled/
sudo rm -f /etc/nginx/sites-enabled/default
sudo nginx -t
sudo systemctl reload nginx

# Setup SSL with Let's Encrypt
echo "🔒 Setting up SSL certificate..."
sudo certbot --nginx -d $DOMAIN --redirect -n --agree-tos -m admin@throoner.my.id

# Setup firewall
echo "🛡️ Configuring firewall..."
sudo ufw allow 'Nginx Full'
sudo ufw allow OpenSSH
sudo ufw --force enable

echo "✅ Deployment completed successfully!"
echo "🌐 Your API is now available at: https://$DOMAIN"
echo "📚 API Documentation: https://$DOMAIN/api/kos"
