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

CREATE TABLE users (
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

CREATE TABLE hostel_blocks (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    block_name VARCHAR(20) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO hostel_blocks (id, block_name, created_at)
VALUES 
(1, 'Block A', '2026-06-20 22:18:17'),
(2, 'Block B', '2026-06-20 22:18:17'),
(3, 'Block C', '2026-06-20 22:18:17');
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