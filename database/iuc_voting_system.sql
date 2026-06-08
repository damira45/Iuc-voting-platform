-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1:3306
-- Généré le : jeu. 21 mai 2026 à 04:07
-- Version du serveur : 8.3.0
-- Version de PHP : 8.2.18

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `iuc_voting_system`
--

DELIMITER $$
--
-- Procédures
--
DROP PROCEDURE IF EXISTS `BackupSystem`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `BackupSystem` ()   BEGIN
    DECLARE backup_file VARCHAR(255);
    SET backup_file = CONCAT('backup_', DATE_FORMAT(NOW(), '%Y%m%d_%H%i%s'), '.sql');
    
    SET @sql = CONCAT('mysqldump -u root -p iuc_voting_system > ', backup_file);
    -- PREPARE stmt FROM @sql;
    -- EXECUTE stmt;
    -- DEALLOCATE PREPARE stmt;
    
    SELECT CONCAT('Backup initiated: ', backup_file) as message;
END$$

DROP PROCEDURE IF EXISTS `GetElectionStatistics`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `GetElectionStatistics` (IN `election_id` INT)   BEGIN
    SELECT 
        e.title,
        e.start_date,
        e.end_date,
        COUNT(v.id) as total_votes,
        COUNT(DISTINCT v.user_id) as unique_voters,
        COUNT(c.id) as total_candidates
    FROM elections e
    LEFT JOIN votes v ON e.id = v.election_id
    LEFT JOIN candidates c ON e.id = c.election_id
    WHERE e.id = election_id
    GROUP BY e.id;
END$$

DROP PROCEDURE IF EXISTS `GetStudentVotingHistory`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `GetStudentVotingHistory` (IN `student_id` INT)   BEGIN
    SELECT 
        e.title as election_title,
        c.name as candidate_name,
        v.transaction_hash,
        v.created_at as voted_at
    FROM votes v
    JOIN elections e ON v.election_id = e.id
    JOIN candidates c ON v.candidate_id = c.id
    WHERE v.user_id = student_id
    ORDER BY v.created_at DESC;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Structure de la table `access_codes`
--

DROP TABLE IF EXISTS `access_codes`;
CREATE TABLE IF NOT EXISTS `access_codes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `code` varchar(50) NOT NULL,
  `used` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `user_id` (`user_id`),
  KEY `idx_code` (`code`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Doublure de structure pour la vue `active_elections_view`
-- (Voir ci-dessous la vue réelle)
--
DROP VIEW IF EXISTS `active_elections_view`;
CREATE TABLE IF NOT EXISTS `active_elections_view` (
`id` int
,`title` varchar(191)
,`description` text
,`start_date` date
,`end_date` date
,`status` enum('draft','active','completed','cancelled')
,`created_by` int
,`created_at` timestamp
,`updated_at` timestamp
,`total_votes` bigint
,`unique_voters` bigint
);

-- --------------------------------------------------------

--
-- Structure de la table `activity_logs`
--

DROP TABLE IF EXISTS `activity_logs`;
CREATE TABLE IF NOT EXISTS `activity_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `details` text,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_action` (`user_id`,`action`),
  KEY `idx_created` (`created_at`),
  KEY `idx_activity_logs_created_user` (`created_at`,`user_id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `user_id`, `action`, `details`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 1, 'SETTINGS_UPDATED', 'System settings updated', '::1', NULL, '2026-05-21 00:08:29'),
(2, 1, 'SETTINGS_UPDATED', 'System settings updated', '::1', NULL, '2026-05-21 00:08:49');

-- --------------------------------------------------------

--
-- Structure de la table `admin_users`
--

DROP TABLE IF EXISTS `admin_users`;
CREATE TABLE IF NOT EXISTS `admin_users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `permissions` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `admin_users`
--

INSERT INTO `admin_users` (`id`, `user_id`, `permissions`, `created_at`) VALUES
(1, 1, '{\"view_results\": true, \"manage_students\": true, \"system_settings\": true, \"manage_elections\": true}', '2026-04-23 12:44:37');

-- --------------------------------------------------------

--
-- Structure de la table `blockchain_transactions`
--

DROP TABLE IF EXISTS `blockchain_transactions`;
CREATE TABLE IF NOT EXISTS `blockchain_transactions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `election_id` int DEFAULT NULL,
  `transaction_hash` varchar(100) NOT NULL,
  `block_number` int DEFAULT NULL,
  `type` enum('election_created','vote_cast','results_finalized') NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_transaction_hash` (`transaction_hash`),
  KEY `idx_election_type` (`election_id`,`type`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `blockchain_transactions`
--

INSERT INTO `blockchain_transactions` (`id`, `election_id`, `transaction_hash`, `block_number`, `type`, `created_at`) VALUES
(1, 8, 'MOCK-eb11127789b6c53f8b6bc762dac988b8', NULL, 'vote_cast', '2026-05-18 13:48:07'),
(3, 12, 'MOCK-09881ad1db2cf3abb653b88153a2160d', NULL, 'vote_cast', '2026-05-19 23:40:56');

-- --------------------------------------------------------

--
-- Structure de la table `candidates`
--

DROP TABLE IF EXISTS `candidates`;
CREATE TABLE IF NOT EXISTS `candidates` (
  `id` int NOT NULL AUTO_INCREMENT,
  `election_id` int NOT NULL,
  `name` varchar(191) NOT NULL,
  `description` text,
  `position` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_candidate_position` (`election_id`,`position`),
  KEY `idx_election` (`election_id`)
) ENGINE=MyISAM AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `candidates`
--

INSERT INTO `candidates` (`id`, `election_id`, `name`, `description`, `position`, `created_at`) VALUES
(6, 1, 'Sarah Johnson', 'Experienced leader with vision for student welfare and academic excellence. Proven track record in student government.', 0, '2026-04-24 15:12:39'),
(7, 3, 'nashy', 'Experienced student leader with a vision for academic excellence and student welfare. Proven track record in student government and community service initiatives.', 0, '2026-04-24 15:32:20'),
(9, 5, 'nashy', 'Experienced student leader with a vision for academic excellence and student welfare. Proven track record in student government and community service initiative', 0, '2026-04-24 15:35:50'),
(14, 10, 'cherif', 'national day', 0, '2026-05-19 19:20:33'),
(17, 12, 'sarah', 'camerroon is the world root', 2, '2026-05-19 23:39:59'),
(12, 8, 'cherif', 'i want change', 0, '2026-05-18 13:13:02'),
(16, 12, 'pakou', 'in all the ways we shall become one', 1, '2026-05-19 23:39:59');

-- --------------------------------------------------------

--
-- Structure de la table `elections`
--

DROP TABLE IF EXISTS `elections`;
CREATE TABLE IF NOT EXISTS `elections` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(191) NOT NULL,
  `description` text,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `status` enum('draft','active','completed','cancelled') DEFAULT 'draft',
  `created_by` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  KEY `idx_status` (`status`),
  KEY `idx_dates` (`start_date`,`end_date`),
  KEY `idx_elections_dates_status` (`start_date`,`end_date`,`status`)
) ENGINE=MyISAM AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `elections`
--

INSERT INTO `elections` (`id`, `title`, `description`, `start_date`, `end_date`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'Student Union Election 2024', 'Annual student union leadership election', '2024-01-15', '2024-01-30', 'active', 1, '2026-04-23 12:44:39', '2026-04-23 12:44:39'),
(3, 'Student Council Election 2024 ', '\n\nDescription: Annual student council leadership election for the 2024-2025 academic year\n', '2026-04-24', '2026-04-29', 'active', 1, '2026-04-24 15:32:20', '2026-04-24 15:32:20'),
(5, 'Student Council Election 2024  ', 'Description: Annual student council leadership election for the 2024-2025 academic year', '2026-04-24', '2026-04-30', 'active', 1, '2026-04-24 15:35:50', '2026-04-24 15:35:50'),
(10, 'cameroon national team', 'here we go for our country', '2026-05-19', '2026-05-30', 'active', 1, '2026-05-19 19:20:33', '2026-05-19 19:20:33'),
(8, 'Miss Cameroon', 'this is to prove the world that IUC can provide cameroon with good student ', '2026-05-18', '2026-05-30', 'active', 1, '2026-05-18 13:13:02', '2026-05-18 13:13:02'),
(12, 'anglophone result conflict', 'here is the final analysis of the anglophone conflict', '2026-05-20', '2026-05-28', 'active', 1, '2026-05-19 23:39:59', '2026-05-19 23:39:59');

-- --------------------------------------------------------

--
-- Doublure de structure pour la vue `eligible_students_view`
-- (Voir ci-dessous la vue réelle)
--
DROP VIEW IF EXISTS `eligible_students_view`;
CREATE TABLE IF NOT EXISTS `eligible_students_view` (
`id` int
,`name` varchar(191)
,`email` varchar(191)
,`student_id` varchar(50)
,`department` varchar(100)
,`level` varchar(50)
);

-- --------------------------------------------------------

--
-- Structure de la table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `title` varchar(191) NOT NULL,
  `message` text NOT NULL,
  `type` enum('student_registration','voting_code_required','election_started','system_alert','security_warning','general','info','success','warning','error') DEFAULT 'general',
  `status` enum('unread','read','dismissed') DEFAULT 'unread',
  `priority` enum('low','medium','high') DEFAULT 'medium',
  `related_user_id` int DEFAULT NULL,
  `related_student_id` int DEFAULT NULL,
  `action_required` tinyint(1) DEFAULT '0',
  `action_url` varchar(500) DEFAULT NULL,
  `action_text` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_type` (`type`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_related_user` (`related_user_id`),
  KEY `idx_related_student` (`related_student_id`)
) ENGINE=MyISAM AUTO_INCREMENT=47 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `title`, `message`, `type`, `status`, `priority`, `related_user_id`, `related_student_id`, `action_required`, `action_url`, `action_text`, `created_at`, `updated_at`) VALUES
(41, 0, 'Student Login Successful', 'Student cherif (cherif@gmail.com) has successfully logged in with voting code.', 'general', 'unread', 'low', NULL, 13, 0, NULL, NULL, '2026-05-21 03:37:52', NULL),
(42, 0, 'New Student Registration - Approval Required', 'Student madline (madline@gmail.com, Student ID: IUC 3535 3535) has registered and requires approval and voting code generation.', 'student_registration', 'unread', 'high', NULL, 14, 1, 'index.php?page=voter_registration&action=generate_code&student_id=14', 'Generate Voting Code', '2026-05-21 03:40:00', NULL),
(2, 0, 'Voting Code Generated', 'Voting code has been generated for student ghamegnigni kouotou adra damira (ghamegnignikouotouadradamira@gmail.com). Please send the code to the student.', 'voting_code_required', 'read', 'high', NULL, 2, 1, 'index.php?page=voter_registration&action=send_code&student_id=2&code=VOTE-6W0E-HZK0-EWS8-A1Q6', 'Send Voting Code', '2026-04-24 12:07:29', '2026-05-21 02:50:12'),
(3, 0, 'Voting Code Generated', 'Voting code has been generated for student ghamegnigni kouotou adra damira (ghamegnignikouotouadradamira@gmail.com). Please send the code to the student.', 'voting_code_required', 'read', 'high', NULL, 2, 1, 'index.php?page=voter_registration&action=send_code&student_id=2&code=VOTE-JYGK-7G3U-27W3-CTEU', 'Send Voting Code', '2026-04-24 12:35:28', '2026-05-21 02:50:12'),
(4, 0, 'New Student Registration - Approval Required', 'Student clair (clair@gmail.com, Student ID: IUC 2020 2020) has registered and requires approval and voting code generation.', 'student_registration', 'read', 'high', NULL, 9, 1, 'index.php?page=voter_registration&action=generate_code&student_id=9', 'Generate Voting Code', '2026-04-24 12:38:08', '2026-05-21 02:50:12'),
(5, 0, 'Voting Code Generated', 'Voting code has been generated for student clair (clair@gmail.com). Please send the code to the student.', 'voting_code_required', 'read', 'high', NULL, 9, 1, 'index.php?page=voter_registration&action=send_code&student_id=9&code=VOTE-EOAM-PEKG-JPHF-S3OK', 'Send Voting Code', '2026-04-24 13:01:03', '2026-05-21 02:50:12'),
(6, 0, 'Voting Code Generated', 'Voting code has been generated for student clair (clair@gmail.com). Please send the code to the student.', 'voting_code_required', 'read', 'high', NULL, 9, 1, 'index.php?page=voter_registration&action=send_code&student_id=9&code=VOTE-3BEF-I94L-UIAB-5DQV', 'Send Voting Code', '2026-04-24 13:06:37', '2026-05-21 02:50:12'),
(7, 0, 'Voting Code Generated', 'Voting code has been generated for student clair (clair@gmail.com). Please send the code to the student.', 'voting_code_required', 'read', 'high', NULL, 9, 1, 'index.php?page=voter_registration&student_id=9', 'Send Voting Code', '2026-04-24 13:11:41', '2026-05-21 02:50:12'),
(8, 0, 'Voting Code Generated', 'Voting code has been generated for student clair (clair@gmail.com). Please send the code to the student.', 'voting_code_required', 'read', 'high', NULL, 9, 1, 'index.php?page=voter_registration&student_id=9', 'Send Voting Code', '2026-04-24 13:16:25', '2026-05-21 02:50:12'),
(9, 0, 'Voting Code Generated', 'Voting code has been generated for student clair (clair@gmail.com). Please send the code to the student.', 'voting_code_required', 'read', 'high', NULL, 9, 1, 'index.php?page=voter_registration&student_id=9', 'Send Voting Code', '2026-04-24 13:22:11', '2026-05-21 02:50:12'),
(10, 0, 'Student Login Successful', 'Student clair (clair@gmail.com) has successfully logged in with voting code.', 'general', 'read', 'low', NULL, 9, 0, NULL, NULL, '2026-04-24 13:26:18', '2026-05-21 02:50:12'),
(11, 0, 'Student Login Successful', 'Student clair (clair@gmail.com) has successfully logged in with voting code.', 'general', 'read', 'low', NULL, 9, 0, NULL, NULL, '2026-04-24 13:37:30', '2026-05-21 02:50:12'),
(12, 0, 'Student Login Successful', 'Student clair (clair@gmail.com) has successfully logged in with voting code.', 'general', 'read', 'low', NULL, 9, 0, NULL, NULL, '2026-04-24 13:39:10', '2026-05-21 02:50:12'),
(13, 0, 'Voting Code Generated', 'Voting code has been generated for student clair (clair@gmail.com). Please send the code to the student.', 'voting_code_required', 'read', 'high', NULL, 9, 1, 'index.php?page=voter_registration&student_id=9', 'Send Voting Code', '2026-04-24 13:51:35', '2026-05-21 02:50:12'),
(14, 0, 'New Student Registration - Approval Required', 'Student shakira (shakira@gmail.com, Student ID: IUC 2121 2121) has registered and requires approval and voting code generation.', 'student_registration', 'read', 'high', NULL, 10, 1, 'index.php?page=voter_registration&action=generate_code&student_id=10', 'Generate Voting Code', '2026-04-24 14:09:02', '2026-05-21 02:50:12'),
(15, 0, 'Voting Code Generated', 'Voting code has been generated for student shakira (shakira@gmail.com). Please send the code to the student.', 'voting_code_required', 'read', 'high', NULL, 10, 1, 'index.php?page=voter_registration&student_id=10', 'Send Voting Code', '2026-04-24 14:09:37', '2026-05-21 02:50:12'),
(16, 0, 'Voting Code Generated', 'Voting code has been generated for student shakira (shakira@gmail.com). Please send the code to the student.', 'voting_code_required', 'read', 'high', NULL, 10, 1, 'index.php?page=voter_registration&student_id=10', 'Send Voting Code', '2026-04-24 14:13:56', '2026-05-21 02:50:12'),
(17, 0, 'Voting Code Generated', 'Voting code has been generated for student shakira (shakira@gmail.com). Please send the code to the student.', 'voting_code_required', 'read', 'high', NULL, 10, 1, 'index.php?page=voter_registration&student_id=10', 'Send Voting Code', '2026-04-24 14:23:36', '2026-05-21 02:50:12'),
(18, 0, 'Student Login Successful', 'Student shakira (shakira@gmail.com) has successfully logged in with voting code.', 'general', 'read', 'low', NULL, 10, 0, NULL, NULL, '2026-04-24 14:23:52', '2026-05-21 02:50:12'),
(19, 0, 'New Student Registration - Approval Required', 'Student nadia (nadia@gmail.com, Student ID: IUC 9090 9090) has registered and requires approval and voting code generation.', 'student_registration', 'read', 'high', NULL, 11, 1, 'index.php?page=voter_registration&action=generate_code&student_id=11', 'Generate Voting Code', '2026-04-24 14:25:31', '2026-05-21 02:50:12'),
(20, 0, 'Voting Code Generated', 'Voting code has been generated for student nadia (nadia@gmail.com). Please send the code to the student.', 'voting_code_required', 'read', 'high', NULL, 11, 1, 'index.php?page=voter_registration&student_id=11', 'Send Voting Code', '2026-04-24 14:25:50', '2026-05-21 02:50:12'),
(21, 0, 'Student Login Successful', 'Student nadia (nadia@gmail.com) has successfully logged in with voting code.', 'general', 'read', 'low', NULL, 11, 0, NULL, NULL, '2026-04-24 14:26:20', '2026-05-21 02:50:12'),
(22, 0, 'Student Login Successful', 'Student nadia (nadia@gmail.com) has successfully logged in with voting code.', 'general', 'read', 'low', NULL, 11, 0, NULL, NULL, '2026-04-24 14:29:22', '2026-05-21 02:50:12'),
(23, 0, 'Student Login Successful', 'Student nadia (nadia@gmail.com) has successfully logged in with voting code.', 'general', 'read', 'low', NULL, 11, 0, NULL, NULL, '2026-04-24 15:04:56', '2026-05-21 02:50:12'),
(24, 0, 'Student Login Successful', 'Student nadia (nadia@gmail.com) has successfully logged in with voting code.', 'general', 'read', 'low', NULL, 11, 0, NULL, NULL, '2026-05-18 12:12:06', '2026-05-21 02:50:12'),
(25, 0, 'New Student Registration - Approval Required', 'Student nousra (nousra@gmail.com, Student ID: IUC 2022 2022) has registered and requires approval and voting code generation.', 'student_registration', 'read', 'high', NULL, 12, 1, 'index.php?page=voter_registration&action=generate_code&student_id=12', 'Generate Voting Code', '2026-05-18 12:16:03', '2026-05-21 02:50:12'),
(26, 0, 'Voting Code Generated', 'Voting code has been generated for student nousra (nousra@gmail.com). Please send the code to the student.', 'voting_code_required', 'read', 'high', NULL, 12, 1, 'index.php?page=voter_registration&student_id=12', 'Send Voting Code', '2026-05-18 12:17:58', '2026-05-21 02:50:12'),
(28, 0, 'Student Login Successful', 'Student nousra (nousra@gmail.com) has successfully logged in with voting code.', 'general', 'read', 'low', NULL, 12, 0, NULL, NULL, '2026-05-18 13:32:34', '2026-05-21 02:50:12'),
(29, 0, 'Student Login Successful', 'Student nousra (nousra@gmail.com) has successfully logged in with voting code.', 'general', 'read', 'low', NULL, 12, 0, NULL, NULL, '2026-05-18 13:47:58', '2026-05-21 02:50:12'),
(30, 0, 'Student Login Successful', 'Student nousra (nousra@gmail.com) has successfully logged in with voting code.', 'general', 'read', 'low', NULL, 12, 0, NULL, NULL, '2026-05-18 13:56:16', '2026-05-21 02:50:12'),
(31, 0, 'Student Login Successful', 'Student nousra (nousra@gmail.com) has successfully logged in with voting code.', 'general', 'read', 'low', NULL, 12, 0, NULL, NULL, '2026-05-18 13:58:31', '2026-05-21 02:50:12'),
(32, 0, 'Student Login Successful', 'Student nousra (nousra@gmail.com) has successfully logged in with voting code.', 'general', 'read', 'low', NULL, 12, 0, NULL, NULL, '2026-05-19 18:21:27', '2026-05-21 02:50:12'),
(33, 0, 'Student Login Successful', 'Student nousra (nousra@gmail.com) has successfully logged in with voting code.', 'general', 'dismissed', 'low', NULL, 12, 0, NULL, NULL, '2026-05-19 18:50:05', '2026-05-21 02:51:38'),
(34, 0, 'Student Login Successful', 'Student nousra (nousra@gmail.com) has successfully logged in with voting code.', 'general', 'dismissed', 'low', NULL, 12, 0, NULL, NULL, '2026-05-19 19:21:26', '2026-05-21 02:51:21'),
(35, 0, 'Student Login Successful', 'Student nousra (nousra@gmail.com) has successfully logged in with voting code.', 'general', 'read', 'low', NULL, 12, 0, NULL, NULL, '2026-05-19 23:40:30', '2026-05-21 02:50:12'),
(36, 0, 'Student Login Successful', 'Student nousra (nousra@gmail.com) has successfully logged in with voting code.', 'general', 'read', 'low', NULL, 12, 0, NULL, NULL, '2026-05-21 02:08:14', '2026-05-21 02:50:12'),
(37, 0, 'New Student Registration - Approval Required', 'Student cherif (cherif@gmail.com, Student ID: IUC-4040-4040) has registered and requires approval and voting code generation.', 'student_registration', 'read', 'high', NULL, 13, 1, 'index.php?page=voter_registration&action=generate_code&student_id=13', 'Generate Voting Code', '2026-05-21 02:16:50', '2026-05-21 02:50:12'),
(38, 0, 'Voting Code Generated', 'Voting code has been generated for student cherif (cherif@gmail.com). Please send the code to the student.', 'voting_code_required', 'read', 'high', NULL, 13, 1, 'index.php?page=voter_registration&student_id=13', 'Send Voting Code', '2026-05-21 02:22:19', '2026-05-21 02:50:12'),
(39, 0, 'Student Login Successful', 'Student cherif (cherif@gmail.com) has successfully logged in with voting code.', 'general', 'read', 'low', NULL, 13, 0, NULL, NULL, '2026-05-21 02:23:17', '2026-05-21 02:50:12'),
(40, 0, 'Student Login Successful', 'Student cherif (cherif@gmail.com) has successfully logged in with voting code.', 'general', 'read', 'low', NULL, 13, 0, NULL, NULL, '2026-05-21 02:33:44', '2026-05-21 02:50:12'),
(43, 0, 'Voting Code Generated', 'Voting code has been generated for student madline (madline@gmail.com). Please send the code to the student.', 'voting_code_required', 'unread', 'high', NULL, 14, 1, 'index.php?page=voter_registration&student_id=14', 'Send Voting Code', '2026-05-21 03:40:36', NULL),
(44, 0, 'Student Login Successful', 'Student madline (madline@gmail.com) has successfully logged in with voting code.', 'general', 'unread', 'low', NULL, 14, 0, NULL, NULL, '2026-05-21 03:41:49', NULL),
(45, 0, 'Voting Code Generated', 'Voting code has been generated for student madline (madline@gmail.com). Please send the code to the student.', 'voting_code_required', 'unread', 'high', NULL, 14, 1, 'index.php?page=voter_registration&student_id=14', 'Send Voting Code', '2026-05-21 03:53:42', NULL),
(46, 0, 'Student Login Successful', 'Student madline (madline@gmail.com) has successfully logged in with voting code.', 'general', 'unread', 'low', NULL, 14, 0, NULL, NULL, '2026-05-21 03:54:24', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `students`
--

DROP TABLE IF EXISTS `students`;
CREATE TABLE IF NOT EXISTS `students` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `student_id` varchar(50) NOT NULL,
  `department` varchar(100) NOT NULL,
  `level` varchar(50) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  UNIQUE KEY `student_id` (`student_id`),
  KEY `idx_student_id` (`student_id`),
  KEY `idx_department` (`department`)
) ENGINE=MyISAM AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `students`
--

INSERT INTO `students` (`id`, `user_id`, `student_id`, `department`, `level`, `created_at`, `updated_at`) VALUES
(6, 6, 'IUC 9023 7865', 'Civil Engineering', 'Level 4', '2026-04-23 16:09:28', '2026-04-23 16:09:28'),
(2, 3, 'IUC 2223 2014', 'Business Administration', 'Level 1', '2026-04-23 13:06:47', '2026-04-23 13:06:47'),
(3, 4, 'IUC 2024 2025', 'Business Administration', 'Level 4', '2026-04-23 13:22:06', '2026-04-23 13:22:06'),
(4, 0, 'IUC 9090', 'Business Administration', '2', '2026-04-23 14:32:12', '2026-04-23 14:32:12'),
(5, 5, 'IUC 45234', 'Business Administration', '2', '2026-04-23 14:36:10', '2026-04-23 14:36:10'),
(7, 7, 'IUC 2024 2026', 'Social Sciences', '3', '2026-04-24 11:03:42', '2026-04-24 11:03:42'),
(8, 8, 'IUC 3030 3030', 'Business Administration', 'Level 3', '2026-04-24 11:35:28', '2026-04-24 11:35:28'),
(9, 9, 'IUC 2020 2020', 'Environmental Science', 'Level 4', '2026-04-24 12:38:08', '2026-04-24 12:38:08'),
(10, 10, 'IUC 2121 2121', 'Business Administration', 'Level 1', '2026-04-24 14:09:02', '2026-04-24 14:09:02'),
(11, 11, 'IUC 9090 9090', 'Business Administration', 'Level 3', '2026-04-24 14:25:31', '2026-04-24 14:25:31'),
(12, 12, 'IUC 2022 2022', 'Business Administration', 'Level 2', '2026-05-18 12:16:03', '2026-05-18 12:16:03'),
(13, 13, 'IUC-4040-4040', 'Business Administration', 'Level 2', '2026-05-21 02:16:50', '2026-05-21 02:16:50'),
(14, 14, 'IUC 3535 3535', 'Engineering', 'Level 4', '2026-05-21 03:40:00', '2026-05-21 03:40:00');

-- --------------------------------------------------------

--
-- Structure de la table `system_settings`
--

DROP TABLE IF EXISTS `system_settings`;
CREATE TABLE IF NOT EXISTS `system_settings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text,
  `description` text,
  `updated_by` int DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`),
  KEY `updated_by` (`updated_by`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `system_settings`
--

INSERT INTO `system_settings` (`id`, `setting_key`, `setting_value`, `description`, `updated_by`, `updated_at`) VALUES
(1, 'system_name', 'IUC Voting System', 'System name', 1, '2026-05-21 00:08:29'),
(2, 'blockchain_enabled', 'true', 'Enable blockchain integration', 1, '2026-05-21 00:08:29'),
(3, 'max_elections', '12', 'Maximum concurrent elections', 1, '2026-05-21 00:08:49'),
(4, 'voting_timeout', '300', 'Voting session timeout in seconds', 1, '2026-05-21 00:08:29'),
(5, 'email_notifications', 'true', 'Enable email notifications', 1, '2026-05-21 00:08:29');

-- --------------------------------------------------------

--
-- Structure de la table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `email` varchar(191) NOT NULL,
  `password` varchar(191) NOT NULL,
  `type` enum('student','admin') NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_email` (`email`),
  KEY `idx_type` (`type`),
  KEY `idx_status` (`status`),
  KEY `idx_users_type_status` (`type`,`status`)
) ENGINE=MyISAM AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `type`, `status`, `created_at`, `updated_at`) VALUES
(1, 'System Administrator', 'admin@iuc.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'approved', '2026-04-23 12:44:37', '2026-04-23 12:44:37'),
(2, 'ghamegnigni kouotou adra damira', 'ghamegnignikouotouadradamira@gmail.com', '$2y$10$UsGD9Q74F9s.XojyJN8Xp.VAAuZ5lI6zgI4jj2PRSh5Deuh5Mlc/m', 'student', 'pending', '2026-04-23 12:48:39', '2026-04-23 12:48:39'),
(3, 'ghamegnigni kouotou adra damira', 'ghamegnignikouotouadra@gmail.com', '$2y$10$I71YxLfVUvhhBwFjzybidu1jHd/yKcSLtlkT/5KlU8nUqq2avXCU2', 'student', 'pending', '2026-04-23 13:06:47', '2026-04-23 13:06:47'),
(4, 'adra damira', 'iuc@gmail.com', '$2y$10$dY7EtmxhTQ.9uOKqQTuX9eGbxrr9.Qt9GQZV0.9uFYYYCMmQvVk/e', 'student', 'pending', '2026-04-23 13:22:06', '2026-04-23 13:22:06'),
(5, 'cherif nsangou', 'cherifnsangou@gmail.com', '$2y$10$4qiqtdTROUSv/Wa9DjM04OFagxvpVrGNIwzvJhX3A7v4pCvFny33G', 'student', 'approved', '2026-04-23 14:36:10', '2026-04-23 14:36:10'),
(6, 'nazira', 'nazira@gmail.com', '$2y$10$Uud2Lxe02ThF55s1qMzeou0wW2nULE9EqhJFORV6qa6K6pPfZ7Cey', 'student', 'approved', '2026-04-23 16:09:28', '2026-04-23 16:09:28'),
(7, 'rahima mishka', 'rahima@gmail.com', '$2y$10$7NGn6kXY8SJMEdCDieh5g.qIIji9uxDcyFCHMnksE43kYAuFcMejG', 'student', 'approved', '2026-04-24 11:03:42', '2026-04-24 11:03:42'),
(8, 'Anifa mimche', 'anifamimche@gmail.com', '$2y$10$J22y8V2vvByMXDMSrRD44O2drUV1SXcjAfbIvubAgJ0FQjcderMeq', 'student', 'approved', '2026-04-24 11:35:28', '2026-04-24 11:35:28'),
(9, 'clair', 'clair@gmail.com', '$2y$10$4uoa7jMHm7vUzk3VlHqOHuOQ7xOEtsg1YfR/n4LhizI7ZxBjtno4a', 'student', 'approved', '2026-04-24 12:38:08', '2026-04-24 12:38:08'),
(10, 'shakira', 'shakira@gmail.com', '$2y$10$7twgO.gubrzUIRq6yqPfWOT5nxExUd3onjmfTUq/ONgYTKl.G.wl6', 'student', 'approved', '2026-04-24 14:09:02', '2026-04-24 14:09:02'),
(11, 'nadia', 'nadia@gmail.com', '$2y$10$fqciLdbeu55ZgRZOG9a9U.mCesaK7ZcRlpUdZI6F64dkZZ1SuV/ki', 'student', 'approved', '2026-04-24 14:25:31', '2026-04-24 14:25:31'),
(12, 'nousra', 'nousra@gmail.com', '$2y$10$n2/oM2odFlKAWOqN76R1/ujnfHXZ936xsd3U.8H31vCLI2ElRmVJK', 'student', 'approved', '2026-05-18 12:16:03', '2026-05-18 12:16:03'),
(13, 'cherif', 'cherif@gmail.com', '$2y$10$sYK6ohYYSkZ0CrWtDaHlp.Ad8lxZ4BQfLdFL7D7Z2OZbjyrLgsGFe', 'student', 'approved', '2026-05-21 02:16:50', '2026-05-21 02:16:50'),
(14, 'madline', 'madline@gmail.com', '$2y$10$WIgHeVkpWCMrfhXXpgdk.OMROy/C.U1R6bN3c5M6HAaiKOR/LBA9m', 'student', 'approved', '2026-05-21 03:40:00', '2026-05-21 03:40:00');

--
-- Déclencheurs `users`
--
DROP TRIGGER IF EXISTS `after_user_login`;
DELIMITER $$
CREATE TRIGGER `after_user_login` AFTER UPDATE ON `users` FOR EACH ROW BEGIN
    IF NEW.updated_at != OLD.updated_at THEN
        INSERT INTO activity_logs (user_id, action, details, created_at)
        VALUES (NEW.id, 'login', 'User logged in', NOW());
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Structure de la table `votes`
--

DROP TABLE IF EXISTS `votes`;
CREATE TABLE IF NOT EXISTS `votes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `election_id` int NOT NULL,
  `candidate_id` int NOT NULL,
  `user_id` int NOT NULL,
  `transaction_hash` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_vote` (`election_id`,`user_id`),
  KEY `candidate_id` (`candidate_id`),
  KEY `user_id` (`user_id`),
  KEY `idx_election_user` (`election_id`,`user_id`),
  KEY `idx_transaction` (`transaction_hash`),
  KEY `idx_votes_election_candidate` (`election_id`,`candidate_id`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `votes`
--

INSERT INTO `votes` (`id`, `election_id`, `candidate_id`, `user_id`, `transaction_hash`, `created_at`) VALUES
(1, 8, 12, 12, 'MOCK-eb11127789b6c53f8b6bc762dac988b8', '2026-05-18 13:48:07'),
(3, 12, 17, 12, 'MOCK-09881ad1db2cf3abb653b88153a2160d', '2026-05-19 23:40:56');

-- --------------------------------------------------------

--
-- Structure de la table `voting_codes`
--

DROP TABLE IF EXISTS `voting_codes`;
CREATE TABLE IF NOT EXISTS `voting_codes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_id` int NOT NULL,
  `election_id` int NOT NULL,
  `voting_code` varchar(30) NOT NULL,
  `status` enum('generated','sent','used','expired') DEFAULT 'generated',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `sent_at` timestamp NULL DEFAULT NULL,
  `used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `generated_by_admin` int NOT NULL,
  `sent_by_admin` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `voting_code` (`voting_code`),
  KEY `student_id` (`student_id`),
  KEY `election_id` (`election_id`),
  KEY `generated_by_admin` (`generated_by_admin`),
  KEY `sent_by_admin` (`sent_by_admin`)
) ENGINE=MyISAM AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `voting_codes`
--

INSERT INTO `voting_codes` (`id`, `student_id`, `election_id`, `voting_code`, `status`, `created_at`, `sent_at`, `used_at`, `expires_at`, `generated_by_admin`, `sent_by_admin`) VALUES
(12, 9, 1, 'VOTE-0GUF-0HOD-TG1H-X5QO', 'sent', '2026-04-24 13:11:23', NULL, NULL, '2026-05-24 13:11:23', 1, NULL),
(14, 9, 1, 'VOTE-BYWB-C87L-33WA-WYPB', 'used', '2026-04-24 13:16:13', NULL, '2026-04-24 13:39:10', '2026-05-24 13:16:13', 1, NULL),
(19, 10, 1, 'VOTE-ICW3-VD18-NCKQ-HUKP', 'sent', '2026-04-24 14:13:42', NULL, NULL, '2026-05-24 14:13:42', 1, NULL),
(22, 10, 1, 'VOTE-7REX-IDMH-2T3T-D1NC', 'used', '2026-04-24 14:23:36', NULL, '2026-04-24 14:23:52', '2026-05-24 14:23:36', 1, NULL),
(21, 3, 1, 'VOTE-3QPU-5VCT-C6DN-7KGY', 'sent', '2026-04-24 14:23:19', NULL, NULL, '2026-05-24 14:23:19', 1, NULL),
(23, 11, 1, 'VOTE-5KBT-YDKE-W4XB-Y0A6', 'used', '2026-04-24 14:25:50', NULL, '2026-05-18 12:12:06', '2026-05-24 14:25:50', 1, NULL),
(24, 12, 1, 'VOTE-TDCK-0QVT-8HP2-IUI6', 'used', '2026-05-18 12:17:58', NULL, '2026-05-21 02:08:14', '2026-06-17 12:17:58', 1, NULL),
(25, 13, 1, 'VOTE-9YY0-U3G7-E7K3-4EFI', 'used', '2026-05-21 02:22:19', NULL, '2026-05-21 03:37:52', '2026-06-20 02:22:19', 1, NULL),
(26, 14, 1, 'VOTE-E5S7-Z9PO-9E26-2AN8', 'used', '2026-05-21 03:40:36', NULL, '2026-05-21 03:41:49', '2026-06-20 03:40:36', 1, NULL),
(27, 14, 1, 'VOTE-P2C2-4AYW-95D8-34HO', 'used', '2026-05-21 03:53:42', NULL, '2026-05-21 03:54:24', '2026-06-20 03:53:42', 1, NULL);

-- --------------------------------------------------------

--
-- Structure de la vue `active_elections_view`
--
DROP TABLE IF EXISTS `active_elections_view`;

DROP VIEW IF EXISTS `active_elections_view`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `active_elections_view`  AS SELECT `e`.`id` AS `id`, `e`.`title` AS `title`, `e`.`description` AS `description`, `e`.`start_date` AS `start_date`, `e`.`end_date` AS `end_date`, `e`.`status` AS `status`, `e`.`created_by` AS `created_by`, `e`.`created_at` AS `created_at`, `e`.`updated_at` AS `updated_at`, count(`v`.`id`) AS `total_votes`, count(distinct `v`.`user_id`) AS `unique_voters` FROM (`elections` `e` left join `votes` `v` on((`e`.`id` = `v`.`election_id`))) WHERE ((`e`.`status` = 'active') AND (`e`.`start_date` <= curdate()) AND (`e`.`end_date` >= curdate())) GROUP BY `e`.`id` ;

-- --------------------------------------------------------

--
-- Structure de la vue `eligible_students_view`
--
DROP TABLE IF EXISTS `eligible_students_view`;

DROP VIEW IF EXISTS `eligible_students_view`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `eligible_students_view`  AS SELECT `u`.`id` AS `id`, `u`.`name` AS `name`, `u`.`email` AS `email`, `s`.`student_id` AS `student_id`, `s`.`department` AS `department`, `s`.`level` AS `level` FROM (`users` `u` join `students` `s` on((`u`.`id` = `s`.`user_id`))) WHERE ((`u`.`type` = 'student') AND (`u`.`status` = 'approved')) ;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
