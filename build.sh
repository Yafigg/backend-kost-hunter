#!/bin/bash

# Build script untuk Vercel deployment
echo "🚀 Building Gajayana Kost Backend for Vercel..."

# Install dependencies
echo "📦 Installing Composer dependencies..."
composer install --no-dev --optimize-autoloader

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

echo "✅ Build completed successfully!"
