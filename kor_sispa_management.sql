-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jun 05, 2026 at 08:18 PM
-- Server version: 8.4.3
-- PHP Version: 8.3.26

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `kor_sispa_management`
--

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `AnnouncementID` int NOT NULL,
  `Title` varchar(255) NOT NULL,
  `Description` text NOT NULL,
  `CourseID` int DEFAULT NULL,
  `PostedBy` int NOT NULL,
  `Priority` enum('Normal','Urgent','Event') DEFAULT 'Normal',
  `PostDate` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `ExpiryDate` date DEFAULT NULL,
  `EventDate` date DEFAULT NULL,
  `EndDate` datetime DEFAULT NULL,
  `IsArchived` tinyint(1) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `announcements`
--

INSERT INTO `announcements` (`AnnouncementID`, `Title`, `Description`, `CourseID`, `PostedBy`, `Priority`, `PostDate`, `ExpiryDate`, `EventDate`, `EndDate`, `IsArchived`) VALUES
(1, 'PROGRAM ONE BLOOD', 'Program ini adalah program batch siri 13. program ini adalah program derma darah yang diketuai oleh Nurfarina Binti Mohd Zalani.', NULL, 1, 'Event', '2026-01-11 04:53:58', '2025-08-23', '2025-08-22', NULL, 0),
(2, 'NOTIS MESYUARAT', '👤 Penglibatan: \r\n- MPPK Sesi 2025/2026', NULL, 2, 'Urgent', '2026-01-11 05:34:45', '2026-01-14', NULL, NULL, 0),
(3, 'Latihan Ditunda', 'Latihan kawad ditunda ke minggu hadapan', 1, 1, 'Normal', '2026-01-11 13:37:59', '2026-01-26', NULL, NULL, 0),
(4, 'Ujian Fizikal', 'Ujian fizikal akan dijalankan', 8, 1, 'Urgent', '2026-01-11 13:37:59', '2026-05-23', NULL, NULL, 0),
(6, 'Latihan Medan', 'Sila bawa kelengkapan lengkap, barangan yang perlu sahaja, anda bukan berkelah ye, ini latihan', NULL, 2, 'Normal', '2026-01-11 13:37:59', '2026-01-26', NULL, NULL, 0),
(8, 'Kesukarelawanan', 'Aktiviti bersama komuniti', NULL, 1, 'Event', '2026-01-11 13:37:59', '2026-05-02', '2026-05-01', NULL, 0),
(9, 'Pertolongan Cemas', 'Latihan tambahan', 2, 2, 'Normal', '2026-01-11 13:37:59', '2026-01-26', NULL, NULL, 0),
(12, 'Kawalan Bencana', 'Taklimat khas', 6, 1, 'Normal', '2026-01-11 13:37:59', '2026-01-26', NULL, NULL, 0),
(13, 'Latihan Mingguan Batal', 'Latihan ini akan dibawa pada minggu depan', NULL, 1, 'Normal', '2026-01-12 02:54:28', '2026-01-27', NULL, NULL, 0),
(15, 'Latihan Batal', 'Cancel', NULL, 1, 'Normal', '2026-01-12 04:58:14', '2026-01-27', NULL, NULL, 0),
(19, 'Latihan Luar', 'Latihan Medan', NULL, 1, 'Normal', '2026-01-14 16:51:17', '2026-01-30', NULL, NULL, 0),
(20, 'Latihan Tempatan', 'hujung minggu', NULL, 36, 'Urgent', '2026-01-14 18:25:18', '2026-01-18', NULL, NULL, 0),
(21, 'Latihan Tempatan', '20 jan 2026', NULL, 1, 'Normal', '2026-01-15 02:30:39', '2026-01-30', NULL, NULL, 0),
(23, 'Kursus Ditangguhkan', 'pada 12/5/2026', NULL, 1, 'Urgent', '2026-05-06 03:24:06', '2026-05-09', NULL, NULL, 0),
(24, 'Kursus Asas', 'pada tarikh 21/5/2026', NULL, 45, 'Normal', '2026-05-06 03:33:40', '2026-05-21', NULL, NULL, 0),
(30, 'kelas batal', 'asas kawad', NULL, 1, 'Urgent', '2026-05-24 00:33:31', '2026-05-27', NULL, NULL, 0),
(31, 'hari raya', 'raya korban', NULL, 1, 'Event', '2026-05-24 00:34:08', '2026-05-28', '2026-05-27', NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `AttendanceID` int NOT NULL,
  `UserID` int NOT NULL,
  `CourseID` int NOT NULL,
  `Date` date NOT NULL,
  `Time` time DEFAULT NULL,
  `Day` varchar(20) DEFAULT NULL,
  `Status` enum('Present','Absent','Late','Excused') NOT NULL,
  `Remarks` text,
  `RecordedBy` int DEFAULT NULL,
  `CreatedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `UpdatedAt` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`AttendanceID`, `UserID`, `CourseID`, `Date`, `Time`, `Day`, `Status`, `Remarks`, `RecordedBy`, `CreatedAt`, `UpdatedAt`) VALUES
(2, 3, 1, '2026-01-12', '14:00:00', 'Monday', 'Present', 'Demam', 2, '2026-01-11 10:41:43', '2026-01-12 22:02:50'),
(15, 32, 1, '2026-01-12', NULL, NULL, 'Present', '', 2, '2026-01-12 14:41:04', NULL),
(17, 22, 1, '2026-01-12', NULL, NULL, 'Absent', '', 2, '2026-01-12 07:00:00', NULL),
(18, 22, 1, '2026-01-15', NULL, NULL, 'Present', '', 2, '2026-01-15 07:08:00', NULL),
(19, 32, 1, '2026-01-15', NULL, NULL, 'Present', '', 2, '2026-01-15 07:08:00', NULL),
(20, 22, 1, '2026-01-20', NULL, NULL, 'Excused', 'MC', 2, '2026-01-20 09:00:00', NULL),
(21, 32, 1, '2026-01-20', NULL, NULL, 'Present', '', 2, '2026-01-20 09:00:00', NULL),
(22, 22, 1, '2026-01-11', NULL, NULL, 'Present', '', 2, '2026-01-11 07:18:00', NULL),
(23, 32, 1, '2026-01-11', NULL, NULL, 'Present', '', 2, '2026-01-11 07:18:00', NULL),
(25, 32, 6, '2026-01-12', NULL, NULL, 'Excused', 'MC', 2, '2026-01-12 06:00:00', NULL),
(26, 22, 1, '2026-01-13', NULL, NULL, 'Present', '', 2, '2026-01-13 06:00:00', NULL),
(27, 32, 1, '2026-01-13', NULL, NULL, 'Present', '', 2, '2026-01-13 06:00:00', NULL),
(28, 33, 1, '2026-01-13', NULL, NULL, 'Present', '', 2, '2026-01-13 06:00:00', NULL),
(29, 22, 3, '2026-01-13', NULL, NULL, 'Excused', 'MC', 2, '2026-01-13 06:00:00', NULL),
(30, 32, 3, '2026-01-13', NULL, NULL, 'Present', '', 2, '2026-01-13 06:00:00', NULL),
(31, 33, 3, '2026-01-13', NULL, NULL, 'Present', '', 2, '2026-01-13 06:00:00', NULL),
(32, 22, 3, '2026-01-15', NULL, NULL, 'Excused', 'MC', 2, '2026-01-15 02:00:00', NULL),
(33, 32, 3, '2026-01-15', NULL, NULL, 'Present', '', 2, '2026-01-15 02:00:00', NULL),
(34, 33, 3, '2026-01-15', NULL, NULL, 'Present', '', 2, '2026-01-15 02:00:00', NULL),
(35, 22, 3, '2026-01-14', NULL, NULL, 'Present', '', 2, '2026-01-14 05:00:00', NULL),
(36, 34, 3, '2026-01-14', NULL, NULL, 'Present', '', 2, '2026-01-14 05:00:00', NULL),
(37, 32, 3, '2026-01-14', NULL, NULL, 'Present', '', 2, '2026-01-14 05:00:00', NULL),
(38, 33, 3, '2026-01-14', NULL, NULL, 'Present', '', 2, '2026-01-14 05:00:00', NULL),
(39, 37, 17, '2026-01-16', NULL, NULL, 'Present', '', 36, '2026-01-16 07:00:00', NULL),
(40, 33, 1, '2026-01-15', NULL, NULL, 'Present', '', 2, '2026-01-15 06:30:00', NULL),
(44, 38, 1, '2026-01-15', NULL, NULL, 'Present', '', 2, '2026-01-15 07:00:00', NULL),
(46, 22, 1, '2026-04-23', NULL, NULL, 'Present', '', 2, '2026-04-23 02:00:00', NULL),
(47, 32, 1, '2026-04-23', NULL, NULL, 'Present', '', 2, '2026-04-23 02:00:00', NULL),
(48, 38, 1, '2026-04-23', NULL, NULL, 'Present', '', 2, '2026-04-23 02:00:00', NULL),
(49, 33, 1, '2026-04-23', NULL, NULL, 'Present', '', 2, '2026-04-23 02:00:00', NULL),
(50, 22, 2, '2026-04-23', NULL, NULL, 'Present', '', 2, '2026-04-23 02:00:00', NULL),
(51, 34, 2, '2026-04-23', NULL, NULL, 'Present', '', 2, '2026-04-23 02:00:00', NULL),
(52, 38, 2, '2026-04-23', NULL, NULL, 'Present', '', 2, '2026-04-23 02:00:00', NULL),
(53, 33, 2, '2026-04-23', NULL, NULL, 'Present', '', 2, '2026-04-23 02:00:00', NULL),
(54, 22, 1, '2026-05-05', NULL, NULL, 'Present', '', 2, '2026-05-05 07:00:00', NULL),
(55, 32, 1, '2026-05-05', NULL, NULL, 'Present', '', 2, '2026-05-05 07:00:00', NULL),
(56, 38, 1, '2026-05-05', NULL, NULL, 'Present', '', 2, '2026-05-05 07:00:00', NULL),
(57, 33, 1, '2026-05-05', NULL, NULL, 'Present', '', 2, '2026-05-05 07:00:00', NULL),
(58, 32, 19, '2026-05-06', NULL, NULL, 'Present', '', 2, '2026-05-06 05:00:00', NULL),
(59, 42, 20, '2026-05-06', NULL, NULL, 'Present', '', 41, '2026-05-06 00:00:00', NULL),
(60, 42, 21, '2026-05-06', NULL, NULL, 'Present', '', 41, '2026-05-06 04:00:00', NULL),
(61, 42, 22, '2026-05-15', NULL, NULL, 'Present', '', 45, '2026-05-15 02:00:00', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

CREATE TABLE `courses` (
  `CourseID` int NOT NULL,
  `CourseName` varchar(100) NOT NULL,
  `Description` text,
  `TrainerID` int DEFAULT NULL,
  `ScheduleDate` date DEFAULT NULL,
  `ScheduleTime` time DEFAULT NULL,
  `Status` enum('Active','Inactive') DEFAULT 'Active',
  `CreatedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `courses`
--

INSERT INTO `courses` (`CourseID`, `CourseName`, `Description`, `TrainerID`, `ScheduleDate`, `ScheduleTime`, `Status`, `CreatedAt`) VALUES
(1, 'Melawan Kebakaran', 'Melawan kebakaran melibatkan pencegahan (elak bahan mudah terbakar, suis elektrik, rokok, pastikan bersih) dan tindakan semasa kebakaran (guna alat pemadam ikut P.A.S.S. - Pull pin, Aim nozzle, Squeeze handle, Sweep side-to-side, ikut arah angin) menggunakan kaedah memadamkan (laparkan, menyejukkan, atau lemaskan api) dan menyelamatkan diri (ikut laluan keluar, guna tangga, jangan lompat jika tidak perlu).', 2, '2026-01-01', NULL, 'Active', '2026-01-11 04:57:06'),
(2, 'Asas Kawad', 'Latihan asas kawad kaki', 2, '2026-01-14', '08:00:00', 'Active', '2026-01-11 13:35:17'),
(3, 'Pertolongan Cemas', 'Asas rawatan kecemasan', 2, '2026-01-15', '08:00:00', 'Active', '2026-01-11 13:35:17'),
(6, 'Latihan Medan', 'Latihan luar kawasan', 2, '2026-02-05', '08:00:00', 'Active', '2026-01-11 13:35:17'),
(8, 'Kepimpinan', 'Latihan kepimpinan kadet', 2, '2026-02-15', '08:00:00', 'Active', '2026-01-11 13:35:17'),
(10, 'Fire Drill', 'Latihan kebakaran', 2, '2026-02-25', '08:00:00', 'Inactive', '2026-01-11 13:35:17'),
(17, 'Pertolongan Cemas 2', 'PC', 36, '2026-01-16', '15:00:00', 'Active', '2026-01-14 18:26:51'),
(19, 'Asas Kawad 2', 'test', 2, '2026-05-29', '15:00:00', 'Active', '2026-05-05 15:58:15'),
(20, 'Asas Kawad', 'kawad asas', 41, '2026-05-06', '08:00:00', 'Active', '2026-05-05 21:49:58'),
(21, 'Pertolongan Cemas', 'cpr', 41, '2026-05-06', '12:00:00', 'Active', '2026-05-05 21:50:22'),
(22, 'Asas Kawad', '-', 45, '2026-05-15', '10:00:00', 'Active', '2026-05-06 03:35:27');

-- --------------------------------------------------------

--
-- Table structure for table `course_schedules`
--

CREATE TABLE `course_schedules` (
  `ScheduleID` int NOT NULL,
  `CourseID` int NOT NULL,
  `ScheduleDate` date NOT NULL,
  `StartTime` time NOT NULL,
  `EndTime` time NOT NULL,
  `Location` varchar(255) DEFAULT NULL,
  `Status` enum('Active','Inactive','Completed') DEFAULT 'Active',
  `CreatedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `course_schedules`
--

INSERT INTO `course_schedules` (`ScheduleID`, `CourseID`, `ScheduleDate`, `StartTime`, `EndTime`, `Location`, `Status`, `CreatedAt`) VALUES
(1, 2, '2026-05-29', '17:00:00', '19:00:00', 'Kabin SISPA', 'Active', '2026-05-23 17:20:11'),
(2, 19, '2026-05-29', '13:00:00', '15:00:00', 'Kabin SISPA', 'Active', '2026-05-23 17:47:42'),
(4, 8, '2026-05-29', '15:00:00', '17:00:00', 'Kabin SISPA', 'Active', '2026-05-23 18:36:38'),
(6, 3, '2026-05-29', '08:00:00', '10:00:00', '', 'Active', '2026-05-23 18:41:01'),
(7, 1, '2026-05-31', '09:00:00', '12:00:00', 'Bilik A-101', 'Active', '2026-05-23 18:51:55'),
(9, 2, '2026-05-27', '10:00:00', '12:00:00', 'Bilik B-202', 'Active', '2026-05-23 18:51:55');

-- --------------------------------------------------------

--
-- Table structure for table `enrollments`
--

CREATE TABLE `enrollments` (
  `EnrollmentID` int NOT NULL,
  `CadetID` int NOT NULL,
  `CourseID` int NOT NULL,
  `Status` enum('Enrolled','Completed','Dropped') DEFAULT 'Enrolled',
  `EnrolledAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `enrollments`
--

INSERT INTO `enrollments` (`EnrollmentID`, `CadetID`, `CourseID`, `Status`, `EnrolledAt`) VALUES
(1, 32, 2, 'Enrolled', '2026-05-23 19:07:27'),
(2, 32, 19, 'Enrolled', '2026-05-23 19:07:59'),
(5, 32, 20, 'Enrolled', '2026-05-23 21:43:14'),
(6, 32, 22, 'Enrolled', '2026-05-23 21:43:23');

-- --------------------------------------------------------

--
-- Table structure for table `physical_performance`
--

CREATE TABLE `physical_performance` (
  `PerformanceID` int NOT NULL,
  `UserID` int NOT NULL,
  `TestDate` date DEFAULT NULL,
  `PushUps` int DEFAULT '0',
  `SitUps` int DEFAULT '0',
  `PullUps` int DEFAULT '0',
  `Running24km` decimal(5,2) DEFAULT '0.00',
  `Remark` text,
  `RecordedBy` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `physical_performance`
--

INSERT INTO `physical_performance` (`PerformanceID`, `UserID`, `TestDate`, `PushUps`, `SitUps`, `PullUps`, `Running24km`, `Remark`, `RecordedBy`) VALUES
(1, 22, '2026-01-11', 20, 20, 23, 22.00, 'Need Improve', 2),
(2, 3, '2026-01-11', 12, 25, 22, 17.00, 'Need Improve', 2),
(3, 22, '2026-01-11', 20, 30, 30, 12.00, 'Good', 2),
(4, 3, '2026-01-11', 40, 40, 40, 12.00, 'Good', 2),
(5, 3, '2026-05-25', 20, 20, 40, 15.00, 'Need Improveeee', 2),
(7, 22, '2026-01-12', 30, 50, 50, 7.00, 'Good', 2),
(8, 32, '2026-01-13', 40, 40, 45, 9.00, 'Good', 2),
(9, 34, '2026-01-13', 40, 40, 40, 8.00, 'Good', 2),
(10, 37, '2026-01-16', 20, 30, 30, 9.00, 'Gooddddddd', 36),
(11, 32, '2026-01-15', 40, 30, 20, 7.00, 'goodd', 2),
(12, 38, '2026-01-15', 40, 30, 30, 9.00, 'Good', 2),
(13, 3, '2026-05-06', 10, 10, 10, 10.00, '-', 2),
(14, 42, '2026-05-06', 10, 10, 10, 10.00, '-', 41),
(15, 42, '2026-05-15', 10, 10, 10, 10.00, '-', 45),
(16, 32, '2026-05-30', 10, 10, 10, 10.00, 'NICE', 41),
(17, 32, '2026-06-05', 10, 10, 10, 10.00, 'Not Bad', 41);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `UserID` int NOT NULL,
  `Role` enum('Admin','Trainer','Cadet') NOT NULL,
  `Fullname` varchar(100) NOT NULL,
  `Username` varchar(50) NOT NULL,
  `PasswordHash` varchar(255) NOT NULL,
  `ICNumber` varchar(20) NOT NULL,
  `Email` varchar(100) NOT NULL,
  `Phone` varchar(20) DEFAULT NULL,
  `EmergencyContact` varchar(100) DEFAULT NULL,
  `Status` enum('Active','Inactive','Suspended') DEFAULT 'Inactive',
  `CreatedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `UpdatedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`UserID`, `Role`, `Fullname`, `Username`, `PasswordHash`, `ICNumber`, `Email`, `Phone`, `EmergencyContact`, `Status`, `CreatedAt`, `UpdatedAt`) VALUES
(1, 'Admin', 'System Administrator', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '000000000000', 'admin@korsispa.edu.my', '0123456789', '0179156137', 'Active', '2026-01-11 03:46:21', '2026-01-12 02:53:22'),
(2, 'Trainer', 'Puan Hidayah Binti Katiran', 'Puan Hidayah', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '111111111111', 'hidayah@korsispa.edu.my', '0123456780', '0179156137', 'Active', '2026-01-11 03:46:21', '2026-01-15 01:53:58'),
(3, 'Cadet', 'Cadet10', 'Cadet10', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '222222222222', 'Cadet10@korsispa.edu.my', '0123456781', '0179156137', 'Suspended', '2026-01-11 03:46:21', '2026-05-05 20:58:38'),
(4, 'Trainer', 'Trainer Two', 'trainer', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '010918101462', 'trainer@gmail.com', '01128949378', '0179156137', 'Inactive', '2026-01-11 04:45:38', '2026-05-05 20:56:29'),
(6, 'Admin', 'Admin Two', 'admin2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '010918101400', 'admin@uthm.edu.my', '0123456789', '0179156137', 'Active', '2026-01-11 09:48:44', '2026-01-13 12:56:37'),
(20, 'Trainer', 'Siti Aisyah', 'trainer2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '880202022222', 'trainer1@sispa.edu.my', '0134567890', 'Aisyah Mom', 'Inactive', '2026-01-11 13:35:16', '2026-05-05 20:56:15'),
(21, 'Trainer', 'Muhammad Faiz', 'trainer3', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '870303033333', 'trainer2@sispa.edu.my', '0145678901', 'Faiz Dad', 'Inactive', '2026-01-11 13:35:16', '2026-05-05 20:56:57'),
(22, 'Cadet', 'Ali Hassan', 'cadet2', '$2y$10$xKXx5GVJ42Stodu1a5DO7ubsrK9/28fYOBeQ3TXoBXpLpW6frKAWO', '040404044444', 'cadet1@sispa.edu.my', '0156789012', 'Hassan', 'Suspended', '2026-01-11 13:35:16', '2026-05-05 20:59:16'),
(23, 'Cadet', 'Nur Iman', 'cadet8', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '050505055555', 'cadet2@sispa.edu.my', '0167890123', 'Iman', 'Suspended', '2026-01-11 13:35:16', '2026-05-05 20:59:27'),
(24, 'Cadet', 'Aina Sofea', 'cadet3', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '060606066666', 'cadet3@sispa.edu.my', '0178901234', 'Sofea', 'Suspended', '2026-01-11 13:35:16', '2026-05-05 20:59:36'),
(25, 'Cadet', 'Daniel Amir', 'cadet4', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '070707077777', 'cadet4@sispa.edu.my', '0189012345', 'Amir', 'Suspended', '2026-01-11 13:35:16', '2026-05-05 20:59:46'),
(26, 'Cadet', 'Sarah Nadia', 'cadet5', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '080808088888', 'cadet5@sispa.edu.my', '0190123456', 'Nadia', 'Suspended', '2026-01-11 13:35:16', '2026-05-05 20:59:53'),
(27, 'Cadet', 'Adam Firdaus', 'cadet6', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '090909099999', 'cadet6@sispa.edu.my', '0112233445', 'Firdaus', 'Suspended', '2026-01-11 13:35:16', '2026-05-05 21:00:00'),
(32, 'Cadet', 'Aniq Syahmi', 'aniq', '$2y$10$03Up4XSh9DBb3GNvgVMMheQOrr3MVDIeZCHLYvutFExFQ9NaynoDi', '021111080131', 'aniq@student.uthm.edu.my', '0192004767', '0179156137', 'Active', '2026-01-12 13:26:03', '2026-05-05 15:38:20'),
(33, 'Cadet', 'fareez zalani', 'fareez cadet', '$2y$10$9XU9sPG914j9qIqzk7/5.OvWI9uv3uCX9PQRRBhjkMRUf2QHKdqgi', '010918030011', 'fareezzalani@gmail.com', '01128949378', '0179156137', 'Suspended', '2026-01-13 04:04:42', '2026-05-05 20:58:59'),
(34, 'Cadet', 'amin', 'amin cadet', '$2y$10$CMqshhCRKiR1ePhoZBySj.UTheWONOu8KCfG4uj.LcH4u.7GRzZey', '900101011100', 'amin@gmail.com', '01128949378', '0179156137', 'Suspended', '2026-01-13 13:12:01', '2026-05-05 20:58:49'),
(35, 'Trainer', 'ashimah', 'shima', '$2y$10$yXGVKBi.Pboa0BaKNkMWD./6Rc.KOBwjIfPIVcv/0yG.5G8cKAF6e', '010918101234', 'shima@gamil.com', '01128949378', '0179156137', 'Active', '2026-01-14 16:24:02', '2026-05-08 07:52:17'),
(36, 'Trainer', 'haikal', 'haikal123', '$2y$10$cOwM9Z.9NFNai5QdvQSZy.k66BhOGzKuWYUoGiWL7u78yYyqWkJEW', '030918101460', 'haikal@gmail.com', '01128949378', '0179156137', 'Active', '2026-01-14 18:20:23', '2026-01-14 18:22:02'),
(37, 'Cadet', 'muhammad', 'muhammad_cadet', '$2y$10$ahHrl9hRQTjUcywieHTJ7ek1fmbI0uJIJFOYaA2waSSKztbiTVbdW', '900101011000', 'muhammad@gmail.com', '01128949378', '0179156137', 'Suspended', '2026-01-14 18:30:47', '2026-05-05 20:58:29'),
(38, 'Cadet', 'Faiz', 'Faiz_Cadet', '$2y$10$KN49SqEhCPsGMJhxGhUcqeNH15ZXxZtrK2Gjoqz9ujZyZkIGgrgaO', '010918101430', 'faiz@gmail.com', '01128949378', '0179156137', 'Inactive', '2026-01-15 02:42:14', '2026-05-05 20:57:40'),
(40, 'Cadet', 'sahibul', 'sahibul', '$2y$10$aHYLG6ntfh7voV0ANe5uk.bVaxcFJGqLNQ1CjrVIl0PKlsBrZ8g7G', '650101011111', 'sahibul@gmail.com', '01128949378', '0179156137', 'Suspended', '2026-04-22 20:19:12', '2026-05-05 20:57:23'),
(41, 'Trainer', 'Puan Bat', 'puan Bat', '$2y$10$USdnNFKv.MOhZ6lYCBHSYu9ZsQ/HWlrryP40AjFwr8ilqFSmkKcHK', '020918101462', 'puanbat@sispa.edu.my', '01128949378', '0179156137', 'Active', '2026-05-05 21:48:26', '2026-05-05 21:48:54'),
(42, 'Cadet', 'Muhammad Haikal', 'haikal izanni', '$2y$10$qRO6M68nz7vRMQ4FrGejoOzPaR2E38zVBntZf9cvMXdxUFPwHecbq', '020901101455', 'haikalizanni@gmail.com', '01128949378', '0179156137', 'Active', '2026-05-05 21:52:46', '2026-05-05 21:54:18'),
(45, 'Trainer', 'yusliza', 'yusliza', '$2y$10$tirgpQYCAXUzTu6NwU3NhOopDaFii7Igod8sDxgG9A.xKWuXxQqea', '991009101456', 'yusliza@gmail.com', '01128949378', '01128949378', 'Active', '2026-05-06 03:31:16', '2026-05-06 03:31:59'),
(47, 'Trainer', 'Azimah', 'Azimah', '$2y$10$tPTanBmQCbUMtAgxeihMueleeUixBzBrykHllwB6mO13TQwk5xdp2', '651015036545', 'azimah@gmail.com', '01128949378', '01128949378', 'Inactive', '2026-05-22 16:16:49', '2026-05-22 16:16:49'),
(48, 'Trainer', 'Davies Gendum', 'Davies', '$2y$10$quYfCm1A73g7/pol6NxDB.3xfqAYt63hFz2ARICO2i0TeHMuvuEHu', '961007111789', 'davies@korsispa.uthm.edu.my', '01128949378', '01128949378', 'Inactive', '2026-05-24 15:32:43', '2026-05-24 15:32:43');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`AnnouncementID`),
  ADD KEY `CourseID` (`CourseID`),
  ADD KEY `PostedBy` (`PostedBy`);

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`AttendanceID`),
  ADD UNIQUE KEY `unique_attendance` (`UserID`,`CourseID`,`Date`),
  ADD KEY `CourseID` (`CourseID`),
  ADD KEY `RecordedBy` (`RecordedBy`);

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`CourseID`),
  ADD KEY `TrainerID` (`TrainerID`);

--
-- Indexes for table `course_schedules`
--
ALTER TABLE `course_schedules`
  ADD PRIMARY KEY (`ScheduleID`),
  ADD KEY `idx_course` (`CourseID`),
  ADD KEY `idx_date` (`ScheduleDate`);

--
-- Indexes for table `enrollments`
--
ALTER TABLE `enrollments`
  ADD PRIMARY KEY (`EnrollmentID`),
  ADD UNIQUE KEY `unique_enrollment` (`CadetID`,`CourseID`),
  ADD KEY `CourseID` (`CourseID`);

--
-- Indexes for table `physical_performance`
--
ALTER TABLE `physical_performance`
  ADD PRIMARY KEY (`PerformanceID`),
  ADD KEY `UserID` (`UserID`),
  ADD KEY `RecordedBy` (`RecordedBy`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`UserID`),
  ADD UNIQUE KEY `Username` (`Username`),
  ADD UNIQUE KEY `ICNumber` (`ICNumber`),
  ADD UNIQUE KEY `Email` (`Email`),
  ADD KEY `idx_status` (`Status`),
  ADD KEY `idx_role` (`Role`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `AnnouncementID` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `AttendanceID` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=62;

--
-- AUTO_INCREMENT for table `courses`
--
ALTER TABLE `courses`
  MODIFY `CourseID` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `course_schedules`
--
ALTER TABLE `course_schedules`
  MODIFY `ScheduleID` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `enrollments`
--
ALTER TABLE `enrollments`
  MODIFY `EnrollmentID` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `physical_performance`
--
ALTER TABLE `physical_performance`
  MODIFY `PerformanceID` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `UserID` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `announcements`
--
ALTER TABLE `announcements`
  ADD CONSTRAINT `announcements_ibfk_1` FOREIGN KEY (`CourseID`) REFERENCES `courses` (`CourseID`) ON DELETE CASCADE,
  ADD CONSTRAINT `announcements_ibfk_2` FOREIGN KEY (`PostedBy`) REFERENCES `users` (`UserID`) ON DELETE CASCADE;

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `users` (`UserID`) ON DELETE CASCADE,
  ADD CONSTRAINT `attendance_ibfk_2` FOREIGN KEY (`CourseID`) REFERENCES `courses` (`CourseID`) ON DELETE CASCADE,
  ADD CONSTRAINT `attendance_ibfk_3` FOREIGN KEY (`RecordedBy`) REFERENCES `users` (`UserID`) ON DELETE SET NULL;

--
-- Constraints for table `courses`
--
ALTER TABLE `courses`
  ADD CONSTRAINT `courses_ibfk_1` FOREIGN KEY (`TrainerID`) REFERENCES `users` (`UserID`) ON DELETE SET NULL;

--
-- Constraints for table `course_schedules`
--
ALTER TABLE `course_schedules`
  ADD CONSTRAINT `course_schedules_ibfk_1` FOREIGN KEY (`CourseID`) REFERENCES `courses` (`CourseID`) ON DELETE CASCADE;

--
-- Constraints for table `enrollments`
--
ALTER TABLE `enrollments`
  ADD CONSTRAINT `enrollments_ibfk_1` FOREIGN KEY (`CadetID`) REFERENCES `users` (`UserID`) ON DELETE CASCADE,
  ADD CONSTRAINT `enrollments_ibfk_2` FOREIGN KEY (`CourseID`) REFERENCES `courses` (`CourseID`) ON DELETE CASCADE;

--
-- Constraints for table `physical_performance`
--
ALTER TABLE `physical_performance`
  ADD CONSTRAINT `physical_performance_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `users` (`UserID`),
  ADD CONSTRAINT `physical_performance_ibfk_2` FOREIGN KEY (`RecordedBy`) REFERENCES `users` (`UserID`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
