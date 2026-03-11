# Requirements Document: Umrah Family Tracker

## Introduction

The Umrah Family Tracker is a family safety tracking system designed specifically for umrah and hajj pilgrims. The system enables family members to track each other's locations during their pilgrimage journey with full consent and transparency. The system consists of an Android mobile application that collects and transmits location and device status data, and a web-based backend that displays real-time tracking information on a map interface.

## Glossary

- **Pilgrim**: A family member participating in umrah or hajj who has consented to location tracking
- **Tracker_App**: The Android mobile application installed on pilgrims' devices
- **Web_Backend**: The server-side application that receives, stores, and displays tracking data
- **Web_Dashboard**: The web-based user interface displaying the map and device information
- **Device_Ping**: A data transmission from the Tracker_App containing location and device status
- **Tracking_Session**: An active period during which a pilgrim's device is transmitting location data
- **Device_Marker**: A visual indicator on the map representing a pilgrim's current location
- **Info_Drawer**: A sliding panel that displays detailed device information for a selected pilgrim
- **Manual_Update**: An administrator-triggered request for immediate device location update

## Requirements

### Requirement 1: Simple Device Registration

**User Story:** As a pilgrim, I want to quickly start using the tracking app by just entering my name, so that I can begin tracking without complex account setup.

#### Acceptance Criteria

1. WHEN the Tracker_App is first launched, THE Tracker_App SHALL prompt the user to enter their name
2. WHEN a user enters a name, THE Tracker_App SHALL automatically register the device with that name
3. THE Web_Backend SHALL accept device registration with only a name and device identifier
4. THE Web_Backend SHALL store the device name and identifier in the MySQL database
5. THE Tracker_App SHALL use the entered name as the device identifier on the map

### Requirement 2: Android Application Installation and Setup

**User Story:** As a pilgrim, I want to easily install and set up the tracking app, so that my family can monitor my safety during umrah.

#### Acceptance Criteria

1. THE Tracker_App SHALL provide a simple installation process on Android devices
2. WHEN the Tracker_App is first launched, THE Tracker_App SHALL request necessary permissions for location, battery status, and optional features
3. WHEN the Tracker_App is first launched, THE Tracker_App SHALL prompt the user to enter their name
4. WHEN a pilgrim enters their name, THE Tracker_App SHALL automatically register with the Web_Backend and start a Tracking_Session
5. THE Tracker_App SHALL display a clear indication that tracking is active and the user is being monitored

### Requirement 3: Background Location Tracking

**User Story:** As a pilgrim, I want the app to track my location continuously in the background, so that my family can find me even when I'm not actively using my phone.

#### Acceptance Criteria

1. WHEN a Tracking_Session is active, THE Tracker_App SHALL collect GPS location data continuously
2. WHILE the Tracker_App is running, THE Tracker_App SHALL operate in the background even when the app is not in the foreground
3. THE Tracker_App SHALL transmit Device_Ping data to the Web_Backend every 30 seconds
4. WHEN GPS signal is unavailable, THE Tracker_App SHALL attempt to use alternative location methods and indicate reduced accuracy
5. THE Tracker_App SHALL maintain background operation across device restarts until explicitly stopped by the user

### Requirement 4: Device Status Collection

**User Story:** As a family member monitoring pilgrims, I want to see device status information, so that I can assess if a pilgrim's device is functioning properly.

#### Acceptance Criteria

1. WHEN transmitting a Device_Ping, THE Tracker_App SHALL include current GPS coordinates
2. WHEN transmitting a Device_Ping, THE Tracker_App SHALL include current battery level percentage
3. WHEN transmitting a Device_Ping, THE Tracker_App SHALL include current signal strength
4. WHEN transmitting a Device_Ping, THE Tracker_App SHALL include a timestamp of data collection
5. WHERE microphone monitoring is enabled, THE Tracker_App SHALL include microphone status in the Device_Ping
6. WHERE camera monitoring is enabled, THE Tracker_App SHALL include camera status in the Device_Ping
7. WHERE recording capability is enabled, THE Tracker_App SHALL include recording status in the Device_Ping

### Requirement 5: Real-Time Map Display

**User Story:** As a family member, I want to see all pilgrims' locations on a map in real-time, so that I can monitor their whereabouts during umrah.

#### Acceptance Criteria

1. THE Web_Dashboard SHALL display a full-screen map interface using a free mapping solution
2. WHEN the Web_Dashboard loads, THE Web_Dashboard SHALL display Device_Markers for all active pilgrims
3. WHEN a Device_Ping is received, THE Web_Backend SHALL update the corresponding Device_Marker position on the map within 5 seconds
4. THE Web_Dashboard SHALL display Device_Markers with visual indicators distinguishing different pilgrims
5. THE Web_Dashboard SHALL support map zoom and pan interactions
6. THE Web_Dashboard SHALL center the map view to show all active Device_Markers on initial load

### Requirement 6: Device Information Display

**User Story:** As a family member, I want to view detailed information about a specific pilgrim's device, so that I can check their status and last known details.

#### Acceptance Criteria

1. WHEN a user clicks on a Device_Marker, THE Web_Dashboard SHALL open an Info_Drawer sliding from the right edge
2. WHEN the Info_Drawer is open, THE Web_Dashboard SHALL display the pilgrim's name or identifier
3. WHEN the Info_Drawer is open, THE Web_Dashboard SHALL display current location coordinates
4. WHEN the Info_Drawer is open, THE Web_Dashboard SHALL display the last update timestamp
5. WHEN the Info_Drawer is open, THE Web_Dashboard SHALL display battery level percentage
6. WHEN the Info_Drawer is open, THE Web_Dashboard SHALL display signal strength
7. WHERE optional device status is available, THE Web_Dashboard SHALL display microphone, camera, and recording status
8. WHEN a user clicks outside the Info_Drawer or on a close button, THE Web_Dashboard SHALL close the Info_Drawer

### Requirement 7: Manual Device Update Trigger

**User Story:** As a family member, I want to request an immediate location update from a pilgrim's device, so that I can get the most current information when needed.

#### Acceptance Criteria

1. THE Web_Backend SHALL provide an API endpoint for triggering Manual_Update requests
2. WHEN a Manual_Update is triggered for a specific device, THE Web_Backend SHALL send a push notification or signal to the Tracker_App
3. WHEN the Tracker_App receives a Manual_Update request, THE Tracker_App SHALL immediately collect and transmit a Device_Ping
4. THE Web_Dashboard SHALL provide a user interface element to trigger Manual_Update requests for each pilgrim
5. WHEN a Manual_Update is in progress, THE Web_Dashboard SHALL display a loading indicator

### Requirement 8: Data Persistence and API

**User Story:** As a system administrator, I want all tracking data stored in the MySQL database, so that I can maintain historical records and ensure data reliability.

#### Acceptance Criteria

1. WHEN a Device_Ping is received, THE Web_Backend SHALL store the data in the MySQL database
2. THE Web_Backend SHALL store device registration information (name and device identifier) in the MySQL database
3. THE Web_Backend SHALL provide RESTful API endpoints for the Tracker_App to register devices
4. THE Web_Backend SHALL provide RESTful API endpoints for the Tracker_App to transmit Device_Ping data
5. THE Web_Backend SHALL provide RESTful API endpoints for the Web_Dashboard to retrieve current device locations
6. WHEN storing location data, THE Web_Backend SHALL include timestamps for all records

### Requirement 9: Privacy and Consent

**User Story:** As a pilgrim, I want to be clearly informed that I am being tracked, so that I can make an informed decision about participating in the tracking system.

#### Acceptance Criteria

1. WHEN the Tracker_App is first launched, THE Tracker_App SHALL display a consent screen explaining the tracking functionality
2. THE Tracker_App SHALL require explicit user consent before starting a Tracking_Session
3. WHILE a Tracking_Session is active, THE Tracker_App SHALL display a persistent notification indicating tracking is active
4. THE Tracker_App SHALL provide a clear user interface element to stop tracking
5. WHEN a user stops tracking, THE Tracker_App SHALL cease transmitting Device_Ping data and notify the Web_Backend
6. THE Tracker_App SHALL display information about what data is being collected and transmitted

### Requirement 10: Multi-User Support and Scalability

**User Story:** As a system administrator, I want the system to support multiple pilgrims simultaneously, so that entire families can use the tracking system together.

#### Acceptance Criteria

1. THE Web_Backend SHALL support at least 10 concurrent Tracking_Sessions
2. THE Web_Dashboard SHALL display Device_Markers for all active pilgrims simultaneously
3. WHEN multiple Device_Pings are received concurrently, THE Web_Backend SHALL process them without data loss
4. THE Web_Backend SHALL maintain separate tracking data for each registered device
5. THE Web_Dashboard SHALL provide visual differentiation between Device_Markers for different pilgrims

### Requirement 11: Deployment Configuration

**User Story:** As a system administrator, I want to deploy the system in both development and production environments, so that I can test changes before releasing them to users.

#### Acceptance Criteria

1. THE Web_Backend SHALL support configuration for localhost development environment
2. THE Web_Backend SHALL support configuration for hajj.sibu.org.my production environment
3. THE Web_Backend SHALL connect to MySQL database with configurable host, user, password, and database name
4. WHERE deployed in development, THE Web_Backend SHALL use localhost as the database host
5. WHERE deployed in production, THE Web_Backend SHALL use the production database configuration
6. THE Tracker_App SHALL support configurable backend URL for development and production environments

### Requirement 12: Error Handling and Reliability

**User Story:** As a pilgrim, I want the app to handle errors gracefully, so that tracking continues even when temporary issues occur.

#### Acceptance Criteria

1. WHEN network connectivity is lost, THE Tracker_App SHALL queue Device_Ping data locally
2. WHEN network connectivity is restored, THE Tracker_App SHALL transmit queued Device_Ping data to the Web_Backend
3. IF a Device_Ping transmission fails, THE Tracker_App SHALL retry transmission up to 3 times with exponential backoff
4. WHEN the Web_Backend is unreachable, THE Tracker_App SHALL display a warning to the user while continuing to collect data
5. WHEN a Device_Marker has not updated for more than 2 minutes, THE Web_Dashboard SHALL visually indicate the marker as stale
6. IF the Web_Backend encounters a database error, THE Web_Backend SHALL log the error and return an appropriate error response
