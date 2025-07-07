# IoT Vehicle Tracker Backend – Laravel API

A simplified Laravel-based backend system for real-time GPS vehicle tracking and remote ignition control using microcontroller modules (e.g., Arduino) connected via GSM/GPRS.

> This is a public-safe version with mock data and generic endpoints. Production implementation includes additional security and device-specific configurations.

---

## Features

- Receive location data (lat, lng, speed, ignition status) via GSM or HTTP
- Store and timestamp GPS logs to database
- Remote kill switch command endpoint (e.g., `#START`, `#STOP`, `#MODE`)
- JWT-secured API for device-server communication
- Admin dashboard or mobile client support (optional)
- Laravel Scheduler-ready for periodic status updates

---

## Tech Stack

- Laravel 12 (REST API)
- PHP 8.x
- MySQL
- JWT Authentication
- Postman (for API testing)
- Arduino, GSM modules, Relay Module, GPS Module (Ardunino Uno, SIM800L, etc.) – external hardware

---

## API Endpoints (Sample)

| Method | Endpoint | Description |
|--------|----------|-------------|
| `POST` | `/api/device` | Device sends location, speed, ignition status|
| `POST` | `/api/device/command/{device_id}` | Server sends remote command to device |
| `GET`  | `/api/location/device/{device_id}` | Fetch recent GPS logs for a device |
| `POST` | `/api/login` | JWT auth for user login to access dashboard and other features  |

> Example device report payload:
```json
{
  "device_id": "device_001",
  "lat": "7.3934",
  "lng": "3.8948",
  "speed": "45.2",
  "ignition": "on"
}
```

## Setup Instructions
1. Clone the repository  
   ```bash
   git clone https://github.com/isamaj20/vehicle-tracker-backend-simplify.git
   ```
   
2. Install dependencies:
    ```bash
     composer install
    ```
3. Create .env file:
   ```bash
    cp .env.example .env
    php artisan key:generate
   ```
  
4. Configure DB credentials in `.env`


5. Run migrations:
   ```bash
   php artisan migrate
   ```

6. Launch the dev server:

   ```bash
   php artisan server
   ```
   
## Folder Structure
  
   ```swift
   /app/Http/Controllers/Api/   → API logic
   /app/Http/Middleware/        → Middleware for endpoint access logic
   /app/Utilities/              → API response helper class
   /app/Models/                 → Device, User,  DeviceCommand, Location Nodels
   /routes/api.php              → API routes  
   /database/migrations/        → DB schema   
   .env.example                 → Environment template
   ```
   
## Notes
    - Replace production secrets with mock data
	- Ensure API keys and SIM IDs are excluded
	- Code is educational and demo-ready; use at your discretion
	
## Author
John Isama – [LinkedIn](https://www.linkedin.com/in/isama-john-adeyi/) | [GitHub](https://github.com/isamaj20/)

## License

Open-source for learning and demonstration purposes.
Feel free to fork and adapt with credit.