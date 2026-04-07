# Attendance API - Postman Test Scenarios

## Setup

**Base URL:** `http://localhost:8000/api`

**Authentication:** All requests require Bearer token authentication
- Add to Headers: `Authorization: Bearer {your_token}`
- Get token from `/api/auth/login` endpoint

---

## Test Scenarios

### 1. Check-In (Manual)

**Endpoint:** `POST /attendance/check-in`

**Description:** Records employee check-in with location tracking

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
Accept: application/json
```

**Request Body:**
```json
{
  "branchId": "1",
  "latitude": 28.6139,
  "longitude": 77.2090,
  "qrCode": "BRANCH-001-QR-CODE"
}
```

**Success Response (201):**
```json
{
  "success": true,
  "message": "Check-in successful at 09:15 AM",
  "data": {
    "id": "1",
    "employeeId": "5",
    "branchId": "1",
    "attendanceDate": "2026-04-05",
    "status": "On Time",
    "statusReason": null,
    "checkInTime": "2026-04-05T09:15:30.000000Z",
    "checkInLatitude": "28.61390000",
    "checkInLongitude": "77.20900000",
    "checkOutTime": null,
    "checkOutLatitude": null,
    "checkOutLongitude": null,
    "workDurationMinutes": null,
    "employee": {...},
    "branch": {...},
    "scans": [...]
  }
}
```

**Error Response - Already Checked In (400):**
```json
{
  "success": false,
  "message": "You have already checked in today at 09:15 AM",
  "data": {...}
}
```

**Test Cases:**
1. ✅ First check-in of the day (should succeed)
2. ✅ Duplicate check-in attempt (should fail with 400)
3. ✅ Check-in without QR code (qrCode is optional)
4. ✅ Check-in with invalid branchId (should fail with validation error)
5. ✅ Check-in with invalid coordinates (should fail with validation error)

---

### 2. Check-Out (Manual)

**Endpoint:** `POST /attendance/check-out`

**Description:** Records employee check-out with location and calculates work duration

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
Accept: application/json
```

**Request Body:**
```json
{
  "branchId": "1",
  "latitude": 28.6140,
  "longitude": 77.2091,
  "qrCode": "BRANCH-001-QR-CODE"
}
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "Check-out successful at 05:30 PM. Total work time: 8h 15m",
  "data": {
    "id": "1",
    "employeeId": "5",
    "branchId": "1",
    "attendanceDate": "2026-04-05",
    "status": "On Time",
    "checkInTime": "2026-04-05T09:15:30.000000Z",
    "checkInLatitude": "28.61390000",
    "checkInLongitude": "77.20900000",
    "checkOutTime": "2026-04-05T17:30:45.000000Z",
    "checkOutLatitude": "28.61400000",
    "checkOutLongitude": "77.20910000",
    "workDurationMinutes": 495,
    "employee": {...},
    "branch": {...},
    "scans": [...]
  }
}
```

**Error Response - No Check-In (400):**
```json
{
  "success": false,
  "message": "No check-in record found for today. Please check in first."
}
```

**Error Response - Already Checked Out (400):**
```json
{
  "success": false,
  "message": "You have already checked out today at 05:30 PM",
  "data": {...}
}
```

**Test Cases:**
1. ✅ Check-out without check-in (should fail with 400)
2. ✅ Normal check-out after check-in (should succeed)
3. ✅ Duplicate check-out attempt (should fail with 400)
4. ✅ Early departure (before 4:30 PM - status should be "Early Departure")
5. ✅ Check-out without QR code (qrCode is optional)

---

### 3. Get Today's Attendance Status

**Endpoint:** `GET /attendance/today`

**Description:** Get current day's attendance status for the authenticated user

**Headers:**
```
Authorization: Bearer {token}
Accept: application/json
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "Today's attendance status fetched successfully.",
  "data": {
    "hasCheckedIn": true,
    "hasCheckedOut": false,
    "checkInTime": "2026-04-05T09:15:30.000000Z",
    "checkOutTime": null,
    "workDuration": null,
    "attendanceRecord": {...}
  }
}
```

**Test Cases:**
1. ✅ Before check-in (hasCheckedIn: false, hasCheckedOut: false)
2. ✅ After check-in only (hasCheckedIn: true, hasCheckedOut: false)
3. ✅ After check-out (hasCheckedIn: true, hasCheckedOut: true, workDuration populated)

---

### 4. Get My Attendance Records

**Endpoint:** `GET /attendance/my-records`

**Description:** Get attendance history for the authenticated user

**Headers:**
```
Authorization: Bearer {token}
Accept: application/json
```

**Query Parameters (Optional):**
- `startDate` - Filter from date (YYYY-MM-DD)
- `endDate` - Filter to date (YYYY-MM-DD)
- `branchId` - Filter by branch

**Example Request:**
```
GET /attendance/my-records?startDate=2026-04-01&endDate=2026-04-30
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "Attendance records fetched successfully.",
  "data": [
    {
      "id": "1",
      "employeeId": "5",
      "branchId": "1",
      "attendanceDate": "2026-04-05",
      "status": "On Time",
      "checkInTime": "2026-04-05T09:15:30.000000Z",
      "checkOutTime": "2026-04-05T17:30:45.000000Z",
      "workDurationMinutes": 495,
      "employee": {...},
      "branch": {...}
    },
    ...
  ]
}
```

**Test Cases:**
1. ✅ Get all records (no filters)
2. ✅ Filter by date range
3. ✅ Filter by branch
4. ✅ Combined filters

---

### 5. Get All Attendance Records (Admin/Manager)

**Endpoint:** `GET /attendance/all-records`

**Description:** Get all attendance records (Admin, HR Manager, Branch Manager only)

**Headers:**
```
Authorization: Bearer {token}
Accept: application/json
```

**Query Parameters (Optional):**
- `startDate` - Filter from date (YYYY-MM-DD)
- `endDate` - Filter to date (YYYY-MM-DD)
- `branchId` - Filter by branch (Admin/HR only)
- `employeeId` - Filter by employee

**Example Request:**
```
GET /attendance/all-records?branchId=1&startDate=2026-04-01
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "Attendance records fetched successfully.",
  "data": [...]
}
```

**Test Cases:**
1. ✅ Admin can see all branches
2. ✅ HR Manager can filter by branch
3. ✅ Branch Manager can only see their branch
4. ✅ Employee role should get 403 Forbidden
5. ✅ Filter by specific employee
6. ✅ Filter by date range

---

## Complete Test Flow

### Scenario: Full Day Attendance Cycle

1. **Login**
   ```
   POST /auth/login
   Body: { "email": "employee@example.com", "password": "password" }
   Save the token from response
   ```

2. **Check Today's Status (Before Check-in)**
   ```
   GET /attendance/today
   Expected: hasCheckedIn: false, hasCheckedOut: false
   ```

3. **Check-In**
   ```
   POST /attendance/check-in
   Body: {
     "branchId": "1",
     "latitude": 28.6139,
     "longitude": 77.2090,
     "qrCode": "BRANCH-001-QR"
   }
   Expected: 201 Created, status: "On Time" or "Late"
   ```

4. **Check Today's Status (After Check-in)**
   ```
   GET /attendance/today
   Expected: hasCheckedIn: true, hasCheckedOut: false
   ```

5. **Try Duplicate Check-In (Should Fail)**
   ```
   POST /attendance/check-in
   Expected: 400 Bad Request, "already checked in" message
   ```

6. **Check-Out**
   ```
   POST /attendance/check-out
   Body: {
     "branchId": "1",
     "latitude": 28.6140,
     "longitude": 77.2091,
     "qrCode": "BRANCH-001-QR"
   }
   Expected: 200 OK, workDurationMinutes calculated
   ```

7. **Check Today's Status (After Check-out)**
   ```
   GET /attendance/today
   Expected: hasCheckedIn: true, hasCheckedOut: true, workDuration populated
   ```

8. **Try Duplicate Check-Out (Should Fail)**
   ```
   POST /attendance/check-out
   Expected: 400 Bad Request, "already checked out" message
   ```

9. **View Attendance History**
   ```
   GET /attendance/my-records
   Expected: Array with today's record
   ```

---

## Database Migration

Before testing, run the migration:

```bash
php artisan migrate
```

This will update the `attendance_records` table with the new structure:
- Separate check-in and check-out times
- Location tracking for both check-in and check-out
- Automatic work duration calculation
- Attendance date field for easier querying

---

## Status Values

- **On Time** - Checked in before or within 15 minutes of expected start time (9:00 AM)
- **Late** - Checked in more than 15 minutes after expected start time
- **Early Departure** - Checked out more than 30 minutes before expected end time (5:00 PM)
- **Verified** - Manually verified by manager
- **Fraudulent** - Flagged for suspicious activity

---

## Notes

1. **QR Code is Optional** - The system supports both manual check-in/out and QR code scanning
2. **Location is Required** - Both latitude and longitude must be provided for check-in and check-out
3. **One Record Per Day** - Each employee can only have one attendance record per day per branch
4. **Work Duration** - Automatically calculated in minutes when checking out
5. **Status Logic** - Can be customized based on shift timings in the controller
