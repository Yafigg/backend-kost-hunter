#!/bin/bash

echo "=== CHECKING ALL USER CREDENTIALS ==="
echo ""

# Test connection to Railway domain
echo "Testing connection to Railway domain..."
curl -s -X GET "https://gajayana-kost-backend-production-9d53.up.railway.app/api/health" | jq .

echo ""
echo "=== GETTING ALL USERS ==="

# Get all users (this should work without authentication for now)
echo "Fetching all users from database..."
curl -s -X GET "https://gajayana-kost-backend-production-9d53.up.railway.app/api/users" | jq .

echo ""
echo "=== TESTING LOGIN WITH KNOWN CREDENTIALS ==="

# Test login with owner credentials
echo "Testing owner login..."
OWNER_RESPONSE=$(curl -s -X POST "https://gajayana-kost-backend-production-9d53.up.railway.app/api/auth/login" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "owner@production.com",
    "password": "password"
  }')

echo "Owner login response:"
echo "$OWNER_RESPONSE" | jq .

# Test login with society credentials
echo ""
echo "Testing society login..."
SOCIETY_RESPONSE=$(curl -s -X POST "https://gajayana-kost-backend-production-9d53.up.railway.app/api/auth/login" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "society@production.com",
    "password": "password"
  }')

echo "Society login response:"
echo "$SOCIETY_RESPONSE" | jq .

echo ""
echo "=== TESTING REGISTRATION ==="

# Test registration to see what fields are required
echo "Testing registration endpoint..."
REGISTER_RESPONSE=$(curl -s -X POST "https://gajayana-kost-backend-production-9d53.up.railway.app/api/auth/register" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Test User",
    "email": "test@example.com",
    "password": "password",
    "password_confirmation": "password",
    "phone": "081234567890",
    "role": "society"
  }')

echo "Registration response:"
echo "$REGISTER_RESPONSE" | jq .

echo ""
echo "=== SUMMARY ==="
echo "1. Check if /api/users endpoint works (should show all users)"
echo "2. Test login with owner@production.com / password"
echo "3. Test login with society@production.com / password"
echo "4. Test registration to see required fields"
echo ""
echo "If /api/users doesn't work, we need to check the database directly or use a different approach."
