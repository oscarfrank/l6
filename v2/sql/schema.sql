-- Full schema (v1 tables plus users, bookings, flights, hotels).
-- Import into an existing database (CloudPanel / phpMyAdmin: select it first).
-- DROP TABLE IF EXISTS so this can be re-run. Drop child tables first.

-- Children first because of foreign keys.
DROP TABLE IF EXISTS bookings;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS flights;
DROP TABLE IF EXISTS hotels;
DROP TABLE IF EXISTS offers;
DROP TABLE IF EXISTS branches;
DROP TABLE IF EXISTS admins;

-- Version 1 tables (FR1 offers, FR2 branches, FR5 admins)

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

CREATE TABLE admins (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  username      VARCHAR(50) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Extra tables for Version 2

-- register.php (FR6) + dashboard contact details (FR8)
CREATE TABLE users (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  name          VARCHAR(100) NOT NULL,
  email         VARCHAR(150) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  phone         VARCHAR(30),
  address       VARCHAR(255),
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Package requests (FR9). Staff confirm / mark paid. No card checkout.
CREATE TABLE bookings (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  user_id      INT NOT NULL,
  offer_id     INT NULL,
  package_name VARCHAR(150) NOT NULL,
  destination  VARCHAR(100),
  travel_date  DATE,
  price        DECIMAL(10,2),
  status       VARCHAR(30) DEFAULT 'requested',
  created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- search-flights.php (FR10). origin indexed because most searches start there.
CREATE TABLE flights (
  id                INT AUTO_INCREMENT PRIMARY KEY,
  airline           VARCHAR(100) NOT NULL,
  origin            VARCHAR(100) NOT NULL,
  destination       VARCHAR(100) NOT NULL,
  depart_time       DATETIME,
  arrive_time       DATETIME,
  duration_minutes  INT,
  stops             INT DEFAULT 0,
  price             DECIMAL(10,2) NOT NULL,
  INDEX idx_flights_origin (origin),
  INDEX idx_flights_destination (destination)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- search-hotels.php (FR11). city is the main WHERE column.
CREATE TABLE hotels (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  name            VARCHAR(150) NOT NULL,
  city            VARCHAR(100) NOT NULL,
  star_rating     INT,
  price_per_night DECIMAL(10,2) NOT NULL,
  amenities       VARCHAR(255),
  image_url       VARCHAR(255),
  INDEX idx_hotels_city (city)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
