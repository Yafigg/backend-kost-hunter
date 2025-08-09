#!/bin/bash

# Railway build script untuk Laravel
echo "🚀 Building Laravel for Railway..."

# Install dependencies
echo "📦 Installing Composer dependencies..."
composer install --no-dev --optimize-autoloader

# Copy environment file
echo "⚙️ Setting up environment..."
cp railway.env .env

# Generate application key
echo "🔑 Generating application key..."
php artisan key:generate --force

# Run migrations with force flag
echo "🗄️ Running database migrations..."
php artisan migrate --force --no-interaction

# Create storage link
echo "📁 Creating storage link..."
php artisan storage:link

# Optimize application
echo "⚡ Optimizing application..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "✅ Railway build completed successfully!"
