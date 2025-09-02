#!/bin/bash

echo "=== CREDENTIALS YANG BENAR-BENAR BISA DIGUNAKAN ==="
echo ""

echo "🔐 OWNER CREDENTIALS (Password: password123):"
echo "   Email: testowner@test.com"
echo "   Password: password123"
echo ""

echo "👥 SOCIETY CREDENTIALS (Password: password123):"
echo "   Email: testsociety@test.com"
echo "   Password: password123"
echo ""

echo "🔑 ADMIN CREDENTIALS (Password: password):"
echo "   Email: admin@test.com"
echo "   Password: password"
echo ""

echo "=== TEST LOGIN ==="
echo ""

echo "Test Owner Login:"
curl -s -X POST "https://gajayana-kost-backend-production-9d53.up.railway.app/api/auth/login" \
  -H "Content-Type: application/json" \
  -d '{"email": "testowner@test.com", "password": "password123"}' | jq -r '.success, .message, .data.user.email, .data.user.role'

echo ""
echo "Test Society Login:"
curl -s -X POST "https://gajayana-kost-backend-production-9d53.up.railway.app/api/auth/login" \
  -H "Content-Type: application/json" \
  -d '{"email": "testsociety@test.com", "password": "password123"}' | jq -r '.success, .message, .data.user.email, .data.user.role'

echo ""
echo "Test Admin Login:"
curl -s -X POST "https://gajayana-kost-backend-production-9d53.up.railway.app/api/auth/login" \
  -H "Content-Type: application/json" \
  -d '{"email": "admin@test.com", "password": "password"}' | jq -r '.success, .message, .data.user.email, .data.user.role'

echo ""
echo "=== CARA PENGGUNAAN ==="
echo ""
echo "1. Untuk testing Owner endpoints:"
echo "   Email: testowner@test.com"
echo "   Password: password123"
echo ""
echo "2. Untuk testing Society endpoints:"
echo "   Email: testsociety@test.com"
echo "   Password: password123"
echo ""
echo "3. Untuk testing Admin endpoints:"
echo "   Email: admin@test.com"
echo "   Password: password"
echo ""
echo "4. Untuk testing di Postman:"
echo "   - Method: POST"
echo "   - URL: https://gajayana-kost-backend-production-9d53.up.railway.app/api/auth/login"
echo "   - Headers: Content-Type: application/json"
echo "   - Body: {\"email\": \"EMAIL_DISINI\", \"password\": \"PASSWORD_DISINI\"}"
