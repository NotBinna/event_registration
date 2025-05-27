CREATE DATABASE mydb_eventRegistration;
USE mydb_eventRegistration;
-- Tabel roles (opsional tapi recommended)
CREATE TABLE roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL -- e.g. guest, member, admin, keuangan, panitia
);

-- Tabel users
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role_id INT, -- FK ke tabel roles
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id)
);

-- Tabel events
CREATE TABLE events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    date DATE,
    time TIME,
    location VARCHAR(255),
    speaker VARCHAR(255),
    poster VARCHAR(255),
    price DECIMAL(10,2),
    max_participants INT,
    is_active TINYINT DEFAULT 1,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Tabel event_registration
CREATE TABLE event_registration (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    users_id INT NOT NULL,
    registered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    qr_code VARCHAR(255),
    payment_id INT, -- tambahkan kolom tapi tanpa FK dulu
    FOREIGN KEY (event_id) REFERENCES events(id),
    FOREIGN KEY (users_id) REFERENCES users(id)
);


-- Tabel payments
CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    registration_id INT NOT NULL,
    uploaded_by INT,
    proof_path VARCHAR(255),
    verified_by INT,
    status ENUM('pending', 'verified', 'rejected') DEFAULT 'pending',
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    verified_at TIMESTAMP NULL,
    FOREIGN KEY (registration_id) REFERENCES event_registration(id),
    FOREIGN KEY (uploaded_by) REFERENCES users(id),
    FOREIGN KEY (verified_by) REFERENCES users(id)
);

ALTER TABLE event_registration
ADD CONSTRAINT fk_payment_id
FOREIGN KEY (payment_id) REFERENCES payments(id);


-- Tabel event_attendance
CREATE TABLE event_attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    registration_id INT NOT NULL,
    scanned_by INT,
    scanned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (registration_id) REFERENCES event_registration(id),
    FOREIGN KEY (scanned_by) REFERENCES users(id)
);
