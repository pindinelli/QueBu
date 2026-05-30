--
-- File: setup.sql
-- Description: SQL script to prepare the database for QueBu examples.
--

-- Drop tables if they exist to ensure a clean setup.
DROP TABLE IF EXISTS `test_items`;
DROP TABLE IF EXISTS `categories`;

-- Create reference table `categories`.
CREATE TABLE `categories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create main table `test_items`.
CREATE TABLE `test_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `description` TEXT,
  `value` INT,
  `category_id` INT,
  FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert seed data for categories.
INSERT INTO `categories` (`id`, `name`) VALUES
(1, 'Category 1'),
(2, 'Category 2');

-- Insert seed data for test_items.
INSERT INTO `test_items` (`id`, `name`, `description`, `value`, `category_id`) VALUES
(1, 'Item A', 'Description A', 100, 1),
(2, 'Item B', 'Description B', 50, 2),
(3, 'Item C', 'Description C', 150, 1);
