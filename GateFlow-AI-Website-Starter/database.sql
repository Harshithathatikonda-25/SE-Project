CREATE DATABASE IF NOT EXISTS gateflow_ai;
USE gateflow_ai;

CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('Admin','Security Guard') DEFAULT 'Security Guard',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE vehicles (
    vehicle_id INT AUTO_INCREMENT PRIMARY KEY,
    vehicle_number VARCHAR(20) UNIQUE NOT NULL,
    owner_name VARCHAR(100) NOT NULL,
    vehicle_type VARCHAR(50),
    department VARCHAR(100),
    phone VARCHAR(20),
    status ENUM('Authorized','Blocked','Pending') DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE entry_exit (
    record_id INT AUTO_INCREMENT PRIMARY KEY,
    vehicle_number VARCHAR(20) NOT NULL,
    entry_time DATETIME,
    exit_time DATETIME,
    status ENUM('INSIDE','EXITED') DEFAULT 'INSIDE',
    access_type ENUM('ANPR','Manual') DEFAULT 'ANPR',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE alerts (
    alert_id INT AUTO_INCREMENT PRIMARY KEY,
    vehicle_number VARCHAR(20),
    alert_type VARCHAR(100) NOT NULL,
    description TEXT,
    severity ENUM('Low','Medium','High','Critical') DEFAULT 'Medium',
    status ENUM('New','Acknowledged','Resolved') DEFAULT 'New',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE camera_events (
    event_id INT AUTO_INCREMENT PRIMARY KEY,
    camera_name VARCHAR(100),
    event_type VARCHAR(100),
    description TEXT,
    event_time DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE crowd_monitoring (
    monitoring_id INT AUTO_INCREMENT PRIMARY KEY,
    location VARCHAR(100),
    crowd_count INT DEFAULT 0,
    density_level ENUM('Low','Medium','High','Critical'),
    recorded_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO vehicles
(vehicle_number, owner_name, vehicle_type, department, phone, status)
VALUES
('TS09AB1234','Demo User','Car','Administration','9000000000','Authorized'),
('TS10CD5678','Demo Student','Bike','Student','9000000001','Authorized'),
('TS11EF9012','Blocked Demo','Car','Unknown','9000000002','Blocked');

INSERT INTO entry_exit
(vehicle_number, entry_time, status, access_type)
VALUES
('TS09AB1234', NOW(), 'INSIDE', 'ANPR');

INSERT INTO alerts
(vehicle_number, alert_type, description, severity, status)
VALUES
('TS11EF9012','Unauthorized Vehicle','Blocked vehicle detected at campus gate.','High','New');
