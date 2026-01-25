-- Database Backup
-- Database: ezyro_40986489_aboutblogs
-- Generated: 2026-01-25 15:16:13


-- Table: demo
DROP TABLE IF EXISTS `demo`;
CREATE TABLE `demo` (
  `in` int(100) NOT NULL,
  `Name` varchar(100) NOT NULL,
  `Mail` varchar(100) NOT NULL,
  `Time` date NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

