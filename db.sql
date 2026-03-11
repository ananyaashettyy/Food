-- Food Delivery Admin Mini Project
-- Import this file in phpMyAdmin (http://localhost/phpmyadmin)

CREATE DATABASE IF NOT EXISTS food_delivery CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE food_delivery;

CREATE TABLE IF NOT EXISTS products (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(120) NOT NULL,
  category VARCHAR(80) NOT NULL,
  description TEXT NULL,
  price DECIMAL(10,2) NOT NULL,
  is_available TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_category (category),
  KEY idx_available (is_available)
) ENGINE=InnoDB;

INSERT INTO products (name, category, description, price, is_available) VALUES
('Margherita Pizza', 'Pizza', 'Classic cheese pizza with fresh basil.', 249.00, 1),
('Chicken Biryani', 'Biryani', 'Aromatic basmati rice with spiced chicken.', 299.00, 1),
('Veg Burger', 'Burger', 'Crispy patty with lettuce and sauce.', 149.00, 1),
('Cold Coffee', 'Beverage', 'Chilled coffee with milk and ice.', 99.00, 1);
