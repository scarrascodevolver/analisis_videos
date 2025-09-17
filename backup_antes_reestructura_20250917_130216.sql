-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: 127.0.0.1    Database: rugby_analysis
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache`
--

LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
INSERT INTO `cache` VALUES ('rugby_analysis_system_los_troncos_cache_eliascarrascoaguayo@gmail.com|127.0.0.1','i:1;',1757982546),('rugby_analysis_system_los_troncos_cache_eliascarrascoaguayo@gmail.com|127.0.0.1:timer','i:1757982546;',1757982546);
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache_locks`
--

LOCK TABLES `cache_locks` WRITE;
/*!40000 ALTER TABLE `cache_locks` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_locks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `categories_name_unique` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES (1,'Adulta Primera','Categoría primera división adulta','2025-09-15 03:58:39','2025-09-15 03:58:39'),(2,'Adulta Intermedia','Categoría intermedia división adulta','2025-09-15 03:58:39','2025-09-15 03:58:39'),(3,'Juveniles','Categoría juvenil','2025-09-15 03:58:39','2025-09-15 03:58:39'),(4,'Femenino','Categoría femenina','2025-09-15 03:58:39','2025-09-15 03:58:39');
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `failed_jobs`
--

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_batches`
--

LOCK TABLES `job_batches` WRITE;
/*!40000 ALTER TABLE `job_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jobs`
--

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0001_01_01_000000_create_users_table',1),(2,'0001_01_01_000001_create_cache_table',1),(3,'0001_01_01_000002_create_jobs_table',1),(4,'2025_06_28_041508_create_teams_table',1),(5,'2025_06_28_041529_create_categories_table',1),(6,'2025_06_28_041537_create_user_profiles_table',1),(7,'2025_06_28_041545_create_videos_table',1),(8,'2025_06_28_041552_create_video_comments_table',1),(9,'2025_06_28_041600_create_video_assignments_table',1),(10,'2025_06_28_061456_add_general_category_to_video_comments_table',1),(11,'2025_06_28_064958_create_rugby_situations_table',1),(12,'2025_06_28_065058_add_rugby_situation_to_videos_table',1),(13,'2025_09_15_011500_remove_status_from_video_assignments',2),(14,'2025_09_15_050000_add_player_fields_to_user_profiles_table',3),(15,'2025_09_15_060000_remove_experience_level_from_user_profiles_table',4),(16,'2025_09_16_001746_add_thumbnail_path_to_videos_table',5),(17,'2025_09_16_010004_add_visibility_type_to_videos_table',6),(18,'2025_09_16_014224_add_user_category_id_to_user_profiles_table',7);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_reset_tokens`
--

LOCK TABLES `password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rugby_situations`
--

DROP TABLE IF EXISTS `rugby_situations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rugby_situations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `category` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `color` varchar(7) NOT NULL DEFAULT '#007bff',
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rugby_situations`
--

LOCK TABLES `rugby_situations` WRITE;
/*!40000 ALTER TABLE `rugby_situations` DISABLE KEYS */;
INSERT INTO `rugby_situations` VALUES (1,'Scrum (Melé)','Jugadas Fijas',NULL,'#dc3545',1,10,'2025-09-15 03:57:14','2025-09-15 03:57:14'),(2,'Line Out (Saque lateral)','Jugadas Fijas',NULL,'#dc3545',1,20,'2025-09-15 03:57:14','2025-09-15 03:57:14'),(3,'Reinicio (Saque de 22, drop out)','Jugadas Fijas',NULL,'#dc3545',1,30,'2025-09-15 03:57:14','2025-09-15 03:57:14'),(4,'Ruck (Agrupamiento)','Juego Dinámico',NULL,'#fd7e14',1,40,'2025-09-15 03:57:14','2025-09-15 03:57:14'),(5,'Maul (Maul)','Juego Dinámico',NULL,'#fd7e14',1,50,'2025-09-15 03:57:14','2025-09-15 03:57:14'),(6,'Tackle (Placaje)','Juego Dinámico',NULL,'#fd7e14',1,60,'2025-09-15 03:57:14','2025-09-15 03:57:14'),(7,'Breakdown (Disputa de pelota)','Juego Dinámico',NULL,'#fd7e14',1,70,'2025-09-15 03:57:14','2025-09-15 03:57:14'),(8,'Ataque estructurado (1-3 fases)','Fase de Ataque',NULL,'#28a745',1,80,'2025-09-15 03:57:14','2025-09-15 03:57:14'),(9,'Ataque prolongado (4+ fases)','Fase de Ataque',NULL,'#28a745',1,90,'2025-09-15 03:57:14','2025-09-15 03:57:14'),(10,'Penetración individual','Fase de Ataque',NULL,'#28a745',1,100,'2025-09-15 03:57:14','2025-09-15 03:57:14'),(11,'Juego de backs','Fase de Ataque',NULL,'#28a745',1,110,'2025-09-15 03:57:14','2025-09-15 03:57:14'),(12,'Contraataque','Fase de Ataque',NULL,'#28a745',1,120,'2025-09-15 03:57:14','2025-09-15 03:57:14'),(13,'Línea defensiva','Fase Defensiva',NULL,'#007bff',1,130,'2025-09-15 03:57:14','2025-09-15 03:57:14'),(14,'Defensa de canal','Fase Defensiva',NULL,'#007bff',1,140,'2025-09-15 03:57:14','2025-09-15 03:57:14'),(15,'Defensa de patadas','Fase Defensiva',NULL,'#007bff',1,150,'2025-09-15 03:57:14','2025-09-15 03:57:14'),(16,'Presión defensiva','Fase Defensiva',NULL,'#007bff',1,160,'2025-09-15 03:57:14','2025-09-15 03:57:14'),(17,'Patadas tácticas','Juego Aéreo',NULL,'#6f42c1',1,170,'2025-09-15 03:57:14','2025-09-15 03:57:14'),(18,'Box kick','Juego Aéreo',NULL,'#6f42c1',1,180,'2025-09-15 03:57:14','2025-09-15 03:57:14'),(19,'Patada de despeje','Juego Aéreo',NULL,'#6f42c1',1,190,'2025-09-15 03:57:14','2025-09-15 03:57:14'),(20,'Drop goal','Juego Aéreo',NULL,'#6f42c1',1,200,'2025-09-15 03:57:14','2025-09-15 03:57:14'),(21,'Zona roja (cerca del in-goal)','Situaciones Especiales',NULL,'#e83e8c',1,210,'2025-09-15 03:57:14','2025-09-15 03:57:14'),(22,'Penales','Situaciones Especiales',NULL,'#e83e8c',1,220,'2025-09-15 03:57:14','2025-09-15 03:57:14'),(23,'Ventaja','Situaciones Especiales',NULL,'#e83e8c',1,230,'2025-09-15 03:57:14','2025-09-15 03:57:14'),(24,'Superioridad numérica','Situaciones Especiales',NULL,'#e83e8c',1,240,'2025-09-15 03:57:14','2025-09-15 03:57:14'),(25,'Manejo de pelota','Habilidades Individuales',NULL,'#20c997',1,250,'2025-09-15 03:57:14','2025-09-15 03:57:14'),(26,'Pase','Habilidades Individuales',NULL,'#20c997',1,260,'2025-09-15 03:57:14','2025-09-15 03:57:14'),(27,'Decisiones','Habilidades Individuales',NULL,'#20c997',1,270,'2025-09-15 03:57:14','2025-09-15 03:57:14'),(28,'Comunicación','Habilidades Individuales',NULL,'#20c997',1,280,'2025-09-15 03:57:14','2025-09-15 03:57:14');
/*!40000 ALTER TABLE `rugby_situations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sessions`
--

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
INSERT INTO `sessions` VALUES ('4dAWsfQfmFJ5RDsLptuhKgtpAo01rzx4l53Xx5Qv',NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoidXFJQXRvbGc3VmZ2MjFUUDBPR2piY1hqSEdhUDRxQVBaQ0JjbkZMWiI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6NDU6Imh0dHA6Ly9sb2NhbGhvc3QvcnVnYnktYW5hbHlzaXMtc3lzdGVtL3B1YmxpYyI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=',1758121006),('cWtoAcadp0ChhbpKguZ9WLlLziDorO3Kd5ow4aLo',32,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','YTo2OntzOjY6Il90b2tlbiI7czo0MDoicUJTUnk1VU9VOFlyMzhiamxjUFZwM2EwcHBFWnpxY0o5VjF5bWVMVyI7czozOiJ1cmwiO2E6MDp7fXM6OToiX3ByZXZpb3VzIjthOjE6e3M6MzoidXJsIjtzOjM4OiJodHRwOi8vMTI3LjAuMC4xOjgwMDAvdmlkZW9zLzE0L3N0cmVhbSI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fXM6NTA6ImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjtpOjMyO3M6NDoiYXV0aCI7YToxOntzOjIxOiJwYXNzd29yZF9jb25maXJtZWRfYXQiO2k6MTc1ODEyMTMyODt9fQ==',1758124178),('EjlfSYWgJyQSRyKJY656QVyFd6JxgihC3UaXSQVi',NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoib0hiMWlNSE82Q0FaS2p4S3M4SXNsbHN5bHhRZ0FiUXc5RU05RElhWSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6NDU6Imh0dHA6Ly9sb2NhbGhvc3QvcnVnYnktYW5hbHlzaXMtc3lzdGVtL3B1YmxpYyI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=',1758121006),('gxzZwks6jW0aBs46DmyGBjF0FueV2PlovmtXogcv',NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiR2w0ZzhxVjBOMmc3MjkyU1BKamVqYUp6WjRZZng1ZzE1dGozc0NJRiI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6NDU6Imh0dHA6Ly9sb2NhbGhvc3QvcnVnYnktYW5hbHlzaXMtc3lzdGVtL3B1YmxpYyI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=',1758121006),('P5oswepUm8uuRw47daMoqNeG8th23AvXv7TRe8X4',36,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0','YTo1OntzOjY6Il90b2tlbiI7czo0MDoiSU5wdXJrcm9vNDMzRGFEZW4ySXgxRUhka3RQTTRNd1hIM2JwMXdveiI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mzg6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC92aWRlb3MvMTQvc3RyZWFtIjt9czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6MzY7czo0OiJhdXRoIjthOjE6e3M6MjE6InBhc3N3b3JkX2NvbmZpcm1lZF9hdCI7aToxNzU4MTIzOTgyO319',1758124361),('WRyenfyo94LChf6Ebxy1JpNAmUHOcf2zb1nAbvm3',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0','YTozOntzOjY6Il90b2tlbiI7czo0MDoieUxtNURXYlE1dXZBaDNmTEY2RnVPa1NacWx5ZFQ3OE9qWkx0R1JjQSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzA6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9yZWdpc3RlciI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=',1758123002);
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `teams`
--

DROP TABLE IF EXISTS `teams`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `teams` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `abbreviation` varchar(10) DEFAULT NULL,
  `is_own_team` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `teams_name_unique` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `teams`
--

LOCK TABLES `teams` WRITE;
/*!40000 ALTER TABLE `teams` DISABLE KEYS */;
INSERT INTO `teams` VALUES (1,'Los Troncos','LT',1,'2025-09-15 03:58:11','2025-09-15 03:58:11'),(2,'DOBS','DOBS',0,'2025-09-15 03:58:11','2025-09-15 03:58:11'),(3,'All Brads A','ABA',0,'2025-09-15 03:58:11','2025-09-15 03:58:11'),(4,'Old Georgians','OG',0,'2025-09-15 03:58:11','2025-09-15 03:58:11'),(5,'Tabancura RC','TRC',0,'2025-09-15 03:58:11','2025-09-15 03:58:11'),(6,'Old Locks','OL',0,'2025-09-15 03:58:11','2025-09-15 03:58:11'),(7,'Lagartos RC','LRC',0,'2025-09-15 03:58:11','2025-09-15 03:58:11'),(8,'Old Gabs','OGB',0,'2025-09-15 03:58:11','2025-09-15 03:58:11'),(9,'Old Anglonians','OA',0,'2025-09-15 03:58:11','2025-09-15 03:58:11'),(10,'Costa del Sol','CDS',0,'2025-09-15 03:58:11','2025-09-15 03:58:11');
/*!40000 ALTER TABLE `teams` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_profiles`
--

DROP TABLE IF EXISTS `user_profiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_profiles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `position` varchar(255) DEFAULT NULL,
  `secondary_position` varchar(255) DEFAULT NULL,
  `player_number` int(11) DEFAULT NULL,
  `weight` int(11) DEFAULT NULL,
  `height` int(11) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `goals` text DEFAULT NULL,
  `coaching_experience` int(11) DEFAULT NULL,
  `certifications` text DEFAULT NULL,
  `specializations` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`specializations`)),
  `club_team_organization` varchar(255) DEFAULT NULL,
  `division_category` varchar(255) DEFAULT NULL,
  `user_category_id` bigint(20) unsigned DEFAULT NULL COMMENT 'FK a categories - categoría del usuario (Juveniles, Adulta Primera, etc.)',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_profiles_user_id_foreign` (`user_id`),
  KEY `user_profiles_user_category_id_foreign` (`user_category_id`),
  CONSTRAINT `user_profiles_user_category_id_foreign` FOREIGN KEY (`user_category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `user_profiles_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=35 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_profiles`
--

LOCK TABLES `user_profiles` WRITE;
/*!40000 ALTER TABLE `user_profiles` DISABLE KEYS */;
INSERT INTO `user_profiles` VALUES (1,1,'Analista Senior',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Los Troncos Rugby Club','Primera División',NULL,'2025-08-27 07:29:22','2025-08-27 07:29:22'),(2,2,'Primera Línea',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Los Troncos Rugby Club','Primera División',3,'2025-08-27 07:29:22','2025-09-16 04:46:55'),(3,3,'Entrenador Principal',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Los Troncos Rugby Club','Primera División',NULL,'2025-08-27 07:29:23','2025-08-27 07:29:23'),(4,4,'Director Técnico',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Los Troncos Rugby Club','Primera División',NULL,'2025-08-27 07:29:23','2025-08-27 07:29:23'),(5,5,'Scout',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Los Troncos Rugby Club','Primera División',NULL,'2025-08-27 07:29:23','2025-08-27 07:29:23'),(6,6,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Los Troncos Rugby Club',NULL,NULL,'2025-08-27 07:29:24','2025-08-27 07:29:24'),(7,7,'Hooker (#2)',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Los Troncos Rugby Club','Primera División',1,'2025-09-15 03:57:21','2025-09-17 03:52:04'),(9,9,'Prop Derecho (#3)',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Los Troncos Rugby Club','Primera División',2,'2025-09-15 03:57:22','2025-09-17 03:52:04'),(30,32,'Analista de Video',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'\"An\\u00e1lisis t\\u00e1ctico, video analysis\"','Club Los Troncos',NULL,NULL,'2025-09-16 02:31:19','2025-09-16 02:31:19'),(31,35,'Entrenador Principal',NULL,NULL,NULL,NULL,NULL,NULL,10,'World Rugby Level 3, Certificación Nacional','[\"Estrategia general\",\"Liderazgo de equipo\",\"Planificaci\\u00f3n t\\u00e1ctica\"]','Club Los Troncos',NULL,3,'2025-09-16 02:33:32','2025-09-17 18:44:18'),(32,36,'Entrenador Asistente',NULL,NULL,NULL,NULL,NULL,NULL,5,'World Rugby Level 2, Certificación de Asistente','[\"Asistencia t\\u00e9cnica\",\"Desarrollo de jugadores\",\"An\\u00e1lisis de juego\"]','Club Los Troncos',NULL,1,'2025-09-16 02:33:48','2025-09-17 18:44:18'),(33,37,'Entrenador de Forwards',NULL,NULL,NULL,NULL,NULL,NULL,8,'Especialización en Forwards, World Rugby Level 2','[\"Scrum\",\"Line-out\",\"Forwards t\\u00e9cnicos\",\"Maul\"]','Club Los Troncos',NULL,2,'2025-09-16 02:34:05','2025-09-17 18:44:18'),(34,38,'Director de Club',NULL,NULL,NULL,NULL,NULL,NULL,15,'Dirección Deportiva, Gestión de Clubes','[\"Gesti\\u00f3n deportiva\",\"Direcci\\u00f3n t\\u00e9cnica\",\"Planificaci\\u00f3n estrat\\u00e9gica\"]','Club Los Troncos',NULL,NULL,'2025-09-16 02:34:22','2025-09-16 02:34:22');
/*!40000 ALTER TABLE `user_profiles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `role` enum('analista','jugador','entrenador','director_tecnico','scout','aficionado') NOT NULL DEFAULT 'jugador',
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=40 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Juan Pérez','analista@rugby.com',NULL,'$2y$12$QsyqYOZNEiIwToKyGgg69e4ELqWtBVP2MLmy9IUTekM6dRlfiREaW','+56912345678','analista','Q9sitll6BoW4VqqilND6JuRmSMfbnMW29WXnZuIPIfavpFrynCNu9n0Z87Cz','2025-08-27 07:29:22','2025-08-27 07:29:22'),(2,'Carlos Rodríguez','jugador@rugby.com',NULL,'$2y$12$0IOLY0.qcdYq.gkxbjyRCOx8TC37O6Y6f0X11wBgAOysZlOHF5WRS','+56987654321','jugador',NULL,'2025-08-27 07:29:22','2025-08-27 07:29:22'),(3,'Roberto Silva','entrenador@rugby.com',NULL,'$2y$12$/.P8xSkn4UHVyrzdhS1RYOW4IVIJMOtD3xVFqEu528m8.Jy0ICmWa','+56911223344','entrenador',NULL,'2025-08-27 07:29:23','2025-08-27 07:29:23'),(4,'Miguel Ángel González','dt@rugby.com',NULL,'$2y$12$v8JmjaLDnW/JKyce3YBY1e8E3ycn1yOBwDMwhjxTr5gbu58jcH03y','+56955667788','director_tecnico',NULL,'2025-08-27 07:29:23','2025-08-27 07:29:23'),(5,'Fernando Martínez','scout@rugby.com',NULL,'$2y$12$.OOY6toOiMl2qgoLE7mcfOnJOIH.VNFPGHnwLohLSv2Vnp2kg7Nqa','+56999887766','scout',NULL,'2025-08-27 07:29:23','2025-08-27 07:29:23'),(6,'María López','aficionado@rugby.com',NULL,'$2y$12$JqURp.rKbrmgsGmBvESRo.oMVcz3u2iy6Mnqy.1McsD4Ljrte0PsK','+56933445566','aficionado',NULL,'2025-08-27 07:29:24','2025-08-27 07:29:24'),(7,'Diego Morales','diego.morales@lostroncos.cl',NULL,'$2y$12$DO5D7xuClU91WD0h9Se/CuSZgj/TsmouD44yMsnXf0ZODv7wk4PEe','+56999221171','jugador',NULL,'2025-09-15 03:57:21','2025-09-15 03:57:21'),(9,'Felipe Castillo','felipe.castillo@lostroncos.cl',NULL,'$2y$12$ZGTRNnqPZT94fXH.5RT8heHZIxPUVWi8DeBpR34/E23xBP/SXwhRS','+56989703419','jugador',NULL,'2025-09-15 03:57:22','2025-09-15 03:57:22'),(32,'Jeremias Rodriguez','jere@clublostroncos.cl','2025-09-16 03:03:36','$2y$12$1UssV7l2pBZhI8O/W28Ap.VDA77g7mo/wNXxW6GGVIgBl1DwCyTbu',NULL,'analista','ux2C1Vb38nSUFiXhj5uCePojemYiPYd10DAS9Po1FGhZdDqrZsa2pFGU3m9a','2025-09-16 02:31:19','2025-09-16 03:03:36'),(35,'Juan Cruz Fleitas','juancruz@clublostroncos.cl','2025-09-16 03:03:36','$2y$12$cNL9.v.h9Usv7aIHHM/XaOMYzK1bsfSb6WE5kNPrrorfrlWOQgT5u',NULL,'entrenador',NULL,'2025-09-16 02:33:32','2025-09-16 03:03:36'),(36,'Valentín Dapena','valentin@clublostroncos.cl','2025-09-16 03:03:36','$2y$12$OrNeuvNW1cGhJHIn1pT3Y.sjrkzbXtbTvoG.BYJpo2MyiRUaBBWXu',NULL,'entrenador',NULL,'2025-09-16 02:33:48','2025-09-16 03:03:36'),(37,'Víctor Escobar','victor@clublostroncos.cl','2025-09-16 03:03:36','$2y$12$no4J/3jrYvGF.a1s7/KIVe84csDpgEN9WIxyunYaRpa6m4cGfZQIi',NULL,'entrenador',NULL,'2025-09-16 02:34:05','2025-09-16 03:03:36'),(38,'Juan Carlos Rodríguez','juancarlos@clublostroncos.cl','2025-09-16 03:03:36','$2y$12$ijQduxc4NdiyqtJmTiGs5e3o2WYsZdqHSQlAaZ/G9wxbUD8Zin9JC',NULL,'director_tecnico',NULL,'2025-09-16 02:34:22','2025-09-16 03:03:36');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `video_assignments`
--

DROP TABLE IF EXISTS `video_assignments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `video_assignments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `video_id` bigint(20) unsigned NOT NULL,
  `assigned_by` bigint(20) unsigned NOT NULL,
  `assigned_to` bigint(20) unsigned NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `video_assignments_video_id_foreign` (`video_id`),
  KEY `video_assignments_assigned_by_foreign` (`assigned_by`),
  KEY `video_assignments_assigned_to_foreign` (`assigned_to`),
  CONSTRAINT `video_assignments_assigned_by_foreign` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`),
  CONSTRAINT `video_assignments_assigned_to_foreign` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`),
  CONSTRAINT `video_assignments_video_id_foreign` FOREIGN KEY (`video_id`) REFERENCES `videos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `video_assignments`
--

LOCK TABLES `video_assignments` WRITE;
/*!40000 ALTER TABLE `video_assignments` DISABLE KEYS */;
INSERT INTO `video_assignments` VALUES (6,10,32,2,'adsds','2025-09-17 18:03:33','2025-09-17 18:03:33');
/*!40000 ALTER TABLE `video_assignments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `video_comments`
--

DROP TABLE IF EXISTS `video_comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `video_comments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `video_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `parent_id` bigint(20) unsigned DEFAULT NULL,
  `comment` text NOT NULL,
  `timestamp_seconds` int(11) NOT NULL,
  `category` enum('tecnico','tactico','fisico','mental','general') DEFAULT 'tecnico',
  `priority` enum('baja','media','alta','critica') NOT NULL DEFAULT 'media',
  `status` enum('pendiente','en_revision','completado') NOT NULL DEFAULT 'pendiente',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `video_comments_video_id_foreign` (`video_id`),
  KEY `video_comments_user_id_foreign` (`user_id`),
  KEY `video_comments_parent_id_foreign` (`parent_id`),
  CONSTRAINT `video_comments_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `video_comments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `video_comments_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `video_comments_video_id_foreign` FOREIGN KEY (`video_id`) REFERENCES `videos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `video_comments`
--

LOCK TABLES `video_comments` WRITE;
/*!40000 ALTER TABLE `video_comments` DISABLE KEYS */;
/*!40000 ALTER TABLE `video_comments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `videos`
--

DROP TABLE IF EXISTS `videos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `videos` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `file_path` varchar(255) NOT NULL,
  `thumbnail_path` varchar(255) DEFAULT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_size` int(11) NOT NULL,
  `mime_type` varchar(255) NOT NULL,
  `duration` int(11) DEFAULT NULL,
  `uploaded_by` bigint(20) unsigned NOT NULL,
  `analyzed_team_id` bigint(20) unsigned NOT NULL,
  `rival_team_id` bigint(20) unsigned DEFAULT NULL,
  `category_id` bigint(20) unsigned NOT NULL,
  `match_date` date NOT NULL,
  `status` enum('pending','processing','completed','archived') NOT NULL DEFAULT 'pending',
  `visibility_type` enum('public','forwards','backs','specific') NOT NULL DEFAULT 'public' COMMENT 'Visibility level: public=all team, forwards=positions 1-8, backs=positions 9-15, specific=assigned players only',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `rugby_situation_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `videos_uploaded_by_foreign` (`uploaded_by`),
  KEY `videos_analyzed_team_id_foreign` (`analyzed_team_id`),
  KEY `videos_rival_team_id_foreign` (`rival_team_id`),
  KEY `videos_category_id_foreign` (`category_id`),
  KEY `videos_rugby_situation_id_foreign` (`rugby_situation_id`),
  CONSTRAINT `videos_analyzed_team_id_foreign` FOREIGN KEY (`analyzed_team_id`) REFERENCES `teams` (`id`),
  CONSTRAINT `videos_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`),
  CONSTRAINT `videos_rival_team_id_foreign` FOREIGN KEY (`rival_team_id`) REFERENCES `teams` (`id`),
  CONSTRAINT `videos_rugby_situation_id_foreign` FOREIGN KEY (`rugby_situation_id`) REFERENCES `rugby_situations` (`id`) ON DELETE SET NULL,
  CONSTRAINT `videos_uploaded_by_foreign` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `videos`
--

LOCK TABLES `videos` WRITE;
/*!40000 ALTER TABLE `videos` DISABLE KEYS */;
INSERT INTO `videos` VALUES (10,'Pase - Los Troncos vs Lagartos RC',NULL,'videos/1758121413_LIAD6750.MP4',NULL,'1758121413_LIAD6750.MP4',6426303,'video/mp4',NULL,32,1,7,3,'2025-09-17','pending','specific','2025-09-17 18:03:33','2025-09-17 18:03:33',26),(11,'Ataque estructurado (1-3 fases) - Los Troncos vs Tabancura RC','atentos jolaperras','videos/1758121687_Screenrecorder-2023-11-16-07-47-59-219.mp4',NULL,'1758121687_Screenrecorder-2023-11-16-07-47-59-219.mp4',34984901,'video/mp4',NULL,32,1,5,3,'2025-09-17','pending','public','2025-09-17 18:08:07','2025-09-17 18:08:07',8),(12,'Penetración individual - Los Troncos vs All Brads A',NULL,'videos/1758123150_Screenrecorder-2023-11-16-07-47-59-219.mp4',NULL,'1758123150_Screenrecorder-2023-11-16-07-47-59-219.mp4',34984901,'video/mp4',NULL,32,1,3,2,'2025-09-17','pending','public','2025-09-17 18:32:30','2025-09-17 18:32:30',10),(13,'Contraataque - Los Troncos vs Tabancura RC',NULL,'videos/1758123548_Screenrecorder-2023-11-16-07-47-59-219.mp4',NULL,'1758123548_Screenrecorder-2023-11-16-07-47-59-219.mp4',34984901,'video/mp4',NULL,32,1,5,3,'2025-09-17','pending','public','2025-09-17 18:39:08','2025-09-17 18:39:08',12),(14,'Penetración individual - Los Troncos vs Old Locks',NULL,'videos/1758124013_Screenrecorder-2023-11-16-07-47-59-219.mp4',NULL,'1758124013_Screenrecorder-2023-11-16-07-47-59-219.mp4',34984901,'video/mp4',NULL,32,1,6,1,'2025-09-17','pending','public','2025-09-17 18:46:53','2025-09-17 18:46:53',10);
/*!40000 ALTER TABLE `videos` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-09-17 13:02:18
