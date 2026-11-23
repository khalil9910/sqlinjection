CREATE DATABASE IF NOT EXISTS sqli_demo CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
DROP USER IF EXISTS 'sqli_lab_store'@'localhost';
CREATE USER IF NOT EXISTS 'sqli_lab'@'localhost' IDENTIFIED BY 'LabOnly_ChangeMe_2026!';
ALTER USER 'sqli_lab'@'localhost' IDENTIFIED BY 'LabOnly_ChangeMe_2026!';
REVOKE ALL PRIVILEGES, GRANT OPTION FROM 'sqli_lab'@'localhost';
GRANT SELECT ON sqli_demo.* TO 'sqli_lab'@'localhost';
USE sqli_demo;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS demo_users;
DROP TABLE IF EXISTS saved_filters;
CREATE TABLE demo_users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(80) NOT NULL UNIQUE,
  demo_password VARCHAR(100) NOT NULL,
  role VARCHAR(32) NOT NULL
);
CREATE TABLE products (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  category VARCHAR(70) NOT NULL,
  price DECIMAL(10,2) NOT NULL,
  visibility TINYINT(1) NOT NULL DEFAULT 1
);
FLUSH PRIVILEGES;
INSERT INTO demo_users(username,demo_password,role) VALUES
 ('admin','AdminDemo2026!','administrator'),
 ('student','StudentDemo2026!','student');
INSERT INTO products(name,category,price,visibility) VALUES
 ('Cyber Atlas Laptop','Computers',7499.00,1),
 ('Aurora Mechanical Keyboard','Accessories',699.00,1),
 ('Pulse Wireless Headset','Audio',459.00,1),
 ('Nova USB-C Dock','Accessories',549.00,1),
 ('INTERNAL: Prototype pricing','Internal demo data',1.00,0),
 ('INTERNAL: Unreleased launch item','Internal demo data',2.00,0);
