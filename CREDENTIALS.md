# 🔐 Credentials untuk Gajayana Kost Backend

## 📋 User Default yang Sudah Dibuat

### 👤 **OWNER (Pemilik Kos)**

-   **Email:** `owner@test.com`
-   **Password:** `password123`
-   **Role:** `owner`
-   **Phone:** `081234567890`

### 👥 **PENYEWA (Society)**

-   **Email:** `penyewa@test.com`
-   **Password:** `password123`
-   **Role:** `society`
-   **Phone:** `081234567891`

## 🚀 Cara Menggunakan

### 1. **Login via API**

```bash
curl -X POST "http://backend-gajayana-kost.throoner.my.id/api/auth/login" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "owner@test.com",
    "password": "password123"
  }'
```

### 2. **Login via Flutter App**

-   Buka aplikasi Flutter
-   Masukkan email dan password sesuai credentials di atas
-   Pilih role sesuai kebutuhan (Owner atau Penyewa)

## 📝 Catatan

-   **Password untuk semua user default:** `password123`
-   **Backend URL:** `http://backend-gajayana-kost.throoner.my.id`
-   **Database:** MySQL (Docker container)
-   **Status:** User sudah dibuat di database, namun masih ada issue dengan password hashing yang perlu diperbaiki

## ⚠️ Troubleshooting

Jika login gagal, kemungkinan:

1. Password hash belum di-update dengan benar
2. Database connection issue
3. Backend container perlu di-restart

## 🔧 Perbaikan yang Diperlukan

Password user perlu di-update dengan hash yang benar menggunakan Laravel Hash::make(). Saat ini masih ada error "This password does not use the Bcrypt algorithm".
