# API Testing Guide - Gajayana Kost Backend

## 1. Health Check Test

Method: GET
Endpoint: https://backend-gajayana-kost.throoner.my.id/api/health
Json: No body required

## 2. Test Endpoint

Method: GET
Endpoint: https://backend-gajayana-kost.throoner.my.id/api/test
Json: No body required

## 3. User Registration Test

Method: POST
Endpoint: https://backend-gajayana-kost.throoner.my.id/api/auth/register
Json:

```json
{
    "name": "Test User New",
    "email": "newuser@example.com",
    "phone": "081234567899",
    "password": "password",
    "password_confirmation": "password",
    "role": "society"
}
```

## 4. User Login Test

Method: POST
Endpoint: https://backend-gajayana-kost.throoner.my.id/api/auth/login
Json:

```json
{
    "email": "owner@production.com",
    "password": "password"
}
```

## 5. Get User Profile Test

Method: GET
Endpoint: https://backend-gajayana-kost.throoner.my.id/api/user
Json: No body required
Headers: Authorization: Bearer {YOUR_TOKEN}

## 6. User Logout Test

Method: POST
Endpoint: https://backend-gajayana-kost.throoner.my.id/api/auth/logout
Json: No body required
Headers: Authorization: Bearer {YOUR_TOKEN}

## 7. Get All Users Test (Admin)

Method: GET
Endpoint: https://backend-gajayana-kost.throoner.my.id/api/users
Json: No body required
Headers: Authorization: Bearer {YOUR_TOKEN}

## 8. Get All Kos Test (Public)

Method: GET
Endpoint: https://backend-gajayana-kost.throoner.my.id/api/kos
Json: No body required

## 9. Get Kos by ID Test (Public)

Method: GET
Endpoint: https://backend-gajayana-kost.throoner.my.id/api/kos/1
Json: No body required

## 10. Create Kos Test (Owner Only)

Method: POST
Endpoint: https://backend-gajayana-kost.throoner.my.id/api/owner/kos
Headers: Authorization: Bearer {YOUR_TOKEN}
Json:

```json
{
    "name": "Kos Gajayana Premium 2",
    "address": "Jl. Gajayana No. 456, Malang",
    "description": "Kos premium dengan fasilitas lengkap dan modern",
    "price_per_month": 2000000,
    "gender": "all",
    "whatsapp_number": "081234567890",
    "latitude": "-7.98390000",
    "longitude": "112.62140000"
}
```

## 11. Update Kos Test (Owner Only)

Method: PUT
Endpoint: https://backend-gajayana-kost.throoner.my.id/api/owner/kos/1
Headers: Authorization: Bearer {YOUR_TOKEN}
Json:

```json
{
    "name": "Kos Gajayana Premium Updated",
    "price_per_month": 1800000
}
```

## 12. Delete Kos Test (Owner Only)

Method: DELETE
Endpoint: https://backend-gajayana-kost.throoner.my.id/api/owner/kos/2
Headers: Authorization: Bearer {YOUR_TOKEN}
Json: No body required

## 13. Get Rooms by Kos ID Test (Public)

Method: GET
Endpoint: https://backend-gajayana-kost.throoner.my.id/api/kos/1/rooms
Json: No body required

## 14. Add Rooms to Kos Test (Owner Only)

Method: POST
Endpoint: https://backend-gajayana-kost.throoner.my.id/api/owner/kos/1/rooms
Headers: Authorization: Bearer {YOUR_TOKEN}
Json:

```json
{
    "rooms": [
        {
            "room_number": "A1",
            "room_type": "single",
            "price_per_month": 1500000,
            "is_available": true,
            "description": "Kamar single dengan AC dan kamar mandi dalam"
        },
        {
            "room_number": "A2",
            "room_type": "double",
            "price_per_month": 2000000,
            "is_available": true,
            "description": "Kamar double dengan AC, kamar mandi dalam, dan dapur"
        }
    ]
}
```

## 15. Add Facilities to Kos Test (Owner Only)

Method: POST
Endpoint: https://backend-gajayana-kost.throoner.my.id/api/owner/kos/1/facilities
Headers: Authorization: Bearer {YOUR_TOKEN}
Json:

```json
{
    "facilities": [
        {
            "facility": "AC",
            "icon": "ac"
        },
        {
            "facility": "Kamar Mandi Dalam",
            "icon": "bathroom"
        },
        {
            "facility": "Dapur",
            "icon": "kitchen"
        },
        {
            "facility": "Wifi",
            "icon": "wifi"
        },
        {
            "facility": "Parkir Motor",
            "icon": "parking"
        }
    ]
}
```

## 16. Add Payment Methods to Kos Test (Owner Only)

Method: POST
Endpoint: https://backend-gajayana-kost.throoner.my.id/api/owner/kos/1/payment-methods
Headers: Authorization: Bearer {YOUR_TOKEN}
Json:

```json
{
    "payment_methods": [
        {
            "bank_name": "BCA",
            "account_number": "1234567890",
            "account_name": "Gajayana Kost",
            "type": "bank_transfer"
        },
        {
            "bank_name": "Mandiri",
            "account_number": "0987654321",
            "account_name": "Gajayana Kost",
            "type": "bank_transfer"
        }
    ]
}
```

## 17. Create Booking Test (Society Only)

Method: POST
Endpoint: https://backend-gajayana-kost.throoner.my.id/api/bookings
Headers: Authorization: Bearer {YOUR_TOKEN}
Json:

```json
{
    "kos_id": 1,
    "room_id": 1,
    "start_date": "2025-09-01",
    "end_date": "2026-08-31",
    "total_amount": 1500000,
    "payment_method_id": 1
}
```

## 18. Get User Bookings Test

Method: GET
Endpoint: https://backend-gajayana-kost.throoner.my.id/api/bookings
Headers: Authorization: Bearer {YOUR_TOKEN}
Json: No body required

## 19. Create Review Test (Society Only)

Method: POST
Endpoint: https://backend-gajayana-kost.throoner.my.id/api/reviews
Headers: Authorization: Bearer {YOUR_TOKEN}
Json:

```json
{
    "kos_id": 1,
    "rating": 5,
    "comment": "Kos yang sangat nyaman dan bersih! Fasilitas lengkap dan harga terjangkau."
}
```

## 20. Get Reviews by Kos ID Test (Public)

Method: GET
Endpoint: https://backend-gajayana-kost.throoner.my.id/api/kos/1/reviews
Json: No body required

## 21. Add to Favorites Test (Society Only)

Method: POST
Endpoint: https://backend-gajayana-kost.throoner.my.id/api/favorites
Headers: Authorization: Bearer {YOUR_TOKEN}
Json:

```json
{
    "kos_id": 1
}
```

## 22. Get User Favorites Test

Method: GET
Endpoint: https://backend-gajayana-kost.throoner.my.id/api/favorites
Headers: Authorization: Bearer {YOUR_TOKEN}
Json: No body required

## 23. Remove from Favorites Test

Method: DELETE
Endpoint: https://backend-gajayana-kost.throoner.my.id/api/favorites/1
Headers: Authorization: Bearer {YOUR_TOKEN}
Json: No body required

## Notes:

-   Ganti {YOUR_TOKEN} dengan token yang didapat dari login
-   Untuk endpoint yang memerlukan ID, ganti dengan ID yang sesuai
-   Endpoint public tidak memerlukan Authorization header
-   Endpoint owner hanya bisa diakses oleh user dengan role owner
-   Endpoint society hanya bisa diakses oleh user dengan role society
