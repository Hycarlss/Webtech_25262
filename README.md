## Project Description

This project is a Vue CLI-based Hostel Management application.

The application will be gradually improved through several lab activities, including:

1. Vue component refactoring
2. Vue Router integration
3. API integration using JSON Server
4. Fetch API and Axios
5. Frontend–backend communication

---

## Project Setup

After cloning this repository, run:

```bash
npm install
```

MySQL Database Setup
```bash
CREATE DATABASE IF NOT EXISTS hostel_management;
USE hostel_management;

CREATE TABLE IF NOT EXISTS users (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    uuid VARCHAR(50) NOT NULL,
    name VARCHAR(150) NOT NULL,
    matrixNumber VARCHAR(20),
    email VARCHAR(150) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    hostelBlock VARCHAR(20),
    roomNumber VARCHAR(20),
    role ENUM('student', 'staff/admin') NOT NULL DEFAULT 'student',
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    reset_token VARCHAR(255),
    reset_expires DATETIME
);

CREATE TABLE IF NOT EXISTS hostel_blocks (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    block_name VARCHAR(20) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT IGNORE INTO hostel_blocks (id, block_name, created_at)
VALUES 
(1, 'Block A', '2026-06-20 22:18:17'),
(2, 'Block B', '2026-06-20 22:18:17'),
(3, 'Block C', '2026-06-20 22:18:17');

-- Additional tables for Monitoring, Booking, and Analytics Modules
CREATE TABLE IF NOT EXISTS rooms (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    number VARCHAR(20) NOT NULL,
    block VARCHAR(20) NOT NULL,
    capacity INT(11) NOT NULL DEFAULT 2,
    occupied INT(11) NOT NULL DEFAULT 0,
    status ENUM('Available', 'Full', 'Maintenance') NOT NULL DEFAULT 'Available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS reports (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    room VARCHAR(50) NOT NULL,
    studentName VARCHAR(150) NOT NULL,
    dateSubmitted DATE NOT NULL,
    assignedStaff VARCHAR(150) DEFAULT 'Unassigned',
    deadline DATE NULL,
    status ENUM('Pending', 'In Progress', 'Completed') NOT NULL DEFAULT 'Pending',
    category VARCHAR(50) DEFAULT NULL,
    priority VARCHAR(50) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS facilities (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    category VARCHAR(150) NOT NULL,
    description TEXT,
    capacity INT(11) NOT NULL,
    amenities JSON DEFAULT NULL,
    availability TINYINT(1) NOT NULL DEFAULT 1,
    status VARCHAR(50) NOT NULL DEFAULT 'Available',
    restricted TINYINT(1) NOT NULL DEFAULT 0,
    authorizedRoles JSON DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS bookings (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    userId VARCHAR(50) NOT NULL,
    userName VARCHAR(150) NOT NULL,
    studentName VARCHAR(150) NOT NULL,
    facilityId VARCHAR(50) NOT NULL,
    facilityName VARCHAR(150) NOT NULL,
    date DATE NOT NULL,
    startTime TIME NOT NULL,
    endTime TIME NOT NULL,
    purpose TEXT,
    status ENUM('Pending', 'Approved', 'Rejected') NOT NULL DEFAULT 'Pending',
    rejectionReason VARCHAR(255) DEFAULT NULL,
    createdAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    approvedAt DATETIME DEFAULT NULL,
    rejectedAt DATETIME DEFAULT NULL
);

CREATE TABLE IF NOT EXISTS analytics_reports (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    summary VARCHAR(255) NOT NULL,
    filterText VARCHAR(255) NOT NULL,
    date DATE NOT NULL,
    createdAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    createdBy VARCHAR(150) NOT NULL,
    filters JSON NOT NULL,
    snapshot JSON NOT NULL
);

CREATE TABLE IF NOT EXISTS blocked_slots (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    facilityId VARCHAR(50) NOT NULL,
    facilityName VARCHAR(150) NOT NULL,
    date DATE NOT NULL,
    startTime TIME NOT NULL,
    endTime TIME NOT NULL,
    reason VARCHAR(255) NOT NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'Blocked',
    createdAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    createdBy VARCHAR(150) NOT NULL
);

CREATE TABLE IF NOT EXISTS booking_logs (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    timestamp DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    userId VARCHAR(50) NOT NULL,
    userName VARCHAR(150) NOT NULL,
    facilityId VARCHAR(50) NOT NULL,
    facilityName VARCHAR(150) NOT NULL,
    action VARCHAR(100) NOT NULL
);

CREATE TABLE IF NOT EXISTS notifications (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    userId VARCHAR(50) NOT NULL,
    message VARCHAR(255) NOT NULL,
    type VARCHAR(100) NOT NULL,
    `read` TINYINT(1) NOT NULL DEFAULT 0,
    createdAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Seed mock rooms
INSERT IGNORE INTO rooms (id, number, block, capacity, occupied, status) VALUES 
(1, 'A-205', 'A', 2, 1, 'Available'),
(2, 'A-211', 'A', 2, 2, 'Full'),
(3, 'A-215', 'A', 2, 1, 'Available'),
(4, 'B-101', 'B', 2, 0, 'Available'),
(5, 'B-102', 'B', 4, 0, 'Available'),
(6, 'C-221', 'C', 2, 1, 'Available'),
(7, 'C-222', 'C', 2, 0, 'Maintenance');

-- Seed mock facilities
INSERT IGNORE INTO facilities (id, name, category, description, capacity, amenities, availability, status, restricted, authorizedRoles) VALUES
(1, 'Study Room', 'Study Room', 'Quiet group study space with whiteboards and comfortable seating.', 20, '["Air Conditioning", "Whiteboard", "Power Outlets", "Wi-Fi"]', 1, 'Available', 0, '["student", "staff/admin"]'),
(2, 'Laundry Room', 'Laundry Room', 'Shared laundry facility with washing machines, dryers, and folding tables.', 8, '["Washing Machines", "Dryers", "Folding Table"]', 1, 'Available', 0, '["student", "staff/admin"]'),
(3, 'Sports Court', 'Sports Court', 'Outdoor multipurpose court for basketball, futsal, and recreation.', 30, '["Lighting", "Equipment Storage", "Outdoor Seating"]', 1, 'Available', 0, '["student", "staff/admin"]'),
(4, 'Common Hall', 'Common Hall', 'Large hostel hall for meetings, events, presentations, and club sessions.', 100, '["Projector", "Sound System", "Stage", "Air Conditioning"]', 0, 'Maintenance', 1, '["staff/admin"]');
```

Backend Setup
```bash
cd backend 
php -S localhost:8000 -t public 
```

Frontend Setup
```bash
npm run serve
open at http://localhost:8080/ 
```

- Register account
- Login account