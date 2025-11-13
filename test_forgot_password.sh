#!/bin/bash

echo "=== TESTING FORGOT PASSWORD FEATURE ==="
echo ""

# Test 1: Cek email yang tidak ada
echo "1. Test cek email yang TIDAK ADA (harus gagal):"
curl -s -X POST "https://gajayana-kost-backend-production-9d53.up.railway.app/api/auth/check-email" \
  -H "Content-Type: application/json" \
  -d '{"email": "nonexistent@test.com"}' | jq .
echo ""

# Test 2: Cek email yang ada
echo "2. Test cek email yang ADA (harus berhasil):"
curl -s -X POST "https://gajayana-kost-backend-production-9d53.up.railway.app/api/auth/check-email" \
  -H "Content-Type: application/json" \
  -d '{"email": "testowner@test.com"}' | jq .
echo ""

# Test 3: Reset password dengan email yang ada
echo "3. Test reset password dengan email yang ada:"
curl -s -X POST "https://gajayana-kost-backend-production-9d53.up.railway.app/api/auth/reset-password" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "testowner@test.com",
    "new_password": "forgotpassword123",
    "new_password_confirmation": "forgotpassword123"
  }' | jq .
echo ""

# Test 4: Login dengan password baru
echo "4. Test login dengan password baru:"
curl -s -X POST "https://gajayana-kost-backend-production-9d53.up.railway.app/api/auth/login" \
  -H "Content-Type: application/json" \
  -d '{"email": "testowner@test.com", "password": "forgotpassword123"}' | jq -r '.success, .message'
echo ""

# Test 5: Login dengan password lama (harus gagal)
echo "5. Test login dengan password lama (harus gagal):"
curl -s -X POST "https://gajayana-kost-backend-production-9d53.up.railway.app/api/auth/login" \
  -H "Content-Type: application/json" \
  -d '{"email": "testowner@test.com", "password": "mynewpassword456"}' | jq -r '.success, .message' 2>/dev/null || echo "Gagal (expected)"
echo ""

echo "=== RINGKASAN WORKFLOW FORGOT PASSWORD ==="
echo "✅ Step 1: POST /api/auth/check-email - Cek email ada atau tidak"
echo "✅ Step 2: POST /api/auth/reset-password - Reset password jika email ada"
echo "✅ Step 3: Login dengan password baru"
echo ""
echo "=== CARA PENGGUNAAN ==="
echo ""
echo "STEP 1 - Cek Email:"
echo "POST /api/auth/check-email"
echo "Body: {\"email\": \"user@example.com\"}"
echo ""
echo "STEP 2 - Reset Password:"
echo "POST /api/auth/reset-password"
echo "Body: {"
echo "  \"email\": \"user@example.com\","
echo "  \"new_password\": \"password_baru\","
echo "  \"new_password_confirmation\": \"password_baru\""
echo "}"
echo ""
echo "STEP 3 - Login:"
echo "POST /api/auth/login"
echo "Body: {\"email\": \"user@example.com\", \"password\": \"password_baru\"}"
