# API Testing Tasks - Family Tracker

## Test Environment

### Localhost
- **URL**: http://localhost:8000
- **Username**: `family`
- **Password**: `password123`
- **Status**: ✅ Server running

### Production
- **URL**: https://hajj.sibu.org.my
- **Username**: `administrator@root`
- **Password**: `F@iz@n!984`
- **Status**: ⚠️ User needs to be created in database

---

## Test Results

### 1. Public Endpoints (No Authentication Required)

#### 1.1 Get Avatar Icons
**Endpoint**: `GET /api/devices/avatar-icons`

| Environment | Status | Response | Notes |
|-------------|--------|----------|-------|
| Localhost | ✅ PASSED | 200 OK | Returns 48 avatar icons |
| Production | ⏳ PENDING | - | Ready to test |

**Test Command**:
```powershell
Invoke-WebRequest -Uri "http://localhost:8000/api/devices/avatar-icons" -Method GET -UseBasicParsing
```

---

#### 1.2 Send Location Ping
**Endpoint**: `POST /api/pings`

| Environment | Status | Response | Notes |
|-------------|--------|----------|-------|
| Localhost | ✅ PASSED | 201 Created | ping_id: 52 |
| Production | ⏳ PENDING | - | Ready to test |

**Request Body**:
```json
{
  "deviceId": "test-api-final-001",
  "name": "Test Device",
  "latitude": 21.4225,
  "longitude": 39.8262,
  "accuracy": 10.5,
  "batteryLevel": 85,
  "signalStrength": -65,
  "microphoneStatus": true,
  "cameraStatus": false,
  "recordingStatus": false,
  "timestamp": 1773259500
}
```

**Important**: Device must exist in database before sending ping.

**Test Command**:
```powershell
$body = '{"deviceId":"test-api-final-001","name":"Test","latitude":21.4225,"longitude":39.8262,"accuracy":10.5,"batteryLevel":85,"signalStrength":-65,"microphoneStatus":true,"cameraStatus":false,"recordingStatus":false,"timestamp":1773259500}'
Invoke-WebRequest -Uri "http://localhost:8000/api/pings" -Method POST -Body $body -ContentType "application/json" -UseBasicParsing
```

---

#### 1.3 Check Device Updates
**Endpoint**: `GET /api/devices/{deviceId}/check-updates`

| Environment | Status | Response | Notes |
|-------------|--------|----------|-------|
| Localhost | ✅ PASSED | 200 OK | {"updateRequested":false} |
| Production | ⏳ PENDING | - | Ready to test |

**Test Command**:
```powershell
Invoke-WebRequest -Uri "http://localhost:8000/api/devices/test-api-final-001/check-updates" -Method GET -UseBasicParsing
```

---

### 2. Authentication Endpoints

#### 2.1 API Login
**Endpoint**: `POST /api/login`

| Environment | Status | Response | Notes |
|-------------|--------|----------|-------|
| Localhost | ✅ PASSED | 200 OK | Returns token and user data |
| Production | ⏳ PENDING | - | User needs to be created first |

**Request Body**:
```json
{
  "username": "family",
  "password": "password123"
}
```

**Test Command**:
```powershell
$body = '{"username":"family","password":"password123"}'
Invoke-WebRequest -Uri "http://localhost:8000/api/login" -Method POST -Body $body -ContentType "application/json" -UseBasicParsing
```

---

### 3. Protected Endpoints (Bearer Token Authentication)

#### 3.1 Get User Profile
**Endpoint**: `GET /api/user`

| Environment | Status | Response | Notes |
|-------------|--------|----------|-------|
| Localhost | ✅ PASSED | 200 OK | Returns user profile |
| Production | ⏳ PENDING | - | Requires valid token |

**Headers**: `Authorization: Bearer {token}`

**Test Command**:
```powershell
$headers = @{"Authorization"="Bearer YOUR_TOKEN_HERE"}
Invoke-WebRequest -Uri "http://localhost:8000/api/user" -Method GET -Headers $headers -UseBasicParsing
```

---

#### 3.2 Register Device
**Endpoint**: `POST /api/devices/register`

| Environment | Status | Response | Notes |
|-------------|--------|----------|-------|
| Localhost | ✅ PASSED | 201 Created | Device registered successfully |
| Production | ⏳ PENDING | - | Requires valid token |

**Headers**: `Authorization: Bearer {token}`

**Request Body**:
```json
{
  "device_id": "test-api-final-001",
  "name": "Test Device",
  "avatar_type": "icon",
  "avatar_value": "man"
}
```

**Test Command**:
```powershell
$headers = @{"Authorization"="Bearer YOUR_TOKEN_HERE"}
$body = '{"device_id":"test-api-final-001","name":"Test Device","avatar_type":"icon","avatar_value":"man"}'
Invoke-WebRequest -Uri "http://localhost:8000/api/devices/register" -Method POST -Body $body -ContentType "application/json" -Headers $headers -UseBasicParsing
```

---

### 4. Protected Endpoints (Web Session - Browser Only)

⚠️ **These endpoints require browser login and cannot be tested via curl/PowerShell**

#### 4.1 Get User Devices
**Endpoint**: `GET /api/user/devices`
- **Status**: ⚠️ Requires browser session
- **Middleware**: `web`
- **Manual Testing**: Login to dashboard first

#### 4.2 Get Device Locations
**Endpoint**: `GET /api/locations`
- **Status**: ⚠️ Requires browser session
- **Middleware**: `web`
- **Manual Testing**: Login to dashboard first

#### 4.3 Request Device Update
**Endpoint**: `POST /api/devices/{deviceId}/update`
- **Status**: ⚠️ Requires browser session
- **Middleware**: `web`
- **Manual Testing**: Login to dashboard first

#### 4.4 Generate Verification Code
**Endpoint**: `POST /api/devices/{deviceId}/generate-code`
- **Status**: ⚠️ Requires browser session + CSRF token
- **Middleware**: `web`
- **Manual Testing**: Login to dashboard first

#### 4.5 Delete Device with Code
**Endpoint**: `DELETE /api/devices/{deviceId}/with-code`
- **Status**: ⚠️ Requires browser session + CSRF token
- **Middleware**: `web`
- **Request Body**: `{"verification_code": "ABC12345"}`
- **Manual Testing**: Login to dashboard first

#### 4.6 Update Device Name
**Endpoint**: `PUT /api/devices/{deviceId}/name`
- **Status**: ⚠️ Requires browser session
- **Middleware**: `web`
- **Request Body**: `{"name": "New Device Name"}`
- **Manual Testing**: Login to dashboard first

#### 4.7 Update User Profile
**Endpoint**: `PUT /api/user/profile`
- **Status**: ⚠️ Requires browser session
- **Middleware**: `web`
- **Request Body**: `{"username": "family", "email": "family@example.com"}`
- **Manual Testing**: Login to dashboard first

#### 4.8 Update User Avatar
**Endpoint**: `PUT /api/user/avatar`
- **Status**: ⚠️ Requires browser session
- **Middleware**: `web`
- **Request Body**: `{"avatar_type": "icon", "avatar_value": "man"}`
- **Manual Testing**: Login to dashboard first

#### 4.9 Change Password
**Endpoint**: `POST /api/user/change-password`
- **Status**: ⚠️ Requires browser session
- **Middleware**: `web`
- **Request Body**: `{"current_password": "password123", "new_password": "newpassword123", "new_password_confirmation": "newpassword123"}`
- **Manual Testing**: Login to dashboard first

---

## Summary

### Automated Testing (curl/PowerShell)

| Category | Total | Localhost | Production |
|----------|-------|-----------|------------|
| Public APIs | 3 | ✅ 3/3 | ⏳ 0/3 |
| Auth APIs | 1 | ✅ 1/1 | ⏳ 0/1 |
| Bearer Token APIs | 2 | ✅ 2/2 | ⏳ 0/2 |
| **TOTAL** | **6** | **✅ 6/6** | **⏳ 0/6** |

### Manual Testing (Browser Required)

| Category | Total | Status |
|----------|-------|--------|
| Web Session APIs | 9 | ⚠️ Manual testing required |

### Overall Progress

- ✅ **Localhost**: 6/6 automated tests PASSED
- ⏳ **Production**: 0/6 automated tests (pending)
- ⚠️ **Browser APIs**: 9 endpoints require manual testing

---

## Next Steps

### Step 1: Create Production User ⏳
User `administrator@root` needs to be created in production database.

**SQL Command** (see `create-admin-user.sql`):
```sql
INSERT INTO users (username, name, email, password, created_at, updated_at)
VALUES (
    'administrator@root',
    'Administrator',
    'admin@hajj.sibu.org.my',
    '$2y$12$H1uU3vUNx7F7VPVtxGV2yecrUQV/zClu0lrz7hdwS49d6jRjzr2B6',
    NOW(),
    NOW()
);
```

**How to run**:
```bash
# SSH to production server
ssh kflegacy@indigo.herosite.pro

# Navigate to project directory
cd ~/hajj.sibu.org.my

# Run SQL
php artisan tinker --execute="DB::insert('INSERT INTO users (username, name, email, password, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())', ['administrator@root', 'Administrator', 'admin@hajj.sibu.org.my', '\$2y\$12\$H1uU3vUNx7F7VPVtxGV2yecrUQV/zClu0lrz7hdwS49d6jRjzr2B6']);"

# Verify user was created
php artisan tinker --execute="echo json_encode(App\Models\User::where('username', 'administrator@root')->first());"
```

### Step 2: Test Production APIs ⏳
Run automated test script:
```powershell
powershell -ExecutionPolicy Bypass -File test-api-production.ps1
```

### Step 3: Manual Browser Testing ⏳
1. Login to http://localhost:8000/login (localhost)
2. Login to https://hajj.sibu.org.my/login (production)
3. Test all web session endpoints manually in browser console
4. Document results

### Step 4: Push to Git ⏳
After all tests pass:
```bash
git add .
git commit -m "Add API login endpoint and complete API testing"
git push origin master
```

---

## Test Scripts

### Localhost Testing
```powershell
powershell -ExecutionPolicy Bypass -File test-api-complete.ps1
```

### Production Testing
```powershell
powershell -ExecutionPolicy Bypass -File test-api-production.ps1
```

---

## Files Created

1. ✅ `API_TEST_TASKS.md` - This file (test documentation)
2. ✅ `test-api-complete.ps1` - Localhost automated testing script
3. ✅ `test-api-production.ps1` - Production automated testing script
4. ✅ `create-admin-user.sql` - SQL to create admin user in production

---

## Notes

- All public and Bearer token APIs are working correctly on localhost
- Production testing blocked until user `administrator@root` is created
- Web session endpoints require manual browser testing (cannot be automated via curl/PowerShell)
- CSRF token protection is enabled for destructive operations (delete, update)
- All changes are ready to be pushed to git after production testing completes

---

**Last Updated**: 2026-03-12
**Status**: Localhost testing complete ✅ | Production testing pending ⏳
