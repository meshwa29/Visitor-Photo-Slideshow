-- Visitor Photo Slideshow - database script
-- Import in phpMyAdmin: Import tab -> choose this file -> Go.
-- Default admin login:  admin / Admin@123   (change it after first login)

CREATE DATABASE IF NOT EXISTS visitor_photo_slideshow
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE visitor_photo_slideshow;

CREATE TABLE IF NOT EXISTS visitors (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    visitor_code  VARCHAR(20)  NOT NULL,
    visitor_name  VARCHAR(100) NOT NULL,
    created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_visitors_code (visitor_code),
    KEY idx_visitors_name (visitor_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS photos (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    visitor_id   INT UNSIGNED NOT NULL,
    photo_name   VARCHAR(255) NOT NULL,
    photo_path   VARCHAR(255) NOT NULL,
    status       ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    consent_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    uploaded_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_photos_name (photo_name),
    KEY idx_photos_visitor (visitor_id),
    KEY idx_photos_status_uploaded (status, uploaded_at),
    CONSTRAINT fk_photos_visitor FOREIGN KEY (visitor_id)
        REFERENCES visitors (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS admins (
    id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    username       VARCHAR(50)  NOT NULL,
    password_hash  VARCHAR(255) NOT NULL,
    created_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_admins_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default admin (password: Admin@123), stored as a bcrypt hash - never plain text.
INSERT IGNORE INTO admins (username, password_hash)
VALUES ('admin', '$2y$12$BIvjo8VtZ3MB.NmugzobIuh42yH2Jh7nxu7qDj6JM9xxfPmh.o17O');
