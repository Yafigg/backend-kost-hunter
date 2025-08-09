#!/bin/bash

# Script untuk menjalankan migration di Supabase
echo "🚀 Running Laravel migrations on Supabase..."

# Set environment variables untuk Supabase
export DB_CONNECTION=pgsql
export DB_HOST=aws-0-ap-southeast-1.pooler.supabase.com
export DB_PORT=6543
export DB_DATABASE=postgres
export DB_USERNAME=postgres.licatwuqqoddjhgjyrvb
export DB_PASSWORD=Socrates36
export DB_SSLMODE=require

# Jalankan migration
echo "📦 Running migrations..."
php artisan migrate --force

# Jalankan seeder
echo "🌱 Running seeders..."
php artisan db:seed --force

echo "✅ Supabase database setup completed!"
