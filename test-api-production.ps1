# Complete API Testing Script for Production
$baseUrl = "https://hajj.sibu.org.my/api"
$username = "administrator@root"
$password = "F@iz@n!984"

Write-Host "`n========================================" -ForegroundColor Cyan
Write-Host "   PRODUCTION API TESTING" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Base URL: $baseUrl" -ForegroundColor Yellow
Write-Host "Username: $username`n" -ForegroundColor Yellow

$passedTests = 0
$failedTests = 0

# Test 1: Get Avatar Icons
Write-Host "[TEST 1] GET /devices/avatar-icons" -ForegroundColor Green
try {
    $response = Invoke-WebRequest -Uri "$baseUrl/devices/avatar-icons" -Method GET -UseBasicParsing
    Write-Host "  ✅ PASSED - Status: $($response.StatusCode)" -ForegroundColor Green
    $passedTests++
} catch {
    Write-Host "  ❌ FAILED - $($_.Exception.Message)" -ForegroundColor Red
    if ($_.ErrorDetails.Message) {
        Write-Host "  Error: $($_.ErrorDetails.Message)" -ForegroundColor Yellow
    }
    $failedTests++
}

# Test 2: API Login
Write-Host "`n[TEST 2] POST /login" -ForegroundColor Green
try {
    $loginBody = @{
        username = $username
        password = $password
    } | ConvertTo-Json
    
    $response = Invoke-WebRequest -Uri "$baseUrl/login" -Method POST -Body $loginBody -ContentType "application/json" -UseBasicParsing
    $loginData = $response.Content | ConvertFrom-Json
    $token = $loginData.token
    Write-Host "  ✅ PASSED - Status: $($response.StatusCode)" -ForegroundColor Green
    Write-Host "  Token: $($token.Substring(0,30))..." -ForegroundColor Gray
    $passedTests++
} catch {
    Write-Host "  ❌ FAILED - $($_.Exception.Message)" -ForegroundColor Red
    if ($_.ErrorDetails.Message) {
        Write-Host "  Error: $($_.ErrorDetails.Message)" -ForegroundColor Yellow
    }
    $failedTests++
    Write-Host "`n⚠️  Cannot continue without valid token. Stopping tests." -ForegroundColor Yellow
    Write-Host "`nPossible issues:" -ForegroundColor Yellow
    Write-Host "  1. User '$username' does not exist in production database" -ForegroundColor Gray
    Write-Host "  2. Password is incorrect" -ForegroundColor Gray
    Write-Host "  3. Database connection issue" -ForegroundColor Gray
    Write-Host "`nTo create user in production, run this SQL:" -ForegroundColor Yellow
    Write-Host "  INSERT INTO users (username, name, email, password, created_at, updated_at)" -ForegroundColor Gray
    Write-Host "  VALUES ('administrator@root', 'Administrator', 'admin@hajj.sibu.org.my'," -ForegroundColor Gray
    Write-Host "  '\$2y\$12\$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewY5NANh6B56WTK2', NOW(), NOW());" -ForegroundColor Gray
    Write-Host "  -- Password: F@iz@n!984" -ForegroundColor Gray
    exit
}

# Test 3: Get User Profile (with Bearer token)
Write-Host "`n[TEST 3] GET /user (Bearer token)" -ForegroundColor Green
try {
    $headers = @{
        "Authorization" = "Bearer $token"
    }
    $response = Invoke-WebRequest -Uri "$baseUrl/user" -Method GET -Headers $headers -UseBasicParsing
    $userData = $response.Content | ConvertFrom-Json
    Write-Host "  ✅ PASSED - Status: $($response.StatusCode)" -ForegroundColor Green
    Write-Host "  User: $($userData.username)" -ForegroundColor Gray
    $passedTests++
} catch {
    Write-Host "  ❌ FAILED - $($_.Exception.Message)" -ForegroundColor Red
    if ($_.ErrorDetails.Message) {
        Write-Host "  Error: $($_.ErrorDetails.Message)" -ForegroundColor Yellow
    }
    $failedTests++
}

# Test 4: Register Device (with Bearer token)
Write-Host "`n[TEST 4] POST /devices/register (Bearer token)" -ForegroundColor Green
try {
    $timestamp = [int][double]::Parse((Get-Date -UFormat %s))
    $newDeviceId = "prod-test-device-$timestamp"
    
    $deviceBody = @{
        device_id = $newDeviceId
        name = "Production Test Device"
        avatar_type = "icon"
        avatar_value = "man"
    } | ConvertTo-Json
    
    $headers = @{
        "Authorization" = "Bearer $token"
    }
    $response = Invoke-WebRequest -Uri "$baseUrl/devices/register" -Method POST -Body $deviceBody -ContentType "application/json" -Headers $headers -UseBasicParsing
    $deviceData = $response.Content | ConvertFrom-Json
    $testDeviceId = $deviceData.deviceId
    Write-Host "  ✅ PASSED - Status: $($response.StatusCode)" -ForegroundColor Green
    Write-Host "  Device ID: $testDeviceId" -ForegroundColor Gray
    $passedTests++
} catch {
    Write-Host "  ❌ FAILED - $($_.Exception.Message)" -ForegroundColor Red
    if ($_.ErrorDetails.Message) {
        Write-Host "  Error: $($_.ErrorDetails.Message)" -ForegroundColor Yellow
    }
    $failedTests++
    $testDeviceId = $newDeviceId
}

# Test 5: Send Location Ping
Write-Host "`n[TEST 5] POST /pings" -ForegroundColor Green
try {
    $timestamp = [int][double]::Parse((Get-Date -UFormat %s))
    $pingBody = @{
        deviceId = $testDeviceId
        name = "Production Test"
        latitude = 21.4225
        longitude = 39.8262
        accuracy = 10.5
        batteryLevel = 85
        signalStrength = -65
        microphoneStatus = $true
        cameraStatus = $false
        recordingStatus = $false
        timestamp = $timestamp
    } | ConvertTo-Json
    
    $response = Invoke-WebRequest -Uri "$baseUrl/pings" -Method POST -Body $pingBody -ContentType "application/json" -UseBasicParsing
    $pingData = $response.Content | ConvertFrom-Json
    Write-Host "  ✅ PASSED - Status: $($response.StatusCode)" -ForegroundColor Green
    Write-Host "  Ping ID: $($pingData.ping_id)" -ForegroundColor Gray
    $passedTests++
} catch {
    Write-Host "  ❌ FAILED - $($_.Exception.Message)" -ForegroundColor Red
    if ($_.ErrorDetails.Message) {
        Write-Host "  Error: $($_.ErrorDetails.Message)" -ForegroundColor Yellow
    }
    $failedTests++
}

# Test 6: Check Device Updates
Write-Host "`n[TEST 6] GET /devices/{deviceId}/check-updates" -ForegroundColor Green
try {
    $response = Invoke-WebRequest -Uri "$baseUrl/devices/$testDeviceId/check-updates" -Method GET -UseBasicParsing
    $updateData = $response.Content | ConvertFrom-Json
    Write-Host "  ✅ PASSED - Status: $($response.StatusCode)" -ForegroundColor Green
    Write-Host "  Update Requested: $($updateData.updateRequested)" -ForegroundColor Gray
    $passedTests++
} catch {
    Write-Host "  ❌ FAILED - $($_.Exception.Message)" -ForegroundColor Red
    if ($_.ErrorDetails.Message) {
        Write-Host "  Error: $($_.ErrorDetails.Message)" -ForegroundColor Yellow
    }
    $failedTests++
}

# Summary
Write-Host "`n========================================" -ForegroundColor Cyan
Write-Host "   TEST SUMMARY" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Total Tests: $($passedTests + $failedTests)" -ForegroundColor White
Write-Host "Passed: $passedTests" -ForegroundColor Green
Write-Host "Failed: $failedTests" -ForegroundColor Red

if ($failedTests -eq 0) {
    Write-Host "`n✅ All production API tests passed!" -ForegroundColor Green
} else {
    Write-Host "`n⚠️  Some tests failed. Please review errors above." -ForegroundColor Yellow
}

Write-Host "`n⚠️  NOTE: Web session endpoints cannot be tested via PowerShell" -ForegroundColor Yellow
Write-Host "These require browser login:" -ForegroundColor Yellow
Write-Host "  • GET /api/user/devices" -ForegroundColor Gray
Write-Host "  • GET /api/locations" -ForegroundColor Gray
Write-Host "  • POST /api/devices/{id}/update" -ForegroundColor Gray
Write-Host "  • POST /api/devices/{id}/generate-code" -ForegroundColor Gray
Write-Host "  • DELETE /api/devices/{id}/with-code" -ForegroundColor Gray
Write-Host "  • PUT /api/devices/{id}/name" -ForegroundColor Gray
Write-Host "  • PUT /api/user/profile" -ForegroundColor Gray
Write-Host "  • PUT /api/user/avatar" -ForegroundColor Gray
Write-Host "  • POST /api/user/change-password`n" -ForegroundColor Gray
