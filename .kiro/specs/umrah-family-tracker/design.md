# Design Document: Umrah Family Tracker

## Overview

The Umrah Family Tracker is a distributed system consisting of three main components:

1. **Android Mobile Application (Tracker_App)**: A native Android application that runs on pilgrims' devices, collecting GPS location and device status data and transmitting it to the backend server every 30 seconds. On first launch, users simply enter their name to register the device.

2. **Web Backend (Web_Backend)**: A server-side application that provides RESTful APIs for device registration, data ingestion, and real-time location queries. It persists all data to a MySQL database. No password-based authentication is required for device registration.

3. **Web Dashboard (Web_Dashboard)**: A browser-based interface that displays a real-time map with device markers, allows interaction with markers to view detailed device information, and provides manual update triggers. Dashboard access can be open or protected with simple authentication if needed.

The system is designed with privacy and consent as core principles, ensuring all users are aware of and agree to location tracking. The architecture supports both development (localhost) and production (hajj.sibu.org.my) deployments.

## Architecture

### System Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                     Android Devices                          │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐      │
│  │ Tracker_App  │  │ Tracker_App  │  │ Tracker_App  │      │
│  │  (Pilgrim 1) │  │  (Pilgrim 2) │  │  (Pilgrim N) │      │
│  └──────┬───────┘  └──────┬───────┘  └──────┬───────┘      │
└─────────┼──────────────────┼──────────────────┼─────────────┘
          │                  │                  │
          │ HTTPS/REST API   │                  │
          │ (30s intervals)  │                  │
          ▼                  ▼                  ▼
┌─────────────────────────────────────────────────────────────┐
│                      Web Backend                             │
│  ┌────────────────────────────────────────────────────────┐ │
│  │              RESTful API Server                        │ │
│  │  • Authentication endpoints                            │ │
│  │  • Device ping ingestion                               │ │
│  │  • Location query endpoints                            │ │
│  │  • Manual update trigger                               │ │
│  └────────────┬───────────────────────────┬───────────────┘ │
│               │                           │                  │
│               ▼                           ▼                  │
│  ┌────────────────────┐     ┌────────────────────────────┐ │
│  │  Business Logic    │     │   WebSocket/SSE Handler    │ │
│  │  • Data validation │     │   (Optional for real-time) │ │
│  │  • Location update │     └────────────────────────────┘ │
│  └────────┬───────────┘                                     │
│           │                                                  │
│           ▼                                                  │
│  ┌────────────────────┐                                     │
│  │  MySQL Database    │                                     │
│  │  • users           │                                     │
│  │  • devices         │                                     │
│  │  • location_pings  │                                     │
│  └────────────────────┘                                     │
└─────────────────────────────────────────────────────────────┘
          ▲
          │ HTTPS
          │ (Web requests)
          │
┌─────────┴───────────────────────────────────────────────────┐
│                    Web Dashboard                             │
│  ┌────────────────────────────────────────────────────────┐ │
│  │           Browser-Based Interface                      │ │
│  │  • OpenStreetMap/Leaflet map display                   │ │
│  │  • Device markers with real-time updates               │ │
│  │  • Info drawer for device details                      │ │
│  │  • Manual update trigger UI                            │ │
│  └────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────┘
```

### Technology Stack

**Android Application:**
- Language: Kotlin (recommended) or Java
- Minimum SDK: Android 8.0 (API 26) for background location support
- Key Libraries:
  - Google Play Services Location API for GPS
  - Retrofit or OkHttp for HTTP networking
  - WorkManager for background task scheduling
  - Room (optional) for local data queuing

**Web Backend:**
- Language: Node.js with Express (recommended for real-time capabilities) or PHP
- Database: MySQL 5.7+
- Key Libraries:
  - Express.js (Node.js) or Slim Framework (PHP)
  - MySQL driver (mysql2 for Node.js, PDO for PHP)
  - UUID generation for device IDs

**Web Dashboard:**
- Frontend: HTML5, CSS3, JavaScript (vanilla or lightweight framework)
- Mapping: Leaflet.js with OpenStreetMap tiles (free)
- Real-time updates: Polling (simple) or WebSocket/SSE (advanced)
- UI Framework: Minimal CSS framework or custom styles

### Deployment Architecture

**Development Environment:**
- Backend: http://localhost:3000 (or port 8000 for PHP)
- Database: localhost:3306 (root/root)
- Frontend: http://localhost:8080 (or served by backend)

**Production Environment:**
- Backend: https://hajj.sibu.org.my
- Database: localhost:3306 (production credentials)
- Frontend: https://hajj.sibu.org.my (served by backend or separate static hosting)
- SSL/TLS: Required for HTTPS (Let's Encrypt recommended)

## Components and Interfaces

### Android Application Components

#### 1. Device Registration Module
**Responsibility:** Handle device registration with user's name

**Interface:**
```kotlin
interface DeviceRegistrationService {
    suspend fun registerDevice(name: String, deviceId: String): Result<RegistrationToken>
    fun getRegistrationToken(): RegistrationToken?
    fun isRegistered(): Boolean
}

data class RegistrationToken(
    val deviceId: String,
    val name: String,
    val registeredAt: Long
)
```

#### 2. Location Tracking Module
**Responsibility:** Collect GPS location data continuously

**Interface:**
```kotlin
interface LocationTrackingService {
    fun startTracking()
    fun stopTracking()
    fun getCurrentLocation(): Location?
    fun isTracking(): Boolean
}

data class Location(
    val latitude: Double,
    val longitude: Double,
    val accuracy: Float,
    val timestamp: Long
)
```

#### 3. Device Status Module
**Responsibility:** Collect device status information

**Interface:**
```kotlin
interface DeviceStatusService {
    fun getBatteryLevel(): Int
    fun getSignalStrength(): Int
    fun getMicrophoneStatus(): Boolean?
    fun getCameraStatus(): Boolean?
    fun getRecordingStatus(): Boolean?
}
```

#### 4. Data Transmission Module
**Responsibility:** Send device pings to backend and handle queuing

**Interface:**
```kotlin
interface DataTransmissionService {
    suspend fun sendDevicePing(ping: DevicePing): Result<Unit>
    fun queueDevicePing(ping: DevicePing)
    suspend fun sendQueuedPings(): Result<Int>
}

data class DevicePing(
    val deviceId: String,
    val name: String,
    val latitude: Double,
    val longitude: Double,
    val accuracy: Float,
    val batteryLevel: Int,
    val signalStrength: Int,
    val microphoneStatus: Boolean?,
    val cameraStatus: Boolean?,
    val recordingStatus: Boolean?,
    val timestamp: Long
)
```

#### 5. Background Service
**Responsibility:** Coordinate periodic data collection and transmission

**Interface:**
```kotlin
class TrackingWorker : Worker() {
    override fun doWork(): Result {
        // Collect location and device status
        // Create DevicePing
        // Transmit to backend
        // Schedule next execution (30 seconds)
    }
}
```

### Web Backend Components

#### 1. Device Registration API
**Responsibility:** Register devices with name and device identifier

**Endpoints:**
```
POST /api/devices/register
Request: { name: string, deviceId: string }
Response: { deviceId: string, name: string, registered: boolean }

GET /api/devices
Response: { devices: Array<Device> }
```

#### 2. Location Ping API
**Responsibility:** Receive and store device pings

**Endpoints:**
```
POST /api/pings
Request: {
  deviceId: string,
  name: string,
  latitude: number,
  longitude: number,
  accuracy: number,
  batteryLevel: number,
  signalStrength: number,
  microphoneStatus: boolean?,
  cameraStatus: boolean?,
  recordingStatus: boolean?,
  timestamp: number
}
Response: { success: boolean, pingId: string }
```

#### 3. Location Query API
**Responsibility:** Provide current locations for dashboard

**Endpoints:**
```
GET /api/locations
Response: {
  locations: Array<{
    deviceId: string,
    name: string,
    latitude: number,
    longitude: number,
    accuracy: number,
    batteryLevel: number,
    signalStrength: number,
    microphoneStatus: boolean?,
    cameraStatus: boolean?,
    recordingStatus: boolean?,
    lastUpdate: number
  }>
}

GET /api/locations/:deviceId
Response: { location: LocationData }
```

#### 4. Manual Update API
**Responsibility:** Trigger immediate device updates

**Endpoints:**
```
POST /api/devices/:deviceId/update
Response: { success: boolean, message: string }
```

**Note:** Manual update implementation can use push notifications (Firebase Cloud Messaging) or polling mechanism where devices check for update requests.

### Web Dashboard Components

#### 1. Map Display Component
**Responsibility:** Render the map and device markers

**Interface:**
```javascript
class MapDisplay {
  constructor(containerId, options)
  
  // Initialize map with OpenStreetMap tiles
  initialize()
  
  // Add or update device marker
  updateMarker(deviceId, latitude, longitude, data)
  
  // Remove device marker
  removeMarker(deviceId)
  
  // Center map to show all markers
  fitBounds()
  
  // Handle marker click events
  onMarkerClick(callback)
}
```

#### 2. Info Drawer Component
**Responsibility:** Display detailed device information

**Interface:**
```javascript
class InfoDrawer {
  constructor(containerId)
  
  // Open drawer with device data
  open(deviceData)
  
  // Close drawer
  close()
  
  // Update displayed data
  update(deviceData)
  
  // Handle close events
  onClose(callback)
}
```

#### 3. Data Refresh Service
**Responsibility:** Poll backend for location updates

**Interface:**
```javascript
class DataRefreshService {
  constructor(apiClient, refreshInterval)
  
  // Start polling for updates
  start()
  
  // Stop polling
  stop()
  
  // Trigger manual refresh
  refresh()
  
  // Register callback for new data
  onDataUpdate(callback)
}
```

#### 4. API Client
**Responsibility:** Communicate with backend APIs

**Interface:**
```javascript
class ApiClient {
  constructor(baseUrl)
  
  // Fetch all device locations
  async getLocations()
  
  // Fetch specific device location
  async getDeviceLocation(deviceId)
  
  // Trigger manual device update
  async triggerManualUpdate(deviceId)
}
```

## Data Models

### Database Schema

#### devices Table
```sql
CREATE TABLE devices (
  id VARCHAR(36) PRIMARY KEY,
  device_id VARCHAR(255) UNIQUE NOT NULL,
  name VARCHAR(255) NOT NULL,
  registered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  last_seen TIMESTAMP,
  is_active BOOLEAN DEFAULT TRUE,
  INDEX idx_device_id (device_id)
);
```

#### location_pings Table
```sql
CREATE TABLE location_pings (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  device_id VARCHAR(36) NOT NULL,
  name VARCHAR(255) NOT NULL,
  latitude DECIMAL(10, 8) NOT NULL,
  longitude DECIMAL(11, 8) NOT NULL,
  accuracy FLOAT,
  battery_level INT,
  signal_strength INT,
  microphone_status BOOLEAN,
  camera_status BOOLEAN,
  recording_status BOOLEAN,
  ping_timestamp BIGINT NOT NULL,
  received_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (device_id) REFERENCES devices(id) ON DELETE CASCADE,
  INDEX idx_device_timestamp (device_id, ping_timestamp DESC),
  INDEX idx_received_at (received_at DESC)
);
```

### Data Transfer Objects

#### DevicePing (Android → Backend)
```json
{
  "deviceId": "string",
  "name": "Mother",
  "latitude": 21.4225,
  "longitude": 39.8262,
  "accuracy": 15.5,
  "batteryLevel": 85,
  "signalStrength": -70,
  "microphoneStatus": false,
  "cameraStatus": false,
  "recordingStatus": false,
  "timestamp": 1704067200000
}
```

#### LocationData (Backend → Dashboard)
```json
{
  "deviceId": "string",
  "name": "Mother",
  "latitude": 21.4225,
  "longitude": 39.8262,
  "accuracy": 15.5,
  "batteryLevel": 85,
  "signalStrength": -70,
  "microphoneStatus": false,
  "cameraStatus": false,
  "recordingStatus": false,
  "lastUpdate": 1704067200000,
  "isStale": false
}
```

#### RegistrationToken
```json
{
  "deviceId": "string",
  "name": "Mother",
  "registeredAt": 1704067200000
}
```


## Correctness Properties

A property is a characteristic or behavior that should hold true across all valid executions of a system—essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.

### Property 1: Device Registration with Name
*For any* valid name string, when submitted to the device registration endpoint with a device identifier, the Web_Backend should successfully register the device and return a registration confirmation.

**Validates: Requirements 1.2, 1.3**

### Property 2: Device Registration Persistence
*For any* registered device, the device name and identifier should be stored in the database and retrievable for location tracking.

**Validates: Requirements 1.4**

### Property 3: Name-Based Device Identification
*For any* registered device, the name entered by the user should be used as the device identifier on the map display.

**Validates: Requirements 1.5**

### Property 4: Automatic Registration Starts Tracking
*For any* successful device registration from the Tracker_App, the app should automatically start a tracking session and begin collecting location data.

**Validates: Requirements 2.4**

### Property 5: Active Session Collects Location
*For any* active tracking session, the Tracker_App should continuously collect GPS location data at regular intervals.

**Validates: Requirements 3.1**

### Property 6: Background Operation Persistence
*For any* tracking session, when the app is moved to the background, location collection and transmission should continue without interruption.

**Validates: Requirements 3.2**

### Property 7: Ping Transmission Interval
*For any* active tracking session, Device_Ping transmissions should occur at 30-second intervals (±5 seconds tolerance for system delays).

**Validates: Requirements 3.3**

### Property 8: Service Persistence Across Restarts
*For any* active tracking session, when the device is restarted, the tracking service should automatically resume unless explicitly stopped by the user.

**Validates: Requirements 3.5**

### Property 9: Device Ping Completeness
*For any* Device_Ping transmitted to the backend, it should contain all required fields: device ID, name, GPS coordinates, battery level, signal strength, timestamp, and any enabled optional fields (microphone, camera, recording status).

**Validates: Requirements 4.1, 4.2, 4.3, 4.4, 4.5, 4.6, 4.7**

### Property 10: Map Displays All Active Devices
*For any* Web_Dashboard load, the map should display Device_Markers for all devices that have sent a ping within the last 5 minutes.

**Validates: Requirements 5.2**

### Property 11: Location Update Timeliness
*For any* Device_Ping received by the Web_Backend, the corresponding Device_Marker on the Web_Dashboard should update its position within 5 seconds.

**Validates: Requirements 5.3**

### Property 12: Marker Click Opens Drawer
*For any* Device_Marker on the map, when clicked, the Info_Drawer should open and display information for that specific device.

**Validates: Requirements 6.1**

### Property 13: Info Drawer Completeness
*For any* opened Info_Drawer, it should display all required device information: device name, location coordinates, last update timestamp, battery level, signal strength, and any available optional status fields.

**Validates: Requirements 6.2, 6.3, 6.4, 6.5, 6.6, 6.7**

### Property 14: Drawer Close Interaction
*For any* opened Info_Drawer, clicking outside the drawer or on the close button should close the drawer.

**Validates: Requirements 6.8**

### Property 15: Manual Update Triggers Signal
*For any* manual update request triggered for a device, the Web_Backend should send a signal or notification to the corresponding Tracker_App.

**Validates: Requirements 7.2**

### Property 16: Manual Update Immediate Response
*For any* manual update signal received by the Tracker_App, the app should immediately collect current location and device status and transmit a Device_Ping.

**Validates: Requirements 7.3**

### Property 17: Manual Update Loading Indicator
*For any* manual update request in progress, the Web_Dashboard should display a loading indicator until the update completes or times out.

**Validates: Requirements 7.5**

### Property 18: Device Ping Persistence
*For any* Device_Ping received by the Web_Backend, the data should be stored in the MySQL database with all fields intact (including device name) and a timestamp of when it was received.

**Validates: Requirements 8.1, 8.6**

### Property 19: Device Registration Persistence  
*For any* device registered in the system, the device information (name and device identifier) should be stored in the MySQL database and retrievable for tracking.

**Validates: Requirements 8.2**

### Property 21: Tracking Requires Consent
*For any* attempt to start a tracking session, the Tracker_App should not begin collecting or transmitting location data until the user has explicitly granted consent.

**Validates: Requirements 9.2**

### Property 22: Active Tracking Notification
*For any* active tracking session, the Tracker_App should display a persistent notification indicating that tracking is active.

**Validates: Requirements 9.3**

### Property 23: Stop Tracking Ceases Transmission
*For any* tracking session, when the user stops tracking, the Tracker_App should immediately cease transmitting Device_Ping data and notify the Web_Backend of the session termination.

**Validates: Requirements 9.5**

### Property 24: Concurrent Session Support
*For any* set of up to 10 concurrent tracking sessions, the Web_Backend should successfully receive, process, and store all Device_Ping data without errors or data loss.

**Validates: Requirements 10.1**

### Property 25: All Active Devices Displayed
*For any* set of active tracking sessions, the Web_Dashboard should display Device_Markers for all devices simultaneously without omission.

**Validates: Requirements 10.2**

### Property 26: Concurrent Ping Processing
*For any* set of Device_Pings received simultaneously, the Web_Backend should process and store all pings without data loss or corruption.

**Validates: Requirements 10.3**

### Property 27: Device Data Isolation
*For any* two different devices, the location and status data stored for one device should not affect or mix with the data for the other device.

**Validates: Requirements 10.4**

### Property 28: Offline Data Queuing
*For any* Device_Ping that fails to transmit due to network unavailability, the Tracker_App should queue the ping locally for later transmission.

**Validates: Requirements 12.1**

### Property 29: Queue Transmission on Reconnect
*For any* queued Device_Ping data, when network connectivity is restored, the Tracker_App should transmit all queued pings to the Web_Backend in chronological order.

**Validates: Requirements 12.2**

### Property 30: Transmission Retry Logic
*For any* Device_Ping transmission that fails, the Tracker_App should retry up to 3 times with exponential backoff before queuing for later.

**Validates: Requirements 12.3**

### Property 31: Backend Unreachable Warning
*For any* period when the Web_Backend is unreachable, the Tracker_App should display a warning to the user while continuing to collect location data locally.

**Validates: Requirements 12.4**

### Property 32: Stale Marker Indication
*For any* Device_Marker that has not received an update for more than 2 minutes, the Web_Dashboard should visually indicate the marker as stale or outdated.

**Validates: Requirements 12.5**

### Property 33: Database Error Handling
*For any* database error encountered by the Web_Backend, the system should log the error details and return an appropriate error response to the client without crashing.

**Validates: Requirements 12.6**

## Error Handling

### Android Application Error Handling

**Network Errors:**
- Implement exponential backoff retry mechanism (3 attempts)
- Queue failed pings locally using Room database or SharedPreferences
- Display connection status to user with warning icon
- Automatically retry queued pings when connectivity restored

**Location Errors:**
- Handle GPS unavailable: fall back to network location provider
- Handle location permission denied: display clear message and request permission
- Handle location timeout: use last known location with accuracy indicator
- Log all location errors for debugging

**Registration Errors:**
- Handle invalid device ID: display error message
- Handle network timeout during registration: display retry option
- Clear local registration data on registration failure

**Battery and Resource Management:**
- Reduce ping frequency when battery is low (<15%)
- Use efficient location update strategies (fused location provider)
- Release resources when app is stopped
- Handle doze mode and app standby restrictions

### Web Backend Error Handling

**Database Errors:**
- Wrap all database operations in try-catch blocks
- Log errors with context (query, parameters, timestamp)
- Return appropriate HTTP status codes (500 for server errors)
- Implement connection pooling with retry logic
- Handle connection timeouts gracefully

**Data Validation Errors:**
- Validate all incoming data against schemas
- Return 400 Bad Request with descriptive error messages
- Sanitize inputs to prevent SQL injection
- Validate coordinate ranges (latitude: -90 to 90, longitude: -180 to 180)
- Validate battery level (0-100), signal strength ranges
- Validate device name (non-empty, reasonable length)

**Rate Limiting:**
- Implement rate limiting per device (max 3 pings per minute)
- Return 429 Too Many Requests when limit exceeded
- Use sliding window or token bucket algorithm

**API Errors:**
- Return consistent error response format:
  ```json
  {
    "error": true,
    "message": "Human-readable error message",
    "code": "ERROR_CODE",
    "timestamp": 1704067200000
  }
  ```

### Web Dashboard Error Handling

**Network Errors:**
- Display user-friendly error messages for failed API calls
- Implement automatic retry with exponential backoff
- Show offline indicator when backend is unreachable
- Cache last known positions for display during outages

**Map Rendering Errors:**
- Handle tile loading failures gracefully
- Provide fallback to alternative tile servers
- Display error message if map fails to initialize
- Implement map bounds validation

**Data Display Errors:**
- Handle missing or malformed device data
- Display "N/A" or placeholder for missing fields
- Validate data before rendering (prevent XSS)
- Handle empty states (no active devices)

**Real-time Update Errors:**
- Handle polling failures with retry logic
- Display staleness indicator for outdated data
- Implement connection health monitoring
- Gracefully degrade to manual refresh if auto-update fails

## Testing Strategy

### Overview

The testing strategy employs a dual approach combining unit tests for specific examples and edge cases with property-based tests for universal correctness properties. This comprehensive approach ensures both concrete functionality and general system behavior are validated.

### Unit Testing

Unit tests focus on specific examples, edge cases, and integration points:

**Android Application:**
- Test specific device registration flows (valid name, empty name, network timeout)
- Test permission request handling on first launch
- Test consent screen display on first launch
- Test UI state transitions (tracking active → stopped)
- Test specific location accuracy scenarios
- Test battery level edge cases (0%, 100%, low battery)
- Test configuration loading (development vs production URLs)

**Web Backend:**
- Test specific API endpoints with example requests
- Test database schema creation and migrations
- Test device registration with specific names
- Test coordinate validation edge cases (poles, date line, invalid ranges)
- Test configuration loading (development vs production database)
- Test specific error responses (404, 400, 500)

**Web Dashboard:**
- Test map initialization with specific coordinates
- Test marker rendering with example device data
- Test drawer opening/closing with specific interactions
- Test initial map centering with example device sets
- Test empty state display (no active devices)
- Test specific error message displays

### Property-Based Testing

Property-based tests validate universal properties across randomized inputs. Each test should run a minimum of 100 iterations to ensure comprehensive coverage.

**Testing Library Selection:**
- Android (Kotlin): Use Kotest Property Testing or kotlinx-quickcheck
- Backend (Node.js): Use fast-check
- Backend (PHP): Use Eris or php-quickcheck
- Frontend (JavaScript): Use fast-check

**Property Test Configuration:**
Each property test must:
- Run minimum 100 iterations
- Include a comment tag referencing the design property
- Tag format: `// Feature: umrah-family-tracker, Property N: [property text]`

**Property Test Examples:**

*Property 1: Device Registration with Name*
```javascript
// Feature: umrah-family-tracker, Property 1: Device Registration with Name
test('device registration with name succeeds', () => {
  fc.assert(
    fc.property(
      fc.record({
        name: fc.string({ minLength: 1, maxLength: 50 }),
        deviceId: fc.uuid()
      }),
      async (registration) => {
        // Attempt registration
        const result = await registerDevice(registration.name, registration.deviceId);
        
        // Should receive valid registration confirmation
        expect(result.deviceId).toBeDefined();
        expect(result.name).toBe(registration.name);
        expect(result.registered).toBe(true);
      }
    ),
    { numRuns: 100 }
  );
});
```

*Property 9: Device Ping Completeness*
```kotlin
// Feature: umrah-family-tracker, Property 9: Device Ping Completeness
class DevicePingPropertyTest : StringSpec({
    "all device pings contain required fields" {
        checkAll(100, Arb.devicePing()) { ping ->
            val json = ping.toJson()
            
            json.shouldContainKey("deviceId")
            json.shouldContainKey("name")
            json.shouldContainKey("latitude")
            json.shouldContainKey("longitude")
            json.shouldContainKey("batteryLevel")
            json.shouldContainKey("signalStrength")
            json.shouldContainKey("timestamp")
            
            // Validate ranges
            json["latitude"].toDouble() shouldBeInRange -90.0..90.0
            json["longitude"].toDouble() shouldBeInRange -180.0..180.0
            json["batteryLevel"].toInt() shouldBeInRange 0..100
        }
    }
})
```

*Property 28: Offline Data Queuing*
```kotlin
// Feature: umrah-family-tracker, Property 28: Offline Data Queuing
class OfflineQueuePropertyTest : StringSpec({
    "failed pings are queued locally" {
        checkAll(100, Arb.list(Arb.devicePing(), 1..20)) { pings ->
            // Simulate offline mode
            networkSimulator.setOffline()
            
            // Attempt to send pings
            pings.forEach { transmissionService.sendDevicePing(it) }
            
            // Verify all pings are queued
            val queuedPings = localQueue.getAll()
            queuedPings.size shouldBe pings.size
            queuedPings shouldContainAll pings
        }
    }
})
```

*Property 27: Device Data Isolation*
```javascript
// Feature: umrah-family-tracker, Property 27: Device Data Isolation
test('device data remains isolated', () => {
  fc.assert(
    fc.property(
      fc.array(fc.devicePing(), { minLength: 2, maxLength: 10 }),
      async (pings) => {
        // Store pings for different devices
        await Promise.all(pings.map(ping => storePing(ping)));
        
        // Retrieve data for each device
        for (const ping of pings) {
          const deviceData = await getDeviceData(ping.deviceId);
          
          // Should only contain data for this device
          deviceData.every(d => d.deviceId === ping.deviceId).should.be.true;
          
          // Should not contain data from other devices
          const otherDeviceIds = pings
            .filter(p => p.deviceId !== ping.deviceId)
            .map(p => p.deviceId);
          deviceData.every(d => !otherDeviceIds.includes(d.deviceId)).should.be.true;
        }
      }
    ),
    { numRuns: 100 }
  );
});
```

### Integration Testing

Integration tests validate component interactions:

**Android to Backend:**
- Test complete device registration flow (app → backend → database)
- Test ping transmission flow (app → backend → database → dashboard)
- Test manual update flow (dashboard → backend → app → backend)

**Backend to Database:**
- Test data persistence flows
- Test query performance with realistic data volumes
- Test transaction handling and rollback

**Dashboard to Backend:**
- Test real-time update polling
- Test manual update triggering

### End-to-End Testing

Automated E2E tests for critical user journeys:
- Complete pilgrim tracking flow (install → enter name → track → view on dashboard)
- Manual update request flow
- Offline/online transition handling
- Multi-device tracking scenario

### Performance Testing

**Load Testing:**
- Test backend with 10+ concurrent devices sending pings
- Test dashboard rendering with 10+ device markers
- Test database query performance with 10,000+ location records

**Battery Testing:**
- Measure battery drain over 8-hour tracking session
- Test battery optimization strategies
- Validate reduced frequency mode when battery is low

### Security Testing

**Data Security:**
- Test SQL injection prevention
- Test XSS prevention in dashboard
- Verify secure transmission (HTTPS)

**Privacy Testing:**
- Verify consent is required before tracking
- Verify secure transmission (HTTPS)

### Test Coverage Goals

- Unit test coverage: >80% for business logic
- Property test coverage: All 19 correctness properties implemented (reduced from 33 after removing authentication)
- Integration test coverage: All major component interactions
- E2E test coverage: All critical user journeys

### Continuous Integration

- Run unit tests on every commit
- Run property tests on every pull request
- Run integration tests nightly
- Run E2E tests before deployment
- Monitor test execution time and optimize slow tests
