# API Testing Script for Localhost
$baseUrl = "http://localhost:8000/api"
$username = "family"
$password = "password123"

Write-Host "`n=== TESTING LOCALHOST APIs ===" -ForegroundColor Cyan
Write-Host "Base URL: $baseUrl`n" -ForegroundColor Yellow

# Test 1: Get Avatar Icons
Write-Host "[1] Testing GET /devices/avatar-icons..." -ForegroundColor Green
try {
    $response = Invoke-WebRequest -Uri "$baseUrl/devices/avatar-icons" -Method GET
    Write-Host "✅ PASSED - Status: $($response.StatusCode)" -ForegroundColor Green
} catch {
    Write-Host "❌ FAILED - Error: $($_.Exception.Message)" -ForegroundColor Red
}

# Test 2: API Login
Write-Host "`n[2] Testing POST /login..." -ForegroundColor Green
try {
    $loginBody = @{
        username = $username
        password = $password
    } | ConvertTo-Json
    
    $response = Invoke-WebRequest -Uri "$baseUrl/login" -Method POST -Body $loginBody -ContentType "application/json"
    $loginData = $response.Content | ConvertFrom-Json
    $token = $loginData.token
    Write-Host "✅ PASSED - Status: $($response.StatusCode), Token: $($token.Substring(0,20))..." -ForegroundColor Green
} catch {
    Write-Host "❌ FAILED - Error: $($_.Exception.Message)" -ForegroundColor Red
    exit
}

# Test 3: Get User Profile (with Bearer token)
Write-Host "`n[3] Testing GET /user (with Bearer token)..." -ForegroundColor Green
try {
    $headers = @{
        "Authorization" = "Bearer $token"
    }
    $response = Invoke-WebRequest -Uri "$baseUrl/user" -Method GET -Headers $headers
    Write-Host "✅ PASSED - Status: $($response.StatusCode)" -ForegroundColor Green
} catch {
    Write-Host "❌ FAILED - Error: $($_.Exception.Message)" -ForegroundColor Red
}

# Test 4: Register Device (with Bearer token)
Write-Host "`n[4] Testing POST /devices/register (with Bearer token)..." -ForegroundColor Green
try {
    $deviceBody = @{
        device_id = "test-device-$(Get-Date -Format 'yyyyMMddHHmmss')"
        name = "Test Device"
        avatar_type = "icon"
        avatar_value = "man"
    } | ConvertTo-Json
    
    $headers = @{
        "Authorization" = "Bearer $token"
    }
    $response = Invoke-WebRequest -Uri "$baseUrl/devices/register" -Method POST -Body $deviceBody -ContentType "application/json" -Headers $headers
    $deviceData = $response.Content | ConvertFrom-Json
    $testDeviceId = $deviceData.device.device_id
    Write-Host "✅ PASSED - Status: $($response.StatusCode), Device ID: $testDeviceId" -ForegroundColor Green
} catch {
    Write-Host "❌ FAILED - Error: $($_.Exception.Message)" -ForegroundColor Red
}

# Test 5: Send Location Ping
Write-Host "`n[5] Testing POST /pings..." -ForegroundColor Green
try {
    $pingBody = @{
        deviceId = $testDeviceId
        name = "Test Device"
        latitude = 21.4225
        longitude = 39.8262
        accuracy = 10.5
        batteryLevel = 85
        signalStrength = -65
        microphoneStatus = $true
        cameraStatus = $false
        recordingStatus = $false
        timestamp = [int][double]::Parse((Get-Date -UFormat %s))
    } | ConvertTo-Json
    
    $response = Invoke-WebRequest -Uri "$baseUrl/pings" -Method POST -Body $pingBody -ContentType "application/json"
    Write-Host "✅ PASSED - Status: $($response.StatusCode)" -ForegroundColor Green
} catch {
    Write-Host "❌ FAILED - Error: $($_.Exception.Message)" -ForegroundColor Red
}

# Test 6: Check Device Updates
Write-Host "`n[6] Testing GET /devices/{deviceId}/check-updates..." -ForegroundColor Green
try {
    $response = Invoke-WebRequest -Uri "$baseUrl/devices/$testDeviceId/check-updates" -Method GET
    Write-Host "✅ PASSED - Status: $($response.StatusCode)" -ForegroundColor Green
} catch {
    Write-Host "❌ FAILED - Error: $($_.Exception.Message)" -ForegroundColor Red
}

Write-Host "`n=== LOCALHOST TESTING COMPLETE ===" -ForegroundColor Cyan
Write-Host "Note: Web session endpoints require browser login and cannot be tested via curl/PowerShell" -ForegroundColor Yellow
