# RFLMS API v1 — Mobile vendor contract (draft)

**Status:** Draft for vendor integration (Phase 1)  
**Base URL (local):** `http://127.0.0.1:8000/api/v1`  
**Auth:** Laravel Sanctum — `Authorization: Bearer {token}`  
**Format:** JSON (`Accept: application/json`)  
**Multipart:** use `multipart/form-data` only when uploading files (receipt / photos)

> Replace the base URL with staging/production when deployed. Keep path prefix `/api/v1`.

---

## Quick start

1. `GET /districts` and `GET /license-categories` (no auth).
2. `POST /auth/register` → OTP → `POST /auth/verify-otp` **or** `POST /auth/login`.
3. `PUT /profile` until `profile_complete: true`.
4. `GET /reservoirs?q=Kundal` → pick eligible water body (`is_open_for_licensing: true`).
5. `POST /licenses/applications` (multipart if bank receipt).
6. Poll `GET /licenses/applications` / `GET /licenses` after staff approval.
7. Public QR: `GET /licenses/verify/{qr_token}`.

### curl login example

```bash
curl -s -X POST http://127.0.0.1:8000/api/v1/auth/login \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"email":"citizen@example.com","password":"Password123!"}'
```

Use returned `token`:

```bash
curl -s http://127.0.0.1:8000/api/v1/auth/me \
  -H "Accept: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### Headers

| Header | When |
|--------|------|
| `Accept: application/json` | Always |
| `Content-Type: application/json` | JSON bodies |
| `Authorization: Bearer {token}` | Protected routes |
| `Content-Type: multipart/form-data` | File uploads (let client set boundary) |

---

## Error shape

**Validation (422)** — Laravel style:

```json
{
  "message": "The email field is required.",
  "errors": {
    "email": ["The email field is required."]
  }
}
```

**Business codes (apply)** — also 422:

| `code` | Meaning |
|--------|---------|
| `PROFILE_INCOMPLETE` | Complete `PUT /profile` first |
| `NOT_ELIGIBLE` | Water body not open for e-licence |
| `CLOSED_SEASON` | Fishing start date falls in closed season |

**Auth**

| Status | Meaning |
|--------|---------|
| 401 | Missing/invalid token |
| 403 | Wrong role (e.g. citizen calling officer APIs) |
| 404 | Resource not found |

---

## Auth

### `POST /auth/register` — public

Request:

```json
{
  "name": "Ali Khan",
  "email": "ali@example.com",
  "cnic": "17301-1234567-1"
}
```

Response `200`:

```json
{
  "message": "OTP sent",
  "email": "ali@example.com",
  "expires_in_seconds": 600,
  "resend_in_seconds": 60,
  "debug_otp": "123456"
}
```

`debug_otp` only when `APP_DEBUG=true`.

---

### `POST /auth/verify-otp` — public

Request:

```json
{
  "email": "ali@example.com",
  "otp": "123456",
  "password": "Password123!",
  "password_confirmation": "Password123!"
}
```

Response `201`:

```json
{
  "message": "Registered",
  "token": "1|xxxxxxxxxxxxxxxx",
  "user": {
    "id": 10,
    "name": "Ali Khan",
    "email": "ali@example.com",
    "mobile": null,
    "user_type": "citizen",
    "profile_complete": false
  }
}
```

---

### `POST /auth/login` — public

Request:

```json
{
  "email": "ali@example.com",
  "password": "Password123!"
}
```

Response `200`:

```json
{
  "token": "2|xxxxxxxxxxxxxxxx",
  "user": {
    "id": 10,
    "name": "Ali Khan",
    "email": "ali@example.com",
    "mobile": "03001234567",
    "user_type": "citizen",
    "profile_complete": true
  }
}
```

`user_type`: `citizen` | `admin` | `super_admin`.

---

### `POST /auth/forgot-password` — public

```json
{ "email": "ali@example.com" }
```

```json
{
  "message": "Reset OTP sent",
  "expires_in_seconds": 600,
  "debug_otp": "654321"
}
```

---

### `POST /auth/reset-password` — public

```json
{
  "email": "ali@example.com",
  "otp": "654321",
  "password": "NewPass123!",
  "password_confirmation": "NewPass123!"
}
```

```json
{ "message": "Password updated" }
```

Revokes existing tokens.

---

### `POST /auth/verify-pattern` — Bearer

Verify pattern lock sequence for authenticated user.

Request:
```json
{
  "pattern": "12589"
}
```

Response `200`:
```json
{
  "status": "success",
  "message": "Pattern verified successfully."
}
```

---

### `POST /profile/pattern-lock` — Bearer

Enable or disable pattern lock for citizen user.

Request:
```json
{
  "enabled": true,
  "pattern": "12589"
}
```

Response `200`:
```json
{
  "status": "success",
  "message": "Pattern lock enabled.",
  "user": {
    "id": 10,
    "pattern_lock_enabled": true,
    "has_pattern_lock": true
  }
}
```

---

### `POST /auth/logout` — Bearer

```json
{ "message": "Logged out" }
```

---

### `GET /auth/me` — Bearer

```json
{
  "user": {
    "id": 10,
    "name": "Ali Khan",
    "email": "ali@example.com",
    "mobile": "03001234567",
    "user_type": "citizen",
    "profile_complete": true,
    "pattern_lock_enabled": true,
    "has_pattern_lock": true
  }
}
```

---

## Catalogue (public)

### `GET /districts`

```json
{
  "data": [
    { "id": 20, "name": "Swabi", "code": "SWABI" }
  ]
}
```

---

### `GET /reservoirs`

Query: `district_id`, `type` (`dam|river|stream|canal|headworks|other`), `q`, `page`, `per_page` (default 20).

Laravel paginator JSON (`data`, `current_page`, `last_page`, `total`, …).

Each item includes `district`, `office`, `is_open_for_licensing` (`true` / `false` / `null`).

**Pilot rule:** only apply when eligible. With strict policy, require `is_open_for_licensing === true`.

Pilot examples: **Kundal Dam**, **Tanda Dam**, **Azakhel Dam Peshawar**.

---

### `GET /reservoirs/{id}`

```json
{
  "data": {
    "id": 117,
    "name": "Kundal Dam",
    "district": { "id": 20, "name": "Swabi" },
    "office": { "id": 1, "name": "…", "phone": null, "email": null },
    "water_body_type": "dam",
    "trout_type": "non_trout",
    "description": null,
    "species_notes": null,
    "latitude": 34.1,
    "longitude": 72.5,
    "directions_url": "https://www.google.com/maps?q=…",
    "is_open_for_licensing": true,
    "lease_notes": null
  }
}
```

---

### `GET /license-categories`

```json
{
  "data": [
    {
      "id": 1,
      "code": "DAILY",
      "name": "Daily Licence",
      "description": "Valid for one fishing day.",
      "duration_type": "daily",
      "duration_days": 1,
      "fee_amount": "500.00",
      "currency": "PKR",
      "expiry_rule": "from_start",
      "fixed_expiry_month_day": null,
      "instructions": "…",
      "terms": "…"
    },
    {
      "id": 3,
      "code": "SEASONAL",
      "name": "Seasonal Licence",
      "duration_type": "seasonal",
      "duration_days": null,
      "fee_amount": "8000.00",
      "currency": "PKR",
      "expiry_rule": "fixed_date",
      "fixed_expiry_month_day": "06-30"
    }
  ]
}
```

Pilot fees: Daily **500**, Monthly **3000**, Seasonal **8000** PKR (seasonal expires **30 June**).

---

### `GET /licenses/verify/{token}` — public QR

`token` = licence `qr_token` (from issued licence / QR).

```json
{
  "status": "Valid",
  "valid": true,
  "license_no": "LIC-2026-00001",
  "holder": "A** K***",
  "reservoir": "Kundal Dam",
  "district": "Swabi",
  "category": "Daily Licence",
  "issue_date": "2026-08-05",
  "expiry_date": "2026-08-05",
  "privacy": "masked"
}
```

`status`: `Valid` | `Expired` | `Cancelled`.  
`privacy`: `masked` (default) or `full`.  
`cnic` appears only if admin enabled “show CNIC on public QR”.

---

## Citizen profile — Bearer

### `GET /profile`

```json
{
  "user": {
    "id": 10,
    "name": "Ali Khan",
    "email": "ali@example.com",
    "mobile": "03001234567",
    "profile_complete": true
  },
  "profile": {
    "full_name": "Ali Khan",
    "father_name": "…",
    "cnic": "17301-1234567-1",
    "dob": "1990-01-15",
    "gender": "male",
    "address": "…",
    "residence_district_id": 20,
    "province": "Khyber Pakhtunkhwa",
    "emergency_contact": "03009876543"
  }
}
```

---

### `PUT /profile` (or `POST /profile`)

Accepts `multipart/form-data` or `application/json`.

**Multipart Form Data (Recommended for file uploads):**

| Field | Type | Required | Description |
|---|---|---|---|
| `full_name` | string (max: 120) | Yes | Citizen's full name |
| `father_name` | string (max: 120) | Yes | Father's name |
| `cnic` | string (`#####-#######-#`) | Yes | Valid 13-digit Pakistani CNIC |
| `dob` | date (`YYYY-MM-DD`) | Yes | Date of birth (before today) |
| `gender` | `male` \| `female` \| `other` | Yes | Gender |
| `mobile` | string (max: 30) | Yes | Mobile number |
| `address` | string (max: 255) | Yes | Residential address |
| `residence_district_id` | integer | Yes | Existing district ID |
| `province` | string (max: 80) | Yes | Province name |
| `emergency_contact` | string (max: 80) | Yes | Emergency contact number |
| `profile_pic` | image file / base64 string | **Yes** | Profile picture (jpeg, png, jpg, webp ≤ 5MB) or base64 data URI |

**JSON Example (with base64 image or existing path):**

```json
{
  "full_name": "Ali Khan",
  "father_name": "Imran Khan",
  "cnic": "17301-1234567-1",
  "dob": "1990-01-15",
  "gender": "male",
  "mobile": "03001234567",
  "address": "Village …, Swabi",
  "residence_district_id": 20,
  "province": "Khyber Pakhtunkhwa",
  "emergency_contact": "03009876543",
  "profile_pic": "data:image/jpeg;base64,/9j/4AAQSkZJRg..."
}
```

CNIC format: `#####-#######-#`  
`gender`: `male` | `female` | `other`

**Success Response (200 OK):**

```json
{
  "message": "Profile updated",
  "profile_complete": true,
  "user": {
    "id": 2,
    "name": "Ali Khan",
    "email": "ali@example.com",
    "mobile": "03001234567",
    "user_type": "citizen",
    "profile_complete": true
  },
  "profile": {
    "id": 1,
    "user_id": 2,
    "full_name": "Ali Khan",
    "father_name": "Imran Khan",
    "cnic": "17301-1234567-1",
    "dob": "1990-01-15",
    "gender": "male",
    "address": "Village …, Swabi",
    "residence_district_id": 20,
    "province": "Khyber Pakhtunkhwa",
    "emergency_contact": "03009876543",
    "photo_path": "profiles/xxxx.jpg",
    "photo_url": "http://127.0.0.1:8000/storage/profiles/xxxx.jpg"
  },
  "districts_hint": 22
}
```

---

## Applications — Bearer (citizen)

### `GET /licenses/applications`

Paginated list of the logged-in user’s applications (with `reservoir`, `category`, `license`).

### `GET /licenses/applications/{id}`

Own application only (403 otherwise). Includes `payments`.

### `POST /licenses/applications`

**JSON** (1Bill demo — no file):

```json
{
  "reservoir_id": 117,
  "category_id": 1,
  "fishing_start_date": "2026-08-10",
  "payment_method": "1bill",
  "accept_terms": true
}
```

**multipart** (bank transfer / deposit — receipt required):

| Field | Notes |
|-------|--------|
| `reservoir_id` | integer |
| `category_id` | integer |
| `fishing_start_date` | `Y-m-d`, today or later |
| `payment_method` | `bank_transfer` \| `bank_deposit` \| `1bill` \| `online_transfer` |
| `payment_reference` | required for bank_* |
| `payment_receipt` | file jpg/png/pdf ≤ 4MB — required for bank_* |
| `accept_terms` | `1` / `true` / `on` |

`online_transfer` is stored as `bank_transfer`.

curl example:

```bash
curl -X POST http://127.0.0.1:8000/api/v1/licenses/applications \
  -H "Accept: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -F reservoir_id=117 \
  -F category_id=1 \
  -F fishing_start_date=2026-08-10 \
  -F payment_method=bank_deposit \
  -F payment_reference=TXN123456 \
  -F accept_terms=1 \
  -F payment_receipt=@/path/receipt.jpg
```

Response `201`:

```json
{
  "message": "Application submitted",
  "data": {
    "id": 1,
    "application_no": "APP-2026-00001",
    "status": "under_review",
    "fee_amount_snapshot": "500.00",
    "psid_code": null,
    "reservoir": { "id": 117, "name": "Kundal Dam" },
    "category": { "id": 1, "name": "Daily Licence" }
  }
}
```

For `1bill`, `psid_code` like `PSID-DEMO-…` (display only until live 1Bill).

**Application statuses:** `submitted` | `under_review` | `info_required` | `approved` | `rejected` | `cancelled`

---

## Licences — Bearer (citizen)

### `GET /licenses`

Paginated issued licences for the user.

### `GET /licenses/{id}`

```json
{
  "data": { "id": 1, "license_no": "LIC-…", "qr_token": "…", "status": "active", "…": "…" },
  "verify_url": "http://127.0.0.1:8000/verify/licence/{qr_token}",
  "card_url": "http://127.0.0.1:8000/account/licenses/1/card"
}
```

`verify_url` is the public web QR page. Mobile can also call `GET /licenses/verify/{qr_token}`.

**Licence statuses:** `active` | `expired` | `cancelled`

---

## Violations

### `POST /reports/violation` — public **or** Bearer

Auth optional. If logged in, report is linked to the user.

```json
{
  "district_id": 20,
  "reservoir_id": 117,
  "violation_type": "net_fishing",
  "description": "Illegal netting near dam wall",
  "occurred_at": "2026-08-05T18:30:00",
  "latitude": 34.12,
  "longitude": 72.45,
  "reporter_name": "Ali Khan",
  "reporter_phone": "03001234567"
}
```

Or multipart with `photos[]` (max 3, jpg/png ≤ 4MB).

`violation_type`:

| Value | Label |
|-------|--------|
| `net_fishing` | Net fishing |
| `poison` | Poison |
| `explosives` | Explosives |
| `illegal_hunting` | Illegal hunting |
| `over_fishing` | Over fishing |
| `out_of_season` | Out of season |
| `other` | Other |

`district_id` required unless `reservoir_id` is sent (district inferred).  
GPS ranges validated roughly for KP (`lat` 30–37, `lng` 69–75).

Response `201`:

```json
{
  "message": "Report submitted",
  "data": {
    "id": 1,
    "report_no": "VR-2026-00001",
    "status": "pending"
  }
}
```

### `GET /reports/violation` — Bearer

Citizen’s own reports (paginated).

### `GET /reports/violation/{id}` — Bearer

Own report only.

---

## Officer (staff Bearer)

Login with staff email (`user_type` `admin` or `super_admin`). Citizen tokens get **403**.

### `POST /officer/verify-qr`

```json
{ "token": "qr-token-from-licence" }
```

or

```json
{ "license_no": "LIC-2026-00001" }
```

Response (full holder details — not masked):

```json
{
  "status": "Valid",
  "valid": true,
  "license": { "id": 1, "license_no": "…", "user": { "name": "Ali Khan" }, "reservoir": { "…" : "…" } }
}
```

Respects staff **district scope** (`Outside district scope` → 403).

---

### `POST /officer/walkin-application`

Create a licence application for a walk-in citizen identified by CNIC.

Request fields:
- `cnic`: string (formatted `17301-1234567-1` or unformatted `1730112345671`) - **required**
- `reservoir_id`: integer - **required**
- `category_id`: integer - **required**
- `fishing_start_date`: `Y-m-d` - **required**
- `payment_method`: `counter_cash` | `bank_transfer` | `bank_deposit` | `1bill` | `online_transfer` | `cash` - **required**
- `payment_reference`: string (required if `bank_transfer`/`bank_deposit`)
- `payment_receipt`: file (jpg/png/pdf ≤ 4MB)
- `auto_approve`: boolean (auto-approves licence immediately if true or if payment method is `counter_cash`/`cash`)
- `officer_remarks`: string

Response `201`:

```json
{
  "status": "success",
  "message": "Walk-in license application submitted and approved immediately.",
  "data": {
    "application": { "id": 1, "application_no": "APP-2026-00001", "status": "approved", "..." : "..." },
    "license": { "id": 1, "license_no": "LIC-2026-00001", "status": "active", "..." : "..." }
  }
}
```

---

### `GET /officer/offices/applications`

Get paginated list of license applications belonging to the staff user's assigned/scoped office(s).

Query Parameters:
- `office_id`: integer (optional, filter applications specifically for this office ID)
- `status`: string (optional, e.g. `submitted`, `under_review`, `approved`, `rejected`, `info_required`)
- `search` / `q`: string (optional, search by application number, citizen CNIC, or citizen name)
- `page`: integer (default 1)
- `per_page`: integer (default 20)

Response `200`:

```json
{
  "current_page": 1,
  "data": [
    {
      "id": 1,
      "application_no": "APP-2026-00001",
      "user_id": 10,
      "applied_by": 2,
      "applied_by_name": "Staff Officer",
      "status": "submitted",
      "payment_method": "counter_cash",
      "user": {
        "id": 10,
        "name": "Citizen User",
        "email": "citizen@example.com",
        "citizen_profile": {
          "full_name": "Citizen User",
          "cnic": "17301-1234567-1"
        }
      },
      "reservoir": {
        "id": 1,
        "name": "Kabal Stream",
        "district": { "id": 1, "name": "Swat" },
        "office": { "id": 1, "name": "Swat Fisheries Office" }
      },
      "category": {
        "id": 1,
        "name": "Trout Angling Permit",
        "fee_amount": 1000,
        "currency": "PKR"
      },
      "license": null
    }
  ],
  "total": 1
}
```

---

### `GET /officer/offices/{office}/applications`

Get paginated list of license applications belonging specifically to the target office ID (verifying staff district/office scope).

Query Parameters:
- `status`: string (optional, e.g. `submitted`, `under_review`, `approved`, `rejected`)
- `search` / `q`: string (optional)
- `page`: integer (default 1)
- `per_page`: integer (default 20)

---

### `GET /officer/applications`

Get paginated list of license applications submitted by or processed by the authenticated staff member (`applied_by = auth user id`).

Query Parameters:
- `status`: string (optional, e.g. `submitted`, `under_review`, `approved`, `rejected`)
- `search`: string (optional, search by application number, citizen CNIC, or citizen name)
- `page`: integer (default 1)
- `per_page`: integer (default 20)

Response `200`:

```json
{
  "current_page": 1,
  "data": [
    {
      "id": 1,
      "application_no": "APP-2026-00001",
      "user_id": 10,
      "applied_by": 2,
      "applied_by_name": "Staff Officer",
      "status": "approved",
      "payment_method": "counter_cash",
      "user": {
        "id": 10,
        "name": "Citizen User",
        "email": "citizen@example.com",
        "citizen_profile": {
          "full_name": "Citizen User",
          "cnic": "17301-1234567-1"
        }
      },
      "reservoir": {
        "id": 1,
        "name": "Kabal Stream",
        "district": { "id": 1, "name": "Swat" }
      },
      "category": {
        "id": 1,
        "name": "Trout Angling Permit",
        "fee_amount": 1000,
        "currency": "PKR"
      },
      "license": {
        "id": 1,
        "license_no": "LIC-2026-00001",
        "status": "active"
      }
    }
  ],
  "total": 1
}
```

---

### `POST /officer/applications/{id}/approve`

Approve a licence application and issue the licence immediately.

Request:
```json
{
  "officer_remarks": "Verified payment receipt and approved permit."
}
```

Response `200`:
```json
{
  "status": "success",
  "message": "Application approved and license issued successfully.",
  "data": {
    "application": {
      "id": 1,
      "application_no": "APP-2026-00001",
      "status": "approved",
      "officer_remarks": "Verified payment receipt and approved permit."
    },
    "license": {
      "id": 1,
      "license_no": "LIC-2026-00001",
      "status": "active",
      "issue_date": "2026-08-20",
      "expiry_date": "2026-09-20"
    }
  }
}
```

---

### `POST /officer/applications/{id}/reject`

Reject a licence application.

Request:
```json
{
  "officer_remarks": "Invalid document attached."
}
```

Response `200`:
```json
{
  "status": "success",
  "message": "Application rejected successfully.",
  "data": {
    "application": {
      "id": 1,
      "status": "rejected",
      "officer_remarks": "Invalid document attached."
    }
  }
}
```

---

### `POST /officer/applications/{id}/request-info`

Request additional information from citizen.

Request:
```json
{
  "officer_remarks": "Please upload a clearer payment slip."
}
```

Response `200`:
```json
{
  "status": "success",
  "message": "Information requested from citizen.",
  "data": {
    "application": {
      "id": 1,
      "status": "info_required",
      "officer_remarks": "Please upload a clearer payment slip."
    }
  }
}
```

---

### `GET /officer/violations`

Requires module `violations.view`.  
Query: `status`, `page`, `per_page`.

Statuses: `pending` | `under_investigation` | `resolved`

---

### `PATCH /officer/violations/{id}`

Requires module `violations.status`.

```json
{
  "status": "under_investigation",
  "resolution_notes": "Team dispatched"
}
```

```json
{ "message": "Updated", "data": { "…" : "…" } }
```

---

## Suggested mobile screens → endpoints

| Screen | Endpoints |
|--------|-----------|
| Splash / home catalogue | `GET /districts`, `GET /reservoirs`, `GET /license-categories` |
| Register | `POST /auth/register` → `POST /auth/verify-otp` |
| Login / forgot | `POST /auth/login`, forgot + reset |
| Profile | `GET/PUT /profile` |
| Apply | `POST /licenses/applications` |
| My apps / licences | `GET /licenses/applications`, `GET /licenses` |
| QR scanner (public) | `GET /licenses/verify/{token}` |
| QR scanner (officer) | `POST /officer/verify-qr` |
| Report illegal fishing | `POST /reports/violation` |
| Officer inbox | `GET /officer/violations`, `PATCH …` |

---

## Local notes for vendors

- Server must be running: `cd backend && php artisan serve`
- OTP emails use `MAIL_MAILER=log` → check `storage/logs/laravel.log`, or use `debug_otp` when debug is on
- Closed season (pilot): **1 Jun – 31 Jul** blocks apply for those start dates
- Do not commit real production tokens; rotate after staging cutover
- This doc is **draft** — freeze paths with MIS before production app store release

---

## Endpoint index

| Method | Path | Auth |
|--------|------|------|
| POST | `/auth/register` | No |
| POST | `/auth/verify-otp` | No |
| POST | `/auth/login` | No |
| POST | `/auth/forgot-password` | No |
| POST | `/auth/reset-password` | No |
| POST | `/auth/logout` | Bearer |
| GET | `/auth/me` | Bearer |
| GET | `/districts` | No |
| GET | `/reservoirs` | No |
| GET | `/reservoirs/{id}` | No |
| GET | `/license-categories` | No |
| GET | `/licenses/verify/{token}` | No |
| GET/PUT | `/profile` | Bearer |
| GET/POST | `/licenses/applications` | Bearer |
| GET | `/licenses/applications/{id}` | Bearer |
| GET | `/licenses` | Bearer |
| GET | `/licenses/{id}` | Bearer |
| POST | `/reports/violation` | Optional |
| GET | `/reports/violation` | Bearer |
| GET | `/reports/violation/{id}` | Bearer |
| POST | `/officer/verify-qr` | Staff Bearer |
| POST | `/officer/walkin-registration` | Staff Bearer |
| POST | `/officer/walkin-application` | Staff Bearer |
| GET | `/officer/violations` | Staff Bearer |
| PATCH | `/officer/violations/{id}` | Staff Bearer |
