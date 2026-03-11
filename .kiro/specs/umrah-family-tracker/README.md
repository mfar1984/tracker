# Umrah Family Tracker - Backend API

Laravel backend API for the Umrah Family Tracker system.

## Requirements

- PHP 8.2 or higher
- Composer
- MySQL 5.7+
- Node.js and NPM (for frontend assets)

## Installation

1. Install dependencies:
```bash
composer install
```

2. Configure environment:
```bash
cp .env .env.local  # For local development
```

3. Generate application key:
```bash
php artisan key:generate
```

4. Create database:
```bash
mysql -u root -p
CREATE DATABASE hajj;
exit;
```

5. Run migrations:
```bash
php artisan migrate
```

## Configuration

### Development Environment

The `.env` file is configured for local development:
- Database: localhost, root/root, hajj database
- APP_URL: http://localhost:8000
- APP_DEBUG: true

### Production Environment

Use `.env.production` as a template for production deployment:
- Database: localhost, production credentials
- APP_URL: https://hajj.sibu.org.my
- APP_DEBUG: false
- Update APP_KEY with a new generated key
- Update DB_PASSWORD with production password

## Running the Application

### Development Server

```bash
php artisan serve
```

The API will be available at http://localhost:8000

### Production Deployment

1. Copy `.env.production` to `.env` on production server
2. Update production-specific values (APP_KEY, DB_PASSWORD)
3. Run migrations:
```bash
php artisan migrate --force
```
4. Optimize for production:
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## CORS Configuration

CORS is configured to allow all origins for development. For production, update `config/cors.php`:
- Set `allowed_origins` to specific domains
- Configure `supports_credentials` if needed

## API Endpoints

API endpoints will be available under `/api` prefix:
- POST /api/devices/register - Register device
- GET /api/devices - List devices
- POST /api/pings - Submit location ping
- GET /api/locations - Get all device locations
- GET /api/locations/{deviceId} - Get specific device location
- POST /api/devices/{deviceId}/update - Trigger manual update

## Testing

Run tests:
```bash
php artisan test
```

## Database

The application uses MySQL database named `hajj` with the following tables:
- devices - Device registration information
- location_pings - Location and status data
- Standard Laravel tables (users, cache, jobs, etc.)
