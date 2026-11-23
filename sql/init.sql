-- CYBERMART SQL Injection Lab (local / isolated environments only)
-- This is the single source of truth for the sqli_demo database.

CREATE DATABASE IF NOT EXISTS sqli_demo
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE sqli_demo;

-- Re-running this script resets all fictitious lab data.
DROP TABLE IF EXISTS saved_filters;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS demo_users;

CREATE TABLE demo_users (
  id INT NOT NULL AUTO_INCREMENT,
  username VARCHAR(80) NOT NULL,
  demo_password VARCHAR(100) NOT NULL,
  role VARCHAR(32) NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_demo_users_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE products (
  id INT NOT NULL AUTO_INCREMENT,
  name VARCHAR(120) NOT NULL,
  category VARCHAR(70) NOT NULL,
  price DECIMAL(10,2) NOT NULL,
  visibility TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE saved_filters (
  id INT NOT NULL AUTO_INCREMENT,
  owner_token CHAR(48) NOT NULL,
  label VARCHAR(80) NOT NULL,
  filter_text VARCHAR(255) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_saved_filters_owner (owner_token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO demo_users (username, demo_password, role) VALUES
  ('admin', 'AdminDemo2026!', 'administrator'),
  ('student', 'StudentDemo2026!', 'student');

INSERT INTO products (name, category, price, visibility) VALUES
  ('Cyber Atlas Laptop', 'Computers', 7499.00, 1),
  ('Aurora Mechanical Keyboard', 'Accessories', 699.00, 1),
  ('Pulse Wireless Headset', 'Audio', 459.00, 1),
  ('Nova USB-C Dock', 'Accessories', 549.00, 1),
  ('INTERNAL: Prototype pricing', 'Internal demo data', 1.00, 0),
  ('INTERNAL: Unreleased launch item', 'Internal demo data', 2.00, 0);

-- The web application can read only; import this script as a MySQL administrator.
CREATE USER IF NOT EXISTS 'sqli_lab'@'localhost'
  IDENTIFIED BY 'LabOnly_ChangeMe_2026!';
ALTER USER 'sqli_lab'@'localhost'
  IDENTIFIED BY 'LabOnly_ChangeMe_2026!';
REVOKE ALL PRIVILEGES, GRANT OPTION FROM 'sqli_lab'@'localhost';
GRANT SELECT ON sqli_demo.* TO 'sqli_lab'@'localhost';
FLUSH PRIVILEGES;
