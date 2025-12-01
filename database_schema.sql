-- Water Quality Monitoring System - Sensor Readings Table
-- Create this table in your MySQL database

CREATE TABLE IF NOT EXISTS `sensor_readings` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `tds_value` DECIMAL(10,2) NOT NULL,
  `tds_status` VARCHAR(50) NOT NULL,
  `ph_value` DECIMAL(5,2) NOT NULL,
  `ph_status` VARCHAR(50) NOT NULL,
  `turbidity_value` DECIMAL(10,2) NOT NULL,
  `turbidity_status` VARCHAR(50) NOT NULL,
  `lead_value` DECIMAL(10,5) NOT NULL,
  `lead_status` VARCHAR(50) NOT NULL,
  `color_result` VARCHAR(100) NOT NULL,
  `color_status` VARCHAR(50) NOT NULL,
  `timestamp` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_timestamp` (`timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

