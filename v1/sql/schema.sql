-- Version 1 schema: branches, offers, admins.
-- If both versions share a database, import v2/sql/schema.sql instead (it includes these).
-- Select the database in phpMyAdmin first. DROP TABLE IF EXISTS so it can be re-run.

DROP TABLE IF EXISTS offers;
DROP TABLE IF EXISTS branches;
DROP TABLE IF EXISTS admins;

-- Five locations (FR2, also used on contact.php). lat/long stored but we don't load a map library.
CREATE TABLE branches (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  name          VARCHAR(100) NOT NULL,
  city          VARCHAR(100) NOT NULL,
  address       VARCHAR(255) NOT NULL,
  phone         VARCHAR(30),
  email         VARCHAR(150),
  opening_hours VARCHAR(255),
  latitude      DECIMAL(9,6),
  longitude     DECIMAL(9,6)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Holiday packages (FR1). is_bestseller = home page badge. end_date hides expired rows.
CREATE TABLE offers (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  title         VARCHAR(150) NOT NULL,
  description   TEXT,
  destination   VARCHAR(100),
  price         DECIMAL(10,2) NOT NULL,
  image_url     VARCHAR(255),
  is_bestseller TINYINT(1) DEFAULT 0,
  start_date    DATE,
  end_date      DATE,
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_offers_end_date (end_date),
  INDEX idx_offers_bestseller (is_bestseller)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Staff login (FR5). password_hash only, never plaintext.
CREATE TABLE admins (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  username      VARCHAR(50) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
