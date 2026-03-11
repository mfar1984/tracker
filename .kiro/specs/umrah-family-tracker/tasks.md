# Implementation Plan: Umrah Family Tracker

## Overview

This implementation plan breaks down the Umrah Family Tracker system into discrete coding tasks. The system uses Flutter for the cross-platform mobile application and Laravel for the web backend with MySQL database. The web dashboard will be built using Blade templates with Leaflet.js for mapping.

**Technology Stack:**
- Mobile App: Flutter (Dart)
- Backend API: Laravel (PHP)
- Database: MySQL
- Web Dashboard: Blade templates, JavaScript, Leaflet.js
- Maps: OpenStreetMap with Leaflet.js

## Tasks

- [x] 1. Set up Laravel backend project structure
  - Initialize Laravel project with required dependencies
  - Configure MySQL database connection (localhost, root/root, hajj database)
  - Set up environment configuration for development and production
  - Configure CORS for API access
  - _Requirements: 11.3, 11.4, 11.5_

- [ ] 2. Create database migrations and models
  - [x] 2.1 Create devices table migration and Device model
    - Migration: id, device_id (unique), name, registered_at, last_seen, is_active
    - Model: Device with timestamps
    - _Requirements: 1.4, 8.2_
  
  - [x] 2.2 Create location_pings table migration and LocationPing model
    - Migration: id, device_id (foreign key), name, latitude, longitude, accuracy, battery_level, signal_strength, microphone_status, camera_status, recording_status, ping_timestamp, received_at
    - Model: LocationPing with device relationship, indexes on device_id and timestamps
    - _Requirements: 8.1, 8.6_

- [ ] 3. Implement device registration API endpoints
  - [x] 3.1 Create DeviceController with registration methods
    - POST /api/devices/register - register device with name and device_id
    - GET /api/devices - list all registered devices
    - Validate device_id uniqueness
    - Validate name is non-empty
    - _Requirements: 1.2, 1.3, 1.4_
  
  - [ ]* 3.2 Write property test for device registration
    - **Property 1: Device Registration with Name**
    - **Property 2: Device Registration Persistence**
    - **Validates: Requirements 1.2, 1.3, 1.4**

- [ ] 4. Implement location ping ingestion API
  - [x] 4.1 Create LocationPingController with ping storage
    - POST /api/pings - receive and store device ping data (including name)
    - Validate latitude (-90 to 90), longitude (-180 to 180), battery (0-100)
    - Validate name is non-empty
    - Update device last_seen timestamp
    - Return success response with ping_id
    - _Requirements: 8.1, 8.6_
  
  - [ ]* 4.2 Write property test for ping persistence
    - **Property 18: Device Ping Persistence**
    - **Validates: Requirements 8.1, 8.6**
  
  - [ ]* 4.3 Write property test for concurrent ping processing
    - **Property 26: Concurrent Ping Processing**
    - **Validates: Requirements 10.3**
  
  - [ ]* 4.4 Write unit tests for coordinate validation edge cases
    - Test invalid coordinates (out of range)
    - Test boundary values (poles, date line)
    - _Requirements: 8.1_

- [ ] 5. Implement location query API
  - [x] 5.1 Create LocationController with query methods
    - GET /api/locations - return all active device locations (last 5 minutes)
    - GET /api/locations/{deviceId} - return specific device location
    - Include device info, name, and latest ping data
    - Mark devices as stale if no update for 2+ minutes
    - _Requirements: 5.2, 12.5_
  
  - [ ]* 5.2 Write property test for device data isolation
    - **Property 27: Device Data Isolation**
    - **Validates: Requirements 10.4**

- [x] 6. Checkpoint - Test backend APIs
  - Ensure all tests pass, verify API endpoints with Postman or similar tool, ask the user if questions arise.

- [ ] 7. Implement manual update trigger API
  - [x] 7.1 Create manual update endpoint and notification system
    - POST /api/devices/{deviceId}/update - trigger manual update
    - Store update request in database or cache (Redis recommended)
    - Return success response
    - _Requirements: 7.2_
  
  - [x] 7.2 Create polling endpoint for devices to check update requests
    - GET /api/devices/{deviceId}/check-updates - return pending update requests
    - Clear request after device retrieves it
    - _Requirements: 7.2_

- [ ] 8. Implement error handling and validation
  - [x] 8.1 Create API exception handler
    - Handle database errors with logging and 500 responses
    - Handle validation errors with 400 responses
    - Return consistent error JSON format
    - _Requirements: 12.6_
  
  - [ ]* 8.2 Write property test for database error handling
    - **Property 33: Database Error Handling**
    - **Validates: Requirements 12.6**
  
  - [ ]* 8.3 Write unit tests for rate limiting
    - Test max 3 pings per minute per device
    - Test 429 response when limit exceeded
    - _Requirements: Error Handling section_

- [ ] 9. Create web dashboard interface (no authentication required)
  - [x] 9.1 Create dashboard Blade template with full-screen map
    - Include Leaflet.js and OpenStreetMap tiles
    - Full width and height map container
    - Initialize map centered on Mecca (21.4225, 39.8262)
    - Add favicon using app.ico from .kiro/specs/app.ico
    - _Requirements: 5.1_
  
  - [x] 9.2 Implement device marker rendering
    - Fetch locations from /api/locations endpoint
    - Create markers for each active device
    - Use different colors/icons for different devices
    - Fit map bounds to show all markers on load
    - _Requirements: 5.2, 5.6_
  
  - [ ]* 9.3 Write unit test for initial map centering
    - Test map centers to show all markers on load
    - _Requirements: 5.6_

- [ ] 10. Implement real-time location updates on dashboard
  - [x] 10.1 Create JavaScript polling service
    - Poll /api/locations every 30 seconds
    - Update marker positions when new data received
    - Update within 5 seconds of receiving data
    - Display stale indicator for markers not updated in 2+ minutes
    - _Requirements: 5.3, 12.5_
  
  - [ ]* 10.2 Write property test for location update timeliness
    - **Property 11: Location Update Timeliness**
    - **Validates: Requirements 5.3**
  
  - [ ]* 10.3 Write property test for stale marker indication
    - **Property 32: Stale Marker Indication**
    - **Validates: Requirements 12.5**

- [ ] 11. Implement info drawer component
  - [x] 11.1 Create info drawer HTML/CSS
    - Drawer slides from right edge
    - Display device information fields
    - Close button and click-outside-to-close functionality
    - _Requirements: 6.1, 6.8_
  
  - [x] 11.2 Implement marker click handler
    - Open drawer when marker clicked
    - Populate drawer with device data: name, coordinates, timestamp, battery, signal, optional status
    - Close drawer on close button or outside click
    - _Requirements: 6.1, 6.2, 6.3, 6.4, 6.5, 6.6, 6.7, 6.8_
  
  - [ ]* 11.3 Write property test for drawer completeness
    - **Property 13: Info Drawer Completeness**
    - **Validates: Requirements 6.2, 6.3, 6.4, 6.5, 6.6, 6.7**

- [ ] 12. Implement manual update trigger UI
  - [x] 12.1 Add manual update button to info drawer
    - Button to trigger manual update for selected device
    - Display loading indicator during update
    - Call POST /api/devices/{deviceId}/update endpoint
    - _Requirements: 7.4, 7.5_
  
  - [ ]* 12.2 Write property test for manual update loading indicator
    - **Property 17: Manual Update Loading Indicator**
    - **Validates: Requirements 7.5**

- [x] 13. Checkpoint - Test web dashboard
  - Ensure all tests pass, verify dashboard functionality in browser, ask the user if questions arise.

- [x] 14. Set up Flutter project structure
  - Initialize Flutter project with required dependencies
  - Add packages: http, geolocator, permission_handler, shared_preferences, workmanager, battery_plus, connectivity_plus
  - Configure Android permissions in AndroidManifest.xml (location, battery, network)
  - Set up project structure with folders: models, services, screens, widgets
  - Configure app icon using logo.png from .kiro/specs/logo.png
  - _Requirements: 2.1_

- [ ] 15. Create Flutter data models
  - [ ] 15.1 Create DevicePing model
    - Fields: deviceId, name, latitude, longitude, accuracy, batteryLevel, signalStrength, microphoneStatus, cameraStatus, recordingStatus, timestamp
    - toJson() and fromJson() methods
    - _Requirements: 4.1, 4.2, 4.3, 4.4, 4.5, 4.6, 4.7_
  
  - [ ] 15.2 Create RegistrationToken model
    - Fields: deviceId, name, registeredAt
    - toJson() and fromJson() methods
    - _Requirements: 1.2_
  
  - [ ]* 15.3 Write property test for device ping completeness
    - **Property 9: Device Ping Completeness**
    - **Validates: Requirements 4.1, 4.2, 4.3, 4.4, 4.5, 4.6, 4.7**

- [ ] 16. Implement device registration service
  - [ ] 16.1 Create DeviceRegistrationService class
    - registerDevice(name, deviceId) - call /api/devices/register, store registration token
    - getRegistrationToken() - retrieve stored token
    - isRegistered() - check if valid registration exists
    - Use SharedPreferences for token storage
    - _Requirements: 1.2, 1.3, 2.4_
  
  - [ ]* 16.2 Write property test for automatic registration starts tracking
    - **Property 4: Automatic Registration Starts Tracking**
    - **Validates: Requirements 2.4**

- [ ] 17. Implement location tracking service
  - [ ] 17.1 Create LocationTrackingService class
    - startTracking() - begin continuous location collection
    - stopTracking() - stop location collection
    - getCurrentLocation() - get current GPS coordinates
    - isTracking() - check tracking status
    - Use geolocator package with high accuracy
    - Handle location permission requests
    - _Requirements: 3.1, 3.4_
  
  - [ ]* 17.2 Write property test for active session location collection
    - **Property 5: Active Session Collects Location**
    - **Validates: Requirements 3.1**

- [ ] 18. Implement device status service
  - [ ] 18.1 Create DeviceStatusService class
    - getBatteryLevel() - use battery_plus package
    - getSignalStrength() - use connectivity_plus or platform channels
    - getMicrophoneStatus() - optional, return null if not implemented
    - getCameraStatus() - optional, return null if not implemented
    - getRecordingStatus() - optional, return null if not implemented
    - _Requirements: 4.2, 4.3, 4.5, 4.6, 4.7_

- [ ] 19. Implement data transmission service
  - [ ] 19.1 Create DataTransmissionService class
    - sendDevicePing(ping) - POST to /api/pings endpoint (including name)
    - queueDevicePing(ping) - store failed ping locally
    - sendQueuedPings() - transmit all queued pings
    - Use http package for API calls
    - Use SharedPreferences or local database for queue
    - _Requirements: 3.3, 12.1, 12.2_
  
  - [ ]* 19.2 Write property test for offline data queuing
    - **Property 28: Offline Data Queuing**
    - **Validates: Requirements 12.1**
  
  - [ ]* 19.3 Write property test for queue transmission on reconnect
    - **Property 29: Queue Transmission on Reconnect**
    - **Validates: Requirements 12.2**
  
  - [ ]* 19.4 Write property test for transmission retry logic
    - **Property 30: Transmission Retry Logic**
    - **Validates: Requirements 12.3**

- [ ] 20. Implement background tracking worker
  - [ ] 20.1 Create TrackingWorker using WorkManager
    - Schedule periodic task every 30 seconds
    - Collect location from LocationTrackingService
    - Collect device status from DeviceStatusService
    - Create DevicePing object with name
    - Send ping via DataTransmissionService
    - Handle network errors with queuing
    - _Requirements: 3.2, 3.3, 3.5_
  
  - [ ]* 20.2 Write property test for background operation persistence
    - **Property 6: Background Operation Persistence**
    - **Validates: Requirements 3.2**
  
  - [ ]* 20.3 Write property test for ping transmission interval
    - **Property 7: Ping Transmission Interval**
    - **Validates: Requirements 3.3**
  
  - [ ]* 20.4 Write property test for service persistence across restarts
    - **Property 8: Service Persistence Across Restarts**
    - **Validates: Requirements 3.5**

- [ ] 21. Implement manual update polling
  - [ ] 21.1 Add manual update check to TrackingWorker
    - Poll /api/devices/{deviceId}/check-updates endpoint
    - If update request exists, immediately collect and send ping
    - _Requirements: 7.3_
  
  - [ ]* 21.2 Write property test for manual update immediate response
    - **Property 16: Manual Update Immediate Response**
    - **Validates: Requirements 7.3**

- [ ] 22. Create consent and name entry screens
  - [ ] 22.1 Create ConsentScreen widget
    - Display on first app launch
    - Explain tracking functionality and data collection
    - Require explicit consent button press
    - Store consent status in SharedPreferences
    - _Requirements: 9.1, 9.2_
  
  - [ ] 22.2 Create NameEntryScreen widget
    - Name input field
    - Register button calling DeviceRegistrationService
    - Error message display
    - Navigate to tracking screen on success
    - _Requirements: 2.3, 2.4_
  
  - [ ]* 22.3 Write unit test for consent screen display
    - Test consent screen appears on first launch
    - _Requirements: 9.1_
  
  - [ ]* 22.4 Write property test for tracking requires consent
    - **Property 21: Tracking Requires Consent**
    - **Validates: Requirements 9.2**

- [ ] 23. Create tracking status screen
  - [ ] 23.1 Create TrackingScreen widget
    - Display tracking active indicator
    - Show persistent notification when tracking active
    - Display connection status (online/offline warning)
    - Stop tracking button
    - Display last ping timestamp and battery level
    - _Requirements: 2.5, 9.3, 9.4, 12.4_
  
  - [ ]* 23.2 Write property test for active tracking notification
    - **Property 22: Active Tracking Notification**
    - **Validates: Requirements 9.3**
  
  - [ ]* 23.3 Write property test for backend unreachable warning
    - **Property 31: Backend Unreachable Warning**
    - **Validates: Requirements 12.4**

- [ ] 24. Implement stop tracking functionality
  - [ ] 24.1 Add stop tracking logic
    - Stop WorkManager periodic task
    - Call LocationTrackingService.stopTracking()
    - Send final ping to backend indicating session end
    - Clear persistent notification
    - _Requirements: 9.5_
  
  - [ ]* 24.2 Write property test for stop tracking ceases transmission
    - **Property 23: Stop Tracking Ceases Transmission**
    - **Validates: Requirements 9.5**

- [ ] 25. Configure environment-specific settings
  - [ ] 25.1 Create configuration file for backend URLs
    - Development: http://localhost:8000/api
    - Production: https://hajj.sibu.org.my/api
    - Load based on build configuration or environment variable
    - _Requirements: 11.6_
  
  - [ ]* 25.2 Write unit tests for configuration loading
    - Test development configuration loads localhost URL
    - Test production configuration loads production URL
    - _Requirements: 11.4, 11.5_

- [ ] 26. Checkpoint - Test Flutter app
  - Ensure all tests pass, test app on Android device or emulator, ask the user if questions arise.

- [ ] 27. Implement multi-user support testing
  - [ ]* 27.1 Write property test for concurrent session support
    - **Property 24: Concurrent Session Support**
    - **Validates: Requirements 10.1**
  
  - [ ]* 27.2 Write property test for all active devices displayed
    - **Property 25: All Active Devices Displayed**
    - **Validates: Requirements 10.2**

- [ ] 28. Create database seeders for testing
  - [ ] 28.1 Create DeviceSeeder
    - Seed test devices with names
    - _Requirements: 8.2_

- [ ] 29. Write integration tests
  - [ ]* 29.1 Write integration test for complete registration flow
    - Test app name entry → backend registration → token storage
    - _Requirements: 1.2, 2.4_
  
  - [ ]* 29.2 Write integration test for ping transmission flow
    - Test app ping → backend storage → dashboard display
    - _Requirements: 3.3, 8.1, 5.3_
  
  - [ ]* 29.3 Write integration test for manual update flow
    - Test dashboard trigger → backend signal → app response
    - _Requirements: 7.2, 7.3_

- [ ] 30. Create deployment documentation
  - [ ] 30.1 Document backend deployment steps
    - Laravel deployment to production server
    - Database migration commands
    - Environment configuration
    - SSL/HTTPS setup
    - _Requirements: 11.2_
  
  - [ ] 30.2 Document Flutter app build and distribution
    - Android APK build commands
    - Signing configuration
    - Distribution method (direct APK or Play Store)
    - _Requirements: 2.1_

- [ ] 31. Final checkpoint - End-to-end testing
  - Test complete user journey: install app → enter name → track → view on dashboard → manual update
  - Test with multiple devices simultaneously
  - Test offline/online transitions
  - Ensure all tests pass, ask the user if questions arise.

## Notes

- Tasks marked with `*` are optional test tasks and can be skipped for faster MVP
- Each task references specific requirements for traceability
- Checkpoints ensure incremental validation at key milestones
- Property tests validate universal correctness properties with 100+ iterations
- Unit tests validate specific examples and edge cases
- The implementation follows a backend-first approach, then dashboard, then mobile app
- All API endpoints should be tested with Postman or similar before integrating with Flutter
- Use Laravel's built-in validation and error handling features
- Use Flutter's best practices for state management (Provider or Riverpod recommended)
- Authentication has been simplified: no username/password required, only name entry on first launch
- Web dashboard can be open access or optionally protected with simple authentication if needed later
