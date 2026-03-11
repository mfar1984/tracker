# Family Tracker API Documentation

## Base URL
```
http://localhost:8000/api
```

## Authentication
- **Web Dashboard**: Session-based authentication
- **Mobile App**: Sanctum token-based authentication

## Endpoints Overview

### 🔓 Public Endpoints (No Auth Required)

#### 1. Get Avatar Icons
```http
GET /devices/avatar-icons
```
**Response:**
```json
{
  "icons": [
    {
      "id": "man",
      "name": "Man", 
      "emoji": "👨",
      "color": "#3498db"
    }
  ]
}
```

#### 2. Send Location Ping
```http
POST /pings
```
**Request Body:**
```json
{
  "deviceId": "device-mother-1773236981514",
  "name": "Mother",
  "latitude": 21.4225,
  "longitude": 39.8262,
  "accuracy": 10.5,
  "batteryLevel": 85,
  "signalStrength": -65,
  "microphoneStatus": true,
  "cameraStatus": false,
  "recordingStatus": false,
  "timestamp": 1710259200
}
```
**Response:**
```json
{
  "success": true,
  "ping_id": 50
}
```

#### 3. Check Device Updates
```http
GET /devices/{deviceId}/check-updates
```
**Response:**
```json
{
  "updateRequested": false
}
```

### 🔐 Protected Endpoints (Require Authentication)

#### 4. Register Device
```http
POST /devices/register
```
**Headers:** `Authorization: Bearer {token}`
**Request Body:**
```json
{
  "device_id": "device-father-1773236981440",
  "name": "Father",
  "avatar_type": "icon",
  "avatar_value": "man"
}
```
**Response:**
```json
{
  "success": true,
  "message": "Device registered successfully",
  "device": {
    "device_id": "device-father-1773236981440",
    "name": "Father",
    "avatar_type": "icon",
    "avatar_value": "man"
  }
}
```

#### 5. Get User Devices
```http
GET /user/devices
```
**Response:**
```json
{
  "devices": [
    {
      "device_id": "device-mother-1773236981514",
      "name": "Mother",
      "avatar_type": "icon",
      "avatar_value": "woman",
      "is_active": true
    }
  ]
}
```

#### 6. Get Device Locations
```http
GET /locations
```
**Response:**
```json
{
  "locations": [
    {
      "deviceId": "device-mother-1773236981514",
      "name": "Mother",
      "latitude": 21.4225,
      "longitude": 39.8262,
      "batteryLevel": 85,
      "signalStrength": -65,
      "isStale": false,
      "avatar": {
        "type": "icon",
        "value": "woman",
        "url": null
      }
    }
  ]
}
```

#### 7. Request Device Update
```http
POST /devices/{deviceId}/update
```
**Response:**
```json
{
  "success": true,
  "message": "Update request sent"
}
```

#### 8. Generate Verification Code
```http
POST /devices/{deviceId}/generate-code
```
**Headers:** `X-CSRF-TOKEN: {token}`
**Response:**
```json
{
  "success": true,
  "verification_code": "ApGGoWVt",
  "expires_at": "2026-03-11T17:17:40.000000Z",
  "message": "Verification code generated successfully"
}
```
**Notes:** 
- Code expires in 30 minutes
- 8-character alphanumeric code (uppercase + lowercase + numbers)
- One-time use only

#### 9. Delete Device with Code
```http
DELETE /devices/{deviceId}/with-code
```
**Headers:** `X-CSRF-TOKEN: {token}`
**Request Body:**
```json
{
  "verification_code": "ApGGoWVt"
}
```
**Response:**
```json
{
  "success": true,
  "message": "Device deleted successfully"
}
```
**Notes:**
- Requires valid verification code from generate-code endpoint
- Code is consumed (deleted) after successful use
- Device and all associated location pings are permanently deleted

#### 10. Update Device Name
```http
PUT /devices/{deviceId}/name
```
**Request Body:**
```json
{
  "name": "New Device Name"
}
```

#### 11. Update User Profile
```http
PUT /user/profile
```
**Request Body:**
```json
{
  "username": "family",
  "email": "family@example.com"
}
```

#### 12. Update User Avatar
```http
PUT /user/avatar
```
**Request Body:**
```json
{
  "avatar_type": "icon",
  "avatar_value": "man"
}
```

#### 13. Change Password
```http
POST /user/change-password
```
**Request Body:**
```json
{
  "current_password": "oldpassword",
  "new_password": "newpassword",
  "new_password_confirmation": "newpassword"
}
```

## Error Responses

### 401 Unauthorized
```json
{
  "error": true,
  "message": "Unauthorized"
}
```

### 404 Not Found
```json
{
  "error": true,
  "message": "Device not found"
}
```

### 400 Bad Request
```json
{
  "error": true,
  "message": "Invalid or expired verification code"
}
```

## Flutter Integration Notes

### Authentication Flow
1. User login → Get Sanctum token
2. Store token securely
3. Include in all API requests: `Authorization: Bearer {token}`

### Device Registration
1. Generate unique device_id
2. Call `/devices/register` with device info
3. Store device_id locally

### Location Tracking
1. Get GPS coordinates
2. Send to `/pings` endpoint every X minutes
3. Include battery, signal, and optional status

### Device Management
1. Get verification code: `/devices/{id}/generate-code`
2. Show code to user
3. Delete with code: `/devices/{id}/with-code`

### Real-time Updates
- Poll `/devices/{id}/check-updates` every 30 seconds
- Check for update requests from dashboard