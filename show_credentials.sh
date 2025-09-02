#!/bin/bash

echo "=== SEMUA CREDENTIALS YANG TERSEDIA ==="
echo ""

# Login sebagai admin untuk mendapatkan token
echo "Login sebagai admin..."
ADMIN_RESPONSE=$(curl -s -X POST "https://gajayana-kost-backend-production-9d53.up.railway.app/api/auth/login" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@test.com",
    "password": "password"
  }')

ADMIN_TOKEN=$(echo "$ADMIN_RESPONSE" | jq -r '.data.token')

if [ "$ADMIN_TOKEN" = "null" ] || [ -z "$ADMIN_TOKEN" ]; then
    echo "❌ Gagal login sebagai admin"
    exit 1
fi

echo "✅ Login berhasil!"
echo ""

# Get all users
echo "Mengambil data semua users..."
USERS_RESPONSE=$(curl -s -X GET "https://gajayana-kost-backend-production-9d53.up.railway.app/api/users" \
  -H "Authorization: Bearer $ADMIN_TOKEN" \
  -H "Accept: application/json")

echo "$USERS_RESPONSE" | jq -r '.data.data[] | "📧 Email: \(.email) | 🔑 Password: password | 👤 Role: \(.role) | 📱 Phone: \(.phone) | 📅 Created: \(.created_at)"'

echo ""
echo "=== RINGKASAN CREDENTIALS ==="
echo ""

# Extract and format credentials
echo "$USERS_RESPONSE" | jq -r '.data.data[] | "\(.email):password (\(.role))"' | sort

echo ""
echo "=== CREDENTIALS UNTUK TESTING ==="
echo ""

# Show specific credentials for testing
echo "🔐 OWNER CREDENTIALS:"
echo "$USERS_RESPONSE" | jq -r '.data.data[] | select(.role == "owner") | "   Email: \(.email) | Password: password"'

echo ""
echo "👥 SOCIETY CREDENTIALS:"
echo "$USERS_RESPONSE" | jq -r '.data.data[] | select(.role == "society") | "   Email: \(.email) | Password: password"'

echo ""
echo "=== CARA LOGIN ==="
echo "curl -X POST \"https://gajayana-kost-backend-production-9d53.up.railway.app/api/auth/login\" \\"
echo "  -H \"Content-Type: application/json\" \\"
echo "  -d '{\"email\": \"EMAIL_DISINI\", \"password\": \"password\"}'"
