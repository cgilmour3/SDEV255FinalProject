-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: May 08, 2025 at 02:20 PM
-- Server version: 9.1.0
-- PHP Version: 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `studentdb`
--

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

DROP TABLE IF EXISTS `courses`;
CREATE TABLE IF NOT EXISTS `courses` (
  `course_id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `course_name` varchar(100) NOT NULL,
  `description` text,
  `subject_area` varchar(100) DEFAULT NULL,
  `credits` decimal(3,1) DEFAULT NULL,
  `teacher_id` int UNSIGNED DEFAULT NULL,
  `instructor` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`course_id`),
  KEY `idx_teacher_id` (`teacher_id`)
) ENGINE=MyISAM AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `courses`
--

INSERT INTO `courses` (`course_id`, `course_name`, `description`, `subject_area`, `credits`, `teacher_id`, `instructor`) VALUES
(3, 'SDEV255 Web Application Development', 'fullstack web development using HTML, CSS, JavaScript and PHP', 'SDEV', 3.0, 2, NULL),
(4, 'CPIN269 Project Management', 'Capstone project management course', 'CPIN', 3.0, 2, NULL),
(5, 'FORC101 Introduction to the Force', 'do or do not, there is no try', 'FORC', 4.0, 10, NULL),
(6, 'FORC205 Combat Basics', 'basics in force and lightsaber combat', 'FORC', 4.0, 10, NULL),
(7, 'PADA101 Intro to Padawan Training', 'introductory course for all padewans', 'PADA', 3.0, 5, NULL),
(8, 'FORC305 Advanced Lightsaber Defense', 'advanced techniques in lightsaber defense and other combat techniques', 'FORC', 4.0, 5, NULL),
(9, 'FORC400 Lightsaber Capstone', 'padawans will build their first lightsaber', 'FORC', 5.0, 5, NULL),
(10, 'DARK101 Entry to the Darkside', 'introductory darkside course', 'DARK', 3.0, 8, NULL),
(11, 'DARK301 Advanced Sith Techniques', 'advanced course in learning sith techniques', 'DARK', 3.0, 8, NULL),
(12, 'DARK201 Itermediate Controlling the Galaxy', 'intermediate course in learning how to control the galaxy.', 'DARK', 3.0, 7, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `enrollments`
--

DROP TABLE IF EXISTS `enrollments`;
CREATE TABLE IF NOT EXISTS `enrollments` (
  `enrollment_id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int UNSIGNED NOT NULL,
  `course_id` int UNSIGNED NOT NULL,
  `enrollment_date` date DEFAULT NULL,
  PRIMARY KEY (`enrollment_id`),
  KEY `idx_enrollment_student` (`user_id`),
  KEY `idx_enrollment_course` (`course_id`)
) ENGINE=MyISAM AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `enrollments`
--

INSERT INTO `enrollments` (`enrollment_id`, `user_id`, `course_id`, `enrollment_date`) VALUES
(1, 1, 3, '2025-05-07'),
(2, 1, 4, '2025-05-08'),
(3, 6, 6, '2025-05-08'),
(4, 6, 8, '2025-05-08'),
(5, 6, 7, '2025-05-08'),
(6, 9, 4, '2025-05-08'),
(7, 9, 5, '2025-05-08'),
(8, 9, 7, '2025-05-08'),
(9, 4, 5, '2025-05-08'),
(10, 4, 6, '2025-05-08'),
(11, 4, 8, '2025-05-08');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `user_id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('student','teacher') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'student',
  `age` int DEFAULT NULL,
  `grade` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_email` (`email`),
  KEY `idx_role` (`role`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `user_name`, `email`, `password`, `role`, `age`, `grade`) VALUES
(1, 'Cameron Gilmour', 'cgilmour@mail.com', '$2y$10$d6I7cxLFimRA2RUvjI.QfubSqCKe5RtCHlN/enBZHX5rXXlcGDCY2', 'student', 23, 'senior'),
(2, 'Lee Wolfe', 'lwolfe@mail.com', '$2y$10$BeMDJzP9vHpgJEw6dim0uum96ifNUI8Hxhr11DoVLJTP/ISJ2loG.', 'teacher', NULL, 'N/A'),
(3, 'Han Solo', 'hsolo@mail.com', '$2y$10$RU50oDStVD80FNvA94HvLeEr/PUl/IdKw48WGYEEELnTwkB6pfgqG', 'student', 36, 'junior'),
(4, 'Luke Skywalker', 'lskywalker@mail.com', '$2y$10$Po0vAzIYPnMx.QdBlGXn/u9rxzjPwyIh6UwJmkrG2AQsxW1kOHxSS', 'student', 24, 'freshman'),
(5, 'Ben Kenobi', 'bkenobi@mail.com', '$2y$10$yN2o2y8/tXfT0T9oWEIK1.jjMcpUj5pi57KblkRnNOIWmsN/8ejUS', 'teacher', NULL, 'N/A'),
(6, 'Chewbacca', 'chewy@mail.com', '$2y$10$r66Dd4XEKNZ5o42uLUBl9OVSICbAby2Fs18oKA.3QCuTRqQxHjXby', 'student', 36, 'sophmore'),
(7, 'Darth Vader', 'dvader@mail.com', '$2y$10$LXykJEabvPOeuwzRvq1t6ub0lrwq189CsrZyAQTAZdMDVLlPhTN.6', 'teacher', NULL, 'N/A'),
(8, 'Emperor Palpatine', 'epalp@mail.com', '$2y$10$.zTKBuNbQn9/COo1jw5wPOmIpr4CzLPqoxvjGLsxu.343Q/wKJOR2', 'teacher', NULL, 'N/A'),
(9, 'Leia Organa', 'lorgana@mail.com', '$2y$10$Q.MxL4cCvXils4z3qRE0RunTnISkBK4fmWM6vCZWNUsXmIWAMeQue', 'student', 24, 'freshman'),
(10, 'Yoda', 'yoda@mail.com', '$2y$10$vTdG.hNj.l77QJohx3j40.haakpAMK/KrHXT1hOxCJotCOYQdXvCa', 'teacher', NULL, 'N/A');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
