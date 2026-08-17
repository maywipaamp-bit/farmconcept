-- MySQL dump 10.13  Distrib 8.0.42, for Win64 (x86_64)
--
-- Host: 157.85.104.53    Database: farmconcept
-- ------------------------------------------------------
-- Server version	8.0.46

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `act_activities`
--

DROP TABLE IF EXISTS `act_activities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `act_activities` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `type` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `participant_type` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `parent_event_id` bigint unsigned DEFAULT NULL,
  `program_id` bigint unsigned DEFAULT NULL,
  `course_id` bigint unsigned DEFAULT NULL,
  `format_id` bigint unsigned DEFAULT NULL,
  `data_source` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `venue_mode` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `registration_mode` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ฉบับร่าง',
  `requires_registration` tinyint(1) NOT NULL DEFAULT '0',
  `requires_checkin` tinyint(1) NOT NULL DEFAULT '0',
  `has_post_survey` tinyint(1) NOT NULL DEFAULT '0',
  `has_fee` tinyint(1) NOT NULL DEFAULT '0',
  `fee` decimal(10,2) NOT NULL DEFAULT '0.00',
  `capacity` int unsigned NOT NULL DEFAULT '0',
  `organizer` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cover_image_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `checkin_start_at` timestamp NULL DEFAULT NULL,
  `checkin_end_at` timestamp NULL DEFAULT NULL,
  `survey_start_at` timestamp NULL DEFAULT NULL,
  `survey_end_at` timestamp NULL DEFAULT NULL,
  `is_published` tinyint(1) NOT NULL DEFAULT '0',
  `publish_start_at` timestamp NULL DEFAULT NULL,
  `publish_end_at` timestamp NULL DEFAULT NULL,
  `registration_start_at` timestamp NULL DEFAULT NULL,
  `registration_end_at` timestamp NULL DEFAULT NULL,
  `visibility` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'สาธารณะ',
  `is_featured` tinyint(1) NOT NULL DEFAULT '0',
  `public_sort_order` int unsigned NOT NULL DEFAULT '0',
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `act_activities_code_unique` (`code`) USING BTREE,
  KEY `act_activities_program_id_foreign` (`program_id`) USING BTREE,
  KEY `act_activities_course_id_foreign` (`course_id`) USING BTREE,
  KEY `act_activities_format_id_foreign` (`format_id`) USING BTREE,
  KEY `act_activities_created_by_foreign` (`created_by`) USING BTREE,
  KEY `act_activities_updated_by_foreign` (`updated_by`) USING BTREE,
  KEY `act_activities_status_index` (`status`) USING BTREE,
  KEY `act_activities_start_date_index` (`start_date`) USING BTREE,
  KEY `act_activities_participant_type_index` (`participant_type`) USING BTREE,
  KEY `act_activities_publish_window_index` (`is_published`,`publish_start_at`,`publish_end_at`) USING BTREE,
  KEY `act_activities_parent_event_id_foreign` (`parent_event_id`) USING BTREE,
  KEY `act_activities_public_listing_index` (`is_published`,`public_sort_order`) USING BTREE,
  CONSTRAINT `act_activities_course_id_foreign` FOREIGN KEY (`course_id`) REFERENCES `mst_courses` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `act_activities_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `act_activities_format_id_foreign` FOREIGN KEY (`format_id`) REFERENCES `mst_activity_formats` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `act_activities_parent_event_id_foreign` FOREIGN KEY (`parent_event_id`) REFERENCES `act_activities` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `act_activities_program_id_foreign` FOREIGN KEY (`program_id`) REFERENCES `mst_programs` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `act_activities_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `act_activities`
--

LOCK TABLES `act_activities` WRITE;
/*!40000 ALTER TABLE `act_activities` DISABLE KEYS */;
INSERT INTO `act_activities` VALUES (1,'ACT-2026-014','ปลูกผักปลอดสารสำหรับครอบครัว','กิจกรรมเรียนรู้การปลูกผักปลอดสารพิษ เหมาะสำหรับครอบครัวที่ต้องการเริ่มต้นปลูกผักไว้รับประทานเอง ผู้เข้าร่วมจะได้ลงมือปฏิบัติจริงตั้งแต่การเตรียมดิน เพาะกล้า จนถึงการดูแลรักษา','กิจกรรม','กลุ่มตัวอย่าง',NULL,1,5,4,'ลงทะเบียนออนไลน์',NULL,NULL,'เปิดรับสมัคร',1,1,1,0,0.00,40,'The Farm Concept ร่วมกับสำนักงานเขตสายไหม',NULL,'2026-08-10','2026-08-10','2026-08-10 01:00:00','2026-08-10 11:00:00',NULL,NULL,1,'2026-07-20 02:00:00','2026-08-09 16:59:00',NULL,NULL,'สาธารณะ',1,0,NULL,NULL,'2026-08-10 21:48:39','2026-08-11 02:18:04',NULL),(2,'ACT-2026-015','Workshop อาหารสุขภาพจากสวน','เรียนรู้การนำผักและสมุนไพรจากสวนมาปรุงเป็นเมนูอาหารเพื่อสุขภาพ พร้อมความรู้ด้านโภชนาการที่เหมาะกับทุกวัย','กิจกรรม','กลุ่มทั่วไป',NULL,1,1,4,'ลงทะเบียนออนไลน์',NULL,NULL,'เต็มแล้ว',1,1,1,1,200.00,30,'The Farm Concept',NULL,'2026-08-17','2026-08-17','2026-08-17 01:00:00','2026-08-17 11:00:00',NULL,NULL,1,'2026-07-25 02:00:00','2026-08-16 16:59:00',NULL,NULL,'สาธารณะ',0,0,NULL,NULL,'2026-08-10 21:48:39','2026-08-10 21:48:39',NULL),(3,'ACT-2026-016','เรียนรู้การทำปุ๋ยหมัก','อบรมเชิงปฏิบัติการทำปุ๋ยหมักจากเศษอาหารและวัสดุเหลือใช้ในครัวเรือน ลดขยะ เพิ่มความอุดมสมบูรณ์ให้ดิน','กิจกรรม','กลุ่มทั่วไป',NULL,2,7,2,'ลงทะเบียนหน้างาน',NULL,NULL,'เปิดรับสมัคร',0,1,1,0,0.00,25,'The Farm Concept ร่วมกับชุมชนตึกร้าง',NULL,'2026-08-24','2026-09-07','2026-08-24 01:00:00','2026-09-07 11:00:00',NULL,NULL,1,'2026-08-01 02:00:00','2026-08-23 16:59:00',NULL,NULL,'เฉพาะกลุ่มเป้าหมาย',0,0,NULL,NULL,'2026-08-10 21:48:39','2026-08-11 01:02:27',NULL),(4,'ACT-2026-017','กิจกรรมฟื้นฟูสุขภาวะชุมชน','กิจกรรมรวมฐานการเรียนรู้ด้านสุขภาวะ ทั้งการออกกำลังกาย โภชนาการ และการปลูกผักสวนครัว สำหรับทุกกลุ่มวัยในชุมชน','อีเว้นท์','กลุ่มตัวอย่าง',NULL,1,3,5,'นำเข้าจากไฟล์',NULL,NULL,'ฉบับร่าง',0,1,1,0,0.00,50,'The Farm Concept',NULL,'2026-07-20','2026-07-20','2026-07-20 01:00:00','2026-07-20 11:00:00',NULL,NULL,1,'2026-06-25 02:00:00','2026-07-19 16:59:00',NULL,NULL,'สาธารณะ',0,0,NULL,NULL,'2026-08-10 21:48:39','2026-08-11 22:21:58','2026-08-11 22:21:58'),(5,'ACT-2026-018','ตลาดนัดผักปลอดสารประจำเดือน','ตลาดนัดจำหน่ายผักปลอดสารพิษจากเกษตรกรในเครือข่ายชุมชน พบปะพูดคุยแลกเปลี่ยนความรู้การปลูกผักกับเกษตรกรตัวจริง','อีเว้นท์','กลุ่มทั่วไป',NULL,2,NULL,5,'บันทึกโดยเจ้าหน้าที่',NULL,NULL,'ฉบับร่าง',0,0,0,0,0.00,60,'The Farm Concept ร่วมกับชุมชนตึกร้าง',NULL,'2026-09-05','2026-09-05','2026-09-05 01:00:00','2026-09-05 05:00:00',NULL,NULL,0,'2026-08-15 02:00:00','2026-09-04 16:59:00',NULL,NULL,'สาธารณะ',0,0,NULL,6,'2026-08-10 21:48:40','2026-08-11 00:33:05','2026-08-11 00:33:05'),(9,'ACT-2026-019','ทดสอบ','รายละเอียด ทดสอบ','กิจกรรม',NULL,NULL,3,9,1,NULL,'จัดในพื้นที่ (Onsite)',NULL,'ฉบับร่าง',1,0,0,0,0.00,15,NULL,NULL,'2026-08-15','2026-08-15',NULL,NULL,NULL,NULL,0,'2026-08-11 00:00:00','2026-08-14 10:00:00',NULL,NULL,'สาธารณะ',1,0,6,6,'2026-08-11 02:35:31','2026-08-11 02:35:31',NULL),(10,'DEMO-ACT-01','Cooking Workshop ลดหวาน มัน เค็ม','กิจกรรมตัวอย่างสำหรับแดชบอร์ด — สร้างโดย DashboardDemoSeeder','กิจกรรม','บุคคลทั่วไป',NULL,8,27,NULL,NULL,NULL,NULL,'ดำเนินการเสร็จสิ้น',1,1,1,0,0.00,30,NULL,NULL,'2026-06-12','2026-06-12',NULL,NULL,NULL,NULL,1,NULL,NULL,NULL,NULL,'สาธารณะ',0,0,NULL,NULL,'2026-08-11 21:04:48','2026-08-11 21:04:48',NULL),(11,'DEMO-ACT-02','วางแผนมื้ออาหารสุขภาพ','กิจกรรมตัวอย่างสำหรับแดชบอร์ด — สร้างโดย DashboardDemoSeeder','กิจกรรม','บุคคลทั่วไป',NULL,3,12,NULL,NULL,NULL,NULL,'ดำเนินการเสร็จสิ้น',1,1,1,0,0.00,22,NULL,NULL,'2026-05-12','2026-05-12',NULL,NULL,NULL,NULL,1,NULL,NULL,NULL,NULL,'สาธารณะ',0,0,NULL,NULL,'2026-08-11 21:04:48','2026-08-11 21:04:48',NULL),(12,'ACT-2026-020','ทดสอบ','..','กิจกรรม',NULL,NULL,3,9,1,NULL,'จัดในพื้นที่ (Onsite)',NULL,'เปิดรับสมัคร',1,0,0,0,0.00,50,NULL,'activity-covers/W7XgwNgC86upZC2hJ4daOwYwEhGgSBfYYkuwa8PR.png','2026-08-12','2026-08-12',NULL,NULL,NULL,NULL,1,'2026-08-12 01:30:00','2026-08-12 09:30:00',NULL,NULL,'สาธารณะ',1,0,6,6,'2026-08-12 06:54:36','2026-08-12 06:54:36',NULL),(13,'ACT-2026-021','ทดสอบ2','..','กิจกรรม',NULL,NULL,3,9,4,NULL,'จัดในพื้นที่ (Onsite)',NULL,'เปิดรับสมัคร',1,0,0,0,0.00,20,NULL,'activity-covers/PKHTN62VelhBxqKRBeRT1iwadqTH0IqzAEcI77Uo.png','2026-08-12','2026-08-12',NULL,NULL,NULL,NULL,1,'2026-08-12 01:30:00','2026-08-12 04:00:00',NULL,NULL,'สาธารณะ',1,0,6,6,'2026-08-12 14:16:34','2026-08-12 14:16:34',NULL),(14,'ACT-PUB-001','ชวนเพลิดเพลิน สร้างสวนในขวดแก้ว','เรียนรู้การจัดสวนขนาดเล็กในขวดแก้ว พร้อมนำผลงานกลับบ้าน','กิจกรรม',NULL,18,3,9,4,NULL,'จัดในพื้นที่ (Onsite)',NULL,'เปิดรับสมัคร',1,1,1,1,199.00,60,'ทีมศิลปะ The Farm Concept','activity-covers/demo/photo-terrarium-featured.png','2026-08-19','2026-08-19','2026-08-16 01:30:00','2026-08-16 06:00:00','2026-08-16 10:00:00','2026-08-16 16:59:00',1,NULL,NULL,'2026-07-10 03:00:00','2026-08-18 16:59:00','สาธารณะ',1,1,NULL,6,'2026-08-13 05:35:47','2026-08-16 03:27:08',NULL),(15,'ACT-PUB-002','พักมือถือมาเพ้นท์กระถางต้นไม้กัน','ลงมือเพ้นท์กระถางต้นไม้ให้น่ารักในสไตล์ของตัวเอง พร้อมนำต้นไม้กลับบ้านไปปลูกต่อ','กิจกรรม',NULL,NULL,NULL,NULL,4,NULL,'จัดในพื้นที่ (Onsite)',NULL,'เปิดรับสมัคร',1,0,0,1,199.00,30,'ทีมศิลปะ The Farm Concept','activity-covers/demo/photo-pot-painting.png','2026-08-16','2026-08-16',NULL,NULL,NULL,NULL,1,NULL,NULL,'2026-08-15 01:00:00','2026-08-15 16:59:00','สาธารณะ',1,2,NULL,6,'2026-08-13 05:35:47','2026-08-15 05:55:32',NULL),(16,'ACT-PUB-003','จัดดอกไม้จากวัสดุธรรมชาติ','จัดช่อดอกไม้และวัสดุจากธรรมชาติด้วยตัวเอง เรียนรู้เทคนิคการจัดวางเบื้องต้น','กิจกรรม',NULL,18,3,10,1,NULL,'จัดในพื้นที่ (Onsite)',NULL,'เปิดรับสมัคร',1,0,0,0,0.00,30,'ทีมจัดสวน The Farm Concept','activity-covers/demo/photo-flower-arranging.png','2026-08-23','2026-08-23',NULL,NULL,NULL,NULL,1,NULL,NULL,'2026-08-15 01:30:00','2026-08-21 16:59:00','สาธารณะ',1,3,NULL,6,'2026-08-13 05:35:47','2026-08-15 05:56:13',NULL),(17,'ACT-PUB-004','เบเกอรี่เพื่อสุขภาพ เมนูไร้น้ำตาล','ลงมือทำเบเกอรี่เพื่อสุขภาพแบบไร้น้ำตาล เรียนรู้การเลือกวัตถุดิบทดแทนน้ำตาล','กิจกรรม',NULL,NULL,NULL,NULL,3,NULL,'จัดในพื้นที่ (Onsite)',NULL,'เปิดรับสมัคร',1,0,0,1,199.00,30,'อาจารย์พิมพ์ชนก ศรีสมบัติ','activity-covers/demo/photo-baking-workshop.png','2026-08-30','2026-08-30',NULL,NULL,NULL,NULL,1,NULL,NULL,'2026-08-15 01:30:00','2026-08-28 16:59:00','สาธารณะ',1,4,NULL,6,'2026-08-13 05:35:47','2026-08-15 05:56:54',NULL),(18,'ACT-PUB-005','จ่ายตลาดในสวน 🌿','ตลาดผักและผลิตภัณฑ์ชุมชนภายในสวน The Farm Concept','อีเว้นท์',NULL,NULL,NULL,NULL,5,NULL,'จัดในพื้นที่ (Onsite)',NULL,'เปิดรับสมัคร',0,0,0,0,0.00,0,'The Farm Concept','activity-covers/demo/photo-market-vegetables.png','2026-08-09','2026-08-09',NULL,NULL,NULL,NULL,1,NULL,NULL,NULL,NULL,'สาธารณะ',1,1,NULL,6,'2026-08-13 05:35:47','2026-08-15 05:53:19',NULL),(19,'ACT-PUB-006','มาปะคอนเสิร์ต','กิจกรรมดนตรีในสวนสำหรับครอบครัวและชุมชน','อีเว้นท์',NULL,NULL,NULL,NULL,5,NULL,NULL,NULL,'เปิดรับสมัคร',0,0,0,0,0.00,0,'The Farm Concept','activity-covers/demo/photo-garden-concert.png','2026-08-24','2026-08-24',NULL,NULL,NULL,NULL,1,NULL,NULL,NULL,NULL,'สาธารณะ',1,6,NULL,NULL,'2026-08-13 05:35:47','2026-08-13 05:35:47',NULL),(20,'ACT-PUB-007','Happy Beagle Day','กิจกรรมพบปะสำหรับผู้เลี้ยงสุนัขบีเกิลและคนรักสัตว์','อีเว้นท์',NULL,NULL,NULL,NULL,5,NULL,'จัดในพื้นที่ (Onsite)',NULL,'เปิดรับสมัคร',0,0,0,0,0.00,0,'The Farm Concept','activity-covers/demo/photo-dog-run.png','2026-09-06','2026-09-06',NULL,NULL,NULL,NULL,1,NULL,NULL,NULL,NULL,'สาธารณะ',0,7,NULL,6,'2026-08-13 05:35:47','2026-08-15 05:50:30',NULL),(21,'ACT-PUB-008','เลอะได้...ไม่เป็นไร','กิจกรรมสร้างสรรค์และการเล่นอย่างอิสระสำหรับเด็ก','อีเว้นท์',NULL,NULL,3,9,5,NULL,'จัดในพื้นที่ (Onsite)',NULL,'เปิดรับสมัคร',1,1,0,1,200.00,50,'The Farm Concept','activity-covers/demo/photo-messy-play.png','2026-09-19','2026-09-19','2026-08-18 23:00:00','2026-08-19 03:00:00',NULL,NULL,1,NULL,NULL,'2026-08-14 01:30:00','2026-08-18 09:30:00','สาธารณะ',1,1,NULL,6,'2026-08-13 05:35:48','2026-08-15 05:28:01',NULL),(22,'ACT-2026-022','เปิดพื้นที่ “ตลาดสีเขียว” ทุกสุดสัปดาห์','The Farm Concept เปิดพื้นที่ตลาดสีเขียว เพื่อเชื่อมโยงผู้บริโภคกับเกษตรกรและผู้ผลิตในชุมชน ชวนทุกคนมาเลือกซื้อผัก ผลไม้ อาหาร และผลิตภัณฑ์ที่คัดสรรโดยคำนึงถึงคุณภาพและความปลอดภัย พร้อมใช้เวลาสบาย ๆ ท่ามกลางบรรยากาศสีเขียว','ข่าวสาร',NULL,NULL,8,29,5,NULL,'จัดในพื้นที่ (Onsite)',NULL,'เปิดรับสมัคร',0,0,0,0,0.00,1000,NULL,'activity-covers/dFDsPrQYMCU8xrmQwnSTUEVDNqZuSpkshG1whkvk.png','2026-08-23','2026-08-23',NULL,NULL,NULL,NULL,1,NULL,NULL,NULL,NULL,'สาธารณะ',1,8,6,6,'2026-08-15 05:43:21','2026-08-15 05:51:38',NULL),(23,'ACT-2026-023','เพิ่มพื้นที่เรียนรู้สวนผักสำหรับครอบครัว','เราเพิ่มพื้นที่สวนผักเรียนรู้สำหรับเด็กและครอบครัว เพื่อให้ทุกคนได้สัมผัสกระบวนการผลิตอาหารผ่านการลงมือทำจริง เรียนรู้เรื่องดิน เมล็ดพันธุ์ การดูแลพืช และการเก็บเกี่ยวในรูปแบบที่เข้าใจง่ายและสนุก','ข่าวสาร',NULL,NULL,3,10,5,NULL,'จัดในพื้นที่ (Onsite)',NULL,'เปิดรับสมัคร',0,0,0,0,0.00,0,NULL,'activity-covers/M6nmXSw4mlUsiiriuIvieQ3g1SSQ6gqejKfGXqls.png','2026-08-20','2026-08-20',NULL,NULL,NULL,NULL,1,NULL,NULL,NULL,NULL,'สาธารณะ',1,9,6,6,'2026-08-15 05:45:11','2026-08-15 05:51:27',NULL),(24,'ACT-2026-024','ปรับเวลาเปิดให้บริการช่วงกิจกรรมพิเศษ','ในบางวันที่มีกิจกรรมพิเศษ The Farm Concept อาจมีการปรับเวลาเปิด–ปิดพื้นที่เป็นกรณีเฉพาะ ผู้เข้าร่วมสามารถตรวจสอบวัน เวลา และรายละเอียดล่าสุดได้จากหน้าเว็บไซต์ก่อนเดินทาง\nเวลาทำการปกติ: วันอังคาร–วันอาทิตย์ 09:00–18:00 น. และปิดวันจันทร์','ข่าวสาร',NULL,NULL,3,9,5,NULL,'จัดในพื้นที่ (Onsite)',NULL,'เปิดรับสมัคร',0,0,0,0,0.00,0,NULL,'activity-covers/juKoaxdPgD1yW4Sfhj6uD1aIFpR7g6G9Nz6NQXPi.png','2026-08-15','2026-08-15',NULL,NULL,NULL,NULL,1,NULL,NULL,NULL,NULL,'สาธารณะ',1,10,6,6,'2026-08-15 05:46:48','2026-08-15 05:50:57',NULL),(25,'ACT-2026-025','เยี่ยมชมสวนที่ The Farm Concept Bearing','..','กิจกรรม',NULL,NULL,NULL,NULL,5,NULL,'จัดในพื้นที่ (Onsite)',NULL,'เปิดรับสมัคร',0,0,1,0,0.00,100,NULL,'activity-covers/O69WzXgG0Xen7RRnPjIGqucvoIc4CR4M1xj3OtqG.jpg','2026-08-17','2026-08-17',NULL,NULL,'2026-08-17 09:00:00','2026-08-24 16:59:00',1,NULL,NULL,NULL,NULL,'สาธารณะ',0,12,6,6,'2026-08-17 07:19:01','2026-08-17 07:24:05',NULL),(26,'ACT-2026-026','เยี่ยมชมสวน The Farm Concept Bearing','...','กิจกรรม',NULL,NULL,NULL,NULL,5,NULL,'จัดในพื้นที่ (Onsite)',NULL,'เปิดรับสมัคร',0,0,0,0,0.00,0,NULL,'activity-covers/RGeB4mWrcQ4Fa4lBORSpsRZCRRY5IxHA8ZSIe2cO.png','2026-01-01','2026-01-01',NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,'สาธารณะ',0,0,6,6,'2026-08-17 07:20:14','2026-08-17 07:22:40',NULL),(27,'ACT-2026-027','แบบประเมินกลุ่มเป้าหมาย The Farm Concept Bearing','..','กิจกรรม',NULL,NULL,NULL,NULL,5,NULL,'จัดในพื้นที่ (Onsite)',NULL,'เปิดรับสมัคร',0,0,1,0,0.00,1000,NULL,'activity-covers/80fxozd1VaXe8Tu51l1gSuNBCnMUKxmpBOtVrLRI.jpg','2026-08-17','2026-08-17',NULL,NULL,'2026-08-17 09:00:00','2026-08-24 16:59:00',1,NULL,NULL,NULL,NULL,'สาธารณะ',0,11,6,6,'2026-08-17 07:21:14','2026-08-17 07:21:15',NULL);
/*!40000 ALTER TABLE `act_activities` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `act_activity_area`
--

DROP TABLE IF EXISTS `act_activity_area`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `act_activity_area` (
  `activity_id` bigint unsigned NOT NULL,
  `area_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`activity_id`,`area_id`) USING BTREE,
  KEY `act_activity_area_area_id_foreign` (`area_id`) USING BTREE,
  CONSTRAINT `act_activity_area_activity_id_foreign` FOREIGN KEY (`activity_id`) REFERENCES `act_activities` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `act_activity_area_area_id_foreign` FOREIGN KEY (`area_id`) REFERENCES `mst_areas` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `act_activity_area`
--

LOCK TABLES `act_activity_area` WRITE;
/*!40000 ALTER TABLE `act_activity_area` DISABLE KEYS */;
INSERT INTO `act_activity_area` VALUES (2,1),(9,1),(10,1),(11,1),(12,1),(13,1),(14,1),(15,1),(16,1),(17,1),(18,1),(20,1),(21,1),(22,1),(23,1),(24,1),(25,1),(26,1),(27,1),(1,2),(4,2),(10,2),(3,3),(4,3),(5,3);
/*!40000 ALTER TABLE `act_activity_area` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `act_activity_instructor`
--

DROP TABLE IF EXISTS `act_activity_instructor`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `act_activity_instructor` (
  `activity_id` bigint unsigned NOT NULL,
  `instructor_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`activity_id`,`instructor_id`) USING BTREE,
  KEY `act_activity_instructor_instructor_id_foreign` (`instructor_id`) USING BTREE,
  CONSTRAINT `act_activity_instructor_activity_id_foreign` FOREIGN KEY (`activity_id`) REFERENCES `act_activities` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `act_activity_instructor_instructor_id_foreign` FOREIGN KEY (`instructor_id`) REFERENCES `mst_instructors` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `act_activity_instructor`
--

LOCK TABLES `act_activity_instructor` WRITE;
/*!40000 ALTER TABLE `act_activity_instructor` DISABLE KEYS */;
INSERT INTO `act_activity_instructor` VALUES (1,1),(3,1),(20,1),(2,2),(9,2),(12,2),(13,2),(14,2),(18,2),(21,2),(24,2),(17,3),(23,3),(4,4),(15,4),(16,4),(5,5),(22,5);
/*!40000 ALTER TABLE `act_activity_instructor` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `act_activity_reg_fields`
--

DROP TABLE IF EXISTS `act_activity_reg_fields`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `act_activity_reg_fields` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `activity_id` bigint unsigned NOT NULL,
  `field_key` enum('gender','birth_year','occupation','area','target_group','source_channel') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_enabled` tinyint(1) NOT NULL DEFAULT '1',
  `is_required` tinyint(1) NOT NULL DEFAULT '0',
  `sort_order` smallint unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `act_activity_reg_fields_activity_id_field_key_unique` (`activity_id`,`field_key`) USING BTREE,
  CONSTRAINT `act_activity_reg_fields_activity_id_foreign` FOREIGN KEY (`activity_id`) REFERENCES `act_activities` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `act_activity_reg_fields`
--

LOCK TABLES `act_activity_reg_fields` WRITE;
/*!40000 ALTER TABLE `act_activity_reg_fields` DISABLE KEYS */;
INSERT INTO `act_activity_reg_fields` VALUES (1,1,'gender',1,1,1),(2,1,'birth_year',1,1,2),(3,1,'occupation',1,0,3),(4,1,'area',1,0,4),(5,1,'target_group',1,0,5),(6,1,'source_channel',1,0,6),(7,2,'gender',1,1,1),(8,2,'birth_year',1,1,2),(9,2,'occupation',1,0,3),(10,2,'area',1,0,4),(11,2,'target_group',1,0,5),(12,2,'source_channel',1,0,6),(13,3,'gender',1,1,1),(14,3,'birth_year',1,1,2),(15,3,'occupation',1,0,3),(16,3,'area',1,0,4),(17,3,'target_group',1,0,5),(18,3,'source_channel',1,0,6),(19,4,'gender',1,1,1),(20,4,'birth_year',1,1,2),(21,4,'occupation',1,0,3),(22,4,'area',1,0,4),(23,4,'target_group',1,0,5),(24,4,'source_channel',1,0,6),(25,5,'gender',1,1,1),(26,5,'birth_year',1,1,2),(27,5,'occupation',1,0,3),(28,5,'area',1,0,4),(29,5,'target_group',1,0,5),(30,5,'source_channel',1,0,6);
/*!40000 ALTER TABLE `act_activity_reg_fields` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `act_activity_rounds`
--

DROP TABLE IF EXISTS `act_activity_rounds`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `act_activity_rounds` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `activity_id` bigint unsigned NOT NULL,
  `round_date` date NOT NULL,
  `time_start` time DEFAULT NULL,
  `time_end` time DEFAULT NULL,
  `location` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `capacity` int unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `act_activity_rounds_activity_id_round_date_index` (`activity_id`,`round_date`) USING BTREE,
  CONSTRAINT `act_activity_rounds_activity_id_foreign` FOREIGN KEY (`activity_id`) REFERENCES `act_activities` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `act_activity_rounds`
--

LOCK TABLES `act_activity_rounds` WRITE;
/*!40000 ALTER TABLE `act_activity_rounds` DISABLE KEYS */;
INSERT INTO `act_activity_rounds` VALUES (1,1,'2026-08-10','09:00:00','12:00:00','ชุมชนพูนทรัพย์',40,'2026-08-10 21:48:39','2026-08-10 21:48:39'),(2,2,'2026-08-17','09:00:00','15:00:00','The Farm Concept',30,'2026-08-10 21:48:39','2026-08-10 21:48:39'),(3,3,'2026-08-24','09:00:00','12:00:00','ชุมชนตึกร้าง',25,'2026-08-10 21:48:39','2026-08-10 21:48:39'),(4,3,'2026-09-07','09:00:00','12:00:00','ชุมชนตึกร้าง',25,'2026-08-10 21:48:39','2026-08-10 21:48:39'),(5,4,'2026-07-20','09:00:00','16:00:00','ชุมชนพูนทรัพย์',50,'2026-08-10 21:48:39','2026-08-10 21:48:39'),(7,5,'2026-09-05','08:00:00','12:00:00','ชุมชนตึกร้าง',60,'2026-08-11 00:18:51','2026-08-11 00:19:47'),(9,9,'2026-08-15','13:00:00','17:30:00','The Farm Concept',15,'2026-08-11 02:35:31','2026-08-11 02:36:19'),(10,12,'2026-08-12','09:30:00','16:30:00','The Farm Concept',50,'2026-08-12 06:54:36','2026-08-12 06:54:36'),(11,13,'2026-08-12','08:30:00','09:30:00','The Farm Concept',20,'2026-08-12 14:16:34','2026-08-12 14:16:34'),(13,15,'2026-08-16','08:30:00','13:00:00','The Farm Concept',30,'2026-08-13 05:35:47','2026-08-15 05:55:32'),(14,16,'2026-08-23','13:00:00','15:00:00','The Farm Concept',30,'2026-08-13 05:35:47','2026-08-15 05:56:13'),(15,17,'2026-08-30','10:00:00','13:00:00','The Farm Concept',30,'2026-08-13 05:35:47','2026-08-15 05:56:54'),(16,18,'2026-08-09','09:00:00','14:00:00','The Farm Concept',0,'2026-08-13 05:35:47','2026-08-15 05:53:19'),(17,19,'2026-08-24','17:00:00','20:00:00','The Farm Concept',0,'2026-08-13 05:35:47','2026-08-13 05:35:47'),(18,20,'2026-09-06','06:30:00','09:00:00','The Farm Concept',0,'2026-08-13 05:35:47','2026-08-15 05:50:30'),(24,21,'2026-09-19','09:00:00','12:00:00','The Farm Concept',50,'2026-08-15 05:27:43','2026-08-15 05:28:01'),(25,22,'2026-08-23','09:00:00','16:00:00','The Farm Concept',1000,'2026-08-15 05:43:21','2026-08-15 05:51:39'),(26,23,'2026-08-20','09:00:00','16:00:00','The Farm Concept',0,'2026-08-15 05:45:11','2026-08-15 05:51:28'),(27,24,'2026-08-15','09:00:00','16:00:00','The Farm Concept',0,'2026-08-15 05:46:48','2026-08-15 05:50:59'),(28,14,'2026-08-19','13:00:00','16:00:00','The Farm Concept',30,'2026-08-16 03:17:47','2026-08-16 03:27:08'),(29,26,'2026-01-01','06:00:00','16:00:00','The Farm Concept',0,'2026-08-17 07:20:14','2026-08-17 07:20:14'),(30,27,'2026-08-17','09:00:00','16:00:00','The Farm Concept',1000,'2026-08-17 07:21:14','2026-08-17 07:21:14'),(31,25,'2026-08-17','09:00:00','16:00:00','The Farm Concept',100,'2026-08-17 07:24:05','2026-08-17 07:24:05');
/*!40000 ALTER TABLE `act_activity_rounds` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `act_activity_target_group`
--

DROP TABLE IF EXISTS `act_activity_target_group`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `act_activity_target_group` (
  `activity_id` bigint unsigned NOT NULL,
  `target_group_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`activity_id`,`target_group_id`) USING BTREE,
  KEY `act_activity_target_group_target_group_id_foreign` (`target_group_id`) USING BTREE,
  CONSTRAINT `act_activity_target_group_activity_id_foreign` FOREIGN KEY (`activity_id`) REFERENCES `act_activities` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `act_activity_target_group_target_group_id_foreign` FOREIGN KEY (`target_group_id`) REFERENCES `mst_target_groups` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `act_activity_target_group`
--

LOCK TABLES `act_activity_target_group` WRITE;
/*!40000 ALTER TABLE `act_activity_target_group` DISABLE KEYS */;
INSERT INTO `act_activity_target_group` VALUES (5,1),(9,1),(12,1),(13,1),(14,1),(15,1),(16,1),(17,1),(18,1),(20,1),(21,1),(22,1),(23,1),(24,1),(25,1),(26,1),(1,2),(2,2),(3,2),(4,2),(5,2),(9,2),(14,2),(22,2),(23,2),(25,2),(26,2),(27,2),(2,3),(4,3),(5,3),(14,3),(22,3),(23,3),(25,3),(26,3),(14,4),(22,4),(23,4),(25,4),(26,4);
/*!40000 ALTER TABLE `act_activity_target_group` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `act_checkin_logs`
--

DROP TABLE IF EXISTS `act_checkin_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `act_checkin_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `registration_id` bigint unsigned NOT NULL,
  `action` enum('check_in','undo') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `method` enum('scan','staff') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `performed_by` bigint unsigned DEFAULT NULL,
  `performed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `act_checkin_logs_performed_by_foreign` (`performed_by`) USING BTREE,
  KEY `act_checkin_logs_registration_id_performed_at_index` (`registration_id`,`performed_at`) USING BTREE,
  CONSTRAINT `act_checkin_logs_performed_by_foreign` FOREIGN KEY (`performed_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `act_checkin_logs_registration_id_foreign` FOREIGN KEY (`registration_id`) REFERENCES `act_registrations` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `act_checkin_logs`
--

LOCK TABLES `act_checkin_logs` WRITE;
/*!40000 ALTER TABLE `act_checkin_logs` DISABLE KEYS */;
INSERT INTO `act_checkin_logs` VALUES (1,167,'check_in','scan',NULL,'2026-08-13 12:09:05'),(2,168,'check_in','scan',NULL,'2026-08-13 12:09:06'),(3,174,'check_in','scan',NULL,'2026-08-15 10:47:36'),(4,175,'check_in','scan',NULL,'2026-08-15 10:47:47');
/*!40000 ALTER TABLE `act_checkin_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `act_payment_slips`
--

DROP TABLE IF EXISTS `act_payment_slips`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `act_payment_slips` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `registration_id` bigint unsigned NOT NULL,
  `file_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `transferred_at` timestamp NULL DEFAULT NULL,
  `status` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'รอตรวจสอบ',
  `reviewed_by` bigint unsigned DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `reject_reason` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `act_payment_slips_registration_id_foreign` (`registration_id`) USING BTREE,
  KEY `act_payment_slips_reviewed_by_foreign` (`reviewed_by`) USING BTREE,
  KEY `act_payment_slips_status_index` (`status`) USING BTREE,
  CONSTRAINT `act_payment_slips_registration_id_foreign` FOREIGN KEY (`registration_id`) REFERENCES `act_registrations` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `act_payment_slips_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `act_payment_slips`
--

LOCK TABLES `act_payment_slips` WRITE;
/*!40000 ALTER TABLE `act_payment_slips` DISABLE KEYS */;
INSERT INTO `act_payment_slips` VALUES (1,188,'payment-slips/PJAA3sRCfWCY2Xvu2fgXtMptLFXqcXWDAw9ptGmU.png',199.00,'2026-08-15 11:18:05','รอตรวจสอบ',NULL,NULL,NULL,'2026-08-15 11:18:05','2026-08-15 11:18:05'),(2,189,'payment-slips/Dif6VzgnH92bWSwwbml87hSB338cggWuaNdqOqt3.png',199.00,'2026-08-15 11:24:26','รอตรวจสอบ',NULL,NULL,NULL,'2026-08-15 11:24:26','2026-08-15 11:24:26');
/*!40000 ALTER TABLE `act_payment_slips` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `act_qr_codes`
--

DROP TABLE IF EXISTS `act_qr_codes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `act_qr_codes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `activity_id` bigint unsigned DEFAULT NULL,
  `purpose` enum('public','checkin','post_survey','health') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `target_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '0',
  `expires_at` timestamp NULL DEFAULT NULL,
  `scan_count` int unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `act_qr_codes_token_unique` (`token`) USING BTREE,
  UNIQUE KEY `act_qr_codes_activity_id_purpose_unique` (`activity_id`,`purpose`) USING BTREE,
  CONSTRAINT `act_qr_codes_activity_id_foreign` FOREIGN KEY (`activity_id`) REFERENCES `act_activities` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=42 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `act_qr_codes`
--

LOCK TABLES `act_qr_codes` WRITE;
/*!40000 ALTER TABLE `act_qr_codes` DISABLE KEYS */;
INSERT INTO `act_qr_codes` VALUES (1,1,'public','ixdwgpryvxregsvrm41yzzmk','/r/ixdwgpryvxregsvrm41yzzmk',1,NULL,0,'2026-08-10 21:48:39','2026-08-10 21:48:39'),(2,1,'checkin','r7itwmt6noirotuyqswpmm2b','/c/r7itwmt6noirotuyqswpmm2b',1,NULL,0,'2026-08-10 21:48:39','2026-08-10 21:48:39'),(3,1,'post_survey','sxoore2m3pe7olwi5ps2kcce','/s/sxoore2m3pe7olwi5ps2kcce',1,NULL,0,'2026-08-10 21:48:39','2026-08-10 21:48:39'),(4,2,'public','qfevfzbxidibaqfxnqqijs9g','/r/qfevfzbxidibaqfxnqqijs9g',1,NULL,0,'2026-08-10 21:48:39','2026-08-10 21:48:39'),(5,2,'checkin','sqlhhjsd9ma0lyg5r3lieqqh','/c/sqlhhjsd9ma0lyg5r3lieqqh',1,NULL,0,'2026-08-10 21:48:39','2026-08-10 21:48:39'),(6,2,'post_survey','khdvcvvhdjsy3osmrcfwhlq6','/s/khdvcvvhdjsy3osmrcfwhlq6',1,NULL,0,'2026-08-10 21:48:39','2026-08-10 21:48:39'),(7,3,'public','ilhzpm7lia5tcncseewslbih','/r/ilhzpm7lia5tcncseewslbih',1,NULL,0,'2026-08-10 21:48:39','2026-08-10 21:48:39'),(8,3,'checkin','kjidwehiu4yarrtifpapwbr8','/c/kjidwehiu4yarrtifpapwbr8',1,NULL,0,'2026-08-10 21:48:39','2026-08-10 21:48:39'),(9,3,'post_survey','buhsevzy4ellb6l3vu0vvwoh','/s/buhsevzy4ellb6l3vu0vvwoh',1,NULL,0,'2026-08-10 21:48:39','2026-08-10 21:48:39'),(10,4,'public','yfdkd2yx4uoc1j07ucgis9mo','/r/yfdkd2yx4uoc1j07ucgis9mo',1,NULL,0,'2026-08-10 21:48:40','2026-08-10 21:48:40'),(11,4,'checkin','ckefrmk22sowszv5j867cf3x','/c/ckefrmk22sowszv5j867cf3x',1,NULL,0,'2026-08-10 21:48:40','2026-08-10 21:48:40'),(12,4,'post_survey','ecmifhlfajoaoyuzi2ecflch','/s/ecmifhlfajoaoyuzi2ecflch',1,NULL,0,'2026-08-10 21:48:40','2026-08-10 21:48:40'),(13,5,'public','bah2jgnsj5xucx8adaponmyd','/r/bah2jgnsj5xucx8adaponmyd',0,NULL,0,'2026-08-10 21:48:40','2026-08-11 00:19:47'),(14,NULL,'health','qtikq4kchoayph1oe1w5rbxj','/h/qtikq4kchoayph1oe1w5rbxj',1,NULL,97,'2026-08-10 21:48:40','2026-08-17 07:07:52'),(15,5,'checkin','abxpqbrhe8bggdadyoostle4','/c/abxpqbrhe8bggdadyoostle4',0,NULL,0,'2026-08-11 00:18:51','2026-08-11 00:19:47'),(16,5,'post_survey','owbskdruzw0drtnv9u2ggfef','/s/owbskdruzw0drtnv9u2ggfef',0,NULL,0,'2026-08-11 00:18:51','2026-08-11 00:19:47'),(20,9,'public','9my3kp2bj4kafjlevllgcpi1','/r/9my3kp2bj4kafjlevllgcpi1',0,NULL,0,'2026-08-11 02:35:31','2026-08-11 02:35:31'),(21,12,'public','wvuqxbhknxotbgvuisxsxxv6','/r/wvuqxbhknxotbgvuisxsxxv6',1,NULL,0,'2026-08-12 06:54:36','2026-08-12 06:54:36'),(22,13,'public','thmq28o8fhedm6svrrqd6bxp','/r/thmq28o8fhedm6svrrqd6bxp',1,NULL,0,'2026-08-12 14:16:34','2026-08-12 14:16:34'),(23,16,'public','8dxcsigoteb5njp46lvopyhu','/r/8dxcsigoteb5njp46lvopyhu',1,NULL,1,'2026-08-13 06:07:01','2026-08-15 06:00:45'),(24,14,'public','awqfmb0nehtbwr2whgkq5aiv','/r/awqfmb0nehtbwr2whgkq5aiv',1,NULL,0,'2026-08-13 06:16:24','2026-08-13 06:16:24'),(25,21,'public','1qyk89i5ojzs80zhbs2ghnos','/r/1qyk89i5ojzs80zhbs2ghnos',1,NULL,3,'2026-08-13 11:19:23','2026-08-13 11:47:24'),(26,21,'checkin','ovq86s9xatqdvrxedymx88g7','/c/ovq86s9xatqdvrxedymx88g7',1,NULL,2,'2026-08-13 11:19:23','2026-08-15 05:28:01'),(27,14,'checkin','b7pjfmsvulva4j5smu6gssf3','/c/b7pjfmsvulva4j5smu6gssf3',1,NULL,3,'2026-08-14 14:06:21','2026-08-15 11:35:20'),(28,22,'public','myzswyubmke6phdqys1dz1o0','/r/myzswyubmke6phdqys1dz1o0',1,NULL,0,'2026-08-15 05:43:21','2026-08-15 05:43:21'),(29,23,'public','2qrrezvj0ywjttta0hxjsmvj','/r/2qrrezvj0ywjttta0hxjsmvj',1,NULL,0,'2026-08-15 05:45:11','2026-08-15 05:45:11'),(30,24,'public','7uznqjeadv0sp2rv2kaxaphx','/r/7uznqjeadv0sp2rv2kaxaphx',1,NULL,0,'2026-08-15 05:46:48','2026-08-15 05:46:48'),(31,20,'public','fxu67p0w0ikfa5virzevl7ad','/r/fxu67p0w0ikfa5virzevl7ad',1,NULL,0,'2026-08-15 05:50:30','2026-08-15 05:50:30'),(32,18,'public','qvsah7gteoeekb53bpxcm204','/r/qvsah7gteoeekb53bpxcm204',1,NULL,0,'2026-08-15 05:53:20','2026-08-15 05:53:20'),(33,15,'public','jdmolxvulex4v7fycnwpqnqd','/r/jdmolxvulex4v7fycnwpqnqd',1,NULL,4,'2026-08-15 05:55:32','2026-08-15 10:43:15'),(34,17,'public','wttujle1h9gw3zlukufj0sbd','/r/wttujle1h9gw3zlukufj0sbd',1,NULL,0,'2026-08-15 05:56:54','2026-08-15 05:56:54'),(35,14,'post_survey','2r58r9tyqcppkduujlpouzmh','/s/2r58r9tyqcppkduujlpouzmh',1,NULL,1,'2026-08-15 10:48:35','2026-08-15 10:48:59'),(36,25,'public','glkypqzfeqeqwnek6wu9qpa2','/r/glkypqzfeqeqwnek6wu9qpa2',1,NULL,0,'2026-08-17 07:19:01','2026-08-17 07:24:05'),(37,26,'public','jxxwocdhptsgrhvg3pybw2hh','/r/jxxwocdhptsgrhvg3pybw2hh',0,NULL,0,'2026-08-17 07:20:14','2026-08-17 07:20:14'),(38,27,'public','8n9zthwr5uglsaexrrw7adnx','/r/8n9zthwr5uglsaexrrw7adnx',1,NULL,0,'2026-08-17 07:21:14','2026-08-17 07:21:14'),(39,27,'post_survey','c92qeixbd1zksbw0dmqszsh1','/s/c92qeixbd1zksbw0dmqszsh1',1,NULL,0,'2026-08-17 07:21:14','2026-08-17 07:21:14'),(40,25,'post_survey','z9f4pj3wep0lrryolh43c7ps','/s/z9f4pj3wep0lrryolh43c7ps',1,NULL,0,'2026-08-17 07:24:05','2026-08-17 07:24:05');
/*!40000 ALTER TABLE `act_qr_codes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `act_registration_interests`
--

DROP TABLE IF EXISTS `act_registration_interests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `act_registration_interests` (
  `registration_id` bigint unsigned NOT NULL,
  `option_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`registration_id`,`option_id`) USING BTREE,
  KEY `act_registration_interests_option_id_foreign` (`option_id`) USING BTREE,
  CONSTRAINT `act_registration_interests_option_id_foreign` FOREIGN KEY (`option_id`) REFERENCES `mst_options` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `act_registration_interests_registration_id_foreign` FOREIGN KEY (`registration_id`) REFERENCES `act_registrations` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `act_registration_interests`
--

LOCK TABLES `act_registration_interests` WRITE;
/*!40000 ALTER TABLE `act_registration_interests` DISABLE KEYS */;
/*!40000 ALTER TABLE `act_registration_interests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `act_registrations`
--

DROP TABLE IF EXISTS `act_registrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `act_registrations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(24) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `activity_id` bigint unsigned NOT NULL,
  `activity_round_id` bigint unsigned DEFAULT NULL,
  `participant_id` bigint unsigned DEFAULT NULL,
  `name` varchar(160) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(160) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `gender` enum('male','female','other','undisclosed') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `birth_year` smallint unsigned DEFAULT NULL,
  `age_range_id` bigint unsigned DEFAULT NULL,
  `occupation_id` bigint unsigned DEFAULT NULL,
  `occupation_raw` varchar(160) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `area_id` bigint unsigned DEFAULT NULL,
  `target_group_id` bigint unsigned DEFAULT NULL,
  `source_channel_id` bigint unsigned DEFAULT NULL,
  `dietary_note` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_status` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ยังไม่ชำระ',
  `checkin_status` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ยังไม่เข้าร่วม',
  `registered_at` timestamp NOT NULL,
  `checked_in_at` timestamp NULL DEFAULT NULL,
  `is_manual_entry` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `act_registrations_code_unique` (`code`) USING BTREE,
  KEY `act_registrations_activity_round_id_foreign` (`activity_round_id`) USING BTREE,
  KEY `act_registrations_participant_id_foreign` (`participant_id`) USING BTREE,
  KEY `act_registrations_occupation_id_foreign` (`occupation_id`) USING BTREE,
  KEY `act_registrations_area_id_foreign` (`area_id`) USING BTREE,
  KEY `act_registrations_target_group_id_foreign` (`target_group_id`) USING BTREE,
  KEY `act_registrations_source_channel_id_foreign` (`source_channel_id`) USING BTREE,
  KEY `act_registrations_activity_id_checkin_status_index` (`activity_id`,`checkin_status`) USING BTREE,
  KEY `act_registrations_activity_id_payment_status_index` (`activity_id`,`payment_status`) USING BTREE,
  KEY `act_registrations_phone_index` (`phone`) USING BTREE,
  KEY `act_registrations_registered_at_index` (`registered_at`) USING BTREE,
  KEY `act_registrations_age_range_id_foreign` (`age_range_id`),
  CONSTRAINT `act_registrations_activity_id_foreign` FOREIGN KEY (`activity_id`) REFERENCES `act_activities` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `act_registrations_activity_round_id_foreign` FOREIGN KEY (`activity_round_id`) REFERENCES `act_activity_rounds` (`id`) ON DELETE SET NULL ON UPDATE RESTRICT,
  CONSTRAINT `act_registrations_age_range_id_foreign` FOREIGN KEY (`age_range_id`) REFERENCES `mst_options` (`id`),
  CONSTRAINT `act_registrations_area_id_foreign` FOREIGN KEY (`area_id`) REFERENCES `mst_areas` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `act_registrations_occupation_id_foreign` FOREIGN KEY (`occupation_id`) REFERENCES `mst_options` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `act_registrations_participant_id_foreign` FOREIGN KEY (`participant_id`) REFERENCES `ptp_participants` (`id`) ON DELETE SET NULL ON UPDATE RESTRICT,
  CONSTRAINT `act_registrations_source_channel_id_foreign` FOREIGN KEY (`source_channel_id`) REFERENCES `mst_options` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `act_registrations_target_group_id_foreign` FOREIGN KEY (`target_group_id`) REFERENCES `mst_target_groups` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=191 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `act_registrations`
--

LOCK TABLES `act_registrations` WRITE;
/*!40000 ALTER TABLE `act_registrations` DISABLE KEYS */;
INSERT INTO `act_registrations` VALUES (1,'ACT-2026-014-R001',1,1,2,'สมชาย ใจงาม','081-100-1000',NULL,'female',1955,NULL,5,NULL,1,3,9,NULL,'ชำระแล้ว','ยังไม่เข้าร่วม','2026-08-08 17:00:00',NULL,0,'2026-08-10 21:48:39','2026-08-10 21:48:39'),(2,'ACT-2026-014-R002',1,1,3,'วิภาดา สายใจ','082-101-1007',NULL,'male',1962,NULL,4,NULL,2,3,14,NULL,'ชำระแล้ว','เข้าร่วมแล้ว','2026-08-07 17:00:00','2026-08-10 02:03:00',0,'2026-08-10 21:48:39','2026-08-10 21:48:39'),(3,'ACT-2026-014-R003',1,1,4,'ธีรพงษ์ แสงทอง','083-102-1014',NULL,'male',1969,NULL,2,NULL,2,2,13,NULL,'ชำระแล้ว','เข้าร่วมแล้ว','2026-08-06 17:00:00','2026-08-10 02:06:00',0,'2026-08-10 21:48:39','2026-08-10 21:48:39'),(4,'ACT-2026-014-R004',1,1,5,'กัลยา รุ่งเจริญ','084-103-1021',NULL,'female',1976,NULL,3,NULL,1,2,10,NULL,'ชำระแล้ว','เข้าร่วมแล้ว','2026-08-05 17:00:00','2026-08-10 02:09:00',0,'2026-08-10 21:48:39','2026-08-10 21:48:39'),(5,'ACT-2026-014-R005',1,1,6,'ประภาส ทองแท้','085-104-1028',NULL,'other',1983,NULL,5,NULL,3,2,9,NULL,'ชำระแล้ว','เข้าร่วมแล้ว','2026-08-04 17:00:00','2026-08-10 02:12:00',0,'2026-08-10 21:48:39','2026-08-10 21:48:39'),(6,'ACT-2026-014-R006',1,1,7,'มณีรัตน์ ใจบุญ','086-105-1035',NULL,'female',1990,NULL,2,NULL,2,2,14,NULL,'ชำระแล้ว','ยังไม่เข้าร่วม','2026-08-03 17:00:00',NULL,0,'2026-08-10 21:48:39','2026-08-10 21:48:39'),(7,'ACT-2026-014-R007',1,1,8,'อดิศักดิ์ พูลสวัสดิ์','087-106-1042',NULL,'male',1997,NULL,3,NULL,1,4,11,NULL,'ชำระแล้ว','เข้าร่วมแล้ว','2026-08-02 17:00:00','2026-08-10 02:18:00',0,'2026-08-10 21:48:39','2026-08-10 21:48:39'),(8,'ACT-2026-014-R008',1,1,9,'พิมพ์ใจ เพียรทำ','088-107-1049',NULL,'male',2004,NULL,4,NULL,1,2,14,NULL,'ชำระแล้ว','เข้าร่วมแล้ว','2026-08-01 17:00:00','2026-08-10 02:21:00',0,'2026-08-10 21:48:39','2026-08-10 21:48:39'),(9,'ACT-2026-014-R009',1,1,10,'ณัฐวุฒิ ใจงาม','089-108-1056',NULL,'female',1961,NULL,1,NULL,3,3,11,NULL,'ชำระแล้ว','เข้าร่วมแล้ว','2026-07-31 17:00:00','2026-08-10 02:24:00',0,'2026-08-10 21:48:39','2026-08-10 21:48:39'),(10,'ACT-2026-014-R010',1,1,11,'สุพรรณี สายใจ','081-109-1063',NULL,'other',1968,NULL,2,NULL,1,2,10,NULL,'ชำระแล้ว','เข้าร่วมแล้ว','2026-07-30 17:00:00','2026-08-10 02:27:00',0,'2026-08-10 21:48:39','2026-08-10 21:48:39'),(11,'ACT-2026-014-R011',1,1,12,'สมชาย แสงทอง','082-110-1070',NULL,'female',1975,NULL,3,NULL,1,2,13,NULL,'ชำระแล้ว','ยังไม่เข้าร่วม','2026-07-29 17:00:00',NULL,0,'2026-08-10 21:48:39','2026-08-10 21:48:39'),(12,'ACT-2026-014-R012',1,1,13,'วิภาดา รุ่งเจริญ','083-111-1077',NULL,'male',1982,NULL,1,NULL,17,2,10,NULL,'ชำระแล้ว','เข้าร่วมแล้ว','2026-07-28 17:00:00','2026-08-10 02:33:00',0,'2026-08-10 21:48:39','2026-08-10 21:48:39'),(13,'ACT-2026-014-R013',1,1,14,'ธีรพงษ์ ทองแท้','084-112-1084',NULL,'male',1989,NULL,3,NULL,1,2,11,NULL,'ชำระแล้ว','เข้าร่วมแล้ว','2026-08-08 17:00:00','2026-08-10 02:36:00',0,'2026-08-10 21:48:39','2026-08-10 21:48:39'),(14,'ACT-2026-014-R014',1,1,15,'กัลยา ใจบุญ','085-113-1091',NULL,'female',1996,NULL,5,NULL,2,2,10,NULL,'ชำระแล้ว','เข้าร่วมแล้ว','2026-08-07 17:00:00','2026-08-10 02:39:00',0,'2026-08-10 21:48:39','2026-08-10 21:48:39'),(15,'ACT-2026-014-R015',1,1,16,'ประภาส พูลสวัสดิ์','086-114-1098',NULL,'other',2003,NULL,1,NULL,3,2,13,NULL,'ชำระแล้ว','เข้าร่วมแล้ว','2026-08-06 17:00:00','2026-08-10 02:42:00',0,'2026-08-10 21:48:39','2026-08-10 21:48:39'),(16,'ACT-2026-014-R016',1,1,17,'มณีรัตน์ เพียรทำ','087-115-1105',NULL,'female',1960,NULL,1,NULL,2,3,12,NULL,'ชำระแล้ว','ยังไม่เข้าร่วม','2026-08-05 17:00:00',NULL,0,'2026-08-10 21:48:39','2026-08-10 21:48:39'),(17,'ACT-2026-014-R017',1,1,18,'อดิศักดิ์ ใจงาม','088-116-1112',NULL,'male',1967,NULL,4,NULL,2,2,13,NULL,'ชำระแล้ว','เข้าร่วมแล้ว','2026-08-04 17:00:00','2026-08-10 02:48:00',0,'2026-08-10 21:48:39','2026-08-10 21:48:39'),(18,'ACT-2026-014-R018',1,1,19,'พิมพ์ใจ สายใจ','089-117-1119',NULL,'male',1974,NULL,4,NULL,3,2,14,NULL,'ชำระแล้ว','เข้าร่วมแล้ว','2026-08-03 17:00:00','2026-08-10 02:51:00',0,'2026-08-10 21:48:39','2026-08-10 21:48:39'),(19,'ACT-2026-014-R019',1,1,20,'ณัฐวุฒิ แสงทอง','081-118-1126',NULL,'female',1981,NULL,5,NULL,3,2,9,NULL,'ชำระแล้ว','เข้าร่วมแล้ว','2026-08-02 17:00:00','2026-08-10 02:54:00',0,'2026-08-10 21:48:39','2026-08-10 21:48:39'),(20,'ACT-2026-014-R020',1,1,21,'สุพรรณี รุ่งเจริญ','082-119-1133',NULL,'other',1988,NULL,4,NULL,1,2,10,NULL,'ชำระแล้ว','เข้าร่วมแล้ว','2026-08-01 17:00:00','2026-08-10 02:57:00',0,'2026-08-10 21:48:39','2026-08-10 21:48:39'),(21,'ACT-2026-014-R021',1,1,22,'สมชาย ทองแท้','083-120-1140',NULL,'female',1995,NULL,2,NULL,1,2,9,NULL,'ชำระแล้ว','ยังไม่เข้าร่วม','2026-07-31 17:00:00',NULL,0,'2026-08-10 21:48:39','2026-08-10 21:48:39'),(22,'ACT-2026-014-R022',1,1,23,'วิภาดา ใจบุญ','084-121-1147',NULL,'male',2002,NULL,7,NULL,2,4,12,NULL,'ชำระแล้ว','เข้าร่วมแล้ว','2026-07-30 17:00:00','2026-08-10 02:03:00',0,'2026-08-10 21:48:39','2026-08-10 21:48:39'),(23,'ACT-2026-014-R023',1,1,24,'ธีรพงษ์ พูลสวัสดิ์','085-122-1154',NULL,'male',1959,NULL,1,NULL,2,3,9,NULL,'ชำระแล้ว','เข้าร่วมแล้ว','2026-07-29 17:00:00','2026-08-10 02:06:00',0,'2026-08-10 21:48:39','2026-08-10 21:48:39'),(24,'ACT-2026-014-R024',1,1,25,'กัลยา เพียรทำ','086-123-1161',NULL,'female',1966,NULL,7,NULL,1,3,10,NULL,'ชำระแล้ว','เข้าร่วมแล้ว','2026-07-28 17:00:00','2026-08-10 02:09:00',0,'2026-08-10 21:48:39','2026-08-10 21:48:39'),(25,'ACT-2026-014-R025',1,1,26,'ประภาส ใจงาม','087-124-1168',NULL,'other',1973,NULL,4,NULL,3,2,13,NULL,'ชำระแล้ว','เข้าร่วมแล้ว','2026-08-08 17:00:00','2026-08-10 02:12:00',0,'2026-08-10 21:48:39','2026-08-10 21:48:39'),(26,'ACT-2026-014-R026',1,1,27,'มณีรัตน์ สายใจ','088-125-1175',NULL,'female',1980,NULL,4,NULL,17,2,14,NULL,'ชำระแล้ว','ยังไม่เข้าร่วม','2026-08-07 17:00:00',NULL,0,'2026-08-10 21:48:39','2026-08-10 21:48:39'),(27,'ACT-2026-014-R027',1,1,28,'อดิศักดิ์ แสงทอง','089-126-1182',NULL,'male',1987,NULL,2,NULL,2,2,13,NULL,'ชำระแล้ว','เข้าร่วมแล้ว','2026-08-06 17:00:00','2026-08-10 02:18:00',0,'2026-08-10 21:48:39','2026-08-10 21:48:39'),(28,'ACT-2026-014-R028',1,1,29,'พิมพ์ใจ รุ่งเจริญ','081-127-1189',NULL,'male',1994,NULL,4,NULL,3,2,14,NULL,'ชำระแล้ว','เข้าร่วมแล้ว','2026-08-05 17:00:00','2026-08-10 02:21:00',0,'2026-08-10 21:48:39','2026-08-10 21:48:39'),(29,'ACT-2026-014-R029',1,1,30,'ณัฐวุฒิ ทองแท้','082-128-1196',NULL,'female',2001,NULL,4,NULL,17,4,11,NULL,'ชำระแล้ว','เข้าร่วมแล้ว','2026-08-04 17:00:00','2026-08-10 02:24:00',0,'2026-08-10 21:48:39','2026-08-10 21:48:39'),(30,'ACT-2026-014-R030',1,1,31,'สุพรรณี ใจบุญ','083-129-1203',NULL,'other',1958,NULL,7,NULL,1,3,10,NULL,'ชำระแล้ว','เข้าร่วมแล้ว','2026-08-03 17:00:00','2026-08-10 02:27:00',0,'2026-08-10 21:48:39','2026-08-10 21:48:39'),(31,'ACT-2026-014-R031',1,1,32,'สมชาย พูลสวัสดิ์','084-130-1210',NULL,'female',1965,NULL,2,NULL,1,3,10,NULL,'ชำระแล้ว','ยังไม่เข้าร่วม','2026-08-02 17:00:00',NULL,0,'2026-08-10 21:48:39','2026-08-10 21:48:39'),(32,'ACT-2026-014-R032',1,1,33,'วิภาดา เพียรทำ','085-131-1217',NULL,'male',1972,NULL,2,NULL,2,2,9,NULL,'ชำระแล้ว','เข้าร่วมแล้ว','2026-08-01 17:00:00','2026-08-10 02:33:00',0,'2026-08-10 21:48:39','2026-08-10 21:48:39'),(33,'ACT-2026-015-R001',2,2,2,'สมชาย ใจงาม','081-100-1000',NULL,'female',1955,NULL,1,NULL,1,3,12,NULL,'รอตรวจสอบ','ยังไม่เข้าร่วม','2026-08-15 17:00:00',NULL,0,'2026-08-10 21:48:39','2026-08-10 21:48:39'),(34,'ACT-2026-015-R002',2,2,3,'วิภาดา สายใจ','082-101-1007',NULL,'male',1962,NULL,7,NULL,2,3,13,NULL,'ชำระแล้ว','ยังไม่เข้าร่วม','2026-08-14 17:00:00',NULL,0,'2026-08-10 21:48:39','2026-08-10 21:48:39'),(35,'ACT-2026-015-R003',2,2,4,'ธีรพงษ์ แสงทอง','083-102-1014',NULL,'male',1969,NULL,1,NULL,1,2,10,NULL,'ชำระแล้ว','ยังไม่เข้าร่วม','2026-08-13 17:00:00',NULL,0,'2026-08-10 21:48:39','2026-08-10 21:48:39'),(36,'ACT-2026-015-R004',2,2,5,'กัลยา รุ่งเจริญ','084-103-1021',NULL,'female',1976,NULL,1,NULL,1,2,9,NULL,'ชำระแล้ว','ยังไม่เข้าร่วม','2026-08-12 17:00:00',NULL,0,'2026-08-10 21:48:39','2026-08-10 21:48:39'),(37,'ACT-2026-015-R005',2,2,6,'ประภาส ทองแท้','085-104-1028',NULL,'other',1983,NULL,5,NULL,1,2,12,NULL,'รอตรวจสอบ','ยังไม่เข้าร่วม','2026-08-11 17:00:00',NULL,0,'2026-08-10 21:48:39','2026-08-10 21:48:39'),(38,'ACT-2026-015-R006',2,2,7,'มณีรัตน์ ใจบุญ','086-105-1035',NULL,'female',1990,NULL,5,NULL,1,2,13,NULL,'ชำระแล้ว','ยังไม่เข้าร่วม','2026-08-10 17:00:00',NULL,0,'2026-08-10 21:48:39','2026-08-10 21:48:39'),(39,'ACT-2026-015-R007',2,2,8,'อดิศักดิ์ พูลสวัสดิ์','087-106-1042',NULL,'male',1997,NULL,6,NULL,2,2,10,NULL,'ชำระแล้ว','ยังไม่เข้าร่วม','2026-08-09 17:00:00',NULL,0,'2026-08-10 21:48:39','2026-08-10 21:48:39'),(40,'ACT-2026-015-R008',2,2,9,'พิมพ์ใจ เพียรทำ','088-107-1049',NULL,'male',2004,NULL,2,NULL,1,2,9,NULL,'ชำระแล้ว','ยังไม่เข้าร่วม','2026-08-08 17:00:00',NULL,0,'2026-08-10 21:48:39','2026-08-10 21:48:39'),(41,'ACT-2026-015-R009',2,2,10,'ณัฐวุฒิ ใจงาม','089-108-1056',NULL,'female',1961,NULL,2,NULL,2,4,13,NULL,'รอตรวจสอบ','ยังไม่เข้าร่วม','2026-08-07 17:00:00',NULL,0,'2026-08-10 21:48:39','2026-08-10 21:48:39'),(42,'ACT-2026-015-R010',2,2,11,'สุพรรณี สายใจ','081-109-1063',NULL,'other',1968,NULL,3,NULL,1,2,14,NULL,'ชำระแล้ว','ยังไม่เข้าร่วม','2026-08-06 17:00:00',NULL,0,'2026-08-10 21:48:39','2026-08-10 21:48:39'),(43,'ACT-2026-015-R011',2,2,12,'สมชาย แสงทอง','082-110-1070',NULL,'female',1975,NULL,6,NULL,2,2,9,NULL,'ชำระแล้ว','ยังไม่เข้าร่วม','2026-08-05 17:00:00',NULL,0,'2026-08-10 21:48:39','2026-08-10 21:48:39'),(44,'ACT-2026-015-R012',2,2,13,'วิภาดา รุ่งเจริญ','083-111-1077',NULL,'male',1982,NULL,7,NULL,1,2,12,NULL,'ชำระแล้ว','ยังไม่เข้าร่วม','2026-08-04 17:00:00',NULL,0,'2026-08-10 21:48:39','2026-08-10 21:48:39'),(45,'ACT-2026-015-R013',2,2,14,'ธีรพงษ์ ทองแท้','084-112-1084',NULL,'male',1989,NULL,5,NULL,17,2,11,NULL,'รอตรวจสอบ','ยังไม่เข้าร่วม','2026-08-15 17:00:00',NULL,0,'2026-08-10 21:48:39','2026-08-10 21:48:39'),(46,'ACT-2026-015-R014',2,2,15,'กัลยา ใจบุญ','085-113-1091',NULL,'female',1996,NULL,6,NULL,3,2,10,NULL,'ชำระแล้ว','ยังไม่เข้าร่วม','2026-08-14 17:00:00',NULL,0,'2026-08-10 21:48:39','2026-08-10 21:48:39'),(47,'ACT-2026-015-R015',2,2,16,'ประภาส พูลสวัสดิ์','086-114-1098',NULL,'other',2003,NULL,2,NULL,2,4,13,NULL,'ชำระแล้ว','ยังไม่เข้าร่วม','2026-08-13 17:00:00',NULL,0,'2026-08-10 21:48:39','2026-08-10 21:48:39'),(48,'ACT-2026-015-R016',2,2,17,'มณีรัตน์ เพียรทำ','087-115-1105',NULL,'female',1960,NULL,1,NULL,1,3,12,NULL,'ชำระแล้ว','ยังไม่เข้าร่วม','2026-08-12 17:00:00',NULL,0,'2026-08-10 21:48:39','2026-08-10 21:48:39'),(49,'ACT-2026-015-R017',2,2,18,'อดิศักดิ์ ใจงาม','088-116-1112',NULL,'male',1967,NULL,8,NULL,1,2,13,NULL,'รอตรวจสอบ','ยังไม่เข้าร่วม','2026-08-11 17:00:00',NULL,0,'2026-08-10 21:48:39','2026-08-10 21:48:39'),(50,'ACT-2026-015-R018',2,2,19,'พิมพ์ใจ สายใจ','089-117-1119',NULL,'male',1974,NULL,7,NULL,1,2,10,NULL,'ชำระแล้ว','ยังไม่เข้าร่วม','2026-08-10 17:00:00',NULL,0,'2026-08-10 21:48:39','2026-08-10 21:48:39'),(51,'ACT-2026-015-R019',2,2,20,'ณัฐวุฒิ แสงทอง','081-118-1126',NULL,'female',1981,NULL,3,NULL,2,2,14,NULL,'ชำระแล้ว','ยังไม่เข้าร่วม','2026-08-09 17:00:00',NULL,0,'2026-08-10 21:48:39','2026-08-10 21:48:39'),(52,'ACT-2026-015-R020',2,2,21,'สุพรรณี รุ่งเจริญ','082-119-1133',NULL,'other',1988,NULL,4,NULL,17,4,13,NULL,'ชำระแล้ว','ยังไม่เข้าร่วม','2026-08-08 17:00:00',NULL,0,'2026-08-10 21:48:39','2026-08-10 21:48:39'),(53,'ACT-2026-015-R021',2,2,22,'สมชาย ทองแท้','083-120-1140',NULL,'female',1995,NULL,8,NULL,2,4,12,NULL,'รอตรวจสอบ','ยังไม่เข้าร่วม','2026-08-07 17:00:00',NULL,0,'2026-08-10 21:48:39','2026-08-10 21:48:39'),(54,'ACT-2026-015-R022',2,2,23,'วิภาดา ใจบุญ','084-121-1147',NULL,'male',2002,NULL,3,NULL,2,2,11,NULL,'ชำระแล้ว','ยังไม่เข้าร่วม','2026-08-06 17:00:00',NULL,0,'2026-08-10 21:48:39','2026-08-10 21:48:39'),(55,'ACT-2026-015-R023',2,2,24,'ธีรพงษ์ พูลสวัสดิ์','085-122-1154',NULL,'male',1959,NULL,2,NULL,17,3,12,NULL,'ชำระแล้ว','ยังไม่เข้าร่วม','2026-08-05 17:00:00',NULL,0,'2026-08-10 21:48:39','2026-08-10 21:48:39'),(56,'ACT-2026-015-R024',2,2,25,'กัลยา เพียรทำ','086-123-1161',NULL,'female',1966,NULL,4,NULL,2,3,9,NULL,'ชำระแล้ว','ยังไม่เข้าร่วม','2026-08-04 17:00:00',NULL,0,'2026-08-10 21:48:39','2026-08-10 21:48:39'),(57,'ACT-2026-015-R025',2,2,26,'ประภาส ใจงาม','087-124-1168',NULL,'other',1973,NULL,4,NULL,1,2,10,NULL,'รอตรวจสอบ','ยังไม่เข้าร่วม','2026-08-15 17:00:00',NULL,0,'2026-08-10 21:48:39','2026-08-10 21:48:39'),(58,'ACT-2026-015-R026',2,2,27,'มณีรัตน์ สายใจ','088-125-1175',NULL,'female',1980,NULL,2,NULL,1,2,13,NULL,'ชำระแล้ว','ยังไม่เข้าร่วม','2026-08-14 17:00:00',NULL,0,'2026-08-10 21:48:39','2026-08-10 21:48:39'),(59,'ACT-2026-015-R027',2,2,28,'อดิศักดิ์ แสงทอง','089-126-1182',NULL,'male',1987,NULL,3,NULL,1,2,10,NULL,'ชำระแล้ว','ยังไม่เข้าร่วม','2026-08-13 17:00:00',NULL,0,'2026-08-10 21:48:39','2026-08-10 21:48:39'),(60,'ACT-2026-015-R028',2,2,29,'พิมพ์ใจ รุ่งเจริญ','081-127-1189',NULL,'male',1994,NULL,2,NULL,3,2,11,NULL,'ชำระแล้ว','ยังไม่เข้าร่วม','2026-08-12 17:00:00',NULL,0,'2026-08-10 21:48:39','2026-08-10 21:48:39'),(61,'ACT-2026-015-R029',2,2,30,'ณัฐวุฒิ ทองแท้','082-128-1196',NULL,'female',2001,NULL,5,NULL,2,2,10,NULL,'รอตรวจสอบ','ยังไม่เข้าร่วม','2026-08-11 17:00:00',NULL,0,'2026-08-10 21:48:39','2026-08-10 21:48:39'),(62,'ACT-2026-015-R030',2,2,31,'สุพรรณี ใจบุญ','083-129-1203',NULL,'other',1958,NULL,1,NULL,17,3,13,NULL,'ชำระแล้ว','ยังไม่เข้าร่วม','2026-08-10 17:00:00',NULL,0,'2026-08-10 21:48:39','2026-08-10 21:48:39'),(63,'ACT-2026-016-R001',3,3,2,'สมชาย ใจงาม','081-100-1000',NULL,'female',1955,NULL,1,NULL,2,4,10,NULL,'ชำระแล้ว','ยังไม่เข้าร่วม','2026-08-22 17:00:00',NULL,1,'2026-08-10 21:48:39','2026-08-10 21:48:39'),(64,'ACT-2026-016-R002',3,4,3,'วิภาดา สายใจ','082-101-1007',NULL,'male',1962,NULL,3,NULL,3,3,9,NULL,'ชำระแล้ว','ยังไม่เข้าร่วม','2026-08-21 17:00:00',NULL,1,'2026-08-10 21:48:39','2026-08-10 21:48:39'),(65,'ACT-2026-016-R003',3,3,4,'ธีรพงษ์ แสงทอง','083-102-1014',NULL,'male',1969,NULL,5,NULL,3,2,12,NULL,'ชำระแล้ว','ยังไม่เข้าร่วม','2026-08-20 17:00:00',NULL,1,'2026-08-10 21:48:39','2026-08-10 21:48:39'),(66,'ACT-2026-016-R004',3,4,5,'กัลยา รุ่งเจริญ','084-103-1021',NULL,'female',1976,NULL,4,NULL,3,4,11,NULL,'ชำระแล้ว','ยังไม่เข้าร่วม','2026-08-19 17:00:00',NULL,1,'2026-08-10 21:48:39','2026-08-10 21:48:39'),(67,'ACT-2026-016-R005',3,3,6,'ประภาส ทองแท้','085-104-1028',NULL,'other',1983,NULL,2,NULL,3,2,10,NULL,'ชำระแล้ว','ยังไม่เข้าร่วม','2026-08-18 17:00:00',NULL,1,'2026-08-10 21:48:39','2026-08-10 21:48:39'),(68,'ACT-2026-016-R006',3,4,7,'มณีรัตน์ ใจบุญ','086-105-1035',NULL,'female',1990,NULL,1,NULL,2,2,9,NULL,'ชำระแล้ว','ยังไม่เข้าร่วม','2026-08-17 17:00:00',NULL,1,'2026-08-10 21:48:39','2026-08-10 21:48:39'),(69,'ACT-2026-016-R007',3,3,8,'อดิศักดิ์ พูลสวัสดิ์','087-106-1042',NULL,'male',1997,NULL,1,NULL,17,2,12,NULL,'ชำระแล้ว','ยังไม่เข้าร่วม','2026-08-16 17:00:00',NULL,1,'2026-08-10 21:48:39','2026-08-10 21:48:39'),(70,'ACT-2026-016-R008',3,4,9,'พิมพ์ใจ เพียรทำ','088-107-1049',NULL,'male',2004,NULL,1,NULL,3,2,9,NULL,'ชำระแล้ว','ยังไม่เข้าร่วม','2026-08-15 17:00:00',NULL,1,'2026-08-10 21:48:39','2026-08-10 21:48:39'),(71,'ACT-2026-016-R009',3,3,10,'ณัฐวุฒิ ใจงาม','089-108-1056',NULL,'female',1961,NULL,1,NULL,2,4,13,NULL,'ชำระแล้ว','ยังไม่เข้าร่วม','2026-08-14 17:00:00',NULL,1,'2026-08-10 21:48:39','2026-08-10 21:48:39'),(72,'ACT-2026-017-R001',4,5,2,'สมชาย ใจงาม','081-100-1000',NULL,'female',1955,NULL,7,NULL,2,3,10,NULL,'ชำระแล้ว','ยังไม่เข้าร่วม','2026-07-18 17:00:00',NULL,1,'2026-08-10 21:48:39','2026-08-10 21:48:39'),(73,'ACT-2026-017-R002',4,5,3,'วิภาดา สายใจ','082-101-1007',NULL,'male',1962,NULL,8,NULL,1,3,13,NULL,'ชำระแล้ว','เข้าร่วมแล้ว','2026-07-17 17:00:00','2026-07-20 02:03:00',1,'2026-08-10 21:48:39','2026-08-10 21:48:39'),(74,'ACT-2026-017-R003',4,5,4,'ธีรพงษ์ แสงทอง','083-102-1014',NULL,'male',1969,NULL,1,NULL,1,2,14,NULL,'ชำระแล้ว','เข้าร่วมแล้ว','2026-07-16 17:00:00','2026-07-20 02:06:00',1,'2026-08-10 21:48:39','2026-08-10 21:48:39'),(75,'ACT-2026-017-R004',4,5,5,'กัลยา รุ่งเจริญ','084-103-1021',NULL,'female',1976,NULL,1,NULL,1,2,11,NULL,'ชำระแล้ว','เข้าร่วมแล้ว','2026-07-15 17:00:00','2026-07-20 02:09:00',1,'2026-08-10 21:48:39','2026-08-10 21:48:39'),(76,'ACT-2026-017-R005',4,5,6,'ประภาส ทองแท้','085-104-1028',NULL,'other',1983,NULL,6,NULL,1,2,10,NULL,'ชำระแล้ว','เข้าร่วมแล้ว','2026-07-14 17:00:00','2026-07-20 02:12:00',1,'2026-08-10 21:48:39','2026-08-10 21:48:39'),(77,'ACT-2026-017-R006',4,5,7,'มณีรัตน์ ใจบุญ','086-105-1035',NULL,'female',1990,NULL,1,NULL,2,2,9,NULL,'ชำระแล้ว','ยังไม่เข้าร่วม','2026-07-13 17:00:00',NULL,1,'2026-08-10 21:48:39','2026-08-10 21:48:39'),(78,'ACT-2026-017-R007',4,5,8,'อดิศักดิ์ พูลสวัสดิ์','087-106-1042',NULL,'male',1997,NULL,1,NULL,3,2,12,NULL,'ชำระแล้ว','เข้าร่วมแล้ว','2026-07-12 17:00:00','2026-07-20 02:18:00',1,'2026-08-10 21:48:39','2026-08-10 21:48:39'),(79,'ACT-2026-017-R008',4,5,9,'พิมพ์ใจ เพียรทำ','088-107-1049',NULL,'male',2004,NULL,1,NULL,3,2,11,NULL,'ชำระแล้ว','เข้าร่วมแล้ว','2026-07-11 17:00:00','2026-07-20 02:21:00',1,'2026-08-10 21:48:39','2026-08-10 21:48:39'),(80,'ACT-2026-017-R009',4,5,10,'ณัฐวุฒิ ใจงาม','089-108-1056',NULL,'female',1961,NULL,6,NULL,3,3,12,NULL,'ชำระแล้ว','เข้าร่วมแล้ว','2026-07-10 17:00:00','2026-07-20 02:24:00',1,'2026-08-10 21:48:39','2026-08-10 21:48:39'),(81,'ACT-2026-017-R010',4,5,11,'สุพรรณี สายใจ','081-109-1063',NULL,'other',1968,NULL,3,NULL,1,2,11,NULL,'ชำระแล้ว','เข้าร่วมแล้ว','2026-07-09 17:00:00','2026-07-20 02:27:00',1,'2026-08-10 21:48:39','2026-08-10 21:48:39'),(82,'ACT-2026-017-R011',4,5,12,'สมชาย แสงทอง','082-110-1070',NULL,'female',1975,NULL,2,NULL,2,2,14,NULL,'ชำระแล้ว','ยังไม่เข้าร่วม','2026-07-08 17:00:00',NULL,1,'2026-08-10 21:48:39','2026-08-10 21:48:39'),(83,'ACT-2026-017-R012',4,5,13,'วิภาดา รุ่งเจริญ','083-111-1077',NULL,'male',1982,NULL,1,NULL,17,2,13,NULL,'ชำระแล้ว','เข้าร่วมแล้ว','2026-07-07 17:00:00','2026-07-20 02:33:00',1,'2026-08-10 21:48:39','2026-08-10 21:48:39'),(84,'ACT-2026-017-R013',4,5,14,'ธีรพงษ์ ทองแท้','084-112-1084',NULL,'male',1989,NULL,2,NULL,2,2,10,NULL,'ชำระแล้ว','เข้าร่วมแล้ว','2026-07-18 17:00:00','2026-07-20 02:36:00',1,'2026-08-10 21:48:40','2026-08-10 21:48:40'),(85,'ACT-2026-017-R014',4,5,15,'กัลยา ใจบุญ','085-113-1091',NULL,'female',1996,NULL,2,NULL,1,2,9,NULL,'ชำระแล้ว','เข้าร่วมแล้ว','2026-07-17 17:00:00','2026-07-20 02:39:00',1,'2026-08-10 21:48:40','2026-08-10 21:48:40'),(86,'ACT-2026-017-R015',4,5,16,'ประภาส พูลสวัสดิ์','086-114-1098',NULL,'other',2003,NULL,3,NULL,2,2,10,NULL,'ชำระแล้ว','เข้าร่วมแล้ว','2026-07-16 17:00:00','2026-07-20 02:42:00',1,'2026-08-10 21:48:40','2026-08-10 21:48:40'),(87,'ACT-2026-017-R016',4,5,17,'มณีรัตน์ เพียรทำ','087-115-1105',NULL,'female',1960,NULL,4,NULL,2,3,9,NULL,'ชำระแล้ว','ยังไม่เข้าร่วม','2026-07-15 17:00:00',NULL,1,'2026-08-10 21:48:40','2026-08-10 21:48:40'),(88,'ACT-2026-017-R017',4,5,18,'อดิศักดิ์ ใจงาม','088-116-1112',NULL,'male',1967,NULL,2,NULL,1,2,14,NULL,'ชำระแล้ว','เข้าร่วมแล้ว','2026-07-14 17:00:00','2026-07-20 02:48:00',1,'2026-08-10 21:48:40','2026-08-10 21:48:40'),(89,'ACT-2026-017-R018',4,5,19,'พิมพ์ใจ สายใจ','089-117-1119',NULL,'male',1974,NULL,2,NULL,2,2,13,NULL,'ชำระแล้ว','เข้าร่วมแล้ว','2026-07-13 17:00:00','2026-07-20 02:51:00',1,'2026-08-10 21:48:40','2026-08-10 21:48:40'),(90,'ACT-2026-017-R019',4,5,20,'ณัฐวุฒิ แสงทอง','081-118-1126',NULL,'female',1981,NULL,3,NULL,2,2,10,NULL,'ชำระแล้ว','เข้าร่วมแล้ว','2026-07-12 17:00:00','2026-07-20 02:54:00',1,'2026-08-10 21:48:40','2026-08-10 21:48:40'),(91,'ACT-2026-017-R020',4,5,21,'สุพรรณี รุ่งเจริญ','082-119-1133',NULL,'other',1988,NULL,1,NULL,3,2,14,NULL,'ชำระแล้ว','เข้าร่วมแล้ว','2026-07-11 17:00:00','2026-07-20 02:57:00',1,'2026-08-10 21:48:40','2026-08-10 21:48:40'),(92,'ACT-2026-017-R021',4,5,22,'สมชาย ทองแท้','083-120-1140',NULL,'female',1995,NULL,5,NULL,1,2,9,NULL,'ชำระแล้ว','ยังไม่เข้าร่วม','2026-07-10 17:00:00',NULL,1,'2026-08-10 21:48:40','2026-08-10 21:48:40'),(93,'ACT-2026-017-R022',4,5,23,'วิภาดา ใจบุญ','084-121-1147',NULL,'male',2002,NULL,1,NULL,1,2,10,NULL,'ชำระแล้ว','เข้าร่วมแล้ว','2026-07-09 17:00:00','2026-07-20 02:03:00',1,'2026-08-10 21:48:40','2026-08-10 21:48:40'),(94,'ACT-2026-017-R023',4,5,24,'ธีรพงษ์ พูลสวัสดิ์','085-122-1154',NULL,'male',1959,NULL,5,NULL,1,3,13,NULL,'ชำระแล้ว','เข้าร่วมแล้ว','2026-07-08 17:00:00','2026-07-20 02:06:00',1,'2026-08-10 21:48:40','2026-08-10 21:48:40'),(95,'ACT-2026-017-R024',4,5,25,'กัลยา เพียรทำ','086-123-1161',NULL,'female',1966,NULL,2,NULL,1,3,10,NULL,'ชำระแล้ว','เข้าร่วมแล้ว','2026-07-07 17:00:00','2026-07-20 02:09:00',1,'2026-08-10 21:48:40','2026-08-10 21:48:40'),(96,'ACT-2026-017-R025',4,5,26,'ประภาส ใจงาม','087-124-1168',NULL,'other',1973,NULL,1,NULL,3,2,13,NULL,'ชำระแล้ว','เข้าร่วมแล้ว','2026-07-18 17:00:00','2026-07-20 02:12:00',1,'2026-08-10 21:48:40','2026-08-10 21:48:40'),(97,'ACT-2026-017-R026',4,5,27,'มณีรัตน์ สายใจ','088-125-1175',NULL,'female',1980,NULL,1,NULL,1,2,10,NULL,'ชำระแล้ว','ยังไม่เข้าร่วม','2026-07-17 17:00:00',NULL,1,'2026-08-10 21:48:40','2026-08-10 21:48:40'),(98,'ACT-2026-017-R027',4,5,28,'อดิศักดิ์ แสงทอง','089-126-1182',NULL,'male',1987,NULL,6,NULL,3,2,9,NULL,'ชำระแล้ว','เข้าร่วมแล้ว','2026-07-16 17:00:00','2026-07-20 02:18:00',1,'2026-08-10 21:48:40','2026-08-10 21:48:40'),(99,'ACT-2026-017-R028',4,5,29,'พิมพ์ใจ รุ่งเจริญ','081-127-1189',NULL,'male',1994,NULL,1,NULL,1,2,10,NULL,'ชำระแล้ว','เข้าร่วมแล้ว','2026-07-15 17:00:00','2026-07-20 02:21:00',1,'2026-08-10 21:48:40','2026-08-10 21:48:40'),(100,'ACT-2026-017-R029',4,5,30,'ณัฐวุฒิ ทองแท้','082-128-1196',NULL,'female',2001,NULL,6,NULL,1,4,13,NULL,'ชำระแล้ว','เข้าร่วมแล้ว','2026-07-14 17:00:00','2026-07-20 02:24:00',1,'2026-08-10 21:48:40','2026-08-10 21:48:40'),(101,'ACT-2026-017-R030',4,5,31,'สุพรรณี ใจบุญ','083-129-1203',NULL,'other',1958,NULL,1,NULL,1,4,11,NULL,'ชำระแล้ว','เข้าร่วมแล้ว','2026-07-13 17:00:00','2026-07-20 02:27:00',1,'2026-08-10 21:48:40','2026-08-10 21:48:40'),(102,'ACT-2026-017-R031',4,5,32,'สมชาย พูลสวัสดิ์','084-130-1210',NULL,'female',1965,NULL,6,NULL,1,3,10,NULL,'ชำระแล้ว','ยังไม่เข้าร่วม','2026-07-12 17:00:00',NULL,1,'2026-08-10 21:48:40','2026-08-10 21:48:40'),(103,'ACT-2026-017-R032',4,5,33,'วิภาดา เพียรทำ','085-131-1217',NULL,'male',1972,NULL,1,NULL,2,2,13,NULL,'ชำระแล้ว','เข้าร่วมแล้ว','2026-07-11 17:00:00','2026-07-20 02:33:00',1,'2026-08-10 21:48:40','2026-08-10 21:48:40'),(104,'ACT-2026-017-R033',4,5,34,'ธีรพงษ์ ใจงาม','086-132-1224',NULL,'male',1979,NULL,5,NULL,2,2,10,NULL,'ชำระแล้ว','เข้าร่วมแล้ว','2026-07-10 17:00:00','2026-07-20 02:36:00',1,'2026-08-10 21:48:40','2026-08-10 21:48:40'),(105,'ACT-2026-017-R034',4,5,35,'กัลยา สายใจ','087-133-1231',NULL,'female',1986,NULL,5,NULL,17,2,9,NULL,'ชำระแล้ว','เข้าร่วมแล้ว','2026-07-09 17:00:00','2026-07-20 02:39:00',1,'2026-08-10 21:48:40','2026-08-10 21:48:40'),(106,'ACT-2026-017-R035',4,5,36,'ประภาส แสงทอง','088-134-1238',NULL,'other',1993,NULL,2,NULL,1,2,14,NULL,'ชำระแล้ว','เข้าร่วมแล้ว','2026-07-08 17:00:00','2026-07-20 02:42:00',1,'2026-08-10 21:48:40','2026-08-10 21:48:40'),(107,'ACT-2026-017-R036',4,5,37,'มณีรัตน์ รุ่งเจริญ','089-135-1245',NULL,'female',2000,NULL,7,NULL,3,2,9,NULL,'ชำระแล้ว','ยังไม่เข้าร่วม','2026-07-07 17:00:00',NULL,1,'2026-08-10 21:48:40','2026-08-10 21:48:40'),(108,'ACT-2026-017-R037',4,5,38,'อดิศักดิ์ ทองแท้','081-136-1252',NULL,'male',1957,NULL,1,NULL,3,3,10,NULL,'ชำระแล้ว','เข้าร่วมแล้ว','2026-07-18 17:00:00','2026-07-20 02:48:00',1,'2026-08-10 21:48:40','2026-08-10 21:48:40'),(109,'ACT-2026-017-R038',4,5,39,'พิมพ์ใจ ใจบุญ','082-137-1259',NULL,'male',1964,NULL,1,NULL,1,3,13,NULL,'ชำระแล้ว','เข้าร่วมแล้ว','2026-07-17 17:00:00','2026-07-20 02:51:00',1,'2026-08-10 21:48:40','2026-08-10 21:48:40'),(110,'ACT-2026-017-R039',4,5,40,'ณัฐวุฒิ พูลสวัสดิ์','083-138-1266',NULL,'female',1971,NULL,2,NULL,17,4,10,NULL,'ชำระแล้ว','เข้าร่วมแล้ว','2026-07-16 17:00:00','2026-07-20 02:54:00',1,'2026-08-10 21:48:40','2026-08-10 21:48:40'),(111,'ACT-2026-017-R040',4,5,41,'สุพรรณี เพียรทำ','084-139-1273',NULL,'other',1978,NULL,2,NULL,3,2,14,NULL,'ชำระแล้ว','เข้าร่วมแล้ว','2026-07-15 17:00:00','2026-07-20 02:57:00',1,'2026-08-10 21:48:40','2026-08-10 21:48:40'),(112,'ACT-2026-017-R041',4,5,42,'สมชาย ใจงาม','085-140-1280',NULL,'female',1985,NULL,1,NULL,1,2,9,NULL,'ชำระแล้ว','ยังไม่เข้าร่วม','2026-07-14 17:00:00',NULL,1,'2026-08-10 21:48:40','2026-08-10 21:48:40'),(113,'ACT-2026-017-R042',4,5,43,'วิภาดา สายใจ','086-141-1287',NULL,'male',1992,NULL,1,NULL,3,2,12,NULL,'ชำระแล้ว','เข้าร่วมแล้ว','2026-07-13 17:00:00','2026-07-20 02:03:00',1,'2026-08-10 21:48:40','2026-08-10 21:48:40'),(114,'ACT-2026-017-R043',4,5,44,'ธีรพงษ์ แสงทอง','087-142-1294',NULL,'male',1999,NULL,2,NULL,3,2,11,NULL,'ชำระแล้ว','เข้าร่วมแล้ว','2026-07-12 17:00:00','2026-07-20 02:06:00',1,'2026-08-10 21:48:40','2026-08-10 21:48:40'),(115,'ACT-2026-017-R044',4,5,45,'กัลยา รุ่งเจริญ','088-143-1301',NULL,'female',1956,NULL,2,NULL,17,3,14,NULL,'ชำระแล้ว','เข้าร่วมแล้ว','2026-07-11 17:00:00','2026-07-20 02:09:00',1,'2026-08-10 21:48:40','2026-08-10 21:48:40'),(116,'ACT-2026-017-R045',4,5,46,'ประภาส ทองแท้','089-144-1308',NULL,'other',1963,NULL,2,NULL,1,3,13,NULL,'ชำระแล้ว','เข้าร่วมแล้ว','2026-07-10 17:00:00','2026-07-20 02:12:00',1,'2026-08-10 21:48:40','2026-08-10 21:48:40'),(117,'ACT-2026-017-R046',4,5,47,'มณีรัตน์ ใจบุญ','081-145-1315',NULL,'female',1970,NULL,2,NULL,1,2,14,NULL,'ชำระแล้ว','ยังไม่เข้าร่วม','2026-07-09 17:00:00',NULL,1,'2026-08-10 21:48:40','2026-08-10 21:48:40'),(118,'ACT-2026-017-R047',4,5,48,'อดิศักดิ์ พูลสวัสดิ์','082-146-1322',NULL,'male',1977,NULL,3,NULL,2,2,11,NULL,'ชำระแล้ว','เข้าร่วมแล้ว','2026-07-08 17:00:00','2026-07-20 02:18:00',1,'2026-08-10 21:48:40','2026-08-10 21:48:40'),(119,'ACT-2026-018-R001',5,NULL,2,'สมชาย ใจงาม','081-100-1000',NULL,'female',1955,NULL,2,NULL,1,3,14,NULL,'ชำระแล้ว','ยังไม่เข้าร่วม','2026-09-03 17:00:00',NULL,1,'2026-08-10 21:48:40','2026-08-10 21:48:40'),(120,'ACT-2026-018-R002',5,NULL,3,'วิภาดา สายใจ','082-101-1007',NULL,'male',1962,NULL,2,NULL,1,3,9,NULL,'ชำระแล้ว','ยังไม่เข้าร่วม','2026-09-02 17:00:00',NULL,1,'2026-08-10 21:48:40','2026-08-10 21:48:40'),(121,'ACT-2026-018-R003',5,NULL,4,'ธีรพงษ์ แสงทอง','083-102-1014',NULL,'male',1969,NULL,6,NULL,1,2,12,NULL,'ชำระแล้ว','ยังไม่เข้าร่วม','2026-09-01 17:00:00',NULL,1,'2026-08-10 21:48:40','2026-08-10 21:48:40'),(122,'ACT-2026-018-R004',5,NULL,5,'กัลยา รุ่งเจริญ','084-103-1021',NULL,'female',1976,NULL,1,NULL,1,2,11,NULL,'ชำระแล้ว','ยังไม่เข้าร่วม','2026-08-31 17:00:00',NULL,1,'2026-08-10 21:48:40','2026-08-10 21:48:40'),(123,'DEMO-REG-0-001',10,NULL,2,'สมชาย ใจงาม','081-100-1000',NULL,'female',1955,NULL,5,NULL,1,3,9,NULL,'ไม่มีค่าใช้จ่าย','เข้าร่วมแล้ว','2026-06-01 17:00:00',NULL,0,'2026-08-11 21:04:48','2026-08-11 21:04:48'),(124,'DEMO-REG-0-002',10,NULL,3,'วิภาดา สายใจ','082-101-1007',NULL,'male',1962,NULL,4,NULL,2,3,14,NULL,'ไม่มีค่าใช้จ่าย','เข้าร่วมแล้ว','2026-06-01 17:00:00',NULL,0,'2026-08-11 21:04:48','2026-08-11 21:04:48'),(125,'DEMO-REG-0-003',10,NULL,4,'ธีรพงษ์ แสงทอง','083-102-1014',NULL,'male',1969,NULL,2,NULL,2,2,13,NULL,'ไม่มีค่าใช้จ่าย','เข้าร่วมแล้ว','2026-06-01 17:00:00',NULL,0,'2026-08-11 21:04:48','2026-08-11 21:04:48'),(126,'DEMO-REG-0-004',10,NULL,5,'กัลยา รุ่งเจริญ','084-103-1021',NULL,'female',1976,NULL,3,NULL,1,2,10,NULL,'ไม่มีค่าใช้จ่าย','เข้าร่วมแล้ว','2026-06-01 17:00:00',NULL,0,'2026-08-11 21:04:48','2026-08-11 21:04:48'),(127,'DEMO-REG-0-005',10,NULL,6,'ประภาส ทองแท้','085-104-1028',NULL,'other',1983,NULL,5,NULL,3,2,9,NULL,'ไม่มีค่าใช้จ่าย','เข้าร่วมแล้ว','2026-06-01 17:00:00',NULL,0,'2026-08-11 21:04:48','2026-08-11 21:04:48'),(128,'DEMO-REG-0-006',10,NULL,7,'มณีรัตน์ ใจบุญ','086-105-1035',NULL,'female',1990,NULL,2,NULL,2,2,14,NULL,'ไม่มีค่าใช้จ่าย','ยังไม่เข้าร่วม','2026-06-01 17:00:00',NULL,0,'2026-08-11 21:04:48','2026-08-11 21:04:48'),(129,'DEMO-REG-0-007',10,NULL,8,'อดิศักดิ์ พูลสวัสดิ์','087-106-1042',NULL,'male',1997,NULL,3,NULL,1,4,11,NULL,'ไม่มีค่าใช้จ่าย','เข้าร่วมแล้ว','2026-06-01 17:00:00',NULL,0,'2026-08-11 21:04:48','2026-08-11 21:04:48'),(130,'DEMO-REG-0-008',10,NULL,9,'พิมพ์ใจ เพียรทำ','088-107-1049',NULL,'male',2004,NULL,4,NULL,1,2,14,NULL,'ไม่มีค่าใช้จ่าย','เข้าร่วมแล้ว','2026-06-01 17:00:00',NULL,0,'2026-08-11 21:04:48','2026-08-11 21:04:48'),(131,'DEMO-REG-0-009',10,NULL,10,'ณัฐวุฒิ ใจงาม','089-108-1056',NULL,'female',1961,NULL,1,NULL,3,3,11,NULL,'ไม่มีค่าใช้จ่าย','ยังไม่เข้าร่วม','2026-06-01 17:00:00',NULL,0,'2026-08-11 21:04:48','2026-08-11 21:04:48'),(132,'DEMO-REG-0-010',10,NULL,11,'สุพรรณี สายใจ','081-109-1063',NULL,'other',1968,NULL,2,NULL,1,2,10,NULL,'ไม่มีค่าใช้จ่าย','เข้าร่วมแล้ว','2026-06-01 17:00:00',NULL,0,'2026-08-11 21:04:48','2026-08-11 21:04:48'),(133,'DEMO-REG-0-011',10,NULL,12,'สมชาย แสงทอง','082-110-1070',NULL,'female',1975,NULL,3,NULL,1,2,13,NULL,'ไม่มีค่าใช้จ่าย','เข้าร่วมแล้ว','2026-06-01 17:00:00',NULL,0,'2026-08-11 21:04:48','2026-08-11 21:04:48'),(134,'DEMO-REG-0-012',10,NULL,13,'วิภาดา รุ่งเจริญ','083-111-1077',NULL,'male',1982,NULL,1,NULL,17,2,10,NULL,'ไม่มีค่าใช้จ่าย','เข้าร่วมแล้ว','2026-06-01 17:00:00',NULL,0,'2026-08-11 21:04:48','2026-08-11 21:04:48'),(135,'DEMO-REG-0-013',10,NULL,14,'ธีรพงษ์ ทองแท้','084-112-1084',NULL,'male',1989,NULL,3,NULL,1,2,11,NULL,'ไม่มีค่าใช้จ่าย','เข้าร่วมแล้ว','2026-06-01 17:00:00',NULL,0,'2026-08-11 21:04:48','2026-08-11 21:04:48'),(136,'DEMO-REG-0-014',10,NULL,15,'กัลยา ใจบุญ','085-113-1091',NULL,'female',1996,NULL,5,NULL,2,2,10,NULL,'ไม่มีค่าใช้จ่าย','เข้าร่วมแล้ว','2026-06-01 17:00:00',NULL,0,'2026-08-11 21:04:48','2026-08-11 21:04:48'),(137,'DEMO-REG-0-015',10,NULL,16,'ประภาส พูลสวัสดิ์','086-114-1098',NULL,'other',2003,NULL,1,NULL,3,2,13,NULL,'ไม่มีค่าใช้จ่าย','เข้าร่วมแล้ว','2026-06-01 17:00:00',NULL,0,'2026-08-11 21:04:48','2026-08-11 21:04:48'),(138,'DEMO-REG-0-016',10,NULL,17,'มณีรัตน์ เพียรทำ','087-115-1105',NULL,'female',1960,NULL,1,NULL,2,3,12,NULL,'ไม่มีค่าใช้จ่าย','เข้าร่วมแล้ว','2026-06-01 17:00:00',NULL,0,'2026-08-11 21:04:48','2026-08-11 21:04:48'),(139,'DEMO-REG-0-017',10,NULL,18,'อดิศักดิ์ ใจงาม','088-116-1112',NULL,'male',1967,NULL,4,NULL,2,2,13,NULL,'ไม่มีค่าใช้จ่าย','เข้าร่วมแล้ว','2026-06-01 17:00:00',NULL,0,'2026-08-11 21:04:48','2026-08-11 21:04:48'),(140,'DEMO-REG-0-018',10,NULL,19,'พิมพ์ใจ สายใจ','089-117-1119',NULL,'male',1974,NULL,4,NULL,3,2,14,NULL,'ไม่มีค่าใช้จ่าย','เข้าร่วมแล้ว','2026-06-01 17:00:00',NULL,0,'2026-08-11 21:04:48','2026-08-11 21:04:48'),(141,'DEMO-REG-0-019',10,NULL,20,'ณัฐวุฒิ แสงทอง','081-118-1126',NULL,'female',1981,NULL,5,NULL,3,2,9,NULL,'ไม่มีค่าใช้จ่าย','เข้าร่วมแล้ว','2026-06-01 17:00:00',NULL,0,'2026-08-11 21:04:48','2026-08-11 21:04:48'),(142,'DEMO-REG-0-020',10,NULL,21,'สุพรรณี รุ่งเจริญ','082-119-1133',NULL,'other',1988,NULL,4,NULL,1,2,10,NULL,'ไม่มีค่าใช้จ่าย','ยังไม่เข้าร่วม','2026-06-01 17:00:00',NULL,0,'2026-08-11 21:04:48','2026-08-11 21:04:48'),(143,'DEMO-REG-0-021',10,NULL,22,'สมชาย ทองแท้','083-120-1140',NULL,'female',1995,NULL,2,NULL,1,2,9,NULL,'ไม่มีค่าใช้จ่าย','เข้าร่วมแล้ว','2026-06-01 17:00:00',NULL,0,'2026-08-11 21:04:48','2026-08-11 21:04:48'),(144,'DEMO-REG-0-022',10,NULL,23,'วิภาดา ใจบุญ','084-121-1147',NULL,'male',2002,NULL,7,NULL,2,4,12,NULL,'ไม่มีค่าใช้จ่าย','เข้าร่วมแล้ว','2026-06-01 17:00:00',NULL,0,'2026-08-11 21:04:48','2026-08-11 21:04:48'),(145,'DEMO-REG-0-023',10,NULL,24,'ธีรพงษ์ พูลสวัสดิ์','085-122-1154',NULL,'male',1959,NULL,1,NULL,2,3,9,NULL,'ไม่มีค่าใช้จ่าย','ยังไม่เข้าร่วม','2026-06-01 17:00:00',NULL,0,'2026-08-11 21:04:48','2026-08-11 21:04:48'),(146,'DEMO-REG-0-024',10,NULL,25,'กัลยา เพียรทำ','086-123-1161',NULL,'female',1966,NULL,7,NULL,1,3,10,NULL,'ไม่มีค่าใช้จ่าย','เข้าร่วมแล้ว','2026-06-01 17:00:00',NULL,0,'2026-08-11 21:04:48','2026-08-11 21:04:48'),(147,'DEMO-REG-0-025',10,NULL,26,'ประภาส ใจงาม','087-124-1168',NULL,'other',1973,NULL,4,NULL,3,2,13,NULL,'ไม่มีค่าใช้จ่าย','เข้าร่วมแล้ว','2026-06-01 17:00:00',NULL,0,'2026-08-11 21:04:48','2026-08-11 21:04:48'),(148,'DEMO-REG-0-026',10,NULL,27,'มณีรัตน์ สายใจ','088-125-1175',NULL,'female',1980,NULL,4,NULL,17,2,14,NULL,'ไม่มีค่าใช้จ่าย','เข้าร่วมแล้ว','2026-06-01 17:00:00',NULL,0,'2026-08-11 21:04:48','2026-08-11 21:04:48'),(149,'DEMO-REG-1-001',11,NULL,2,'สมชาย ใจงาม','081-100-1000',NULL,'female',1955,NULL,5,NULL,1,3,9,NULL,'ไม่มีค่าใช้จ่าย','เข้าร่วมแล้ว','2026-05-01 17:00:00',NULL,0,'2026-08-11 21:04:48','2026-08-11 21:04:48'),(150,'DEMO-REG-1-002',11,NULL,3,'วิภาดา สายใจ','082-101-1007',NULL,'male',1962,NULL,4,NULL,2,3,14,NULL,'ไม่มีค่าใช้จ่าย','เข้าร่วมแล้ว','2026-05-01 17:00:00',NULL,0,'2026-08-11 21:04:48','2026-08-11 21:04:48'),(151,'DEMO-REG-1-003',11,NULL,4,'ธีรพงษ์ แสงทอง','083-102-1014',NULL,'male',1969,NULL,2,NULL,2,2,13,NULL,'ไม่มีค่าใช้จ่าย','เข้าร่วมแล้ว','2026-05-01 17:00:00',NULL,0,'2026-08-11 21:04:48','2026-08-11 21:04:48'),(152,'DEMO-REG-1-004',11,NULL,5,'กัลยา รุ่งเจริญ','084-103-1021',NULL,'female',1976,NULL,3,NULL,1,2,10,NULL,'ไม่มีค่าใช้จ่าย','เข้าร่วมแล้ว','2026-05-01 17:00:00',NULL,0,'2026-08-11 21:04:48','2026-08-11 21:04:48'),(153,'DEMO-REG-1-005',11,NULL,6,'ประภาส ทองแท้','085-104-1028',NULL,'other',1983,NULL,5,NULL,3,2,9,NULL,'ไม่มีค่าใช้จ่าย','ยังไม่เข้าร่วม','2026-05-01 17:00:00',NULL,0,'2026-08-11 21:04:48','2026-08-11 21:04:48'),(154,'DEMO-REG-1-006',11,NULL,7,'มณีรัตน์ ใจบุญ','086-105-1035',NULL,'female',1990,NULL,2,NULL,2,2,14,NULL,'ไม่มีค่าใช้จ่าย','เข้าร่วมแล้ว','2026-05-01 17:00:00',NULL,0,'2026-08-11 21:04:48','2026-08-11 21:04:48'),(155,'DEMO-REG-1-007',11,NULL,8,'อดิศักดิ์ พูลสวัสดิ์','087-106-1042',NULL,'male',1997,NULL,3,NULL,1,4,11,NULL,'ไม่มีค่าใช้จ่าย','เข้าร่วมแล้ว','2026-05-01 17:00:00',NULL,0,'2026-08-11 21:04:48','2026-08-11 21:04:48'),(156,'DEMO-REG-1-008',11,NULL,9,'พิมพ์ใจ เพียรทำ','088-107-1049',NULL,'male',2004,NULL,4,NULL,1,2,14,NULL,'ไม่มีค่าใช้จ่าย','เข้าร่วมแล้ว','2026-05-01 17:00:00',NULL,0,'2026-08-11 21:04:48','2026-08-11 21:04:48'),(157,'DEMO-REG-1-009',11,NULL,10,'ณัฐวุฒิ ใจงาม','089-108-1056',NULL,'female',1961,NULL,1,NULL,3,3,11,NULL,'ไม่มีค่าใช้จ่าย','เข้าร่วมแล้ว','2026-05-01 17:00:00',NULL,0,'2026-08-11 21:04:48','2026-08-11 21:04:48'),(158,'DEMO-REG-1-010',11,NULL,11,'สุพรรณี สายใจ','081-109-1063',NULL,'other',1968,NULL,2,NULL,1,2,10,NULL,'ไม่มีค่าใช้จ่าย','เข้าร่วมแล้ว','2026-05-01 17:00:00',NULL,0,'2026-08-11 21:04:48','2026-08-11 21:04:48'),(159,'DEMO-REG-1-011',11,NULL,12,'สมชาย แสงทอง','082-110-1070',NULL,'female',1975,NULL,3,NULL,1,2,13,NULL,'ไม่มีค่าใช้จ่าย','เข้าร่วมแล้ว','2026-05-01 17:00:00',NULL,0,'2026-08-11 21:04:48','2026-08-11 21:04:48'),(160,'DEMO-REG-1-012',11,NULL,13,'วิภาดา รุ่งเจริญ','083-111-1077',NULL,'male',1982,NULL,1,NULL,17,2,10,NULL,'ไม่มีค่าใช้จ่าย','เข้าร่วมแล้ว','2026-05-01 17:00:00',NULL,0,'2026-08-11 21:04:48','2026-08-11 21:04:48'),(161,'DEMO-REG-1-013',11,NULL,14,'ธีรพงษ์ ทองแท้','084-112-1084',NULL,'male',1989,NULL,3,NULL,1,2,11,NULL,'ไม่มีค่าใช้จ่าย','เข้าร่วมแล้ว','2026-05-01 17:00:00',NULL,0,'2026-08-11 21:04:48','2026-08-11 21:04:48'),(162,'DEMO-REG-1-014',11,NULL,15,'กัลยา ใจบุญ','085-113-1091',NULL,'female',1996,NULL,5,NULL,2,2,10,NULL,'ไม่มีค่าใช้จ่าย','เข้าร่วมแล้ว','2026-05-01 17:00:00',NULL,0,'2026-08-11 21:04:48','2026-08-11 21:04:48'),(163,'DEMO-REG-1-015',11,NULL,16,'ประภาส พูลสวัสดิ์','086-114-1098',NULL,'other',2003,NULL,1,NULL,3,2,13,NULL,'ไม่มีค่าใช้จ่าย','เข้าร่วมแล้ว','2026-05-01 17:00:00',NULL,0,'2026-08-11 21:04:48','2026-08-11 21:04:48'),(164,'DEMO-REG-1-016',11,NULL,17,'มณีรัตน์ เพียรทำ','087-115-1105',NULL,'female',1960,NULL,1,NULL,2,3,12,NULL,'ไม่มีค่าใช้จ่าย','เข้าร่วมแล้ว','2026-05-01 17:00:00',NULL,0,'2026-08-11 21:04:48','2026-08-11 21:04:48'),(165,'DEMO-REG-1-017',11,NULL,18,'อดิศักดิ์ ใจงาม','088-116-1112',NULL,'male',1967,NULL,4,NULL,2,2,13,NULL,'ไม่มีค่าใช้จ่าย','เข้าร่วมแล้ว','2026-05-01 17:00:00',NULL,0,'2026-08-11 21:04:48','2026-08-11 21:04:48'),(166,'DEMO-REG-1-018',11,NULL,19,'พิมพ์ใจ สายใจ','089-117-1119',NULL,'male',1974,NULL,4,NULL,3,2,14,NULL,'ไม่มีค่าใช้จ่าย','เข้าร่วมแล้ว','2026-05-01 17:00:00',NULL,0,'2026-08-11 21:04:48','2026-08-11 21:04:48'),(167,'REG-TDHSHRX8C6MPASKF',21,NULL,49,'แอมมี่','0925399788',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'ยังไม่ชำระ','เข้าร่วมแล้ว','2026-08-13 11:20:12','2026-08-13 12:09:05',0,'2026-08-13 11:20:12','2026-08-13 12:09:05'),(168,'REG-1LQAG8ZYQY1G2TTQ',21,NULL,50,'พี่ชาย','0925399788',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'ยังไม่ชำระ','เข้าร่วมแล้ว','2026-08-13 11:20:12','2026-08-13 12:09:06',0,'2026-08-13 11:20:12','2026-08-13 12:09:06'),(169,'REG-YBT2LPPLITCGRPVJ',21,NULL,51,'แอมมี่','0810766976',NULL,NULL,NULL,47,1,NULL,NULL,NULL,9,NULL,'ยังไม่ชำระ','ยังไม่เข้าร่วม','2026-08-14 08:01:43',NULL,0,'2026-08-14 08:01:43','2026-08-14 08:01:43'),(170,'REG-OEZEZLHEEF5XX4HI',21,NULL,52,'สมชาย','0810766976',NULL,NULL,NULL,49,3,NULL,NULL,NULL,NULL,NULL,'ยังไม่ชำระ','ยังไม่เข้าร่วม','2026-08-14 08:01:43',NULL,0,'2026-08-14 08:01:43','2026-08-14 08:01:43'),(171,'REG-HNOBICAUJ4HVHZEO',21,NULL,53,'แอมมี่','0830646432',NULL,NULL,NULL,48,5,NULL,NULL,NULL,NULL,NULL,'ยังไม่ชำระ','ยังไม่เข้าร่วม','2026-08-14 09:05:56',NULL,0,'2026-08-14 09:05:56','2026-08-14 09:05:56'),(172,'REG-AFTXLWP6DVDYIVQN',21,NULL,54,'อุ้ม','0830646432',NULL,NULL,NULL,48,4,NULL,NULL,NULL,NULL,NULL,'ยังไม่ชำระ','ยังไม่เข้าร่วม','2026-08-14 09:05:56',NULL,0,'2026-08-14 09:05:56','2026-08-14 09:05:56'),(173,'REG-YIJOWUFTH8RZFSFX',21,NULL,55,'เมเม','0935399788',NULL,NULL,NULL,48,5,NULL,NULL,NULL,10,NULL,'ยังไม่ชำระ','ยังไม่เข้าร่วม','2026-08-14 09:14:43',NULL,0,'2026-08-14 09:14:43','2026-08-14 09:14:43'),(174,'REG-QS9GTECX8W0TK8T7',14,NULL,56,'แอม','0925399788',NULL,NULL,NULL,48,5,NULL,NULL,NULL,10,NULL,'ชำระแล้ว','เข้าร่วมแล้ว','2026-08-14 14:13:32','2026-08-15 10:47:36',0,'2026-08-14 14:13:32','2026-08-15 10:47:36'),(175,'REG-OUCNW2JQFSRKGOYM',14,NULL,57,'โชคชัย','0925399788',NULL,NULL,NULL,50,4,NULL,NULL,NULL,NULL,NULL,'รอตรวจสอบ','เข้าร่วมแล้ว','2026-08-14 14:13:32','2026-08-15 10:47:47',0,'2026-08-14 14:13:32','2026-08-15 10:47:47'),(176,'REG-451HNXAYFOALDGEA',14,NULL,58,'แอมมี่','0925498778',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'รอตรวจสอบ','ยังไม่เข้าร่วม','2026-08-14 14:17:33',NULL,0,'2026-08-14 14:17:33','2026-08-14 14:17:46'),(177,'REG-LZGEYAMVDTWKGMX8',14,NULL,59,'พี่ชาย','0925498778',NULL,NULL,NULL,50,1,NULL,NULL,NULL,NULL,NULL,'รอตรวจสอบ','ยังไม่เข้าร่วม','2026-08-14 14:17:33',NULL,0,'2026-08-14 14:17:33','2026-08-14 14:17:46'),(178,'REG-GAPMAZ3BLRAGH7ED',14,NULL,60,'Am','0925399755',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'ยังไม่ชำระ','ยังไม่เข้าร่วม','2026-08-14 16:50:18',NULL,0,'2026-08-14 16:50:18','2026-08-14 16:50:18'),(179,'REG-FMDBVOIMWFUCIBK7',14,NULL,61,'แอม','0925399752',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'รอตรวจสอบ','ยังไม่เข้าร่วม','2026-08-15 04:33:15',NULL,0,'2026-08-15 04:33:15','2026-08-15 04:33:20'),(180,'REG-WD20NVKJA720DL42',14,NULL,62,'ใจดี','0925399752',NULL,NULL,NULL,48,5,NULL,NULL,NULL,NULL,NULL,'รอตรวจสอบ','ยังไม่เข้าร่วม','2026-08-15 04:33:15',NULL,0,'2026-08-15 04:33:15','2026-08-15 04:33:20'),(181,'REG-KN4HJYYZOLF9FRZK',16,14,49,'แอมมี่','0925399788',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'ยังไม่ชำระ','ยังไม่เข้าร่วม','2026-08-15 06:01:20',NULL,0,'2026-08-15 06:01:20','2026-08-15 06:01:20'),(182,'REG-5LKY75XHY7TOEQAO',16,14,63,'Orawan hadkrathok','0861358114',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'ยังไม่ชำระ','ยังไม่เข้าร่วม','2026-08-15 06:18:07',NULL,0,'2026-08-15 06:18:07','2026-08-15 06:18:07'),(183,'REG-ZNYQWA5O7FLYO1IX',15,13,49,'แอมมี่','0925399788',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'รอตรวจสอบ','ยังไม่เข้าร่วม','2026-08-15 10:37:55',NULL,0,'2026-08-15 10:37:55','2026-08-15 10:38:23'),(184,'REG-V6ROD1AUDTKNNVWB',15,13,64,'ฝน','0925399788',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'รอตรวจสอบ','ยังไม่เข้าร่วม','2026-08-15 10:37:55',NULL,0,'2026-08-15 10:37:55','2026-08-15 10:38:23'),(185,'REG-H0RFIJHN9CJUTM9J',15,13,65,'แอม','0925399766',NULL,NULL,NULL,49,NULL,NULL,NULL,NULL,NULL,NULL,'ยังไม่ชำระ','ยังไม่เข้าร่วม','2026-08-15 10:43:38',NULL,0,'2026-08-15 10:43:38','2026-08-15 10:43:38'),(186,'REG-PGBXOHNC8PQ1VXDP',15,13,66,'แอม','0925377866',NULL,NULL,NULL,48,NULL,NULL,NULL,NULL,NULL,NULL,'รอตรวจสอบ','ยังไม่เข้าร่วม','2026-08-15 10:44:20',NULL,0,'2026-08-15 10:44:20','2026-08-15 10:44:22'),(187,'REG-NUWIBLPDO1W1MLFL',14,NULL,67,'แอม','0925300000',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'ยังไม่ชำระ','ยังไม่เข้าร่วม','2026-08-15 11:02:30',NULL,0,'2026-08-15 11:02:30','2026-08-15 11:02:30'),(188,'REG-Y37JFG3DWYBNP58G',14,NULL,69,'Nattchai Charoensri','0986272109','nattchai_tc000@hotmail.com',NULL,NULL,49,NULL,NULL,NULL,NULL,NULL,'-','รอตรวจสอบ','ยังไม่เข้าร่วม','2026-08-15 11:17:52',NULL,0,'2026-08-15 11:17:52','2026-08-15 11:18:05'),(189,'REG-JEZP4RETJSFUCVAG',14,NULL,70,'Nattchai Charoensri','0986272108','nattchai_tc000@hotmail.com',NULL,NULL,49,NULL,NULL,NULL,NULL,NULL,'-','รอตรวจสอบ','ยังไม่เข้าร่วม','2026-08-15 11:20:28',NULL,0,'2026-08-15 11:20:28','2026-08-15 11:24:26'),(190,'REG-SAHF8NHVOVFVY2AQ',14,NULL,71,'เทส','0925399763',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'ยังไม่ชำระ','ยังไม่เข้าร่วม','2026-08-15 11:21:45',NULL,0,'2026-08-15 11:21:45','2026-08-15 11:21:45');
/*!40000 ALTER TABLE `act_registrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint NOT NULL,
  PRIMARY KEY (`key`) USING BTREE,
  KEY `cache_expiration_index` (`expiration`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache`
--

LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
INSERT INTO `cache` VALUES ('thefarmconcept-cache-b6a00c4fb65d1b0e5b9b248a074c0648eb29810b','i:1;',1786950491),('thefarmconcept-cache-b6a00c4fb65d1b0e5b9b248a074c0648eb29810b:timer','i:1786950491;',1786950491);
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint NOT NULL,
  PRIMARY KEY (`key`) USING BTREE,
  KEY `cache_locks_expiration_index` (`expiration`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache_locks`
--

LOCK TABLES `cache_locks` WRITE;
/*!40000 ALTER TABLE `cache_locks` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_locks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `evl_answers`
--

DROP TABLE IF EXISTS `evl_answers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `evl_answers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `response_type` enum('registration','satisfaction','survey') COLLATE utf8mb4_unicode_ci NOT NULL,
  `response_id` bigint unsigned NOT NULL,
  `question_id` bigint unsigned NOT NULL,
  `option_id` bigint unsigned DEFAULT NULL,
  `score` tinyint unsigned DEFAULT NULL,
  `text_value` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `evl_answers_response_type_response_id_index` (`response_type`,`response_id`) USING BTREE,
  KEY `evl_answers_question_id_index` (`question_id`) USING BTREE,
  KEY `evl_answers_option_id_foreign` (`option_id`),
  CONSTRAINT `evl_answers_option_id_foreign` FOREIGN KEY (`option_id`) REFERENCES `evl_question_options` (`id`) ON DELETE SET NULL,
  CONSTRAINT `evl_answers_question_id_foreign` FOREIGN KEY (`question_id`) REFERENCES `evl_questions` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=395 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `evl_answers`
--

LOCK TABLES `evl_answers` WRITE;
/*!40000 ALTER TABLE `evl_answers` DISABLE KEYS */;
INSERT INTO `evl_answers` VALUES (1,'survey',1,1,NULL,3,NULL),(2,'survey',1,2,NULL,2,NULL),(3,'survey',1,3,NULL,3,NULL),(4,'survey',1,4,NULL,3,NULL),(5,'survey',1,5,NULL,2,NULL),(6,'survey',2,1,NULL,3,NULL),(7,'survey',2,2,NULL,2,NULL),(8,'survey',2,3,NULL,3,NULL),(9,'survey',2,4,NULL,3,NULL),(10,'survey',2,5,NULL,2,NULL),(11,'survey',3,1,NULL,2,NULL),(12,'survey',3,2,NULL,2,NULL),(13,'survey',3,3,NULL,3,NULL),(14,'survey',3,4,NULL,2,NULL),(15,'survey',3,5,NULL,2,NULL),(16,'survey',4,1,NULL,3,NULL),(17,'survey',4,2,NULL,2,NULL),(18,'survey',4,3,NULL,3,NULL),(19,'survey',4,4,NULL,3,NULL),(20,'survey',4,5,NULL,2,NULL),(21,'survey',5,1,NULL,2,NULL),(22,'survey',5,2,NULL,2,NULL),(23,'survey',5,3,NULL,3,NULL),(24,'survey',5,4,NULL,3,NULL),(25,'survey',5,5,NULL,2,NULL),(26,'survey',6,1,NULL,3,NULL),(27,'survey',6,2,NULL,2,NULL),(28,'survey',6,3,NULL,3,NULL),(29,'survey',6,4,NULL,3,NULL),(30,'survey',6,5,NULL,2,NULL),(31,'survey',7,1,NULL,3,NULL),(32,'survey',7,2,NULL,3,NULL),(33,'survey',7,3,NULL,3,NULL),(34,'survey',7,4,NULL,3,NULL),(35,'survey',7,5,NULL,2,NULL),(36,'survey',8,1,NULL,3,NULL),(37,'survey',8,2,NULL,2,NULL),(38,'survey',8,3,NULL,3,NULL),(39,'survey',8,4,NULL,2,NULL),(40,'survey',8,5,NULL,2,NULL),(41,'survey',9,1,NULL,3,NULL),(42,'survey',9,2,NULL,3,NULL),(43,'survey',9,3,NULL,3,NULL),(44,'survey',9,4,NULL,3,NULL),(45,'survey',9,5,NULL,3,NULL),(46,'survey',10,1,NULL,3,NULL),(47,'survey',10,2,NULL,2,NULL),(48,'survey',10,3,NULL,3,NULL),(49,'survey',10,4,NULL,2,NULL),(50,'survey',10,5,NULL,2,NULL),(51,'survey',11,1,NULL,3,NULL),(52,'survey',11,2,NULL,3,NULL),(53,'survey',11,3,NULL,3,NULL),(54,'survey',11,4,NULL,3,NULL),(55,'survey',11,5,NULL,2,NULL),(56,'survey',12,1,NULL,2,NULL),(57,'survey',12,2,NULL,3,NULL),(58,'survey',12,3,NULL,2,NULL),(59,'survey',12,4,NULL,3,NULL),(60,'survey',12,5,NULL,2,NULL),(61,'survey',13,1,NULL,3,NULL),(62,'survey',13,2,NULL,3,NULL),(63,'survey',13,3,NULL,3,NULL),(64,'survey',13,4,NULL,3,NULL),(65,'survey',13,5,NULL,2,NULL),(66,'survey',14,1,NULL,3,NULL),(67,'survey',14,2,NULL,3,NULL),(68,'survey',14,3,NULL,3,NULL),(69,'survey',14,4,NULL,3,NULL),(70,'survey',14,5,NULL,2,NULL),(71,'survey',15,1,NULL,3,NULL),(72,'survey',15,2,NULL,2,NULL),(73,'survey',15,3,NULL,3,NULL),(74,'survey',15,4,NULL,3,NULL),(75,'survey',15,5,NULL,2,NULL),(76,'survey',16,1,NULL,3,NULL),(77,'survey',16,2,NULL,2,NULL),(78,'survey',16,3,NULL,3,NULL),(79,'survey',16,4,NULL,3,NULL),(80,'survey',16,5,NULL,2,NULL),(81,'survey',17,1,NULL,3,NULL),(82,'survey',17,2,NULL,3,NULL),(83,'survey',17,3,NULL,3,NULL),(84,'survey',17,4,NULL,3,NULL),(85,'survey',17,5,NULL,2,NULL),(86,'survey',18,1,NULL,3,NULL),(87,'survey',18,2,NULL,3,NULL),(88,'survey',18,3,NULL,3,NULL),(89,'survey',18,4,NULL,3,NULL),(90,'survey',18,5,NULL,2,NULL),(91,'survey',19,1,NULL,3,NULL),(92,'survey',19,2,NULL,2,NULL),(93,'survey',19,3,NULL,3,NULL),(94,'survey',19,4,NULL,2,NULL),(95,'survey',19,5,NULL,2,NULL),(96,'survey',20,1,NULL,2,NULL),(97,'survey',20,2,NULL,3,NULL),(98,'survey',20,3,NULL,3,NULL),(99,'survey',20,4,NULL,3,NULL),(100,'survey',20,5,NULL,2,NULL),(101,'survey',21,1,NULL,4,NULL),(102,'survey',21,2,NULL,3,NULL),(103,'survey',21,3,NULL,3,NULL),(104,'survey',21,4,NULL,3,NULL),(105,'survey',21,5,NULL,2,NULL),(106,'survey',22,1,NULL,3,NULL),(107,'survey',22,2,NULL,2,NULL),(108,'survey',22,3,NULL,3,NULL),(109,'survey',22,4,NULL,3,NULL),(110,'survey',22,5,NULL,2,NULL),(111,'survey',23,1,NULL,3,NULL),(112,'survey',23,2,NULL,3,NULL),(113,'survey',23,3,NULL,3,NULL),(114,'survey',23,4,NULL,3,NULL),(115,'survey',23,5,NULL,2,NULL),(116,'survey',24,1,NULL,3,NULL),(117,'survey',24,2,NULL,3,NULL),(118,'survey',24,3,NULL,4,NULL),(119,'survey',24,4,NULL,3,NULL),(120,'survey',24,5,NULL,3,NULL),(121,'survey',25,1,NULL,3,NULL),(122,'survey',25,2,NULL,3,NULL),(123,'survey',25,3,NULL,3,NULL),(124,'survey',25,4,NULL,2,NULL),(125,'survey',25,5,NULL,2,NULL),(126,'survey',26,1,NULL,3,NULL),(127,'survey',26,2,NULL,2,NULL),(128,'survey',26,3,NULL,3,NULL),(129,'survey',26,4,NULL,3,NULL),(130,'survey',26,5,NULL,2,NULL),(131,'survey',27,1,NULL,3,NULL),(132,'survey',27,2,NULL,3,NULL),(133,'survey',27,3,NULL,4,NULL),(134,'survey',27,4,NULL,4,NULL),(135,'survey',27,5,NULL,3,NULL),(136,'survey',28,1,NULL,3,NULL),(137,'survey',28,2,NULL,2,NULL),(138,'survey',28,3,NULL,3,NULL),(139,'survey',28,4,NULL,2,NULL),(140,'survey',28,5,NULL,2,NULL),(141,'survey',29,1,NULL,3,NULL),(142,'survey',29,2,NULL,3,NULL),(143,'survey',29,3,NULL,4,NULL),(144,'survey',29,4,NULL,3,NULL),(145,'survey',29,5,NULL,3,NULL),(146,'survey',30,1,NULL,2,NULL),(147,'survey',30,2,NULL,2,NULL),(148,'survey',30,3,NULL,3,NULL),(149,'survey',30,4,NULL,3,NULL),(150,'survey',30,5,NULL,2,NULL),(151,'survey',31,1,NULL,2,NULL),(152,'survey',31,2,NULL,2,NULL),(153,'survey',31,3,NULL,3,NULL),(154,'survey',31,4,NULL,3,NULL),(155,'survey',31,5,NULL,2,NULL),(156,'survey',32,1,NULL,3,NULL),(157,'survey',32,2,NULL,3,NULL),(158,'survey',32,3,NULL,3,NULL),(159,'survey',32,4,NULL,3,NULL),(160,'survey',32,5,NULL,3,NULL),(161,'survey',33,1,NULL,3,NULL),(162,'survey',33,2,NULL,2,NULL),(163,'survey',33,3,NULL,3,NULL),(164,'survey',33,4,NULL,3,NULL),(165,'survey',33,5,NULL,2,NULL),(166,'survey',34,1,NULL,3,NULL),(167,'survey',34,2,NULL,3,NULL),(168,'survey',34,3,NULL,3,NULL),(169,'survey',34,4,NULL,3,NULL),(170,'survey',34,5,NULL,2,NULL),(171,'survey',35,1,NULL,2,NULL),(172,'survey',35,2,NULL,3,NULL),(173,'survey',35,3,NULL,3,NULL),(174,'survey',35,4,NULL,2,NULL),(175,'survey',35,5,NULL,2,NULL),(176,'survey',36,1,NULL,3,NULL),(177,'survey',36,2,NULL,2,NULL),(178,'survey',36,3,NULL,3,NULL),(179,'survey',36,4,NULL,3,NULL),(180,'survey',36,5,NULL,2,NULL),(181,'survey',37,1,NULL,4,NULL),(182,'survey',37,2,NULL,2,NULL),(183,'survey',37,3,NULL,4,NULL),(184,'survey',37,4,NULL,3,NULL),(185,'survey',37,5,NULL,3,NULL),(186,'survey',38,1,NULL,2,NULL),(187,'survey',38,2,NULL,3,NULL),(188,'survey',38,3,NULL,2,NULL),(189,'survey',38,4,NULL,3,NULL),(190,'survey',38,5,NULL,2,NULL),(191,'survey',39,1,NULL,3,NULL),(192,'survey',39,2,NULL,3,NULL),(193,'survey',39,3,NULL,3,NULL),(194,'survey',39,4,NULL,3,NULL),(195,'survey',39,5,NULL,2,NULL),(196,'survey',40,1,NULL,3,NULL),(197,'survey',40,2,NULL,3,NULL),(198,'survey',40,3,NULL,4,NULL),(199,'survey',40,4,NULL,3,NULL),(200,'survey',40,5,NULL,3,NULL),(201,'survey',41,1,NULL,3,NULL),(202,'survey',41,2,NULL,2,NULL),(203,'survey',41,3,NULL,3,NULL),(204,'survey',41,4,NULL,2,NULL),(205,'survey',41,5,NULL,2,NULL),(206,'survey',42,1,NULL,3,NULL),(207,'survey',42,2,NULL,2,NULL),(208,'survey',42,3,NULL,3,NULL),(209,'survey',42,4,NULL,3,NULL),(210,'survey',42,5,NULL,2,NULL),(211,'survey',43,1,NULL,3,NULL),(212,'survey',43,2,NULL,3,NULL),(213,'survey',43,3,NULL,3,NULL),(214,'survey',43,4,NULL,3,NULL),(215,'survey',43,5,NULL,2,NULL),(216,'survey',44,1,NULL,4,NULL),(217,'survey',44,2,NULL,4,NULL),(218,'survey',44,3,NULL,4,NULL),(219,'survey',44,4,NULL,4,NULL),(220,'survey',44,5,NULL,3,NULL),(221,'survey',45,1,NULL,3,NULL),(222,'survey',45,2,NULL,2,NULL),(223,'survey',45,3,NULL,3,NULL),(224,'survey',45,4,NULL,3,NULL),(225,'survey',45,5,NULL,2,NULL),(226,'survey',46,1,NULL,3,NULL),(227,'survey',46,2,NULL,3,NULL),(228,'survey',46,3,NULL,3,NULL),(229,'survey',46,4,NULL,3,NULL),(230,'survey',46,5,NULL,2,NULL),(231,'survey',47,1,NULL,4,NULL),(232,'survey',47,2,NULL,3,NULL),(233,'survey',47,3,NULL,3,NULL),(234,'survey',47,4,NULL,3,NULL),(235,'survey',47,5,NULL,3,NULL),(236,'survey',48,1,NULL,3,NULL),(237,'survey',48,2,NULL,2,NULL),(238,'survey',48,3,NULL,3,NULL),(239,'survey',48,4,NULL,2,NULL),(240,'survey',48,5,NULL,2,NULL),(241,'survey',49,1,NULL,3,NULL),(242,'survey',49,2,NULL,3,NULL),(243,'survey',49,3,NULL,3,NULL),(244,'survey',49,4,NULL,3,NULL),(245,'survey',49,5,NULL,2,NULL),(246,'survey',50,1,NULL,3,NULL),(247,'survey',50,2,NULL,3,NULL),(248,'survey',50,3,NULL,4,NULL),(249,'survey',50,4,NULL,3,NULL),(250,'survey',50,5,NULL,3,NULL),(251,'survey',51,1,NULL,4,NULL),(252,'survey',51,2,NULL,3,NULL),(253,'survey',51,3,NULL,4,NULL),(254,'survey',51,4,NULL,4,NULL),(255,'survey',51,5,NULL,3,NULL),(256,'survey',52,1,NULL,2,NULL),(257,'survey',52,2,NULL,2,NULL),(258,'survey',52,3,NULL,3,NULL),(259,'survey',52,4,NULL,3,NULL),(260,'survey',52,5,NULL,2,NULL),(261,'survey',53,1,NULL,3,NULL),(262,'survey',53,2,NULL,2,NULL),(263,'survey',53,3,NULL,4,NULL),(264,'survey',53,4,NULL,3,NULL),(265,'survey',53,5,NULL,2,NULL),(266,'survey',54,1,NULL,4,NULL),(267,'survey',54,2,NULL,3,NULL),(268,'survey',54,3,NULL,3,NULL),(269,'survey',54,4,NULL,3,NULL),(270,'survey',54,5,NULL,2,NULL),(271,'survey',55,1,NULL,4,NULL),(272,'survey',55,2,NULL,3,NULL),(273,'survey',55,3,NULL,4,NULL),(274,'survey',55,4,NULL,4,NULL),(275,'survey',55,5,NULL,3,NULL),(276,'survey',56,1,NULL,3,NULL),(277,'survey',56,2,NULL,3,NULL),(278,'survey',56,3,NULL,3,NULL),(279,'survey',56,4,NULL,3,NULL),(280,'survey',56,5,NULL,2,NULL),(281,'survey',57,1,NULL,3,NULL),(282,'survey',57,2,NULL,2,NULL),(283,'survey',57,3,NULL,3,NULL),(284,'survey',57,4,NULL,3,NULL),(285,'survey',57,5,NULL,2,NULL),(286,'survey',58,1,NULL,4,NULL),(287,'survey',58,2,NULL,4,NULL),(288,'survey',58,3,NULL,4,NULL),(289,'survey',58,4,NULL,4,NULL),(290,'survey',58,5,NULL,3,NULL),(291,'survey',59,1,NULL,3,NULL),(292,'survey',59,2,NULL,2,NULL),(293,'survey',59,3,NULL,3,NULL),(294,'survey',59,4,NULL,3,NULL),(295,'survey',59,5,NULL,2,NULL),(296,'survey',60,1,NULL,3,NULL),(297,'survey',60,2,NULL,3,NULL),(298,'survey',60,3,NULL,4,NULL),(299,'survey',60,4,NULL,3,NULL),(300,'survey',60,5,NULL,2,NULL),(301,'survey',61,1,NULL,4,NULL),(302,'survey',61,2,NULL,3,NULL),(303,'survey',61,3,NULL,3,NULL),(304,'survey',61,4,NULL,3,NULL),(305,'survey',61,5,NULL,2,NULL),(306,'survey',62,1,NULL,4,NULL),(307,'survey',62,2,NULL,4,NULL),(308,'survey',62,3,NULL,4,NULL),(309,'survey',62,4,NULL,4,NULL),(310,'survey',62,5,NULL,3,NULL),(311,'survey',63,1,NULL,2,NULL),(312,'survey',63,2,NULL,2,NULL),(313,'survey',63,3,NULL,3,NULL),(314,'survey',63,4,NULL,3,NULL),(315,'survey',63,5,NULL,2,NULL),(316,'survey',64,1,NULL,3,NULL),(317,'survey',64,2,NULL,3,NULL),(318,'survey',64,3,NULL,3,NULL),(319,'survey',64,4,NULL,3,NULL),(320,'survey',64,5,NULL,2,NULL),(321,'survey',65,1,NULL,4,NULL),(322,'survey',65,2,NULL,3,NULL),(323,'survey',65,3,NULL,4,NULL),(324,'survey',65,4,NULL,4,NULL),(325,'survey',65,5,NULL,3,NULL),(326,'survey',66,1,NULL,2,NULL),(327,'survey',66,2,NULL,2,NULL),(328,'survey',66,3,NULL,3,NULL),(329,'survey',66,4,NULL,2,NULL),(330,'survey',66,5,NULL,2,NULL),(331,'survey',67,1,NULL,3,NULL),(332,'survey',67,2,NULL,3,NULL),(333,'survey',67,3,NULL,3,NULL),(334,'survey',67,4,NULL,2,NULL),(335,'survey',67,5,NULL,2,NULL),(336,'survey',68,1,NULL,4,NULL),(337,'survey',68,2,NULL,3,NULL),(338,'survey',68,3,NULL,4,NULL),(339,'survey',68,4,NULL,4,NULL),(340,'survey',68,5,NULL,3,NULL),(341,'survey',69,1,NULL,3,NULL),(342,'survey',69,2,NULL,2,NULL),(343,'survey',69,3,NULL,3,NULL),(344,'survey',69,4,NULL,3,NULL),(345,'survey',69,5,NULL,2,NULL),(346,'survey',70,1,NULL,3,NULL),(347,'survey',70,2,NULL,3,NULL),(348,'survey',70,3,NULL,3,NULL),(349,'survey',70,4,NULL,3,NULL),(350,'survey',70,5,NULL,3,NULL),(351,'survey',71,1,NULL,3,NULL),(352,'survey',71,2,NULL,3,NULL),(353,'survey',71,3,NULL,3,NULL),(354,'survey',71,4,NULL,4,NULL),(355,'survey',71,5,NULL,2,NULL),(356,'survey',72,1,NULL,4,NULL),(357,'survey',72,2,NULL,4,NULL),(358,'survey',72,3,NULL,5,NULL),(359,'survey',72,4,NULL,4,NULL),(360,'survey',72,5,NULL,3,NULL),(361,'survey',73,1,NULL,3,NULL),(362,'survey',73,2,NULL,3,NULL),(363,'survey',73,3,NULL,3,NULL),(364,'survey',73,4,NULL,3,NULL),(365,'survey',73,5,NULL,2,NULL),(366,'survey',74,1,NULL,3,NULL),(367,'survey',74,2,NULL,2,NULL),(368,'survey',74,3,NULL,3,NULL),(369,'survey',74,4,NULL,3,NULL),(370,'survey',74,5,NULL,2,NULL),(371,'survey',75,1,NULL,4,NULL),(372,'survey',75,2,NULL,3,NULL),(373,'survey',75,3,NULL,4,NULL),(374,'survey',75,4,NULL,3,NULL),(375,'survey',75,5,NULL,3,NULL),(376,'survey',76,1,NULL,4,NULL),(377,'survey',76,2,NULL,4,NULL),(378,'survey',76,3,NULL,4,NULL),(379,'survey',76,4,NULL,4,NULL),(380,'survey',76,5,NULL,3,NULL),(381,'satisfaction',1,6,NULL,5,NULL),(382,'satisfaction',1,7,NULL,5,NULL),(383,'satisfaction',1,8,NULL,5,NULL),(384,'satisfaction',1,9,NULL,5,NULL),(385,'satisfaction',1,10,NULL,5,NULL),(386,'survey',77,19,NULL,1,NULL),(387,'survey',77,20,NULL,2,NULL),(388,'survey',77,21,6,NULL,NULL),(389,'survey',78,19,NULL,2,NULL),(390,'survey',78,20,NULL,4,NULL),(391,'survey',78,21,6,NULL,NULL),(392,'survey',79,19,NULL,1,NULL),(393,'survey',79,20,NULL,2,NULL),(394,'survey',79,21,6,NULL,NULL);
/*!40000 ALTER TABLE `evl_answers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `evl_form_activity`
--

DROP TABLE IF EXISTS `evl_form_activity`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `evl_form_activity` (
  `form_id` bigint unsigned NOT NULL,
  `activity_id` bigint unsigned NOT NULL,
  `slot` enum('registration','post_survey') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`form_id`,`activity_id`,`slot`) USING BTREE,
  KEY `evl_form_activity_activity_id_foreign` (`activity_id`) USING BTREE,
  CONSTRAINT `evl_form_activity_activity_id_foreign` FOREIGN KEY (`activity_id`) REFERENCES `act_activities` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `evl_form_activity_form_id_foreign` FOREIGN KEY (`form_id`) REFERENCES `evl_forms` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `evl_form_activity`
--

LOCK TABLES `evl_form_activity` WRITE;
/*!40000 ALTER TABLE `evl_form_activity` DISABLE KEYS */;
INSERT INTO `evl_form_activity` VALUES (2,14,'post_survey'),(3,14,'registration'),(3,15,'registration'),(3,16,'registration'),(3,17,'registration'),(3,21,'registration'),(13,25,'post_survey'),(13,27,'post_survey');
/*!40000 ALTER TABLE `evl_form_activity` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `evl_form_fields`
--

DROP TABLE IF EXISTS `evl_form_fields`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `evl_form_fields` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `form_id` bigint unsigned NOT NULL,
  `field_key` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_enabled` tinyint(1) NOT NULL DEFAULT '1',
  `is_required` tinyint(1) NOT NULL DEFAULT '0',
  `sort_order` smallint unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `evl_form_fields_form_id_field_key_unique` (`form_id`,`field_key`),
  KEY `evl_form_fields_form_id_sort_order_index` (`form_id`,`sort_order`),
  CONSTRAINT `evl_form_fields_form_id_foreign` FOREIGN KEY (`form_id`) REFERENCES `evl_forms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `evl_form_fields`
--

LOCK TABLES `evl_form_fields` WRITE;
/*!40000 ALTER TABLE `evl_form_fields` DISABLE KEYS */;
INSERT INTO `evl_form_fields` VALUES (1,3,'name',1,1,1,'2026-08-13 10:43:47','2026-08-13 10:43:47'),(2,3,'phone',1,1,2,'2026-08-13 10:43:47','2026-08-13 10:43:47'),(3,3,'email',0,0,3,'2026-08-13 10:43:47','2026-08-13 10:43:47'),(4,3,'gender',0,0,4,'2026-08-13 10:43:47','2026-08-13 10:43:47'),(5,3,'age_range',1,0,5,'2026-08-13 10:43:47','2026-08-13 10:43:47'),(6,3,'occupation',0,0,6,'2026-08-13 10:43:47','2026-08-13 10:43:47'),(7,3,'source_channel',0,0,7,'2026-08-13 10:43:47','2026-08-13 10:43:47'),(8,3,'interests',0,0,8,'2026-08-13 10:43:47','2026-08-13 10:43:47'),(9,3,'pdpa',1,1,9,'2026-08-13 10:43:47','2026-08-13 10:43:47');
/*!40000 ALTER TABLE `evl_form_fields` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `evl_forms`
--

DROP TABLE IF EXISTS `evl_forms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `evl_forms` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `type` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ฉบับร่าง',
  `is_anonymous` tinyint(1) NOT NULL DEFAULT '0',
  `registration_mode` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `max_participants` tinyint unsigned DEFAULT NULL,
  `published_at` timestamp NULL DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `evl_forms_code_unique` (`code`) USING BTREE,
  KEY `evl_forms_created_by_foreign` (`created_by`) USING BTREE,
  KEY `evl_forms_status_index` (`status`) USING BTREE,
  KEY `evl_forms_updated_by_foreign` (`updated_by`),
  KEY `evl_forms_type_index` (`type`),
  KEY `evl_forms_type_status_index` (`type`,`status`),
  CONSTRAINT `evl_forms_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `evl_forms_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `evl_forms`
--

LOCK TABLES `evl_forms` WRITE;
/*!40000 ALTER TABLE `evl_forms` DISABLE KEYS */;
INSERT INTO `evl_forms` VALUES (1,'DEMO-FRM-01','แบบติดตามสุขภาพและพฤติกรรมการบริโภค',NULL,'health_follow_up','active',0,NULL,NULL,'2026-08-13 10:27:54',NULL,NULL,'2026-08-11 20:54:29','2026-08-11 20:54:29'),(2,'EVL-26-0002','แบบประเมินความพึงพอใจต่อกิจกรรม  (มาตรฐาน)','ใช้เวลาเพียง 1 นาที เพื่อช่วยให้เราพัฒนากิจกรรมให้ดียิ่งขึ้น','post_activity','active',1,NULL,NULL,'2026-08-13 10:42:13',6,6,'2026-08-13 10:42:13','2026-08-13 10:42:13'),(3,'EVL-26-0003','แบบลงทะเบียนเข้าร่วมกิจกรรม','กรุณากรอกข้อมูลเพื่อสำรองสิทธิ์และยืนยันการเข้าร่วมกิจกรรม ข้อมูลของท่านจะใช้สำหรับการติดต่อและการดำเนินกิจกรรมที่เกี่ยวข้อง','registration','active',0,'group',3,NULL,6,6,'2026-08-13 10:43:47','2026-08-13 10:43:47'),(4,'EVL-26-0004','..','..','post_activity','draft',1,NULL,NULL,'2026-08-13 10:55:38',6,6,'2026-08-13 10:55:38','2026-08-13 10:55:38'),(5,'EVL-26-0005','แบบประเมินติดตามพฤติกรรมด้านอาหารและการบริโภค','แบบประเมินนี้ใช้สำหรับติดตามการเปลี่ยนแปลงด้านความรู้ การเข้าถึงอาหาร และพฤติกรรมการบริโภคของผู้เข้าร่วมโครงการ ข้อมูลจะนำไปใช้เพื่อการติดตามและประเมินผลโครงการ','health_follow_up','active',0,NULL,NULL,'2026-08-15 11:08:45',6,6,'2026-08-13 10:58:33','2026-08-15 11:08:45'),(6,'EVL-26-0006','test1','test1','post_activity','active',1,NULL,NULL,'2026-08-15 11:09:10',6,6,'2026-08-15 11:09:10','2026-08-15 11:09:10'),(7,'EVL-26-0007','test2','test2','post_activity','active',1,NULL,NULL,'2026-08-15 11:09:38',6,6,'2026-08-15 11:09:38','2026-08-15 11:09:38'),(8,'EVL-26-0008','แบบประเมินความพึงพอใจต่อกิจกรรม  (มาตรฐาน) (สำเนา)','ใช้เวลาเพียง 1 นาที เพื่อช่วยให้เราพัฒนากิจกรรมให้ดียิ่งขึ้น','post_activity','active',1,NULL,NULL,'2026-08-15 11:14:49',6,6,'2026-08-15 11:14:49','2026-08-15 11:14:49'),(9,'EVL-26-0009','แบบติดตามสุขภาพและพฤติกรรมการบริโภค (สำเนา)',NULL,'health_follow_up','inactive',0,NULL,NULL,NULL,6,6,'2026-08-16 08:00:15','2026-08-16 08:00:15'),(10,'EVL-26-0010','แบบติดตามสุขภาพและพฤติกรรมการบริโภค (สำเนา)',NULL,'health_follow_up','inactive',0,NULL,NULL,NULL,6,6,'2026-08-16 08:00:19','2026-08-16 08:00:19'),(11,'EVL-26-0011','เทส',NULL,'health_follow_up','inactive',0,NULL,NULL,'2026-08-16 08:03:07',6,6,'2026-08-16 08:03:07','2026-08-16 08:03:07'),(13,'EVL-26-0013','แบบประเมินกลุ่มเป้าหมาย The Farm Concept Bearing','ขอขอบคุณทุกคนที่สละเวลามาทำแบบประเมินชุดนี้ค่ะ/ครับ แบบประเมินนี้ใช้เวลาทำเพียง 5 นาที ความคิดเห็นของคุณจะเป็นส่วนสำคัญที่ช่วยให้ทีมงานพัฒนาพื้นที่ The Farm Concept Bearing ให้ดียิ่งขึ้นเพื่อให้เป็นพื้นที่การเรียนรู้อย่างสร้างสรรค์ของทุกคนอย่างแท้จริง :)','post_activity','active',1,NULL,NULL,'2026-08-17 07:17:52',6,6,'2026-08-17 07:15:54','2026-08-17 07:17:52'),(14,'EVL-26-0014','แบบประเมินติดตามพฤติกรรมด้านอาหารและการบริโภค (สำเนา)','แบบประเมินนี้ใช้สำหรับติดตามการเปลี่ยนแปลงด้านความรู้ การเข้าถึงอาหาร และพฤติกรรมการบริโภคของผู้เข้าร่วมโครงการ ข้อมูลจะนำไปใช้เพื่อการติดตามและประเมินผลโครงการ','health_follow_up','active',0,NULL,NULL,'2026-08-17 07:25:47',6,6,'2026-08-17 07:25:47','2026-08-17 07:25:47');
/*!40000 ALTER TABLE `evl_forms` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `evl_question_options`
--

DROP TABLE IF EXISTS `evl_question_options`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `evl_question_options` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `question_id` bigint unsigned NOT NULL,
  `sort_order` smallint unsigned NOT NULL DEFAULT '0',
  `label` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_other` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `evl_question_options_question_id_sort_order_unique` (`question_id`,`sort_order`),
  UNIQUE KEY `evl_question_options_question_id_value_unique` (`question_id`,`value`),
  CONSTRAINT `evl_question_options_question_id_foreign` FOREIGN KEY (`question_id`) REFERENCES `evl_questions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=102 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `evl_question_options`
--

LOCK TABLES `evl_question_options` WRITE;
/*!40000 ALTER TABLE `evl_question_options` DISABLE KEYS */;
INSERT INTO `evl_question_options` VALUES (1,13,1,'แนะนำ','option_1',0,'2026-08-13 10:55:38','2026-08-13 10:55:38'),(2,13,2,'ไม่แน่ใจ','option_2',0,'2026-08-13 10:55:38','2026-08-13 10:55:38'),(3,13,3,'ไม่แนะนำ','option_3',0,'2026-08-13 10:55:38','2026-08-13 10:55:38'),(6,21,1,'ตัวเลือกที่ 1','option_1',0,'2026-08-15 11:08:45','2026-08-15 11:08:45'),(7,21,2,'ตัวเลือกที่ 2','option_2',0,'2026-08-15 11:08:45','2026-08-15 11:08:45'),(8,24,1,'แนะนำ','option_1',0,'2026-08-15 11:09:10','2026-08-15 11:09:10'),(9,24,2,'ไม่แน่ใจ','option_2',0,'2026-08-15 11:09:10','2026-08-15 11:09:10'),(10,24,3,'ไม่แนะนำ','option_3',0,'2026-08-15 11:09:10','2026-08-15 11:09:10'),(11,27,1,'แนะนำ','option_1',0,'2026-08-15 11:09:38','2026-08-15 11:09:38'),(12,27,2,'ไม่แน่ใจ','option_2',0,'2026-08-15 11:09:38','2026-08-15 11:09:38'),(13,27,3,'ไม่แนะนำ','option_3',0,'2026-08-15 11:09:38','2026-08-15 11:09:38'),(14,45,1,'แนะนำ','option_1',0,'2026-08-16 08:03:07','2026-08-16 08:03:07'),(15,45,2,'ไม่แน่ใจ','option_2',0,'2026-08-16 08:03:07','2026-08-16 08:03:07'),(16,45,3,'ไม่แนะนำ','option_3',0,'2026-08-16 08:03:07','2026-08-16 08:03:07'),(72,66,1,'หญิง','option_1',0,'2026-08-17 07:17:52','2026-08-17 07:17:52'),(73,66,2,'ชาย','option_2',0,'2026-08-17 07:17:52','2026-08-17 07:17:52'),(74,66,3,'LGBTQIA+','option_3',0,'2026-08-17 07:17:52','2026-08-17 07:17:52'),(75,66,4,'ไม่ระบุ','option_4',0,'2026-08-17 07:17:52','2026-08-17 07:17:52'),(76,67,1,'แนะนำ','option_1',0,'2026-08-17 07:17:52','2026-08-17 07:17:52'),(77,67,2,'ไม่แน่ใจ','option_2',0,'2026-08-17 07:17:52','2026-08-17 07:17:52'),(78,67,3,'ไม่แนะนำ','option_3',0,'2026-08-17 07:17:52','2026-08-17 07:17:52'),(79,68,1,'ต่ำกว่า 15 ปี','option_1',0,'2026-08-17 07:17:52','2026-08-17 07:17:52'),(80,68,2,'15-18 ปี','option_2',0,'2026-08-17 07:17:52','2026-08-17 07:17:52'),(81,68,3,'19-22 ปี','option_3',0,'2026-08-17 07:17:52','2026-08-17 07:17:52'),(82,68,4,'23-29 ปี','option_4',0,'2026-08-17 07:17:52','2026-08-17 07:17:52'),(83,68,5,'30-39 ปี','option_5',0,'2026-08-17 07:17:52','2026-08-17 07:17:52'),(84,68,6,'40-59 ปี','option_6',0,'2026-08-17 07:17:52','2026-08-17 07:17:52'),(85,68,7,'60 ปีขึ้นไป','option_7',0,'2026-08-17 07:17:52','2026-08-17 07:17:52'),(86,69,1,'นักเรียน/นิสิตนักศึกษา','option_1',0,'2026-08-17 07:17:52','2026-08-17 07:17:52'),(87,69,2,'พนักงานบริษัทเอกชน','option_2',0,'2026-08-17 07:17:52','2026-08-17 07:17:52'),(88,69,3,'ข้าราชการ','option_3',0,'2026-08-17 07:17:52','2026-08-17 07:17:52'),(89,69,4,'พนักงานรัฐวิสาหกิจ','option_4',0,'2026-08-17 07:17:52','2026-08-17 07:17:52'),(90,69,5,'เจ้าของธุรกิจ/ธุรกิจส่วนตัว','option_5',0,'2026-08-17 07:17:52','2026-08-17 07:17:52'),(91,69,6,'อาชีพอิสระ','option_6',0,'2026-08-17 07:17:52','2026-08-17 07:17:52'),(92,69,7,'อื่น ๆ','option_7',1,'2026-08-17 07:17:52','2026-08-17 07:17:52'),(93,71,1,'เยี่ยมชมฟาร์ม','option_1',0,'2026-08-17 07:17:52','2026-08-17 07:17:52'),(94,71,2,'ฟังบรรยายเกี่ยวกับฟาร์มในเมือง','option_2',0,'2026-08-17 07:17:52','2026-08-17 07:17:52'),(95,71,3,'กิจกรรมอาสาสมัคร','option_3',0,'2026-08-17 07:17:52','2026-08-17 07:17:52'),(96,71,4,'เข้าร่วมทำกิจกรรมที่จัด','option_4',0,'2026-08-17 07:17:52','2026-08-17 07:17:52'),(97,71,5,'อื่น ๆ','option_5',1,'2026-08-17 07:17:52','2026-08-17 07:17:52'),(98,77,1,'ตัวเลือกที่ 1','option_1',0,'2026-08-17 07:25:47','2026-08-17 07:25:47'),(99,77,2,'ตัวเลือกที่ 2','option_2',0,'2026-08-17 07:25:47','2026-08-17 07:25:47'),(100,78,1,'ตัวเลือกที่ 1','option_1',0,'2026-08-17 07:25:47','2026-08-17 07:25:47'),(101,78,2,'ตัวเลือกที่ 2','option_2',0,'2026-08-17 07:25:47','2026-08-17 07:25:47');
/*!40000 ALTER TABLE `evl_question_options` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `evl_questions`
--

DROP TABLE IF EXISTS `evl_questions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `evl_questions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `form_id` bigint unsigned NOT NULL,
  `sort_order` smallint unsigned NOT NULL DEFAULT '0',
  `question_type` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `text` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `dimension` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_required` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `evl_questions_form_id_sort_order_index` (`form_id`,`sort_order`) USING BTREE,
  CONSTRAINT `evl_questions_form_id_foreign` FOREIGN KEY (`form_id`) REFERENCES `evl_forms` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=79 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `evl_questions`
--

LOCK TABLES `evl_questions` WRITE;
/*!40000 ALTER TABLE `evl_questions` DISABLE KEYS */;
INSERT INTO `evl_questions` VALUES (1,1,1,'rating','ระดับความรอบรู้ด้านอาหารของท่านในช่วง 3 เดือนที่ผ่านมา','ความรอบรู้ด้านอาหาร',1,'2026-08-11 20:54:29','2026-08-11 20:54:29'),(2,1,2,'rating','ระดับความมั่นคงทางอาหารของท่านในช่วง 3 เดือนที่ผ่านมา','ความมั่นคงทางอาหาร',1,'2026-08-11 20:54:29','2026-08-11 20:54:29'),(3,1,3,'rating','ระดับการเข้าถึงอาหารปลอดภัยของท่านในช่วง 3 เดือนที่ผ่านมา','การเข้าถึงอาหารปลอดภัย',1,'2026-08-11 20:54:29','2026-08-11 20:54:29'),(4,1,4,'rating','ระดับพฤติกรรมการบริโภคของท่านในช่วง 3 เดือนที่ผ่านมา','พฤติกรรมการบริโภค',1,'2026-08-11 20:54:29','2026-08-11 20:54:29'),(5,1,5,'rating','ระดับการผลิตอาหารไว้บริโภคเองของท่านในช่วง 3 เดือนที่ผ่านมา','การผลิตอาหารไว้บริโภคเอง',1,'2026-08-11 20:54:29','2026-08-11 20:54:29'),(6,2,1,'rating','ความพึงพอใจต่อกิจกรรมโดยรวม',NULL,1,'2026-08-13 10:42:13','2026-08-13 10:42:13'),(7,2,2,'rating','เนื้อหาและรูปแบบกิจกรรมน่าสนใจ',NULL,0,'2026-08-13 10:42:13','2026-08-13 10:42:13'),(8,2,3,'rating','วิทยากร/ผู้ดำเนินกิจกรรมถ่ายทอดได้เข้าใจง่าย',NULL,0,'2026-08-13 10:42:13','2026-08-13 10:42:13'),(9,2,4,'rating','ระยะเวลาในการจัดกิจกรรมมีความเหมาะสม',NULL,0,'2026-08-13 10:42:13','2026-08-13 10:42:13'),(10,2,5,'rating','สถานที่และบรรยากาศเหมาะสมกับกิจกรรม',NULL,0,'2026-08-13 10:42:13','2026-08-13 10:42:13'),(11,4,1,'section','ความคิดเห็นต่อกิจกรรม',NULL,0,'2026-08-13 10:55:38','2026-08-13 10:55:38'),(12,4,2,'rating','ความพึงพอใจโดยรวมต่อกิจกรรมนี้',NULL,1,'2026-08-13 10:55:38','2026-08-13 10:55:38'),(13,4,3,'single','จะแนะนำกิจกรรมนี้ให้คนอื่นหรือไม่',NULL,1,'2026-08-13 10:55:38','2026-08-13 10:55:38'),(18,5,1,'section','ความรอบรู้ด้านอาหาร',NULL,0,'2026-08-15 11:08:45','2026-08-15 11:08:45'),(19,5,2,'rating','ฉันสามารถเลือกอาหารที่เหมาะสมและมีประโยชน์ต่อสุขภาพได้',NULL,1,'2026-08-15 11:08:45','2026-08-15 11:08:45'),(20,5,3,'rating','ฉันสามารถอ่านและเข้าใจข้อมูลโภชนาการบนฉลากอาหารได้',NULL,1,'2026-08-15 11:08:45','2026-08-15 11:08:45'),(21,5,4,'single','ฉันรู้ว่าควรเลือกรับประทานอาหารแต่ละประเภทในปริมาณที่เหมาะสม',NULL,0,'2026-08-15 11:08:45','2026-08-15 11:08:45'),(22,6,1,'section','ความคิดเห็นต่อกิจกรรม',NULL,0,'2026-08-15 11:09:10','2026-08-15 11:09:10'),(23,6,2,'rating','ความพึงพอใจโดยรวมต่อกิจกรรมนี้',NULL,1,'2026-08-15 11:09:10','2026-08-15 11:09:10'),(24,6,3,'single','จะแนะนำกิจกรรมนี้ให้คนอื่นหรือไม่',NULL,1,'2026-08-15 11:09:10','2026-08-15 11:09:10'),(25,7,1,'section','ความคิดเห็นต่อกิจกรรม',NULL,0,'2026-08-15 11:09:38','2026-08-15 11:09:38'),(26,7,2,'rating','ความพึงพอใจโดยรวมต่อกิจกรรมนี้',NULL,1,'2026-08-15 11:09:38','2026-08-15 11:09:38'),(27,7,3,'single','จะแนะนำกิจกรรมนี้ให้คนอื่นหรือไม่',NULL,1,'2026-08-15 11:09:38','2026-08-15 11:09:38'),(28,8,1,'rating','ความพึงพอใจต่อกิจกรรมโดยรวม',NULL,1,'2026-08-15 11:14:49','2026-08-15 11:14:49'),(29,8,2,'rating','เนื้อหาและรูปแบบกิจกรรมน่าสนใจ',NULL,0,'2026-08-15 11:14:49','2026-08-15 11:14:49'),(30,8,3,'rating','วิทยากร/ผู้ดำเนินกิจกรรมถ่ายทอดได้เข้าใจง่าย',NULL,0,'2026-08-15 11:14:49','2026-08-15 11:14:49'),(31,8,4,'rating','ระยะเวลาในการจัดกิจกรรมมีความเหมาะสม',NULL,0,'2026-08-15 11:14:49','2026-08-15 11:14:49'),(32,8,5,'rating','สถานที่และบรรยากาศเหมาะสมกับกิจกรรม',NULL,0,'2026-08-15 11:14:49','2026-08-15 11:14:49'),(33,9,1,'rating','ระดับความรอบรู้ด้านอาหารของท่านในช่วง 3 เดือนที่ผ่านมา','ความรอบรู้ด้านอาหาร',1,'2026-08-16 08:00:15','2026-08-16 08:00:15'),(34,9,2,'rating','ระดับความมั่นคงทางอาหารของท่านในช่วง 3 เดือนที่ผ่านมา','ความมั่นคงทางอาหาร',1,'2026-08-16 08:00:15','2026-08-16 08:00:15'),(35,9,3,'rating','ระดับการเข้าถึงอาหารปลอดภัยของท่านในช่วง 3 เดือนที่ผ่านมา','การเข้าถึงอาหารปลอดภัย',1,'2026-08-16 08:00:15','2026-08-16 08:00:15'),(36,9,4,'rating','ระดับพฤติกรรมการบริโภคของท่านในช่วง 3 เดือนที่ผ่านมา','พฤติกรรมการบริโภค',1,'2026-08-16 08:00:15','2026-08-16 08:00:15'),(37,9,5,'rating','ระดับการผลิตอาหารไว้บริโภคเองของท่านในช่วง 3 เดือนที่ผ่านมา','การผลิตอาหารไว้บริโภคเอง',1,'2026-08-16 08:00:15','2026-08-16 08:00:15'),(38,10,1,'rating','ระดับความรอบรู้ด้านอาหารของท่านในช่วง 3 เดือนที่ผ่านมา','ความรอบรู้ด้านอาหาร',1,'2026-08-16 08:00:19','2026-08-16 08:00:19'),(39,10,2,'rating','ระดับความมั่นคงทางอาหารของท่านในช่วง 3 เดือนที่ผ่านมา','ความมั่นคงทางอาหาร',1,'2026-08-16 08:00:19','2026-08-16 08:00:19'),(40,10,3,'rating','ระดับการเข้าถึงอาหารปลอดภัยของท่านในช่วง 3 เดือนที่ผ่านมา','การเข้าถึงอาหารปลอดภัย',1,'2026-08-16 08:00:19','2026-08-16 08:00:19'),(41,10,4,'rating','ระดับพฤติกรรมการบริโภคของท่านในช่วง 3 เดือนที่ผ่านมา','พฤติกรรมการบริโภค',1,'2026-08-16 08:00:19','2026-08-16 08:00:19'),(42,10,5,'rating','ระดับการผลิตอาหารไว้บริโภคเองของท่านในช่วง 3 เดือนที่ผ่านมา','การผลิตอาหารไว้บริโภคเอง',1,'2026-08-16 08:00:19','2026-08-16 08:00:19'),(43,11,1,'section','ความคิดเห็นต่อกิจกรรม',NULL,0,'2026-08-16 08:03:07','2026-08-16 08:03:07'),(44,11,2,'rating','ความพึงพอใจโดยรวมต่อกิจกรรมนี้',NULL,1,'2026-08-16 08:03:07','2026-08-16 08:03:07'),(45,11,3,'single','จะแนะนำกิจกรรมนี้ให้คนอื่นหรือไม่',NULL,1,'2026-08-16 08:03:07','2026-08-16 08:03:07'),(65,13,1,'text','ชื่อจริง นามสกุล',NULL,0,'2026-08-17 07:17:52','2026-08-17 07:17:52'),(66,13,2,'single','เพศ',NULL,0,'2026-08-17 07:17:52','2026-08-17 07:17:52'),(67,13,3,'single','จะแนะนำกิจกรรมนี้ให้คนอื่นหรือไม่',NULL,1,'2026-08-17 07:17:52','2026-08-17 07:17:52'),(68,13,4,'single','อายุ',NULL,0,'2026-08-17 07:17:52','2026-08-17 07:17:52'),(69,13,5,'single','อาชีพ',NULL,0,'2026-08-17 07:17:52','2026-08-17 07:17:52'),(70,13,6,'text','วันที่ที่มา The Farm Concept Bearing (หากมาหลายวันให้เลือกวันแรกที่มา)',NULL,0,'2026-08-17 07:17:52','2026-08-17 07:17:52'),(71,13,7,'single','กิจกรรมที่มาทำที่ The Farm Concept Bearing (ตอบได้มากกว่า 1 ข้อ)',NULL,0,'2026-08-17 07:17:52','2026-08-17 07:17:52'),(72,13,8,'rating','ความพึงพอใจโดยรวมในการมาทำกิจกรรมที่ The Farm Concept Bearing',NULL,1,'2026-08-17 07:17:52','2026-08-17 07:17:52'),(73,13,9,'text','ข้อเสนอแนะ ข้อติชม ไอเดียกิจกรรม ความประทับใจ',NULL,0,'2026-08-17 07:17:52','2026-08-17 07:17:52'),(74,14,1,'section','ความรอบรู้ด้านอาหาร',NULL,0,'2026-08-17 07:25:47','2026-08-17 07:25:47'),(75,14,2,'rating','ฉันสามารถเลือกอาหารที่เหมาะสมและมีประโยชน์ต่อสุขภาพได้',NULL,1,'2026-08-17 07:25:47','2026-08-17 07:25:47'),(76,14,3,'rating','ฉันสามารถอ่านและเข้าใจข้อมูลโภชนาการบนฉลากอาหารได้',NULL,1,'2026-08-17 07:25:47','2026-08-17 07:25:47'),(77,14,4,'single','ฉันรู้ว่าควรเลือกรับประทานอาหารแต่ละประเภทในปริมาณที่เหมาะสม',NULL,0,'2026-08-17 07:25:47','2026-08-17 07:25:47'),(78,14,5,'single','..',NULL,0,'2026-08-17 07:25:47','2026-08-17 07:25:47');
/*!40000 ALTER TABLE `evl_questions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `evl_round_batch_members`
--

DROP TABLE IF EXISTS `evl_round_batch_members`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `evl_round_batch_members` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `batch_id` bigint unsigned NOT NULL,
  `cohort_profile_id` bigint unsigned NOT NULL,
  `follow_up_round_id` bigint unsigned NOT NULL,
  `notified_at` timestamp NULL DEFAULT NULL,
  `notify_channel` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notify_result` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `offline_kind` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `offline_note` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `offline_at` timestamp NULL DEFAULT NULL,
  `offline_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `evl_round_batch_members_batch_id_follow_up_round_id_unique` (`batch_id`,`follow_up_round_id`) USING BTREE,
  KEY `evl_round_batch_members_follow_up_round_id_foreign` (`follow_up_round_id`) USING BTREE,
  KEY `evl_round_batch_members_offline_by_foreign` (`offline_by`) USING BTREE,
  KEY `evl_round_batch_members_cohort_profile_id_index` (`cohort_profile_id`) USING BTREE,
  CONSTRAINT `evl_round_batch_members_batch_id_foreign` FOREIGN KEY (`batch_id`) REFERENCES `evl_round_batches` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `evl_round_batch_members_cohort_profile_id_foreign` FOREIGN KEY (`cohort_profile_id`) REFERENCES `ptp_cohort_profiles` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `evl_round_batch_members_follow_up_round_id_foreign` FOREIGN KEY (`follow_up_round_id`) REFERENCES `ptp_follow_up_rounds` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `evl_round_batch_members_offline_by_foreign` FOREIGN KEY (`offline_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `evl_round_batch_members`
--

LOCK TABLES `evl_round_batch_members` WRITE;
/*!40000 ALTER TABLE `evl_round_batch_members` DISABLE KEYS */;
INSERT INTO `evl_round_batch_members` VALUES (1,1,30,86,NULL,'none','ไม่มีช่องทางแจ้งเตือน',NULL,NULL,NULL,NULL,'2026-08-15 11:32:35','2026-08-15 11:32:35'),(2,1,31,90,NULL,'none','ไม่มีช่องทางแจ้งเตือน',NULL,NULL,NULL,NULL,'2026-08-15 11:32:35','2026-08-15 11:32:35'),(3,2,30,87,NULL,'none','ไม่มีช่องทางแจ้งเตือน',NULL,NULL,NULL,NULL,'2026-08-15 11:34:03','2026-08-15 11:34:03'),(4,3,33,100,'2026-08-16 11:35:09','line','ส่งสำเร็จ',NULL,NULL,NULL,NULL,'2026-08-16 11:35:09','2026-08-16 11:35:09');
/*!40000 ALTER TABLE `evl_round_batch_members` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `evl_round_batch_target_group`
--

DROP TABLE IF EXISTS `evl_round_batch_target_group`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `evl_round_batch_target_group` (
  `batch_id` bigint unsigned NOT NULL,
  `target_group_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`batch_id`,`target_group_id`),
  KEY `evl_round_batch_target_group_target_group_id_foreign` (`target_group_id`),
  CONSTRAINT `evl_round_batch_target_group_batch_id_foreign` FOREIGN KEY (`batch_id`) REFERENCES `evl_round_batches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `evl_round_batch_target_group_target_group_id_foreign` FOREIGN KEY (`target_group_id`) REFERENCES `mst_target_groups` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `evl_round_batch_target_group`
--

LOCK TABLES `evl_round_batch_target_group` WRITE;
/*!40000 ALTER TABLE `evl_round_batch_target_group` DISABLE KEYS */;
/*!40000 ALTER TABLE `evl_round_batch_target_group` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `evl_round_batches`
--

DROP TABLE IF EXISTS `evl_round_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `evl_round_batches` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `due_from` date NOT NULL,
  `due_to` date NOT NULL,
  `form_id` bigint unsigned DEFAULT NULL,
  `notification_template` text COLLATE utf8mb4_unicode_ci,
  `state` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'รอเริ่ม',
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `evl_round_batches_code_unique` (`code`) USING BTREE,
  KEY `evl_round_batches_form_id_foreign` (`form_id`) USING BTREE,
  KEY `evl_round_batches_created_by_foreign` (`created_by`) USING BTREE,
  KEY `evl_round_batches_state_index` (`state`) USING BTREE,
  KEY `evl_round_batches_due_from_due_to_index` (`due_from`,`due_to`) USING BTREE,
  CONSTRAINT `evl_round_batches_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `evl_round_batches_form_id_foreign` FOREIGN KEY (`form_id`) REFERENCES `evl_forms` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `evl_round_batches`
--

LOCK TABLES `evl_round_batches` WRITE;
/*!40000 ALTER TABLE `evl_round_batches` DISABLE KEYS */;
INSERT INTO `evl_round_batches` VALUES (1,'RBT-0001','เทส','2026-08-15','2026-08-15',1,'สวัสดีคุณ {ชื่อ} 🌱\nถึงเวลาทำแบบประเมินติดตาม {รอบ} แล้ว\nกรุณาตอบภายในวันที่ {วันครบกำหนด}','กำลังดำเนินการ',6,'2026-08-15 11:32:35','2026-08-15 11:32:35'),(2,'RBT-0002','เทส2','2026-10-02','2027-01-09',1,'สวัสดีคุณ {ชื่อ} 🌱\nถึงเวลาทำแบบประเมินติดตาม {รอบ} แล้ว\nกรุณาตอบภายในวันที่ {วันครบกำหนด}','กำลังดำเนินการ',6,'2026-08-15 11:34:03','2026-08-15 11:34:03'),(3,'RBT-0003','เทส','2026-08-16','2028-01-07',1,'ช่วงนี้เป็นยังไงบ้างคะ ?\n\nมีแบบประเมินสั้น ๆ ให้แวะมาเช็กสุขภาวะของตัวเอง\nใช้เวลาไม่นาน สะดวกเมื่อไหร่ค่อยเข้ามาทำได้ค่ะ 💚','กำลังดำเนินการ',6,'2026-08-16 11:35:09','2026-08-16 11:35:09');
/*!40000 ALTER TABLE `evl_round_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `evl_satisfaction_receipts`
--

DROP TABLE IF EXISTS `evl_satisfaction_receipts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `evl_satisfaction_receipts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `form_id` bigint unsigned NOT NULL,
  `registration_id` bigint unsigned NOT NULL,
  `submitted_at` timestamp NOT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `evl_satisfaction_receipts_form_id_registration_id_unique` (`form_id`,`registration_id`) USING BTREE,
  KEY `evl_satisfaction_receipts_registration_id_foreign` (`registration_id`) USING BTREE,
  CONSTRAINT `evl_satisfaction_receipts_form_id_foreign` FOREIGN KEY (`form_id`) REFERENCES `evl_forms` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `evl_satisfaction_receipts_registration_id_foreign` FOREIGN KEY (`registration_id`) REFERENCES `act_registrations` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `evl_satisfaction_receipts`
--

LOCK TABLES `evl_satisfaction_receipts` WRITE;
/*!40000 ALTER TABLE `evl_satisfaction_receipts` DISABLE KEYS */;
/*!40000 ALTER TABLE `evl_satisfaction_receipts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `evl_satisfaction_responses`
--

DROP TABLE IF EXISTS `evl_satisfaction_responses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `evl_satisfaction_responses` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `form_id` bigint unsigned NOT NULL,
  `activity_id` bigint unsigned NOT NULL,
  `activity_round_id` bigint unsigned DEFAULT NULL,
  `submitted_at` timestamp NOT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `evl_satisfaction_responses_form_id_foreign` (`form_id`) USING BTREE,
  KEY `evl_satisfaction_responses_activity_round_id_foreign` (`activity_round_id`) USING BTREE,
  KEY `evl_satisfaction_responses_activity_id_submitted_at_index` (`activity_id`,`submitted_at`) USING BTREE,
  CONSTRAINT `evl_satisfaction_responses_activity_id_foreign` FOREIGN KEY (`activity_id`) REFERENCES `act_activities` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `evl_satisfaction_responses_activity_round_id_foreign` FOREIGN KEY (`activity_round_id`) REFERENCES `act_activity_rounds` (`id`) ON DELETE SET NULL ON UPDATE RESTRICT,
  CONSTRAINT `evl_satisfaction_responses_form_id_foreign` FOREIGN KEY (`form_id`) REFERENCES `evl_forms` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `evl_satisfaction_responses`
--

LOCK TABLES `evl_satisfaction_responses` WRITE;
/*!40000 ALTER TABLE `evl_satisfaction_responses` DISABLE KEYS */;
INSERT INTO `evl_satisfaction_responses` VALUES (1,2,14,NULL,'2026-08-15 10:49:23');
/*!40000 ALTER TABLE `evl_satisfaction_responses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `evl_survey_responses`
--

DROP TABLE IF EXISTS `evl_survey_responses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `evl_survey_responses` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `form_id` bigint unsigned NOT NULL,
  `participant_id` bigint unsigned NOT NULL,
  `submitted_by_participant_id` bigint unsigned DEFAULT NULL,
  `cohort_round_id` bigint unsigned NOT NULL,
  `submitted_at` timestamp NOT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `evl_survey_responses_cohort_round_id_unique` (`cohort_round_id`) USING BTREE,
  KEY `evl_survey_responses_form_id_foreign` (`form_id`) USING BTREE,
  KEY `evl_survey_responses_participant_id_submitted_at_index` (`participant_id`,`submitted_at`) USING BTREE,
  KEY `evl_survey_responses_submitted_by_participant_id_foreign` (`submitted_by_participant_id`),
  CONSTRAINT `evl_survey_responses_cohort_round_id_foreign` FOREIGN KEY (`cohort_round_id`) REFERENCES `ptp_follow_up_rounds` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `evl_survey_responses_form_id_foreign` FOREIGN KEY (`form_id`) REFERENCES `evl_forms` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `evl_survey_responses_participant_id_foreign` FOREIGN KEY (`participant_id`) REFERENCES `ptp_participants` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `evl_survey_responses_submitted_by_participant_id_foreign` FOREIGN KEY (`submitted_by_participant_id`) REFERENCES `ptp_participants` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=80 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `evl_survey_responses`
--

LOCK TABLES `evl_survey_responses` WRITE;
/*!40000 ALTER TABLE `evl_survey_responses` DISABLE KEYS */;
INSERT INTO `evl_survey_responses` VALUES (1,1,2,NULL,2,'2026-07-15 03:30:00'),(2,1,3,NULL,3,'2026-06-25 03:30:00'),(3,1,4,NULL,4,'2026-06-05 03:30:00'),(4,1,5,NULL,5,'2026-05-16 03:30:00'),(5,1,5,NULL,6,'2026-08-14 03:30:00'),(6,1,6,NULL,7,'2026-04-26 03:30:00'),(7,1,6,NULL,8,'2026-07-25 03:30:00'),(8,1,7,NULL,9,'2026-04-06 03:30:00'),(9,1,7,NULL,10,'2026-07-05 03:30:00'),(10,1,8,NULL,11,'2026-03-17 03:30:00'),(11,1,8,NULL,12,'2026-06-15 03:30:00'),(12,1,9,NULL,13,'2026-02-25 03:30:00'),(13,1,9,NULL,14,'2026-05-26 03:30:00'),(14,1,10,NULL,15,'2026-02-05 03:30:00'),(15,1,10,NULL,16,'2026-05-06 03:30:00'),(16,1,11,NULL,18,'2026-01-16 03:30:00'),(17,1,11,NULL,19,'2026-04-16 03:30:00'),(18,1,11,NULL,20,'2026-07-15 03:30:00'),(19,1,12,NULL,21,'2025-12-27 03:30:00'),(20,1,12,NULL,22,'2026-03-27 03:30:00'),(21,1,12,NULL,23,'2026-06-25 03:30:00'),(22,1,13,NULL,24,'2025-12-07 03:30:00'),(23,1,13,NULL,25,'2026-03-07 03:30:00'),(24,1,13,NULL,26,'2026-06-05 03:30:00'),(25,1,14,NULL,27,'2025-11-17 03:30:00'),(26,1,14,NULL,28,'2026-02-15 03:30:00'),(27,1,14,NULL,29,'2026-05-16 03:30:00'),(28,1,15,NULL,30,'2025-10-28 03:30:00'),(29,1,15,NULL,32,'2026-04-26 03:30:00'),(30,1,16,NULL,33,'2025-10-08 03:30:00'),(31,1,16,NULL,34,'2026-01-06 03:30:00'),(32,1,16,NULL,35,'2026-04-06 03:30:00'),(33,1,17,NULL,36,'2025-09-18 03:30:00'),(34,1,17,NULL,37,'2025-12-17 03:30:00'),(35,1,18,NULL,39,'2025-08-29 03:30:00'),(36,1,18,NULL,40,'2025-11-27 03:30:00'),(37,1,18,NULL,41,'2026-02-25 03:30:00'),(38,1,19,NULL,42,'2025-08-09 03:30:00'),(39,1,19,NULL,43,'2025-11-07 03:30:00'),(40,1,19,NULL,44,'2026-02-05 03:30:00'),(41,1,20,NULL,46,'2025-07-20 03:30:00'),(42,1,20,NULL,47,'2025-10-18 03:30:00'),(43,1,20,NULL,48,'2026-01-16 03:30:00'),(44,1,20,NULL,49,'2026-07-20 03:30:00'),(45,1,21,NULL,50,'2025-06-30 03:30:00'),(46,1,21,NULL,51,'2025-09-28 03:30:00'),(47,1,21,NULL,52,'2025-12-27 03:30:00'),(48,1,22,NULL,54,'2025-06-10 03:30:00'),(49,1,22,NULL,55,'2025-09-08 03:30:00'),(50,1,22,NULL,56,'2025-12-07 03:30:00'),(51,1,22,NULL,57,'2026-06-10 03:30:00'),(52,1,23,NULL,58,'2025-05-21 03:30:00'),(53,1,23,NULL,59,'2025-08-19 03:30:00'),(54,1,23,NULL,60,'2025-11-17 03:30:00'),(55,1,23,NULL,61,'2026-05-21 03:30:00'),(56,1,24,NULL,62,'2025-05-01 03:30:00'),(57,1,24,NULL,63,'2025-07-30 03:30:00'),(58,1,24,NULL,65,'2026-05-01 03:30:00'),(59,1,25,NULL,66,'2025-04-11 03:30:00'),(60,1,25,NULL,67,'2025-07-10 03:30:00'),(61,1,25,NULL,68,'2025-10-08 03:30:00'),(62,1,25,NULL,69,'2026-04-11 03:30:00'),(63,1,26,NULL,70,'2025-03-22 03:30:00'),(64,1,26,NULL,71,'2025-06-20 03:30:00'),(65,1,26,NULL,73,'2026-03-22 03:30:00'),(66,1,27,NULL,74,'2025-03-02 03:30:00'),(67,1,27,NULL,75,'2025-05-31 03:30:00'),(68,1,27,NULL,76,'2025-08-29 03:30:00'),(69,1,28,NULL,78,'2025-02-10 03:30:00'),(70,1,28,NULL,79,'2025-05-11 03:30:00'),(71,1,28,NULL,80,'2025-08-09 03:30:00'),(72,1,28,NULL,81,'2026-02-10 03:30:00'),(73,1,29,NULL,82,'2025-01-21 03:30:00'),(74,1,29,NULL,83,'2025-04-21 03:30:00'),(75,1,29,NULL,84,'2025-07-20 03:30:00'),(76,1,29,NULL,85,'2026-01-21 03:30:00'),(77,5,74,NULL,98,'2026-08-16 09:49:11'),(78,5,75,NULL,102,'2026-08-16 09:51:19'),(79,5,74,NULL,99,'2026-08-16 11:33:48');
/*!40000 ALTER TABLE `evl_survey_responses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`) USING BTREE,
  KEY `failed_jobs_connection_queue_failed_at_index` (`connection`,`queue`,`failed_at`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
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
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
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
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` smallint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `jobs_queue_index` (`queue`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
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
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=43 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (11,'0001_01_01_000000_create_users_table',1),(12,'0001_01_01_000001_create_cache_table',1),(13,'0001_01_01_000002_create_jobs_table',1),(14,'2026_08_11_000001_create_usr_tables',1),(15,'2026_08_11_000002_create_mst_tables',1),(16,'2026_08_11_000003_create_ptp_tables',1),(17,'2026_08_11_000004_create_act_tables',1),(18,'2026_08_11_000005_create_evl_tables',1),(19,'2026_08_11_000006_create_sys_tables',1),(20,'2026_08_11_000007_add_cross_module_foreign_keys',1),(21,'2026_08_11_000008_add_survey_window_to_act_activities',2),(22,'2026_08_11_000009_add_parent_event_to_act_activities',3),(23,'2026_08_11_000010_seed_area_options_into_mst_options',4),(24,'2026_08_11_000011_normalize_area_reference_columns',5),(25,'2026_08_11_000012_drop_area_text_columns',6),(26,'2026_08_12_000001_rename_area_pending_status',7),(27,'2026_08_12_000002_add_updated_by_to_master_tables',8),(28,'2026_08_12_000003_add_updated_by_to_roles',9),(29,'2026_08_12_000004_create_review_tables',10),(30,'2026_08_12_000005_add_project_info_to_review_rounds',11),(31,'2026_08_12_000006_rename_review_status_ready',12),(32,'2026_08_12_000007_add_system_link_to_review_rounds',13),(33,'2026_08_13_000001_add_search_tags_to_mst_instructors',14),(34,'2026_08_13_000002_add_public_listing_fields_to_act_activities',15),(35,'2026_08_13_000003_expand_evaluation_system',16),(36,'2026_08_13_000004_create_mst_payment_accounts_table',16),(37,'2026_08_13_000005_create_registration_master_and_system_settings',16),(38,'2026_08_14_000001_add_age_range_to_act_registrations',17),(39,'2026_08_14_000002_add_line_account_to_ptp_participants',17),(40,'2026_08_14_000003_add_age_range_to_ptp_participants',18),(41,'2026_08_14_000004_add_tracking_round_fields',18),(42,'2026_08_16_000001_add_participant_app_fields',19);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mst_activity_formats`
--

DROP TABLE IF EXISTS `mst_activity_formats`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mst_activity_formats` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `icon` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `mst_activity_formats_code_unique` (`code`) USING BTREE,
  KEY `mst_activity_formats_updated_by_foreign` (`updated_by`) USING BTREE,
  CONSTRAINT `mst_activity_formats_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mst_activity_formats`
--

LOCK TABLES `mst_activity_formats` WRITE;
/*!40000 ALTER TABLE `mst_activity_formats` DISABLE KEYS */;
INSERT INTO `mst_activity_formats` VALUES (1,'FMT-001','CRAFT','craft',1,'2026-08-10 21:31:49','2026-08-11 19:26:24',6),(2,'FMT-002','MIND','heart',1,'2026-08-10 21:31:49','2026-08-11 19:26:22',6),(3,'FMT-003','FOOD','food',1,'2026-08-10 21:31:49','2026-08-11 19:26:20',6),(4,'FMT-004','WORKSHOP','sprout',1,'2026-08-10 21:31:49','2026-08-13 07:31:17',6),(5,'FMT-005','COMMUNITY','users',1,'2026-08-10 21:31:49','2026-08-11 19:26:16',6);
/*!40000 ALTER TABLE `mst_activity_formats` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mst_area_partner_org`
--

DROP TABLE IF EXISTS `mst_area_partner_org`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mst_area_partner_org` (
  `area_id` bigint unsigned NOT NULL,
  `partner_org_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`area_id`,`partner_org_id`) USING BTREE,
  KEY `mst_area_partner_org_partner_org_id_foreign` (`partner_org_id`) USING BTREE,
  CONSTRAINT `mst_area_partner_org_area_id_foreign` FOREIGN KEY (`area_id`) REFERENCES `mst_areas` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `mst_area_partner_org_partner_org_id_foreign` FOREIGN KEY (`partner_org_id`) REFERENCES `mst_partner_orgs` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mst_area_partner_org`
--

LOCK TABLES `mst_area_partner_org` WRITE;
/*!40000 ALTER TABLE `mst_area_partner_org` DISABLE KEYS */;
INSERT INTO `mst_area_partner_org` VALUES (1,1),(2,1),(3,1);
/*!40000 ALTER TABLE `mst_area_partner_org` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mst_areas`
--

DROP TABLE IF EXISTS `mst_areas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mst_areas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(160) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `area_type_id` bigint unsigned DEFAULT NULL,
  `area_group_id` bigint unsigned NOT NULL,
  `district_id` bigint unsigned NOT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `coordinator_name` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `coordinator_phone` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `coordinator_position` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `map_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ดำเนินการอยู่',
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `mst_areas_code_unique` (`code`) USING BTREE,
  KEY `mst_areas_created_by_foreign` (`created_by`) USING BTREE,
  KEY `mst_areas_updated_by_foreign` (`updated_by`) USING BTREE,
  KEY `mst_areas_status_index` (`status`) USING BTREE,
  KEY `mst_areas_area_type_id_foreign` (`area_type_id`) USING BTREE,
  KEY `mst_areas_area_group_id_foreign` (`area_group_id`) USING BTREE,
  KEY `mst_areas_district_id_foreign` (`district_id`) USING BTREE,
  CONSTRAINT `mst_areas_area_group_id_foreign` FOREIGN KEY (`area_group_id`) REFERENCES `mst_options` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `mst_areas_area_type_id_foreign` FOREIGN KEY (`area_type_id`) REFERENCES `mst_options` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `mst_areas_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `mst_areas_district_id_foreign` FOREIGN KEY (`district_id`) REFERENCES `mst_districts` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `mst_areas_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mst_areas`
--

LOCK TABLES `mst_areas` WRITE;
/*!40000 ALTER TABLE `mst_areas` DISABLE KEYS */;
INSERT INTO `mst_areas` VALUES (1,'AREA-001','The Farm Concept',36,41,1,'2024-06-01',NULL,'วีระ ศรีสมบัติ','082-222-3333','หัวหน้าพื้นที่ต้นแบบ','https://maps.google.com/?q=The+Farm+Concept+บางนา','ดำเนินการอยู่',NULL,6,'2026-08-10 21:31:48','2026-08-11 18:44:28'),(2,'AREA-002','ชุมชนพูนทรัพย์',37,42,2,'2025-01-15',NULL,'อรุณี ทองสุข','081-111-2222','ผู้ประสานงานชุมชน','https://maps.google.com/?q=ชุมชนพูนทรัพย์+เขตสายไหม','ดำเนินการอยู่',NULL,NULL,'2026-08-10 21:31:48','2026-08-10 21:31:48'),(3,'AREA-003','ชุมชนตึกร้าง',37,42,3,'2025-03-10',NULL,'ปิยะดา รุ่งเรือง','083-333-4444','ผู้ประสานงานชุมชน','https://maps.google.com/?q','ดำเนินการอยู่',NULL,6,'2026-08-10 21:31:48','2026-08-11 18:37:48'),(17,'AREA-004','โรงเรียนบางเขน',36,41,6,NULL,NULL,'แอมมี่','092539788',NULL,'https://maps.app.goo.gl/2iSSsHPpMz8FnN5u8','รอดำเนินงาน',NULL,6,'2026-08-11 19:16:21','2026-08-11 19:58:20');
/*!40000 ALTER TABLE `mst_areas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mst_consent_documents`
--

DROP TABLE IF EXISTS `mst_consent_documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mst_consent_documents` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `consent_type` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(160) COLLATE utf8mb4_unicode_ci NOT NULL,
  `version` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `effective_date` date DEFAULT NULL,
  `is_required` tinyint(1) NOT NULL DEFAULT '1',
  `is_active` tinyint(1) NOT NULL DEFAULT '0',
  `active_slot` tinyint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `mst_consent_documents_consent_type_version_unique` (`consent_type`,`version`),
  UNIQUE KEY `mst_consent_documents_code_unique` (`code`),
  UNIQUE KEY `mst_consent_documents_consent_type_active_slot_unique` (`consent_type`,`active_slot`),
  KEY `mst_consent_documents_updated_by_foreign` (`updated_by`),
  KEY `mst_consent_documents_consent_type_index` (`consent_type`),
  CONSTRAINT `mst_consent_documents_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mst_consent_documents`
--

LOCK TABLES `mst_consent_documents` WRITE;
/*!40000 ALTER TABLE `mst_consent_documents` DISABLE KEYS */;
INSERT INTO `mst_consent_documents` VALUES (1,'CNS-001','terms','เงื่อนไขการเข้าร่วมและการใช้งาน','1.0','เงื่อนไขการเข้าร่วมและการใช้งาน\nโปรดอ่านเงื่อนไขต่อไปนี้ก่อนลงทะเบียนหรือเข้าร่วมกิจกรรม\n1. ผู้เข้าร่วมต้องกรอกข้อมูลที่ถูกต้องและเป็นปัจจุบัน เพื่อใช้สำหรับการลงทะเบียน การติดต่อ และการเข้าร่วมกิจกรรม\n2. การลงทะเบียนจะถือว่าสมบูรณ์เมื่อดำเนินการตามขั้นตอนที่กิจกรรมนั้นกำหนด เช่น การชำระเงินหรือการยืนยันสิทธิ์\n3. ผู้เข้าร่วมควรมาถึงสถานที่จัดกิจกรรมตามวันและเวลาที่กำหนด และปฏิบัติตามคำแนะนำของผู้จัดกิจกรรม\n4. กรณีไม่สามารถเข้าร่วมกิจกรรมได้ กรุณาแจ้งยกเลิกตามเงื่อนไขของกิจกรรมนั้น ทั้งนี้ การคืนเงินหรือการเปลี่ยนแปลงสิทธิ์เป็นไปตามเงื่อนไขที่ผู้จัดกำหนด\n5. ผู้จัดขอสงวนสิทธิ์ในการเปลี่ยนแปลง วัน เวลา สถานที่ รายละเอียด หรือยกเลิกกิจกรรมตามความเหมาะสม โดยจะแจ้งให้ผู้ลงทะเบียนทราบเมื่อมีการเปลี่ยนแปลง\n6. ภาพถ่ายหรือสื่อที่เกิดขึ้นภายในกิจกรรมอาจถูกนำไปใช้เพื่อประชาสัมพันธ์หรือสื่อสารกิจกรรมของ The Farm Concept ตามความเหมาะสม\n7. ข้อมูลส่วนบุคคลของผู้เข้าร่วมจะถูกใช้เพื่อการลงทะเบียน การติดต่อ การดำเนินกิจกรรม และการประเมินผลที่เกี่ยวข้อง โดยเป็นไปตามนโยบายความเป็นส่วนตัวของระบบ\n\nเมื่อกดลงทะเบียน ถือว่าผู้เข้าร่วมได้อ่าน เข้าใจ และยอมรับเงื่อนไขการเข้าร่วมและการใช้งานข้างต้นแล้ว',NULL,1,1,1,'2026-08-13 10:29:46','2026-08-13 10:39:33',6),(2,'CNS-002','pdpa','PDPA','1.0','นโยบายและความยินยอมในการใช้ข้อมูลส่วนบุคคล (PDPA)\n\nเราให้ความสำคัญกับความเป็นส่วนตัวและการคุ้มครองข้อมูลส่วนบุคคลของท่าน โดยข้อมูลที่ได้รับจะถูกเก็บรวบรวม ใช้ และเปิดเผยเท่าที่จำเป็นตามวัตถุประสงค์ที่เกี่ยวข้องกับการให้บริการและการดำเนินกิจกรรม\n\n1. ข้อมูลที่อาจเก็บรวบรวม\nข้อมูลที่อาจมีการเก็บรวบรวม เช่น\n* ชื่อ-นามสกุล\n* เบอร์โทรศัพท์ และข้อมูลสำหรับการติดต่อ\n* ข้อมูลการลงทะเบียนและการเข้าร่วมกิจกรรม\n* ข้อมูลการชำระเงินหรือหลักฐานการชำระเงิน\n* ข้อมูลจากแบบประเมินหรือแบบสอบถาม\n* รูปภาพหรือข้อมูลอื่นที่ท่านส่งผ่านระบบ\n\n2. วัตถุประสงค์ในการใช้ข้อมูล\nข้อมูลของท่านอาจถูกนำไปใช้เพื่อ\n* ลงทะเบียนและยืนยันการเข้าร่วมกิจกรรม\n* ติดต่อ แจ้งเตือน หรือแจ้งข้อมูลที่เกี่ยวข้องกับกิจกรรม\n* บริหารจัดการการเข้าร่วมและการให้บริการ\n* ประเมินผลและพัฒนากิจกรรมหรือโครงการ\n* จัดทำข้อมูลสรุป รายงาน หรือสถิติ โดยคำนึงถึงความเหมาะสมในการเปิดเผยข้อมูลส่วนบุคคล\n* ปฏิบัติตามหน้าที่หรือข้อกำหนดทางกฎหมายที่เกี่ยวข้อง\n\n3. การเปิดเผยข้อมูล\nเราจะไม่เปิดเผยข้อมูลส่วนบุคคลของท่านแก่บุคคลภายนอก เว้นแต่มีความจำเป็นต่อการดำเนินงาน ได้รับความยินยอมจากท่าน หรือเป็นกรณีที่กฎหมายกำหนดหรืออนุญาต\n\n4. การเก็บรักษาและความปลอดภัย\nข้อมูลส่วนบุคคลจะถูกเก็บรักษาเท่าที่จำเป็นตามวัตถุประสงค์ และมีมาตรการที่เหมาะสมเพื่อป้องกันการเข้าถึง การใช้ การเปลี่ยนแปลง หรือการเปิดเผยข้อมูลโดยไม่ได้รับอนุญาต\n\n5. สิทธิของเจ้าของข้อมูล\nท่านสามารถใช้สิทธิเกี่ยวกับข้อมูลส่วนบุคคลของท่านตามกฎหมาย เช่น ขอเข้าถึงหรือขอสำเนาข้อมูล ขอแก้ไขข้อมูล ขอให้ลบหรือระงับการใช้ข้อมูล คัดค้านการประมวลผล หรือถอนความยินยอมในกรณีที่การประมวลผลนั้นอาศัยความยินยอม\nการถอนความยินยอมจะไม่กระทบต่อการเก็บรวบรวม ใช้ หรือเปิดเผยข้อมูลที่ได้ดำเนินการโดยชอบก่อนการถอนความยินยอม\n\n 6. ช่องทางการติดต่อ\nหากมีคำถามเกี่ยวกับข้อมูลส่วนบุคคล หรือต้องการใช้สิทธิ สามารถติดต่อได้ที่\n**ผู้ควบคุมข้อมูลส่วนบุคคล:** [ชื่อหน่วยงาน/องค์กร]\n**อีเมล:** [อีเมลสำหรับติดต่อ]\n**โทรศัพท์:** [หมายเลขโทรศัพท์]',NULL,1,1,1,'2026-08-13 10:35:23','2026-08-13 10:39:31',6),(3,'CNS-003','cohort_data','ยินยอมเก็บข้อมูลกลุ่มตัวอย่าง','1.0','ความยินยอมในการเข้าร่วมเป็นกลุ่มตัวอย่างและการเก็บข้อมูล\n\nข้าพเจ้ายินยอมเข้าร่วมเป็นกลุ่มตัวอย่างของโครงการ และยินยอมให้โครงการเก็บรวบรวม ใช้ และประมวลผลข้อมูลที่เกี่ยวข้อง เพื่อวัตถุประสงค์ในการติดตามและประเมินผลการเปลี่ยนแปลงตลอดระยะเวลาของโครงการ\n\nข้อมูลที่อาจมีการเก็บรวบรวม ได้แก่ ข้อมูลพื้นฐาน ข้อมูลการเข้าร่วมกิจกรรม ผลการตอบแบบประเมิน และข้อมูลที่เกี่ยวข้องกับการติดตามผลในแต่ละช่วงเวลา เช่น ก่อนเข้าร่วมโครงการ และช่วงติดตามผล 3 เดือน 6 เดือน และ 12 เดือน\n\nข้อมูลดังกล่าวจะถูกนำไปใช้เพื่อการวิเคราะห์ ติดตาม ประเมินผล และจัดทำรายงานของโครงการ โดยจะจำกัดการเข้าถึงข้อมูลเฉพาะผู้ที่เกี่ยวข้อง และดำเนินการตามมาตรการคุ้มครองข้อมูลส่วนบุคคลที่เหมาะสม\n\nข้าพเจ้ารับทราบว่าสามารถถอนความยินยอมได้ตามช่องทางที่โครงการกำหนด โดยการถอนความยินยอมจะไม่กระทบต่อการดำเนินการที่ได้ดำเนินการไปแล้วก่อนการถอนความยินยอม',NULL,1,1,1,'2026-08-13 10:38:58','2026-08-13 10:39:29',6);
/*!40000 ALTER TABLE `mst_consent_documents` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mst_courses`
--

DROP TABLE IF EXISTS `mst_courses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mst_courses` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `program_id` bigint unsigned NOT NULL,
  `name` varchar(160) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `sort_order` smallint unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `mst_courses_program_id_sort_order_index` (`program_id`,`sort_order`) USING BTREE,
  CONSTRAINT `mst_courses_program_id_foreign` FOREIGN KEY (`program_id`) REFERENCES `mst_programs` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mst_courses`
--

LOCK TABLES `mst_courses` WRITE;
/*!40000 ALTER TABLE `mst_courses` DISABLE KEYS */;
INSERT INTO `mst_courses` VALUES (1,1,'รู้จักอาหารหลัก 5 หมู่',1,'2026-08-10 21:31:48','2026-08-10 21:31:48'),(2,1,'ผัก 5 สี สุขภาพดีทุกวัน',2,'2026-08-10 21:31:48','2026-08-10 21:31:48'),(3,1,'ลดหวาน มัน เค็ม',3,'2026-08-10 21:31:48','2026-08-10 21:31:48'),(4,1,'อ่านฉลากอาหารให้เป็น',4,'2026-08-10 21:31:48','2026-08-10 21:31:48'),(5,2,'ปลูกผักสวนครัวเบื้องต้น',1,'2026-08-10 21:31:48','2026-08-10 21:31:48'),(6,2,'ปลูกผักในพื้นที่จำกัด',2,'2026-08-10 21:31:48','2026-08-10 21:31:48'),(7,2,'ทำปุ๋ยหมักจากเศษอาหาร',3,'2026-08-10 21:31:48','2026-08-10 21:31:48'),(8,2,'จากแปลงสู่จาน',4,'2026-08-10 21:31:48','2026-08-10 21:31:48'),(9,3,'รู้เลือก รู้กิน',1,'2026-08-10 21:31:48','2026-08-10 21:31:48'),(10,3,'จ่ายตลาดอย่างฉลาด',2,'2026-08-10 21:31:48','2026-08-10 21:31:48'),(11,3,'รู้จักอาหารปลอดภัย',3,'2026-08-10 21:31:48','2026-08-10 21:31:48'),(12,3,'วางแผนมื้ออาหารสุขภาพ',4,'2026-08-10 21:31:48','2026-08-10 21:31:48'),(26,8,'เมนูสุขภาพทำง่าย',1,'2026-08-11 05:16:53','2026-08-11 05:16:53'),(27,8,'Cooking Workshop ลดหวาน มัน เค็ม',2,'2026-08-11 05:16:53','2026-08-11 05:16:53'),(28,8,'อาหารสำหรับครอบครัว',3,'2026-08-11 05:16:53','2026-08-11 05:16:53'),(29,8,'ครัวชุมชนเพื่อสุขภาวะ',4,'2026-08-11 05:16:53','2026-08-11 05:16:53');
/*!40000 ALTER TABLE `mst_courses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mst_districts`
--

DROP TABLE IF EXISTS `mst_districts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mst_districts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `province` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `mst_districts_province_name_unique` (`province`,`name`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mst_districts`
--

LOCK TABLES `mst_districts` WRITE;
/*!40000 ALTER TABLE `mst_districts` DISABLE KEYS */;
INSERT INTO `mst_districts` VALUES (6,'กรุงเทพมหานคร','เขตจตุจักร'),(5,'กรุงเทพมหานคร','เขตดอนเมือง'),(1,'กรุงเทพมหานคร','เขตบางนา'),(3,'กรุงเทพมหานคร','เขตบางพลัด'),(4,'กรุงเทพมหานคร','เขตบางเขน'),(2,'กรุงเทพมหานคร','เขตสายไหม'),(13,'นนทบุรี','อำเภอบางบัวทอง'),(12,'นนทบุรี','อำเภอปากเกร็ด'),(11,'นนทบุรี','อำเภอเมืองนนทบุรี'),(8,'ปทุมธานี','อำเภอคลองหลวง'),(10,'ปทุมธานี','อำเภอธัญบุรี'),(9,'ปทุมธานี','อำเภอลำลูกกา'),(7,'ปทุมธานี','อำเภอเมืองปทุมธานี'),(16,'สมุทรปราการ','อำเภอบางบ่อ'),(15,'สมุทรปราการ','อำเภอบางพลี'),(14,'สมุทรปราการ','อำเภอเมืองสมุทรปราการ');
/*!40000 ALTER TABLE `mst_districts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mst_follow_up_round_templates`
--

DROP TABLE IF EXISTS `mst_follow_up_round_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mst_follow_up_round_templates` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `offset_days` smallint unsigned NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `sort_order` smallint unsigned NOT NULL DEFAULT '0',
  `line_notify` tinyint(1) NOT NULL DEFAULT '1',
  `notify_days_before` smallint unsigned NOT NULL DEFAULT '7',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `mst_follow_up_round_templates_code_unique` (`code`) USING BTREE,
  UNIQUE KEY `mst_follow_up_round_templates_offset_days_unique` (`offset_days`) USING BTREE,
  KEY `mst_follow_up_round_templates_is_active_sort_order_index` (`is_active`,`sort_order`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mst_follow_up_round_templates`
--

LOCK TABLES `mst_follow_up_round_templates` WRITE;
/*!40000 ALTER TABLE `mst_follow_up_round_templates` DISABLE KEYS */;
INSERT INTO `mst_follow_up_round_templates` VALUES (1,'FRT-1','ก่อนเข้าร่วม',0,1,1,0,0,'2026-08-10 21:31:49','2026-08-11 04:34:55'),(10,'FRT-2','3 เดือน',90,1,2,0,0,'2026-08-11 04:41:57','2026-08-11 04:41:57'),(11,'FRT-3','6 เดือน',180,1,3,0,0,'2026-08-11 04:41:57','2026-08-11 04:43:35'),(12,'FRT-4','12 เดือน',365,1,4,0,0,'2026-08-11 04:41:57','2026-08-11 04:41:57');
/*!40000 ALTER TABLE `mst_follow_up_round_templates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mst_instructor_course`
--

DROP TABLE IF EXISTS `mst_instructor_course`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mst_instructor_course` (
  `instructor_id` bigint unsigned NOT NULL,
  `course_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`instructor_id`,`course_id`) USING BTREE,
  KEY `mst_instructor_course_course_id_foreign` (`course_id`) USING BTREE,
  CONSTRAINT `mst_instructor_course_course_id_foreign` FOREIGN KEY (`course_id`) REFERENCES `mst_courses` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `mst_instructor_course_instructor_id_foreign` FOREIGN KEY (`instructor_id`) REFERENCES `mst_instructors` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mst_instructor_course`
--

LOCK TABLES `mst_instructor_course` WRITE;
/*!40000 ALTER TABLE `mst_instructor_course` DISABLE KEYS */;
INSERT INTO `mst_instructor_course` VALUES (1,1),(2,2),(2,3),(1,4),(4,5),(13,6),(5,7),(4,8),(2,9),(5,10),(3,12);
/*!40000 ALTER TABLE `mst_instructor_course` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mst_instructor_expertises`
--

DROP TABLE IF EXISTS `mst_instructor_expertises`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mst_instructor_expertises` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `instructor_id` bigint unsigned NOT NULL,
  `name` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `mst_instructor_expertises_instructor_id_foreign` (`instructor_id`) USING BTREE,
  CONSTRAINT `mst_instructor_expertises_instructor_id_foreign` FOREIGN KEY (`instructor_id`) REFERENCES `mst_instructors` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=55 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mst_instructor_expertises`
--

LOCK TABLES `mst_instructor_expertises` WRITE;
/*!40000 ALTER TABLE `mst_instructor_expertises` DISABLE KEYS */;
INSERT INTO `mst_instructor_expertises` VALUES (34,5,'การแปรรูปอาหาร'),(35,5,'ผลิตภัณฑ์ชุมชน'),(36,4,'สุขภาวะครอบครัว'),(37,4,'การดูแลผู้สูงอายุ'),(38,3,'สุขภาพ'),(39,3,'การออกกำลังกาย'),(40,2,'อาหารเพื่อสุขภาพ'),(41,2,'การปรับเปลี่ยนพฤติกรรม'),(50,1,'โภชนาการ'),(51,1,'สุขภาวะชุมชน'),(54,13,'ปลูกผัก');
/*!40000 ALTER TABLE `mst_instructor_expertises` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mst_instructors`
--

DROP TABLE IF EXISTS `mst_instructors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mst_instructors` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(160) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `photo_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `expertise` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bio` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `search_tags` json DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `mst_instructors_code_unique` (`code`) USING BTREE,
  KEY `mst_instructors_is_active_index` (`is_active`) USING BTREE,
  KEY `mst_instructors_updated_by_foreign` (`updated_by`) USING BTREE,
  CONSTRAINT `mst_instructors_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mst_instructors`
--

LOCK TABLES `mst_instructors` WRITE;
/*!40000 ALTER TABLE `mst_instructors` DISABLE KEYS */;
INSERT INTO `mst_instructors` VALUES (1,'INS-001','ดร.กิตติพงศ์ วัฒนสุข','08x-xxx-1111','instructor-photos/rPawMaiDy3B9ei2i76J55ZQwtZ3UitA9IyBblpQC.png','โภชนาการ · สุขภาวะชุมชน',NULL,NULL,1,'2026-08-10 21:31:48','2026-08-13 10:31:20',6),(2,'INS-002','อาจารย์พิมพ์ชนก ศรีสมบัติ','08x-xxx-2222',NULL,'อาหารเพื่อสุขภาพ · การปรับเปลี่ยนพฤติกรรม',NULL,NULL,1,'2026-08-10 21:31:48','2026-08-11 19:26:09',6),(3,'INS-003','คุณภูริณัฐ วงศ์สวัสดิ์','08x-xxx-3333',NULL,'สุขภาพ · การออกกำลังกาย',NULL,NULL,1,'2026-08-10 21:31:49','2026-08-11 19:26:07',6),(4,'INS-004','คุณกัญญารัตน์ มีสุข','08x-xxx-4444',NULL,'สุขภาวะครอบครัว · การดูแลผู้สูงอายุ',NULL,NULL,1,'2026-08-10 21:31:49','2026-08-11 19:26:04',6),(5,'INS-005','คุณปกรณ์ชัย ใจดี','08x-xxx-5555',NULL,'การแปรรูปอาหาร · ผลิตภัณฑ์ชุมชน',NULL,NULL,1,'2026-08-10 21:31:49','2026-08-11 19:26:02',6),(13,'INS-006','คุณปูเป้ สุพัตรา ไชยชมภู (สวนผัก \"ปูเป้ ทำเอง\")','0000000000','instructor-photos/i5B8y3ZKRtR1nBmjpUrA9aOJE2x3OFZ5YEFEfOAa.jpg','ปลูกผัก',NULL,NULL,1,'2026-08-13 08:47:27','2026-08-15 11:17:56',6);
/*!40000 ALTER TABLE `mst_instructors` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mst_options`
--

DROP TABLE IF EXISTS `mst_options`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mst_options` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `option_group` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `label` varchar(160) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `sort_order` smallint unsigned NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `mst_options_option_group_code_unique` (`option_group`,`code`) USING BTREE,
  KEY `mst_options_option_group_is_active_sort_order_index` (`option_group`,`is_active`,`sort_order`) USING BTREE,
  KEY `mst_options_updated_by_foreign` (`updated_by`),
  CONSTRAINT `mst_options_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=56 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mst_options`
--

LOCK TABLES `mst_options` WRITE;
/*!40000 ALTER TABLE `mst_options` DISABLE KEYS */;
INSERT INTO `mst_options` VALUES (1,'occupation','occupation-01','รับราชการ',1,1,'2026-08-10 21:31:48','2026-08-10 21:31:48',NULL),(2,'occupation','occupation-02','พนักงานบริษัท',2,1,'2026-08-10 21:31:48','2026-08-10 21:31:48',NULL),(3,'occupation','occupation-03','ธุรกิจส่วนตัว',3,1,'2026-08-10 21:31:48','2026-08-10 21:31:48',NULL),(4,'occupation','occupation-04','เกษตรกร',4,1,'2026-08-10 21:31:48','2026-08-10 21:31:48',NULL),(5,'occupation','occupation-05','นักเรียน/นักศึกษา',5,1,'2026-08-10 21:31:48','2026-08-10 21:31:48',NULL),(6,'occupation','occupation-06','แม่บ้าน',6,1,'2026-08-10 21:31:48','2026-08-10 21:31:48',NULL),(7,'occupation','occupation-07','เกษียณอายุ',7,1,'2026-08-10 21:31:48','2026-08-10 21:31:48',NULL),(8,'occupation','occupation-08','รับจ้างทั่วไป',8,1,'2026-08-10 21:31:48','2026-08-10 21:31:48',NULL),(9,'source_channel','source_channel-01','Facebook',1,1,'2026-08-10 21:31:48','2026-08-10 21:31:48',NULL),(10,'source_channel','source_channel-02','LINE OA',2,1,'2026-08-10 21:31:48','2026-08-10 21:31:48',NULL),(11,'source_channel','source_channel-03','เว็บไซต์',3,1,'2026-08-10 21:31:48','2026-08-10 21:31:48',NULL),(12,'source_channel','source_channel-04','เพื่อนแนะนำ',4,1,'2026-08-10 21:31:48','2026-08-10 21:31:48',NULL),(13,'source_channel','source_channel-05','ผู้นำชุมชน',5,1,'2026-08-10 21:31:48','2026-08-10 21:31:48',NULL),(14,'source_channel','source_channel-06','สื่อสิ่งพิมพ์',6,1,'2026-08-10 21:31:48','2026-08-10 21:31:48',NULL),(15,'interest','interest-01','ปลูกผักปลอดสาร',1,1,'2026-08-10 21:31:48','2026-08-10 21:31:48',NULL),(16,'interest','interest-02','ทำปุ๋ยหมัก',2,1,'2026-08-10 21:31:48','2026-08-10 21:31:48',NULL),(17,'interest','interest-03','อาหารเพื่อสุขภาพ',3,1,'2026-08-10 21:31:48','2026-08-10 21:31:48',NULL),(18,'interest','interest-04','สมุนไพรพื้นบ้าน',4,1,'2026-08-10 21:31:48','2026-08-10 21:31:48',NULL),(19,'interest','interest-05','ออกกำลังกาย',5,1,'2026-08-10 21:31:48','2026-08-10 21:31:48',NULL),(20,'interest','interest-06','สุขภาพจิต',6,1,'2026-08-10 21:31:48','2026-08-10 21:31:48',NULL),(21,'interest','interest-07','เกษตรอินทรีย์',7,1,'2026-08-10 21:31:48','2026-08-10 21:31:48',NULL),(22,'contact_channel','contact_channel-01','โทรศัพท์',1,1,'2026-08-10 21:31:48','2026-08-10 21:31:48',NULL),(23,'contact_channel','contact_channel-02','LINE',2,1,'2026-08-10 21:31:48','2026-08-10 21:31:48',NULL),(24,'contact_channel','contact_channel-03','อีเมล',3,1,'2026-08-10 21:31:48','2026-08-10 21:31:48',NULL),(25,'contact_channel','contact_channel-04','ผ่านผู้ดูแล',4,1,'2026-08-10 21:31:48','2026-08-10 21:31:48',NULL),(26,'note_kind','note_kind-01','โทรติดตาม',1,1,'2026-08-10 21:31:48','2026-08-10 21:31:48',NULL),(27,'note_kind','note_kind-02','เยี่ยมบ้าน',2,1,'2026-08-10 21:31:48','2026-08-10 21:31:48',NULL),(28,'note_kind','note_kind-03','ส่งข้อความ LINE',3,1,'2026-08-10 21:31:48','2026-08-10 21:31:48',NULL),(29,'note_kind','note_kind-04','พบที่กิจกรรม',4,1,'2026-08-10 21:31:48','2026-08-10 21:31:48',NULL),(30,'note_kind','note_kind-05','ฝากผู้นำชุมชน',5,1,'2026-08-10 21:31:48','2026-08-10 21:31:48',NULL),(31,'note_kind','note_kind-06','ส่ง SMS',6,1,'2026-08-10 21:31:48','2026-08-10 21:31:48',NULL),(32,'purchase_channel','purchase_channel-01','หน้าร้าน',1,1,'2026-08-10 21:31:48','2026-08-10 21:31:48',NULL),(33,'purchase_channel','purchase_channel-02','LINE OA',2,1,'2026-08-10 21:31:48','2026-08-10 21:31:48',NULL),(34,'purchase_channel','purchase_channel-03','ออกบูธกิจกรรม',3,1,'2026-08-10 21:31:48','2026-08-10 21:31:48',NULL),(35,'purchase_channel','purchase_channel-04','สั่งล่วงหน้า',4,1,'2026-08-10 21:31:48','2026-08-10 21:31:48',NULL),(36,'area_type','area_type-1','เอกชน',1,1,'2026-08-11 05:08:08','2026-08-11 05:08:08',NULL),(37,'area_type','area_type-2','ชุมชน/หมู่บ้าน',2,1,'2026-08-11 05:08:08','2026-08-11 05:08:08',NULL),(38,'area_type','area_type-3','โรงเรียน',3,1,'2026-08-11 05:08:08','2026-08-11 05:08:08',NULL),(39,'area_type','area_type-4','สถานประกอบการเอกชน',4,1,'2026-08-11 05:08:08','2026-08-11 05:08:08',NULL),(40,'area_type','area_type-5','โรงพยาบาล',5,1,'2026-08-11 05:08:08','2026-08-11 05:08:08',NULL),(41,'area_group','area_group-1','พื้นที่ต้นแบบ',1,1,'2026-08-11 05:08:08','2026-08-11 17:39:28',NULL),(42,'area_group','area_group-2','พื้นที่ส่วนขยาย',2,1,'2026-08-11 05:08:08','2026-08-11 05:08:08',NULL),(43,'area_group','area_group-3','พื้นที่จัดกิจกรรม',3,1,'2026-08-11 05:08:08','2026-08-11 05:08:08',NULL),(44,'area_status','area_status-1','ดำเนินการอยู่',2,1,'2026-08-11 05:08:08','2026-08-11 05:08:08',NULL),(45,'area_status','area_status-2','รอดำเนินงาน',1,1,'2026-08-11 05:08:08','2026-08-11 18:59:18',NULL),(46,'area_status','area_status-3','สิ้นสุดแล้ว',3,1,'2026-08-11 05:08:08','2026-08-11 05:08:08',NULL),(47,'age_range','age_range-01','ต่ำกว่า 15 ปี',1,1,'2026-08-13 10:27:56','2026-08-13 10:27:56',NULL),(48,'age_range','age_range-02','15–24 ปี',2,1,'2026-08-13 10:27:56','2026-08-13 10:27:56',NULL),(49,'age_range','age_range-03','25–39 ปี',3,1,'2026-08-13 10:27:56','2026-08-13 10:27:56',NULL),(50,'age_range','age_range-04','40–59 ปี',4,1,'2026-08-13 10:27:56','2026-08-13 10:27:56',NULL),(51,'age_range','age_range-05','60 ปีขึ้นไป',5,1,'2026-08-13 10:27:56','2026-08-13 10:27:56',NULL),(52,'cohort_source','walk_in','สมัครหรือเข้าร่วมโดยตรง',1,1,'2026-08-13 10:27:56','2026-08-13 10:27:56',NULL),(53,'cohort_source','activity_registration','จากการลงทะเบียนกิจกรรม',2,1,'2026-08-13 10:27:56','2026-08-13 10:27:56',NULL),(54,'cohort_source','referral','ได้รับการแนะนำหรือส่งต่อ',3,1,'2026-08-13 10:27:56','2026-08-13 10:27:56',NULL),(55,'cohort_source','manual','เจ้าหน้าที่เพิ่มข้อมูล',4,1,'2026-08-13 10:27:56','2026-08-13 10:27:56',NULL);
/*!40000 ALTER TABLE `mst_options` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mst_partner_orgs`
--

DROP TABLE IF EXISTS `mst_partner_orgs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mst_partner_orgs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `mst_partner_orgs_name_unique` (`name`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mst_partner_orgs`
--

LOCK TABLES `mst_partner_orgs` WRITE;
/*!40000 ALTER TABLE `mst_partner_orgs` DISABLE KEYS */;
INSERT INTO `mst_partner_orgs` VALUES (1,'สสส. พลเมืองอาสา',1,'2026-08-11 17:28:41','2026-08-11 17:28:41');
/*!40000 ALTER TABLE `mst_partner_orgs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mst_payment_accounts`
--

DROP TABLE IF EXISTS `mst_payment_accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mst_payment_accounts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `bank_code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `account_number` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `account_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `qr_code_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '0',
  `active_slot` tinyint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `mst_payment_accounts_bank_code_account_number_unique` (`bank_code`,`account_number`),
  UNIQUE KEY `mst_payment_accounts_code_unique` (`code`),
  UNIQUE KEY `mst_payment_accounts_active_slot_unique` (`active_slot`),
  KEY `mst_payment_accounts_updated_by_foreign` (`updated_by`),
  KEY `mst_payment_accounts_is_active_index` (`is_active`),
  CONSTRAINT `mst_payment_accounts_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mst_payment_accounts`
--

LOCK TABLES `mst_payment_accounts` WRITE;
/*!40000 ALTER TABLE `mst_payment_accounts` DISABLE KEYS */;
INSERT INTO `mst_payment_accounts` VALUES (1,'PAY-001','KBANK','000-0-00000-0','The Farm Concept (TEST)','payment-qr-codes/Uw2HaCvQabMwb3IFAZggnus69PymyrmZKD4ExGt8.jpg',1,1,6,'2026-08-13 10:28:48','2026-08-13 10:28:49');
/*!40000 ALTER TABLE `mst_payment_accounts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mst_programs`
--

DROP TABLE IF EXISTS `mst_programs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mst_programs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(160) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ดำเนินการอยู่',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `mst_programs_code_unique` (`code`) USING BTREE,
  KEY `mst_programs_is_active_index` (`is_active`) USING BTREE,
  KEY `mst_programs_updated_by_foreign` (`updated_by`) USING BTREE,
  CONSTRAINT `mst_programs_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mst_programs`
--

LOCK TABLES `mst_programs` WRITE;
/*!40000 ALTER TABLE `mst_programs` DISABLE KEYS */;
INSERT INTO `mst_programs` VALUES (1,'PROG-001','โปรแกรมกินดี อยู่ดี','โภชนาการ','ดำเนินการอยู่',1,'2026-08-10 21:31:48','2026-08-11 19:25:58',6),(2,'PROG-002','โปรแกรมปลูกกินเอง','เกษตรและอาหาร','ดำเนินการอยู่',1,'2026-08-10 21:31:48','2026-08-11 19:25:56',6),(3,'PROG-003','โปรแกรม Food Literacy','ความรอบรู้ด้านอาหาร','ดำเนินการอยู่',1,'2026-08-10 21:31:48','2026-08-11 19:25:53',6),(8,'PROG-004','โปรแกรมครัวสุขภาวะ','ครัวและการปรุงอาหาร','ดำเนินการอยู่',1,'2026-08-11 05:16:53','2026-08-11 19:25:51',6);
/*!40000 ALTER TABLE `mst_programs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mst_target_groups`
--

DROP TABLE IF EXISTS `mst_target_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mst_target_groups` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `age_range` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `target_count` int unsigned NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `sort_order` smallint unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `mst_target_groups_code_unique` (`code`) USING BTREE,
  KEY `mst_target_groups_is_active_index` (`is_active`) USING BTREE,
  KEY `mst_target_groups_updated_by_foreign` (`updated_by`) USING BTREE,
  CONSTRAINT `mst_target_groups_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mst_target_groups`
--

LOCK TABLES `mst_target_groups` WRITE;
/*!40000 ALTER TABLE `mst_target_groups` DISABLE KEYS */;
INSERT INTO `mst_target_groups` VALUES (1,'TG-001','เด็กและเยาวชน','6-18 ปี',5000,1,1,'2026-08-10 21:31:48','2026-08-11 19:25:47',6),(2,'TG-002','วัยทำงาน','19-59 ปี',2000,1,2,'2026-08-10 21:31:48','2026-08-11 19:25:45',6),(3,'TG-003','ผู้สูงอายุ','60 ปีขึ้นไป',1000,1,3,'2026-08-10 21:31:48','2026-08-11 19:25:43',6),(4,'TG-004','กลุ่มเปราะบาง','ทุกช่วงวัย',1000,1,4,'2026-08-10 21:31:48','2026-08-11 19:25:41',6);
/*!40000 ALTER TABLE `mst_target_groups` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_reset_tokens`
--

LOCK TABLES `password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ptp_cohort_profiles`
--

DROP TABLE IF EXISTS `ptp_cohort_profiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ptp_cohort_profiles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `participant_id` bigint unsigned NOT NULL,
  `cohort_code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `entry_date` date NOT NULL,
  `source_type` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'walk_in',
  `source_registration_id` bigint unsigned DEFAULT NULL,
  `stopped_at` timestamp NULL DEFAULT NULL,
  `stopped_reason` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `stopped_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `ptp_cohort_profiles_participant_id_unique` (`participant_id`) USING BTREE,
  UNIQUE KEY `ptp_cohort_profiles_cohort_code_unique` (`cohort_code`) USING BTREE,
  KEY `ptp_cohort_profiles_stopped_by_foreign` (`stopped_by`) USING BTREE,
  KEY `ptp_cohort_profiles_entry_date_index` (`entry_date`) USING BTREE,
  KEY `ptp_cohort_profiles_source_registration_id_foreign` (`source_registration_id`) USING BTREE,
  CONSTRAINT `ptp_cohort_profiles_participant_id_foreign` FOREIGN KEY (`participant_id`) REFERENCES `ptp_participants` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `ptp_cohort_profiles_source_registration_id_foreign` FOREIGN KEY (`source_registration_id`) REFERENCES `act_registrations` (`id`) ON DELETE SET NULL ON UPDATE RESTRICT,
  CONSTRAINT `ptp_cohort_profiles_stopped_by_foreign` FOREIGN KEY (`stopped_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=38 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ptp_cohort_profiles`
--

LOCK TABLES `ptp_cohort_profiles` WRITE;
/*!40000 ALTER TABLE `ptp_cohort_profiles` DISABLE KEYS */;
INSERT INTO `ptp_cohort_profiles` VALUES (2,2,'DEMO-CHT-0001','2026-07-13','referral',NULL,NULL,NULL,NULL,'2026-08-11 20:54:29','2026-08-11 20:54:29'),(3,3,'DEMO-CHT-0002','2026-06-23','walk_in',NULL,NULL,NULL,NULL,'2026-08-11 20:54:29','2026-08-11 20:54:29'),(4,4,'DEMO-CHT-0003','2026-06-03','walk_in',NULL,NULL,NULL,NULL,'2026-08-11 20:54:29','2026-08-11 20:54:29'),(5,5,'DEMO-CHT-0004','2026-05-14','referral',NULL,NULL,NULL,NULL,'2026-08-11 20:54:29','2026-08-11 20:54:29'),(6,6,'DEMO-CHT-0005','2026-04-24','walk_in',NULL,NULL,NULL,NULL,'2026-08-11 20:54:29','2026-08-11 20:54:29'),(7,7,'DEMO-CHT-0006','2026-04-04','walk_in',NULL,NULL,NULL,NULL,'2026-08-11 20:54:29','2026-08-11 20:54:29'),(8,8,'DEMO-CHT-0007','2026-03-15','referral',NULL,NULL,NULL,NULL,'2026-08-11 20:54:29','2026-08-11 20:54:29'),(9,9,'DEMO-CHT-0008','2026-02-23','walk_in',NULL,NULL,NULL,NULL,'2026-08-11 20:54:29','2026-08-11 20:54:29'),(10,10,'DEMO-CHT-0009','2026-02-03','walk_in',NULL,NULL,NULL,NULL,'2026-08-11 20:54:29','2026-08-11 20:54:29'),(11,11,'DEMO-CHT-0010','2026-01-14','referral',NULL,NULL,NULL,NULL,'2026-08-11 20:54:29','2026-08-11 20:54:29'),(12,12,'DEMO-CHT-0011','2025-12-25','walk_in',NULL,NULL,NULL,NULL,'2026-08-11 20:54:29','2026-08-11 20:54:29'),(13,13,'DEMO-CHT-0012','2025-12-05','walk_in',NULL,NULL,NULL,NULL,'2026-08-11 20:54:29','2026-08-11 20:54:29'),(14,14,'DEMO-CHT-0013','2025-11-15','referral',NULL,NULL,NULL,NULL,'2026-08-11 20:54:29','2026-08-11 20:54:29'),(15,15,'DEMO-CHT-0014','2025-10-26','walk_in',NULL,NULL,NULL,NULL,'2026-08-11 20:54:29','2026-08-11 20:54:29'),(16,16,'DEMO-CHT-0015','2025-10-06','walk_in',NULL,NULL,NULL,NULL,'2026-08-11 20:54:29','2026-08-11 20:54:29'),(17,17,'DEMO-CHT-0016','2025-09-16','referral',NULL,NULL,NULL,NULL,'2026-08-11 20:54:29','2026-08-11 20:54:29'),(18,18,'DEMO-CHT-0017','2025-08-27','walk_in',NULL,NULL,NULL,NULL,'2026-08-11 20:54:29','2026-08-11 20:54:29'),(19,19,'DEMO-CHT-0018','2025-08-07','walk_in',NULL,NULL,NULL,NULL,'2026-08-11 20:54:29','2026-08-11 20:54:29'),(20,20,'DEMO-CHT-0019','2025-07-18','referral',NULL,NULL,NULL,NULL,'2026-08-11 20:54:29','2026-08-11 20:54:29'),(21,21,'DEMO-CHT-0020','2025-06-28','walk_in',NULL,NULL,NULL,NULL,'2026-08-11 20:54:29','2026-08-11 20:54:29'),(22,22,'DEMO-CHT-0021','2025-06-08','walk_in',NULL,NULL,NULL,NULL,'2026-08-11 20:54:29','2026-08-11 20:54:29'),(23,23,'DEMO-CHT-0022','2025-05-19','referral',NULL,NULL,NULL,NULL,'2026-08-11 20:54:29','2026-08-11 20:54:29'),(24,24,'DEMO-CHT-0023','2025-04-29','walk_in',NULL,NULL,NULL,NULL,'2026-08-11 20:54:29','2026-08-11 20:54:29'),(25,25,'DEMO-CHT-0024','2025-04-09','walk_in',NULL,NULL,NULL,NULL,'2026-08-11 20:54:29','2026-08-11 20:54:29'),(26,26,'DEMO-CHT-0025','2025-03-20','referral',NULL,NULL,NULL,NULL,'2026-08-11 20:54:29','2026-08-11 20:54:29'),(27,27,'DEMO-CHT-0026','2025-02-28','walk_in',NULL,NULL,NULL,NULL,'2026-08-11 20:54:29','2026-08-11 20:54:29'),(28,28,'DEMO-CHT-0027','2025-02-08','walk_in',NULL,NULL,NULL,NULL,'2026-08-11 20:54:29','2026-08-11 20:54:29'),(29,29,'DEMO-CHT-0028','2025-01-19','referral',NULL,NULL,NULL,NULL,'2026-08-11 20:54:29','2026-08-11 20:54:29'),(30,68,'CHT-0001','2026-08-15','walk_in',NULL,NULL,NULL,NULL,'2026-08-15 11:05:17','2026-08-15 11:05:17'),(31,72,'CHT-0002','2026-08-15','activity_registration',NULL,NULL,NULL,NULL,'2026-08-15 11:28:11','2026-08-15 11:28:11'),(32,73,'CHT-0003','2026-08-15','walk_in',NULL,NULL,NULL,NULL,'2026-08-15 13:14:42','2026-08-15 13:14:42'),(33,74,'CHT-0004','2026-08-16','walk_in',NULL,NULL,NULL,NULL,'2026-08-16 09:48:55','2026-08-16 09:48:55'),(34,75,'CHT-0005','2026-08-16','walk_in',NULL,NULL,NULL,NULL,'2026-08-16 09:51:02','2026-08-16 09:51:02'),(35,76,'CHT-0006','2026-08-16','walk_in',NULL,NULL,NULL,NULL,'2026-08-16 10:34:53','2026-08-16 10:34:53'),(36,77,'CHT-0007','2026-08-16','walk_in',NULL,NULL,NULL,NULL,'2026-08-16 11:42:41','2026-08-16 11:42:41'),(37,78,'CHT-0008','2026-08-16','walk_in',NULL,NULL,NULL,NULL,'2026-08-16 11:59:02','2026-08-16 11:59:02');
/*!40000 ALTER TABLE `ptp_cohort_profiles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ptp_consents`
--

DROP TABLE IF EXISTS `ptp_consents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ptp_consents` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `participant_id` bigint unsigned NOT NULL,
  `consent_document_id` bigint unsigned DEFAULT NULL,
  `status` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `consent_version` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `consented_at` date DEFAULT NULL,
  `file_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `note` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `recorded_via` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'registration',
  `recorded_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `ptp_consents_recorded_by_foreign` (`recorded_by`) USING BTREE,
  KEY `ptp_consents_participant_id_created_at_index` (`participant_id`,`created_at`) USING BTREE,
  KEY `ptp_consents_consent_document_id_foreign` (`consent_document_id`),
  CONSTRAINT `ptp_consents_consent_document_id_foreign` FOREIGN KEY (`consent_document_id`) REFERENCES `mst_consent_documents` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `ptp_consents_participant_id_foreign` FOREIGN KEY (`participant_id`) REFERENCES `ptp_participants` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `ptp_consents_recorded_by_foreign` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ptp_consents`
--

LOCK TABLES `ptp_consents` WRITE;
/*!40000 ALTER TABLE `ptp_consents` DISABLE KEYS */;
INSERT INTO `ptp_consents` VALUES (1,49,2,'ยินยอม','1.0','2026-08-13',NULL,NULL,'registration',NULL,'2026-08-13 11:20:12'),(2,50,2,'ยินยอม','1.0','2026-08-13',NULL,NULL,'registration',NULL,'2026-08-13 11:20:12'),(3,51,2,'ยินยอม','1.0','2026-08-14',NULL,NULL,'registration',NULL,'2026-08-14 08:01:43'),(4,52,2,'ยินยอม','1.0','2026-08-14',NULL,NULL,'registration',NULL,'2026-08-14 08:01:43'),(5,53,2,'ยินยอม','1.0','2026-08-14',NULL,NULL,'registration',NULL,'2026-08-14 09:05:56'),(6,54,2,'ยินยอม','1.0','2026-08-14',NULL,NULL,'registration',NULL,'2026-08-14 09:05:56'),(7,55,2,'ยินยอม','1.0','2026-08-14',NULL,NULL,'registration',NULL,'2026-08-14 09:14:43'),(8,56,2,'ยินยอม','1.0','2026-08-14',NULL,NULL,'registration',NULL,'2026-08-14 14:13:32'),(9,57,2,'ยินยอม','1.0','2026-08-14',NULL,NULL,'registration',NULL,'2026-08-14 14:13:32'),(10,58,2,'ยินยอม','1.0','2026-08-14',NULL,NULL,'registration',NULL,'2026-08-14 14:17:33'),(11,59,2,'ยินยอม','1.0','2026-08-14',NULL,NULL,'registration',NULL,'2026-08-14 14:17:33'),(12,60,2,'ยินยอม','1.0','2026-08-14',NULL,NULL,'registration',NULL,'2026-08-14 16:50:18'),(13,61,2,'ยินยอม','1.0','2026-08-15',NULL,NULL,'registration',NULL,'2026-08-15 04:33:15'),(14,62,2,'ยินยอม','1.0','2026-08-15',NULL,NULL,'registration',NULL,'2026-08-15 04:33:15'),(15,49,2,'ยินยอม','1.0','2026-08-15',NULL,NULL,'registration',NULL,'2026-08-15 06:01:20'),(16,63,2,'ยินยอม','1.0','2026-08-15',NULL,NULL,'registration',NULL,'2026-08-15 06:18:07'),(17,49,2,'ยินยอม','1.0','2026-08-15',NULL,NULL,'registration',NULL,'2026-08-15 10:37:55'),(18,64,2,'ยินยอม','1.0','2026-08-15',NULL,NULL,'registration',NULL,'2026-08-15 10:37:55'),(19,65,2,'ยินยอม','1.0','2026-08-15',NULL,NULL,'registration',NULL,'2026-08-15 10:43:38'),(20,66,2,'ยินยอม','1.0','2026-08-15',NULL,NULL,'registration',NULL,'2026-08-15 10:44:20'),(21,67,2,'ยินยอม','1.0','2026-08-15',NULL,NULL,'registration',NULL,'2026-08-15 11:02:30'),(22,68,NULL,'ยินยอม','1.0','2026-08-15',NULL,NULL,'admin_cohort',6,'2026-08-15 11:05:17'),(23,69,2,'ยินยอม','1.0','2026-08-15',NULL,NULL,'registration',NULL,'2026-08-15 11:17:52'),(24,70,2,'ยินยอม','1.0','2026-08-15',NULL,NULL,'registration',NULL,'2026-08-15 11:20:28'),(25,71,2,'ยินยอม','1.0','2026-08-15',NULL,NULL,'registration',NULL,'2026-08-15 11:21:45'),(26,72,NULL,'ยินยอม','1.0','2026-08-15','cohort-consents/lsare1H4apRI16F9yrP8Voh1pt8hK6eDXEE9MTzq.png',NULL,'admin_cohort',6,'2026-08-15 11:28:11'),(27,73,NULL,'ยินยอม','1.0','2026-08-15','cohort-consents/LKXliSxQAyVyHfaYtGMF4M4AEo3VF1n9jL8IZT2M.png',NULL,'admin_cohort',6,'2026-08-15 13:14:42'),(28,74,NULL,'ยินยอม','1.0','2026-08-16',NULL,NULL,'self_qr',NULL,'2026-08-16 09:48:55'),(29,75,NULL,'ยินยอม','1.0','2026-08-16',NULL,NULL,'self_qr',NULL,'2026-08-16 09:51:02'),(30,76,NULL,'ยินยอม','1.0','2026-08-16',NULL,NULL,'self_qr',NULL,'2026-08-16 10:34:53'),(31,77,NULL,'ยินยอม','1.0','2026-08-16',NULL,NULL,'self_qr',NULL,'2026-08-16 11:42:41'),(32,78,NULL,'ยินยอม','1.0','2026-08-16',NULL,NULL,'self_qr',NULL,'2026-08-16 11:59:02');
/*!40000 ALTER TABLE `ptp_consents` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ptp_follow_up_notes`
--

DROP TABLE IF EXISTS `ptp_follow_up_notes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ptp_follow_up_notes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `participant_id` bigint unsigned NOT NULL,
  `source` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `kind` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `noted_at` timestamp NOT NULL,
  `body` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `ptp_follow_up_notes_created_by_foreign` (`created_by`) USING BTREE,
  KEY `ptp_follow_up_notes_participant_id_noted_at_index` (`participant_id`,`noted_at`) USING BTREE,
  CONSTRAINT `ptp_follow_up_notes_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `ptp_follow_up_notes_participant_id_foreign` FOREIGN KEY (`participant_id`) REFERENCES `ptp_participants` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ptp_follow_up_notes`
--

LOCK TABLES `ptp_follow_up_notes` WRITE;
/*!40000 ALTER TABLE `ptp_follow_up_notes` DISABLE KEYS */;
INSERT INTO `ptp_follow_up_notes` VALUES (1,74,'ระบบแจ้งเตือน','แจ้งเตือน LINE','2026-08-16 11:35:09','ส่งแจ้งเตือน6 เดือน · ส่งสำเร็จ (รอบ เทส)',6,'2026-08-16 11:35:09','2026-08-16 11:35:09');
/*!40000 ALTER TABLE `ptp_follow_up_notes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ptp_follow_up_rounds`
--

DROP TABLE IF EXISTS `ptp_follow_up_rounds`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ptp_follow_up_rounds` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `cohort_profile_id` bigint unsigned NOT NULL,
  `template_id` bigint unsigned DEFAULT NULL,
  `name` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `offset_days` smallint unsigned NOT NULL,
  `due_date` date NOT NULL,
  `answered_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `ptp_follow_up_rounds_cohort_profile_id_offset_days_unique` (`cohort_profile_id`,`offset_days`) USING BTREE,
  KEY `ptp_follow_up_rounds_template_id_foreign` (`template_id`) USING BTREE,
  KEY `ptp_follow_up_rounds_due_date_index` (`due_date`) USING BTREE,
  KEY `ptp_follow_up_rounds_answered_at_index` (`answered_at`) USING BTREE,
  CONSTRAINT `ptp_follow_up_rounds_cohort_profile_id_foreign` FOREIGN KEY (`cohort_profile_id`) REFERENCES `ptp_cohort_profiles` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `ptp_follow_up_rounds_template_id_foreign` FOREIGN KEY (`template_id`) REFERENCES `mst_follow_up_round_templates` (`id`) ON DELETE SET NULL ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=118 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ptp_follow_up_rounds`
--

LOCK TABLES `ptp_follow_up_rounds` WRITE;
/*!40000 ALTER TABLE `ptp_follow_up_rounds` DISABLE KEYS */;
INSERT INTO `ptp_follow_up_rounds` VALUES (2,2,1,'ก่อนเข้าร่วม',0,'2026-07-13','2026-07-15 03:30:00','2026-08-11 20:54:29','2026-08-11 20:54:29'),(3,3,1,'ก่อนเข้าร่วม',0,'2026-06-23','2026-06-25 03:30:00','2026-08-11 20:54:29','2026-08-11 20:54:29'),(4,4,1,'ก่อนเข้าร่วม',0,'2026-06-03','2026-06-05 03:30:00','2026-08-11 20:54:29','2026-08-11 20:54:29'),(5,5,1,'ก่อนเข้าร่วม',0,'2026-05-14','2026-05-16 03:30:00','2026-08-11 20:54:29','2026-08-11 20:54:29'),(6,5,10,'3 เดือน',90,'2026-08-12','2026-08-14 03:30:00','2026-08-11 20:54:29','2026-08-11 20:54:29'),(7,6,1,'ก่อนเข้าร่วม',0,'2026-04-24','2026-04-26 03:30:00','2026-08-11 20:54:29','2026-08-11 20:54:29'),(8,6,10,'3 เดือน',90,'2026-07-23','2026-07-25 03:30:00','2026-08-11 20:54:29','2026-08-11 20:54:29'),(9,7,1,'ก่อนเข้าร่วม',0,'2026-04-04','2026-04-06 03:30:00','2026-08-11 20:54:29','2026-08-11 20:54:29'),(10,7,10,'3 เดือน',90,'2026-07-03','2026-07-05 03:30:00','2026-08-11 20:54:29','2026-08-11 20:54:29'),(11,8,1,'ก่อนเข้าร่วม',0,'2026-03-15','2026-03-17 03:30:00','2026-08-11 20:54:29','2026-08-11 20:54:29'),(12,8,10,'3 เดือน',90,'2026-06-13','2026-06-15 03:30:00','2026-08-11 20:54:29','2026-08-11 20:54:29'),(13,9,1,'ก่อนเข้าร่วม',0,'2026-02-23','2026-02-25 03:30:00','2026-08-11 20:54:29','2026-08-11 20:54:29'),(14,9,10,'3 เดือน',90,'2026-05-24','2026-05-26 03:30:00','2026-08-11 20:54:29','2026-08-11 20:54:29'),(15,10,1,'ก่อนเข้าร่วม',0,'2026-02-03','2026-02-05 03:30:00','2026-08-11 20:54:29','2026-08-11 20:54:29'),(16,10,10,'3 เดือน',90,'2026-05-04','2026-05-06 03:30:00','2026-08-11 20:54:29','2026-08-11 20:54:29'),(17,10,11,'6 เดือน',180,'2026-08-02',NULL,'2026-08-11 20:54:29','2026-08-11 20:54:29'),(18,11,1,'ก่อนเข้าร่วม',0,'2026-01-14','2026-01-16 03:30:00','2026-08-11 20:54:29','2026-08-11 20:54:29'),(19,11,10,'3 เดือน',90,'2026-04-14','2026-04-16 03:30:00','2026-08-11 20:54:29','2026-08-11 20:54:29'),(20,11,11,'6 เดือน',180,'2026-07-13','2026-07-15 03:30:00','2026-08-11 20:54:29','2026-08-11 20:54:29'),(21,12,1,'ก่อนเข้าร่วม',0,'2025-12-25','2025-12-27 03:30:00','2026-08-11 20:54:29','2026-08-11 20:54:29'),(22,12,10,'3 เดือน',90,'2026-03-25','2026-03-27 03:30:00','2026-08-11 20:54:29','2026-08-11 20:54:29'),(23,12,11,'6 เดือน',180,'2026-06-23','2026-06-25 03:30:00','2026-08-11 20:54:29','2026-08-11 20:54:29'),(24,13,1,'ก่อนเข้าร่วม',0,'2025-12-05','2025-12-07 03:30:00','2026-08-11 20:54:29','2026-08-11 20:54:29'),(25,13,10,'3 เดือน',90,'2026-03-05','2026-03-07 03:30:00','2026-08-11 20:54:29','2026-08-11 20:54:29'),(26,13,11,'6 เดือน',180,'2026-06-03','2026-06-05 03:30:00','2026-08-11 20:54:29','2026-08-11 20:54:29'),(27,14,1,'ก่อนเข้าร่วม',0,'2025-11-15','2025-11-17 03:30:00','2026-08-11 20:54:29','2026-08-11 20:54:29'),(28,14,10,'3 เดือน',90,'2026-02-13','2026-02-15 03:30:00','2026-08-11 20:54:29','2026-08-11 20:54:29'),(29,14,11,'6 เดือน',180,'2026-05-14','2026-05-16 03:30:00','2026-08-11 20:54:29','2026-08-11 20:54:29'),(30,15,1,'ก่อนเข้าร่วม',0,'2025-10-26','2025-10-28 03:30:00','2026-08-11 20:54:29','2026-08-11 20:54:29'),(31,15,10,'3 เดือน',90,'2026-01-24',NULL,'2026-08-11 20:54:29','2026-08-11 20:54:29'),(32,15,11,'6 เดือน',180,'2026-04-24','2026-04-26 03:30:00','2026-08-11 20:54:29','2026-08-11 20:54:29'),(33,16,1,'ก่อนเข้าร่วม',0,'2025-10-06','2025-10-08 03:30:00','2026-08-11 20:54:29','2026-08-11 20:54:29'),(34,16,10,'3 เดือน',90,'2026-01-04','2026-01-06 03:30:00','2026-08-11 20:54:29','2026-08-11 20:54:29'),(35,16,11,'6 เดือน',180,'2026-04-04','2026-04-06 03:30:00','2026-08-11 20:54:29','2026-08-11 20:54:29'),(36,17,1,'ก่อนเข้าร่วม',0,'2025-09-16','2025-09-18 03:30:00','2026-08-11 20:54:29','2026-08-11 20:54:29'),(37,17,10,'3 เดือน',90,'2025-12-15','2025-12-17 03:30:00','2026-08-11 20:54:29','2026-08-11 20:54:29'),(38,17,11,'6 เดือน',180,'2026-03-15',NULL,'2026-08-11 20:54:29','2026-08-11 20:54:29'),(39,18,1,'ก่อนเข้าร่วม',0,'2025-08-27','2025-08-29 03:30:00','2026-08-11 20:54:29','2026-08-11 20:54:29'),(40,18,10,'3 เดือน',90,'2025-11-25','2025-11-27 03:30:00','2026-08-11 20:54:29','2026-08-11 20:54:29'),(41,18,11,'6 เดือน',180,'2026-02-23','2026-02-25 03:30:00','2026-08-11 20:54:29','2026-08-11 20:54:29'),(42,19,1,'ก่อนเข้าร่วม',0,'2025-08-07','2025-08-09 03:30:00','2026-08-11 20:54:30','2026-08-11 20:54:30'),(43,19,10,'3 เดือน',90,'2025-11-05','2025-11-07 03:30:00','2026-08-11 20:54:30','2026-08-11 20:54:30'),(44,19,11,'6 เดือน',180,'2026-02-03','2026-02-05 03:30:00','2026-08-11 20:54:30','2026-08-11 20:54:30'),(45,19,12,'12 เดือน',365,'2026-08-07',NULL,'2026-08-11 20:54:30','2026-08-11 20:54:30'),(46,20,1,'ก่อนเข้าร่วม',0,'2025-07-18','2025-07-20 03:30:00','2026-08-11 20:54:30','2026-08-11 20:54:30'),(47,20,10,'3 เดือน',90,'2025-10-16','2025-10-18 03:30:00','2026-08-11 20:54:30','2026-08-11 20:54:30'),(48,20,11,'6 เดือน',180,'2026-01-14','2026-01-16 03:30:00','2026-08-11 20:54:30','2026-08-11 20:54:30'),(49,20,12,'12 เดือน',365,'2026-07-18','2026-07-20 03:30:00','2026-08-11 20:54:30','2026-08-11 20:54:30'),(50,21,1,'ก่อนเข้าร่วม',0,'2025-06-28','2025-06-30 03:30:00','2026-08-11 20:54:30','2026-08-11 20:54:30'),(51,21,10,'3 เดือน',90,'2025-09-26','2025-09-28 03:30:00','2026-08-11 20:54:30','2026-08-11 20:54:30'),(52,21,11,'6 เดือน',180,'2025-12-25','2025-12-27 03:30:00','2026-08-11 20:54:30','2026-08-11 20:54:30'),(53,21,12,'12 เดือน',365,'2026-06-28',NULL,'2026-08-11 20:54:30','2026-08-11 20:54:30'),(54,22,1,'ก่อนเข้าร่วม',0,'2025-06-08','2025-06-10 03:30:00','2026-08-11 20:54:30','2026-08-11 20:54:30'),(55,22,10,'3 เดือน',90,'2025-09-06','2025-09-08 03:30:00','2026-08-11 20:54:30','2026-08-11 20:54:30'),(56,22,11,'6 เดือน',180,'2025-12-05','2025-12-07 03:30:00','2026-08-11 20:54:30','2026-08-11 20:54:30'),(57,22,12,'12 เดือน',365,'2026-06-08','2026-06-10 03:30:00','2026-08-11 20:54:30','2026-08-11 20:54:30'),(58,23,1,'ก่อนเข้าร่วม',0,'2025-05-19','2025-05-21 03:30:00','2026-08-11 20:54:30','2026-08-11 20:54:30'),(59,23,10,'3 เดือน',90,'2025-08-17','2025-08-19 03:30:00','2026-08-11 20:54:30','2026-08-11 20:54:30'),(60,23,11,'6 เดือน',180,'2025-11-15','2025-11-17 03:30:00','2026-08-11 20:54:30','2026-08-11 20:54:30'),(61,23,12,'12 เดือน',365,'2026-05-19','2026-05-21 03:30:00','2026-08-11 20:54:30','2026-08-11 20:54:30'),(62,24,1,'ก่อนเข้าร่วม',0,'2025-04-29','2025-05-01 03:30:00','2026-08-11 20:54:30','2026-08-11 20:54:30'),(63,24,10,'3 เดือน',90,'2025-07-28','2025-07-30 03:30:00','2026-08-11 20:54:30','2026-08-11 20:54:30'),(64,24,11,'6 เดือน',180,'2025-10-26',NULL,'2026-08-11 20:54:30','2026-08-11 20:54:30'),(65,24,12,'12 เดือน',365,'2026-04-29','2026-05-01 03:30:00','2026-08-11 20:54:30','2026-08-11 20:54:30'),(66,25,1,'ก่อนเข้าร่วม',0,'2025-04-09','2025-04-11 03:30:00','2026-08-11 20:54:30','2026-08-11 20:54:30'),(67,25,10,'3 เดือน',90,'2025-07-08','2025-07-10 03:30:00','2026-08-11 20:54:30','2026-08-11 20:54:30'),(68,25,11,'6 เดือน',180,'2025-10-06','2025-10-08 03:30:00','2026-08-11 20:54:30','2026-08-11 20:54:30'),(69,25,12,'12 เดือน',365,'2026-04-09','2026-04-11 03:30:00','2026-08-11 20:54:30','2026-08-11 20:54:30'),(70,26,1,'ก่อนเข้าร่วม',0,'2025-03-20','2025-03-22 03:30:00','2026-08-11 20:54:30','2026-08-11 20:54:30'),(71,26,10,'3 เดือน',90,'2025-06-18','2025-06-20 03:30:00','2026-08-11 20:54:30','2026-08-11 20:54:30'),(72,26,11,'6 เดือน',180,'2025-09-16',NULL,'2026-08-11 20:54:30','2026-08-11 20:54:30'),(73,26,12,'12 เดือน',365,'2026-03-20','2026-03-22 03:30:00','2026-08-11 20:54:30','2026-08-11 20:54:30'),(74,27,1,'ก่อนเข้าร่วม',0,'2025-02-28','2025-03-02 03:30:00','2026-08-11 20:54:30','2026-08-11 20:54:30'),(75,27,10,'3 เดือน',90,'2025-05-29','2025-05-31 03:30:00','2026-08-11 20:54:30','2026-08-11 20:54:30'),(76,27,11,'6 เดือน',180,'2025-08-27','2025-08-29 03:30:00','2026-08-11 20:54:30','2026-08-11 20:54:30'),(77,27,12,'12 เดือน',365,'2026-02-28',NULL,'2026-08-11 20:54:30','2026-08-11 20:54:30'),(78,28,1,'ก่อนเข้าร่วม',0,'2025-02-08','2025-02-10 03:30:00','2026-08-11 20:54:30','2026-08-11 20:54:30'),(79,28,10,'3 เดือน',90,'2025-05-09','2025-05-11 03:30:00','2026-08-11 20:54:30','2026-08-11 20:54:30'),(80,28,11,'6 เดือน',180,'2025-08-07','2025-08-09 03:30:00','2026-08-11 20:54:30','2026-08-11 20:54:30'),(81,28,12,'12 เดือน',365,'2026-02-08','2026-02-10 03:30:00','2026-08-11 20:54:30','2026-08-11 20:54:30'),(82,29,1,'ก่อนเข้าร่วม',0,'2025-01-19','2025-01-21 03:30:00','2026-08-11 20:54:30','2026-08-11 20:54:30'),(83,29,10,'3 เดือน',90,'2025-04-19','2025-04-21 03:30:00','2026-08-11 20:54:30','2026-08-11 20:54:30'),(84,29,11,'6 เดือน',180,'2025-07-18','2025-07-20 03:30:00','2026-08-11 20:54:30','2026-08-11 20:54:30'),(85,29,12,'12 เดือน',365,'2026-01-19','2026-01-21 03:30:00','2026-08-11 20:54:30','2026-08-11 20:54:30'),(86,30,1,'ก่อนเข้าร่วม',0,'2026-08-15',NULL,'2026-08-15 11:05:17','2026-08-15 11:05:17'),(87,30,10,'3 เดือน',90,'2026-11-13',NULL,'2026-08-15 11:05:17','2026-08-15 11:05:17'),(88,30,11,'6 เดือน',180,'2027-02-11',NULL,'2026-08-15 11:05:17','2026-08-15 11:05:17'),(89,30,12,'12 เดือน',365,'2027-08-15',NULL,'2026-08-15 11:05:17','2026-08-15 11:05:17'),(90,31,1,'ก่อนเข้าร่วม',0,'2026-08-15',NULL,'2026-08-15 11:28:11','2026-08-15 11:28:11'),(91,31,10,'3 เดือน',90,'2026-11-13',NULL,'2026-08-15 11:28:11','2026-08-15 11:28:11'),(92,31,11,'6 เดือน',180,'2027-02-11',NULL,'2026-08-15 11:28:11','2026-08-15 11:28:11'),(93,31,12,'12 เดือน',365,'2027-08-15',NULL,'2026-08-15 11:28:11','2026-08-15 11:28:11'),(94,32,1,'ก่อนเข้าร่วม',0,'2026-08-15',NULL,'2026-08-15 13:14:42','2026-08-15 13:14:42'),(95,32,10,'3 เดือน',90,'2026-11-13',NULL,'2026-08-15 13:14:42','2026-08-15 13:14:42'),(96,32,11,'6 เดือน',180,'2027-02-11',NULL,'2026-08-15 13:14:42','2026-08-15 13:14:42'),(97,32,12,'12 เดือน',365,'2027-08-15',NULL,'2026-08-15 13:14:42','2026-08-15 13:14:42'),(98,33,1,'ก่อนเข้าร่วม',0,'2026-08-16','2026-08-16 09:49:11','2026-08-16 09:48:55','2026-08-16 09:49:11'),(99,33,10,'3 เดือน',90,'2026-11-14','2026-08-16 11:33:48','2026-08-16 09:48:55','2026-08-16 11:33:48'),(100,33,11,'6 เดือน',180,'2027-02-12',NULL,'2026-08-16 09:48:55','2026-08-16 09:48:55'),(101,33,12,'12 เดือน',365,'2027-08-16',NULL,'2026-08-16 09:48:55','2026-08-16 09:48:55'),(102,34,1,'ก่อนเข้าร่วม',0,'2026-08-16','2026-08-16 09:51:19','2026-08-16 09:51:02','2026-08-16 09:51:19'),(103,34,10,'3 เดือน',90,'2026-11-14',NULL,'2026-08-16 09:51:02','2026-08-16 09:51:02'),(104,34,11,'6 เดือน',180,'2027-02-12',NULL,'2026-08-16 09:51:02','2026-08-16 09:51:02'),(105,34,12,'12 เดือน',365,'2027-08-16',NULL,'2026-08-16 09:51:02','2026-08-16 09:51:02'),(106,35,1,'ก่อนเข้าร่วม',0,'2026-08-16',NULL,'2026-08-16 10:34:53','2026-08-16 10:34:53'),(107,35,10,'3 เดือน',90,'2026-11-14',NULL,'2026-08-16 10:34:53','2026-08-16 10:34:53'),(108,35,11,'6 เดือน',180,'2027-02-12',NULL,'2026-08-16 10:34:53','2026-08-16 10:34:53'),(109,35,12,'12 เดือน',365,'2027-08-16',NULL,'2026-08-16 10:34:53','2026-08-16 10:34:53'),(110,36,1,'ก่อนเข้าร่วม',0,'2026-08-16',NULL,'2026-08-16 11:42:41','2026-08-16 11:42:41'),(111,36,10,'3 เดือน',90,'2026-11-14',NULL,'2026-08-16 11:42:41','2026-08-16 11:42:41'),(112,36,11,'6 เดือน',180,'2027-02-12',NULL,'2026-08-16 11:42:41','2026-08-16 11:42:41'),(113,36,12,'12 เดือน',365,'2027-08-16',NULL,'2026-08-16 11:42:41','2026-08-16 11:42:41'),(114,37,1,'ก่อนเข้าร่วม',0,'2026-08-16',NULL,'2026-08-16 11:59:02','2026-08-16 11:59:02'),(115,37,10,'3 เดือน',90,'2026-11-14',NULL,'2026-08-16 11:59:02','2026-08-16 11:59:02'),(116,37,11,'6 เดือน',180,'2027-02-12',NULL,'2026-08-16 11:59:02','2026-08-16 11:59:02'),(117,37,12,'12 เดือน',365,'2027-08-16',NULL,'2026-08-16 11:59:02','2026-08-16 11:59:02');
/*!40000 ALTER TABLE `ptp_follow_up_rounds` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ptp_participants`
--

DROP TABLE IF EXISTS `ptp_participants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ptp_participants` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `person_code` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(160) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(160) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `gender` enum('male','female','other','undisclosed') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `birth_year` smallint unsigned DEFAULT NULL,
  `age_range_id` bigint unsigned DEFAULT NULL,
  `occupation_id` bigint unsigned DEFAULT NULL,
  `occupation_raw` varchar(160) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `area_id` bigint unsigned DEFAULT NULL,
  `target_group_id` bigint unsigned DEFAULT NULL,
  `source_channel_id` bigint unsigned DEFAULT NULL,
  `contact_channel_id` bigint unsigned DEFAULT NULL,
  `source` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `has_caregiver` tinyint(1) NOT NULL DEFAULT '0',
  `caregiver_name` varchar(160) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `caregiver_relation` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `caregiver_phone` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ใช้งานอยู่',
  `project_status` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'เข้าร่วม',
  `consent_status` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'รอยืนยัน',
  `line_user_id` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `line_notify` tinyint(1) NOT NULL DEFAULT '1',
  `line_display_name` varchar(160) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `line_picture_url` varchar(512) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `ptp_participants_code_unique` (`code`) USING BTREE,
  UNIQUE KEY `ptp_participants_person_code_unique` (`person_code`) USING BTREE,
  UNIQUE KEY `ptp_participants_line_user_id_unique` (`line_user_id`) USING BTREE,
  KEY `ptp_participants_occupation_id_foreign` (`occupation_id`) USING BTREE,
  KEY `ptp_participants_target_group_id_foreign` (`target_group_id`) USING BTREE,
  KEY `ptp_participants_source_channel_id_foreign` (`source_channel_id`) USING BTREE,
  KEY `ptp_participants_contact_channel_id_foreign` (`contact_channel_id`) USING BTREE,
  KEY `ptp_participants_created_by_foreign` (`created_by`) USING BTREE,
  KEY `ptp_participants_updated_by_foreign` (`updated_by`) USING BTREE,
  KEY `ptp_participants_phone_index` (`phone`) USING BTREE,
  KEY `ptp_participants_status_index` (`status`) USING BTREE,
  KEY `ptp_participants_area_id_target_group_id_index` (`area_id`,`target_group_id`) USING BTREE,
  KEY `ptp_participants_age_range_id_foreign` (`age_range_id`),
  CONSTRAINT `ptp_participants_age_range_id_foreign` FOREIGN KEY (`age_range_id`) REFERENCES `mst_options` (`id`),
  CONSTRAINT `ptp_participants_area_id_foreign` FOREIGN KEY (`area_id`) REFERENCES `mst_areas` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `ptp_participants_contact_channel_id_foreign` FOREIGN KEY (`contact_channel_id`) REFERENCES `mst_options` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `ptp_participants_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `ptp_participants_occupation_id_foreign` FOREIGN KEY (`occupation_id`) REFERENCES `mst_options` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `ptp_participants_source_channel_id_foreign` FOREIGN KEY (`source_channel_id`) REFERENCES `mst_options` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `ptp_participants_target_group_id_foreign` FOREIGN KEY (`target_group_id`) REFERENCES `mst_target_groups` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `ptp_participants_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=79 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ptp_participants`
--

LOCK TABLES `ptp_participants` WRITE;
/*!40000 ALTER TABLE `ptp_participants` DISABLE KEYS */;
INSERT INTO `ptp_participants` VALUES (2,'DEMO-PTP-0001','DEMO-PSN-0001','สมชาย ใจงาม','081-100-1000',NULL,'female',1955,NULL,5,NULL,1,3,9,NULL,NULL,0,NULL,NULL,NULL,'ใช้งานอยู่','เข้าร่วม','ยินยอมแล้ว',NULL,1,NULL,NULL,NULL,NULL,'2026-08-11 20:54:29','2026-08-11 20:54:29',NULL),(3,'DEMO-PTP-0002','DEMO-PSN-0002','วิภาดา สายใจ','082-101-1007',NULL,'male',1962,NULL,4,NULL,2,3,14,NULL,NULL,0,NULL,NULL,NULL,'ใช้งานอยู่','เข้าร่วม','ยินยอมแล้ว',NULL,1,NULL,NULL,NULL,NULL,'2026-08-11 20:54:29','2026-08-11 20:54:29',NULL),(4,'DEMO-PTP-0003','DEMO-PSN-0003','ธีรพงษ์ แสงทอง','083-102-1014',NULL,'male',1969,NULL,2,NULL,2,2,13,NULL,NULL,0,NULL,NULL,NULL,'ใช้งานอยู่','เข้าร่วม','ยินยอมแล้ว',NULL,1,NULL,NULL,NULL,NULL,'2026-08-11 20:54:29','2026-08-11 20:56:26',NULL),(5,'DEMO-PTP-0004','DEMO-PSN-0004','กัลยา รุ่งเจริญ','084-103-1021',NULL,'female',1976,NULL,3,NULL,1,2,10,NULL,NULL,0,NULL,NULL,NULL,'ใช้งานอยู่','เข้าร่วม','ยินยอมแล้ว',NULL,1,NULL,NULL,NULL,NULL,'2026-08-11 20:54:29','2026-08-11 20:56:26',NULL),(6,'DEMO-PTP-0005','DEMO-PSN-0005','ประภาส ทองแท้','085-104-1028',NULL,'other',1983,NULL,5,NULL,3,2,9,NULL,NULL,0,NULL,NULL,NULL,'ใช้งานอยู่','เข้าร่วม','ยินยอมแล้ว',NULL,1,NULL,NULL,NULL,NULL,'2026-08-11 20:54:29','2026-08-11 20:56:26',NULL),(7,'DEMO-PTP-0006','DEMO-PSN-0006','มณีรัตน์ ใจบุญ','086-105-1035',NULL,'female',1990,NULL,2,NULL,2,2,14,NULL,NULL,0,NULL,NULL,NULL,'ใช้งานอยู่','เข้าร่วม','ยินยอมแล้ว',NULL,1,NULL,NULL,NULL,NULL,'2026-08-11 20:54:29','2026-08-11 20:54:29',NULL),(8,'DEMO-PTP-0007','DEMO-PSN-0007','อดิศักดิ์ พูลสวัสดิ์','087-106-1042',NULL,'male',1997,NULL,3,NULL,1,4,11,NULL,NULL,0,NULL,NULL,NULL,'ใช้งานอยู่','เข้าร่วม','ยินยอมแล้ว',NULL,1,NULL,NULL,NULL,NULL,'2026-08-11 20:54:29','2026-08-11 20:56:26',NULL),(9,'DEMO-PTP-0008','DEMO-PSN-0008','พิมพ์ใจ เพียรทำ','088-107-1049',NULL,'male',2004,NULL,4,NULL,1,2,14,NULL,NULL,0,NULL,NULL,NULL,'ใช้งานอยู่','เข้าร่วม','ยินยอมแล้ว',NULL,1,NULL,NULL,NULL,NULL,'2026-08-11 20:54:29','2026-08-11 20:56:26',NULL),(10,'DEMO-PTP-0009','DEMO-PSN-0009','ณัฐวุฒิ ใจงาม','089-108-1056',NULL,'female',1961,NULL,1,NULL,3,3,11,NULL,NULL,0,NULL,NULL,NULL,'ใช้งานอยู่','เข้าร่วม','ยินยอมแล้ว',NULL,1,NULL,NULL,NULL,NULL,'2026-08-11 20:54:29','2026-08-11 20:56:26',NULL),(11,'DEMO-PTP-0010','DEMO-PSN-0010','สุพรรณี สายใจ','081-109-1063',NULL,'other',1968,NULL,2,NULL,1,2,10,NULL,NULL,0,NULL,NULL,NULL,'ใช้งานอยู่','เข้าร่วม','ยินยอมแล้ว',NULL,1,NULL,NULL,NULL,NULL,'2026-08-11 20:54:29','2026-08-11 20:56:26',NULL),(12,'DEMO-PTP-0011','DEMO-PSN-0011','สมชาย แสงทอง','082-110-1070',NULL,'female',1975,NULL,3,NULL,1,2,13,NULL,NULL,0,NULL,NULL,NULL,'ใช้งานอยู่','เข้าร่วม','ยินยอมแล้ว',NULL,1,NULL,NULL,NULL,NULL,'2026-08-11 20:54:29','2026-08-11 20:56:26',NULL),(13,'DEMO-PTP-0012','DEMO-PSN-0012','วิภาดา รุ่งเจริญ','083-111-1077',NULL,'male',1982,NULL,1,NULL,17,2,10,NULL,NULL,0,NULL,NULL,NULL,'ใช้งานอยู่','เข้าร่วม','ยินยอมแล้ว',NULL,1,NULL,NULL,NULL,NULL,'2026-08-11 20:54:29','2026-08-11 20:54:29',NULL),(14,'DEMO-PTP-0013','DEMO-PSN-0013','ธีรพงษ์ ทองแท้','084-112-1084',NULL,'male',1989,NULL,3,NULL,1,2,11,NULL,NULL,0,NULL,NULL,NULL,'ใช้งานอยู่','เข้าร่วม','ยินยอมแล้ว',NULL,1,NULL,NULL,NULL,NULL,'2026-08-11 20:54:29','2026-08-11 20:54:29',NULL),(15,'DEMO-PTP-0014','DEMO-PSN-0014','กัลยา ใจบุญ','085-113-1091',NULL,'female',1996,NULL,5,NULL,2,2,10,NULL,NULL,0,NULL,NULL,NULL,'ใช้งานอยู่','เข้าร่วม','ยินยอมแล้ว',NULL,1,NULL,NULL,NULL,NULL,'2026-08-11 20:54:29','2026-08-11 20:54:29',NULL),(16,'DEMO-PTP-0015','DEMO-PSN-0015','ประภาส พูลสวัสดิ์','086-114-1098',NULL,'other',2003,NULL,1,NULL,3,2,13,NULL,NULL,0,NULL,NULL,NULL,'ใช้งานอยู่','เข้าร่วม','ยินยอมแล้ว',NULL,1,NULL,NULL,NULL,NULL,'2026-08-11 20:54:29','2026-08-11 20:54:29',NULL),(17,'DEMO-PTP-0016','DEMO-PSN-0016','มณีรัตน์ เพียรทำ','087-115-1105',NULL,'female',1960,NULL,1,NULL,2,3,12,NULL,NULL,0,NULL,NULL,NULL,'ใช้งานอยู่','เข้าร่วม','ยินยอมแล้ว',NULL,1,NULL,NULL,NULL,NULL,'2026-08-11 20:54:29','2026-08-11 20:56:26',NULL),(18,'DEMO-PTP-0017','DEMO-PSN-0017','อดิศักดิ์ ใจงาม','088-116-1112',NULL,'male',1967,NULL,4,NULL,2,2,13,NULL,NULL,0,NULL,NULL,NULL,'ใช้งานอยู่','เข้าร่วม','ยินยอมแล้ว',NULL,1,NULL,NULL,NULL,NULL,'2026-08-11 20:54:29','2026-08-11 20:56:26',NULL),(19,'DEMO-PTP-0018','DEMO-PSN-0018','พิมพ์ใจ สายใจ','089-117-1119',NULL,'male',1974,NULL,4,NULL,3,2,14,NULL,NULL,0,NULL,NULL,NULL,'ใช้งานอยู่','เข้าร่วม','ยินยอมแล้ว',NULL,1,NULL,NULL,NULL,NULL,'2026-08-11 20:54:29','2026-08-11 20:56:26',NULL),(20,'DEMO-PTP-0019','DEMO-PSN-0019','ณัฐวุฒิ แสงทอง','081-118-1126',NULL,'female',1981,NULL,5,NULL,3,2,9,NULL,NULL,0,NULL,NULL,NULL,'ใช้งานอยู่','เข้าร่วม','ยินยอมแล้ว',NULL,1,NULL,NULL,NULL,NULL,'2026-08-11 20:54:29','2026-08-11 20:54:29',NULL),(21,'DEMO-PTP-0020','DEMO-PSN-0020','สุพรรณี รุ่งเจริญ','082-119-1133',NULL,'other',1988,NULL,4,NULL,1,2,10,NULL,NULL,0,NULL,NULL,NULL,'ใช้งานอยู่','เข้าร่วม','ยินยอมแล้ว',NULL,1,NULL,NULL,NULL,NULL,'2026-08-11 20:54:29','2026-08-11 20:56:26',NULL),(22,'DEMO-PTP-0021','DEMO-PSN-0021','สมชาย ทองแท้','083-120-1140',NULL,'female',1995,NULL,2,NULL,1,2,9,NULL,NULL,0,NULL,NULL,NULL,'ใช้งานอยู่','เข้าร่วม','ยินยอมแล้ว',NULL,1,NULL,NULL,NULL,NULL,'2026-08-11 20:54:29','2026-08-11 20:54:29',NULL),(23,'DEMO-PTP-0022','DEMO-PSN-0022','วิภาดา ใจบุญ','084-121-1147',NULL,'male',2002,NULL,7,NULL,2,4,12,NULL,NULL,0,NULL,NULL,NULL,'ใช้งานอยู่','เข้าร่วม','ยินยอมแล้ว',NULL,1,NULL,NULL,NULL,NULL,'2026-08-11 20:54:29','2026-08-11 20:56:26',NULL),(24,'DEMO-PTP-0023','DEMO-PSN-0023','ธีรพงษ์ พูลสวัสดิ์','085-122-1154',NULL,'male',1959,NULL,1,NULL,2,3,9,NULL,NULL,0,NULL,NULL,NULL,'ใช้งานอยู่','เข้าร่วม','ยินยอมแล้ว',NULL,1,NULL,NULL,NULL,NULL,'2026-08-11 20:54:29','2026-08-11 20:56:26',NULL),(25,'DEMO-PTP-0024','DEMO-PSN-0024','กัลยา เพียรทำ','086-123-1161',NULL,'female',1966,NULL,7,NULL,1,3,10,NULL,NULL,0,NULL,NULL,NULL,'ใช้งานอยู่','เข้าร่วม','ยินยอมแล้ว',NULL,1,NULL,NULL,NULL,NULL,'2026-08-11 20:54:29','2026-08-11 20:56:26',NULL),(26,'DEMO-PTP-0025','DEMO-PSN-0025','ประภาส ใจงาม','087-124-1168',NULL,'other',1973,NULL,4,NULL,3,2,13,NULL,NULL,0,NULL,NULL,NULL,'ใช้งานอยู่','เข้าร่วม','ยินยอมแล้ว',NULL,1,NULL,NULL,NULL,NULL,'2026-08-11 20:54:29','2026-08-11 20:56:26',NULL),(27,'DEMO-PTP-0026','DEMO-PSN-0026','มณีรัตน์ สายใจ','088-125-1175',NULL,'female',1980,NULL,4,NULL,17,2,14,NULL,NULL,0,NULL,NULL,NULL,'ใช้งานอยู่','เข้าร่วม','ยินยอมแล้ว',NULL,1,NULL,NULL,NULL,NULL,'2026-08-11 20:54:29','2026-08-11 20:56:26',NULL),(28,'DEMO-PTP-0027','DEMO-PSN-0027','อดิศักดิ์ แสงทอง','089-126-1182',NULL,'male',1987,NULL,2,NULL,2,2,13,NULL,NULL,0,NULL,NULL,NULL,'ใช้งานอยู่','เข้าร่วม','ยินยอมแล้ว',NULL,1,NULL,NULL,NULL,NULL,'2026-08-11 20:54:29','2026-08-11 20:56:26',NULL),(29,'DEMO-PTP-0028','DEMO-PSN-0028','พิมพ์ใจ รุ่งเจริญ','081-127-1189',NULL,'male',1994,NULL,4,NULL,3,2,14,NULL,NULL,0,NULL,NULL,NULL,'ใช้งานอยู่','เข้าร่วม','ยินยอมแล้ว',NULL,1,NULL,NULL,NULL,NULL,'2026-08-11 20:54:29','2026-08-11 20:56:26',NULL),(30,'DEMO-PTP-0029','DEMO-PSN-0029','ณัฐวุฒิ ทองแท้','082-128-1196',NULL,'female',2001,NULL,4,NULL,17,4,11,NULL,NULL,0,NULL,NULL,NULL,'ใช้งานอยู่','เข้าร่วม','ยินยอมแล้ว',NULL,1,NULL,NULL,NULL,NULL,'2026-08-11 20:54:29','2026-08-11 20:56:26',NULL),(31,'DEMO-PTP-0030','DEMO-PSN-0030','สุพรรณี ใจบุญ','083-129-1203',NULL,'other',1958,NULL,7,NULL,1,3,10,NULL,NULL,0,NULL,NULL,NULL,'ใช้งานอยู่','เข้าร่วม','ยินยอมแล้ว',NULL,1,NULL,NULL,NULL,NULL,'2026-08-11 20:54:29','2026-08-11 20:56:26',NULL),(32,'DEMO-PTP-0031','DEMO-PSN-0031','สมชาย พูลสวัสดิ์','084-130-1210',NULL,'female',1965,NULL,2,NULL,1,3,10,NULL,NULL,0,NULL,NULL,NULL,'ใช้งานอยู่','เข้าร่วม','ยินยอมแล้ว',NULL,1,NULL,NULL,NULL,NULL,'2026-08-11 20:54:29','2026-08-11 20:56:26',NULL),(33,'DEMO-PTP-0032','DEMO-PSN-0032','วิภาดา เพียรทำ','085-131-1217',NULL,'male',1972,NULL,2,NULL,2,2,9,NULL,NULL,0,NULL,NULL,NULL,'ใช้งานอยู่','เข้าร่วม','ยินยอมแล้ว',NULL,1,NULL,NULL,NULL,NULL,'2026-08-11 20:54:29','2026-08-11 20:56:26',NULL),(34,'DEMO-PTP-0033','DEMO-PSN-0033','ธีรพงษ์ ใจงาม','086-132-1224',NULL,'male',1979,NULL,5,NULL,2,2,10,NULL,NULL,0,NULL,NULL,NULL,'ใช้งานอยู่','เข้าร่วม','ยินยอมแล้ว',NULL,1,NULL,NULL,NULL,NULL,'2026-08-11 20:54:29','2026-08-11 20:56:26',NULL),(35,'DEMO-PTP-0034','DEMO-PSN-0034','กัลยา สายใจ','087-133-1231',NULL,'female',1986,NULL,5,NULL,17,2,9,NULL,NULL,0,NULL,NULL,NULL,'ใช้งานอยู่','เข้าร่วม','ยินยอมแล้ว',NULL,1,NULL,NULL,NULL,NULL,'2026-08-11 20:54:29','2026-08-11 20:56:26',NULL),(36,'DEMO-PTP-0035','DEMO-PSN-0035','ประภาส แสงทอง','088-134-1238',NULL,'other',1993,NULL,2,NULL,1,2,14,NULL,NULL,0,NULL,NULL,NULL,'ใช้งานอยู่','เข้าร่วม','ยินยอมแล้ว',NULL,1,NULL,NULL,NULL,NULL,'2026-08-11 20:54:29','2026-08-11 20:56:26',NULL),(37,'DEMO-PTP-0036','DEMO-PSN-0036','มณีรัตน์ รุ่งเจริญ','089-135-1245',NULL,'female',2000,NULL,7,NULL,3,2,9,NULL,NULL,0,NULL,NULL,NULL,'ใช้งานอยู่','เข้าร่วม','ยินยอมแล้ว',NULL,1,NULL,NULL,NULL,NULL,'2026-08-11 20:54:29','2026-08-11 20:54:29',NULL),(38,'DEMO-PTP-0037','DEMO-PSN-0037','อดิศักดิ์ ทองแท้','081-136-1252',NULL,'male',1957,NULL,1,NULL,3,3,10,NULL,NULL,0,NULL,NULL,NULL,'ใช้งานอยู่','เข้าร่วม','ยินยอมแล้ว',NULL,1,NULL,NULL,NULL,NULL,'2026-08-11 20:54:29','2026-08-11 20:56:26',NULL),(39,'DEMO-PTP-0038','DEMO-PSN-0038','พิมพ์ใจ ใจบุญ','082-137-1259',NULL,'male',1964,NULL,1,NULL,1,3,13,NULL,NULL,0,NULL,NULL,NULL,'ใช้งานอยู่','เข้าร่วม','ยินยอมแล้ว',NULL,1,NULL,NULL,NULL,NULL,'2026-08-11 20:54:29','2026-08-11 20:54:29',NULL),(40,'DEMO-PTP-0039','DEMO-PSN-0039','ณัฐวุฒิ พูลสวัสดิ์','083-138-1266',NULL,'female',1971,NULL,2,NULL,17,4,10,NULL,NULL,0,NULL,NULL,NULL,'ใช้งานอยู่','เข้าร่วม','ยินยอมแล้ว',NULL,1,NULL,NULL,NULL,NULL,'2026-08-11 20:54:29','2026-08-11 20:56:26',NULL),(41,'DEMO-PTP-0040','DEMO-PSN-0040','สุพรรณี เพียรทำ','084-139-1273',NULL,'other',1978,NULL,2,NULL,3,2,14,NULL,NULL,0,NULL,NULL,NULL,'ใช้งานอยู่','เข้าร่วม','ยินยอมแล้ว',NULL,1,NULL,NULL,NULL,NULL,'2026-08-11 20:54:29','2026-08-11 20:54:29',NULL),(42,'DEMO-PTP-0041','DEMO-PSN-0041','สมชาย ใจงาม','085-140-1280',NULL,'female',1985,NULL,1,NULL,1,2,9,NULL,NULL,0,NULL,NULL,NULL,'ใช้งานอยู่','เข้าร่วม','ยินยอมแล้ว',NULL,1,NULL,NULL,NULL,NULL,'2026-08-11 20:54:29','2026-08-11 20:56:26',NULL),(43,'DEMO-PTP-0042','DEMO-PSN-0042','วิภาดา สายใจ','086-141-1287',NULL,'male',1992,NULL,1,NULL,3,2,12,NULL,NULL,0,NULL,NULL,NULL,'ใช้งานอยู่','เข้าร่วม','ยินยอมแล้ว',NULL,1,NULL,NULL,NULL,NULL,'2026-08-11 20:54:29','2026-08-11 20:56:26',NULL),(44,'DEMO-PTP-0043','DEMO-PSN-0043','ธีรพงษ์ แสงทอง','087-142-1294',NULL,'male',1999,NULL,2,NULL,3,2,11,NULL,NULL,0,NULL,NULL,NULL,'ใช้งานอยู่','เข้าร่วม','ยินยอมแล้ว',NULL,1,NULL,NULL,NULL,NULL,'2026-08-11 20:54:29','2026-08-11 20:56:26',NULL),(45,'DEMO-PTP-0044','DEMO-PSN-0044','กัลยา รุ่งเจริญ','088-143-1301',NULL,'female',1956,NULL,2,NULL,17,3,14,NULL,NULL,0,NULL,NULL,NULL,'ใช้งานอยู่','เข้าร่วม','ยินยอมแล้ว',NULL,1,NULL,NULL,NULL,NULL,'2026-08-11 20:54:29','2026-08-11 20:56:26',NULL),(46,'DEMO-PTP-0045','DEMO-PSN-0045','ประภาส ทองแท้','089-144-1308',NULL,'other',1963,NULL,2,NULL,1,3,13,NULL,NULL,0,NULL,NULL,NULL,'ใช้งานอยู่','เข้าร่วม','ยินยอมแล้ว',NULL,1,NULL,NULL,NULL,NULL,'2026-08-11 20:54:29','2026-08-11 20:56:26',NULL),(47,'DEMO-PTP-0046','DEMO-PSN-0046','มณีรัตน์ ใจบุญ','081-145-1315',NULL,'female',1970,NULL,2,NULL,1,2,14,NULL,NULL,0,NULL,NULL,NULL,'ใช้งานอยู่','เข้าร่วม','ยินยอมแล้ว',NULL,1,NULL,NULL,NULL,NULL,'2026-08-11 20:54:29','2026-08-11 20:54:29',NULL),(48,'DEMO-PTP-0047','DEMO-PSN-0047','อดิศักดิ์ พูลสวัสดิ์','082-146-1322',NULL,'male',1977,NULL,3,NULL,2,2,11,NULL,NULL,0,NULL,NULL,NULL,'ใช้งานอยู่','เข้าร่วม','ยินยอมแล้ว',NULL,1,NULL,NULL,NULL,NULL,'2026-08-11 20:54:29','2026-08-11 20:54:29',NULL),(49,'PID-SLAJ2409IUFMLFOQ','PID-SLAJ2409IUFMLFOQ','แอมมี่','0925399788',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,'ใช้งานอยู่','เข้าร่วม','ยินยอม',NULL,1,NULL,NULL,NULL,NULL,'2026-08-13 11:20:12','2026-08-13 11:20:12',NULL),(50,'PID-QIRYYIVMP5SZ8P9C','PID-QIRYYIVMP5SZ8P9C','พี่ชาย','0925399788',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,'ใช้งานอยู่','เข้าร่วม','ยินยอม',NULL,1,NULL,NULL,NULL,NULL,'2026-08-13 11:20:12','2026-08-13 11:20:12',NULL),(51,'PID-PQGA1FNKU984NLD1','PID-PQGA1FNKU984NLD1','แอมมี่','0810766976',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,'ใช้งานอยู่','เข้าร่วม','ยินยอม',NULL,1,NULL,NULL,NULL,NULL,'2026-08-14 08:01:43','2026-08-14 08:01:43',NULL),(52,'PID-OYMRZAVGXOZIITVZ','PID-OYMRZAVGXOZIITVZ','สมชาย','0810766976',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,'ใช้งานอยู่','เข้าร่วม','ยินยอม',NULL,1,NULL,NULL,NULL,NULL,'2026-08-14 08:01:43','2026-08-14 08:01:43',NULL),(53,'PID-ZO8KUDHZFOR2WNEN','PID-ZO8KUDHZFOR2WNEN','แอมมี่','0830646432',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,'ใช้งานอยู่','เข้าร่วม','ยินยอม',NULL,1,NULL,NULL,NULL,NULL,'2026-08-14 09:05:56','2026-08-14 09:05:56',NULL),(54,'PID-EY3W7XCTRKMJKNTT','PID-EY3W7XCTRKMJKNTT','อุ้ม','0830646432',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,'ใช้งานอยู่','เข้าร่วม','ยินยอม',NULL,1,NULL,NULL,NULL,NULL,'2026-08-14 09:05:56','2026-08-14 09:05:56',NULL),(55,'PID-M2J13TPRESDYRAJY','PID-M2J13TPRESDYRAJY','เมเม','0935399788',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,'ใช้งานอยู่','เข้าร่วม','ยินยอม',NULL,1,NULL,NULL,NULL,NULL,'2026-08-14 09:14:43','2026-08-14 09:14:43',NULL),(56,'PID-QTQ5XDFWSJBYYH7R','PID-QTQ5XDFWSJBYYH7R','แอม','0925399788',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,'ใช้งานอยู่','เข้าร่วม','ยินยอม',NULL,1,NULL,NULL,NULL,NULL,'2026-08-14 14:13:32','2026-08-14 14:13:32',NULL),(57,'PID-QAW1NWQ6LUD1OCDY','PID-QAW1NWQ6LUD1OCDY','โชคชัย','0925399788',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,'ใช้งานอยู่','เข้าร่วม','ยินยอม',NULL,1,NULL,NULL,NULL,NULL,'2026-08-14 14:13:32','2026-08-14 14:13:32',NULL),(58,'PID-ZN3LBAJGU0ORWMTY','PID-ZN3LBAJGU0ORWMTY','แอมมี่','0925498778',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,'ใช้งานอยู่','เข้าร่วม','ยินยอม',NULL,1,NULL,NULL,NULL,NULL,'2026-08-14 14:17:33','2026-08-14 14:17:33',NULL),(59,'PID-HADQZPQPDFJLLNPV','PID-HADQZPQPDFJLLNPV','พี่ชาย','0925498778',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,'ใช้งานอยู่','เข้าร่วม','ยินยอม',NULL,1,NULL,NULL,NULL,NULL,'2026-08-14 14:17:33','2026-08-14 14:17:33',NULL),(60,'PID-K7RZIWIJLRP3YQ7G','PID-K7RZIWIJLRP3YQ7G','Am','0925399755',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,'ใช้งานอยู่','เข้าร่วม','ยินยอม',NULL,1,NULL,NULL,NULL,NULL,'2026-08-14 16:50:18','2026-08-14 16:50:18',NULL),(61,'PID-N0I5SKX8CGVLGRM7','PID-N0I5SKX8CGVLGRM7','แอม','0925399752',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,'ใช้งานอยู่','เข้าร่วม','ยินยอม',NULL,1,NULL,NULL,NULL,NULL,'2026-08-15 04:33:15','2026-08-15 04:33:15',NULL),(62,'PID-K4F1BXYILJLTEUJM','PID-K4F1BXYILJLTEUJM','ใจดี','0925399752',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,'ใช้งานอยู่','เข้าร่วม','ยินยอม',NULL,1,NULL,NULL,NULL,NULL,'2026-08-15 04:33:15','2026-08-15 04:33:15',NULL),(63,'PID-TZXJXGQ8PZVB7BBC','PID-TZXJXGQ8PZVB7BBC','Orawan hadkrathok','0861358114',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,'ใช้งานอยู่','เข้าร่วม','ยินยอม',NULL,1,NULL,NULL,NULL,NULL,'2026-08-15 06:18:07','2026-08-15 06:18:07',NULL),(64,'PID-V2Z9DW8KMBVUJQL3','PID-V2Z9DW8KMBVUJQL3','ฝน','0925399788',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,'ใช้งานอยู่','เข้าร่วม','ยินยอม',NULL,1,NULL,NULL,NULL,NULL,'2026-08-15 10:37:55','2026-08-15 10:37:55',NULL),(65,'PID-TO0XMW4QC9SBQHUI','PID-TO0XMW4QC9SBQHUI','แอม','0925399766',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,'ใช้งานอยู่','เข้าร่วม','ยินยอม',NULL,1,NULL,NULL,NULL,NULL,'2026-08-15 10:43:38','2026-08-15 10:43:38',NULL),(66,'PID-FH30KD4BRNSZ45CR','PID-FH30KD4BRNSZ45CR','แอม','0925377866',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,'ใช้งานอยู่','เข้าร่วม','ยินยอม',NULL,1,NULL,NULL,NULL,NULL,'2026-08-15 10:44:20','2026-08-15 10:44:20',NULL),(67,'PID-80SEBFCIQSJUYNTJ','PID-80SEBFCIQSJUYNTJ','แอม','0925300000',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,'ใช้งานอยู่','เข้าร่วม','ยินยอม',NULL,1,NULL,NULL,NULL,NULL,'2026-08-15 11:02:30','2026-08-15 11:02:30',NULL),(68,'PID-0081','PID-0081','แอมมี่','0000000000',NULL,'female',NULL,49,2,NULL,1,1,NULL,NULL,NULL,0,NULL,NULL,NULL,'ใช้งานอยู่','เข้าร่วม','ยินยอม',NULL,1,NULL,NULL,6,6,'2026-08-15 11:05:17','2026-08-15 11:05:17',NULL),(69,'PID-61U9VA6JSCVXEIPA','PID-61U9VA6JSCVXEIPA','Nattchai Charoensri','0986272109',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,'ใช้งานอยู่','เข้าร่วม','ยินยอม',NULL,1,NULL,NULL,NULL,NULL,'2026-08-15 11:17:52','2026-08-15 11:17:52',NULL),(70,'PID-9JJINVAK2YVYP4KE','PID-9JJINVAK2YVYP4KE','Nattchai Charoensri','0986272108',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,'ใช้งานอยู่','เข้าร่วม','ยินยอม',NULL,1,NULL,NULL,NULL,NULL,'2026-08-15 11:20:28','2026-08-15 11:20:28',NULL),(71,'PID-L44R7OYWKYISNKEQ','PID-L44R7OYWKYISNKEQ','เทส','0925399763',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,'ใช้งานอยู่','เข้าร่วม','ยินยอม',NULL,1,NULL,NULL,NULL,NULL,'2026-08-15 11:21:45','2026-08-15 11:21:45',NULL),(72,'PID-0082','PID-0082','เทส','092-536-5211',NULL,'female',NULL,NULL,NULL,NULL,1,2,NULL,NULL,NULL,0,NULL,NULL,NULL,'ใช้งานอยู่','เข้าร่วม','ยินยอม',NULL,1,NULL,NULL,6,6,'2026-08-15 11:28:11','2026-08-15 11:28:11',NULL),(73,'PID-0083','PID-0083','N1','098-627-2109',NULL,'undisclosed',NULL,NULL,NULL,NULL,1,2,NULL,NULL,NULL,0,NULL,NULL,NULL,'ใช้งานอยู่','เข้าร่วม','ยินยอม',NULL,1,NULL,NULL,6,6,'2026-08-15 13:14:42','2026-08-15 13:14:42',NULL),(74,'P0001','P0001','ดวงใจ','080-589-6669',NULL,'female',NULL,NULL,NULL,NULL,1,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,'ใช้งานอยู่','เข้าร่วม','ยินยอม','U0396cef4fb659bcbbe65c0536caef04f',1,NULL,NULL,NULL,NULL,'2026-08-16 09:48:55','2026-08-16 09:48:55',NULL),(75,'P0002','P0002','ดวง','092-539-9788',NULL,'female',NULL,NULL,NULL,NULL,1,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,'ใช้งานอยู่','เข้าร่วม','ยินยอม',NULL,1,NULL,NULL,NULL,NULL,'2026-08-16 09:51:02','2026-08-16 09:51:02',NULL),(76,'P0003','P0003','P0003','092-539-9755',NULL,'female',NULL,48,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,'ใช้งานอยู่','เข้าร่วม','ยินยอม',NULL,1,NULL,NULL,NULL,NULL,'2026-08-16 10:34:53','2026-08-16 10:34:53',NULL),(77,'P0004','P0004','P0004','090-000-0001',NULL,'male',NULL,49,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,'ใช้งานอยู่','เข้าร่วม','ยินยอม',NULL,1,NULL,NULL,NULL,NULL,'2026-08-16 11:42:41','2026-08-16 11:42:41',NULL),(78,'P0005','P0005','P0005','092-354-2879',NULL,'male',NULL,49,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,'ใช้งานอยู่','เข้าร่วม','ยินยอม','Ue95ccfdfd9561feb9d84369ff0191ae1',1,NULL,NULL,NULL,NULL,'2026-08-16 11:59:02','2026-08-16 11:59:27',NULL);
/*!40000 ALTER TABLE `ptp_participants` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ptp_purchase_items`
--

DROP TABLE IF EXISTS `ptp_purchase_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ptp_purchase_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `purchase_id` bigint unsigned NOT NULL,
  `product_name` varchar(160) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` int unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `ptp_purchase_items_purchase_id_foreign` (`purchase_id`) USING BTREE,
  CONSTRAINT `ptp_purchase_items_purchase_id_foreign` FOREIGN KEY (`purchase_id`) REFERENCES `ptp_purchases` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ptp_purchase_items`
--

LOCK TABLES `ptp_purchase_items` WRITE;
/*!40000 ALTER TABLE `ptp_purchase_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `ptp_purchase_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ptp_purchases`
--

DROP TABLE IF EXISTS `ptp_purchases`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ptp_purchases` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `participant_id` bigint unsigned NOT NULL,
  `store_name` varchar(160) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `channel_id` bigint unsigned DEFAULT NULL,
  `order_date` date NOT NULL,
  `status` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'รอดำเนินการ',
  `amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `ptp_purchases_channel_id_foreign` (`channel_id`) USING BTREE,
  KEY `ptp_purchases_created_by_foreign` (`created_by`) USING BTREE,
  KEY `ptp_purchases_participant_id_order_date_index` (`participant_id`,`order_date`) USING BTREE,
  CONSTRAINT `ptp_purchases_channel_id_foreign` FOREIGN KEY (`channel_id`) REFERENCES `mst_options` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `ptp_purchases_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `ptp_purchases_participant_id_foreign` FOREIGN KEY (`participant_id`) REFERENCES `ptp_participants` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ptp_purchases`
--

LOCK TABLES `ptp_purchases` WRITE;
/*!40000 ALTER TABLE `ptp_purchases` DISABLE KEYS */;
/*!40000 ALTER TABLE `ptp_purchases` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ptp_verification_codes`
--

DROP TABLE IF EXISTS `ptp_verification_codes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ptp_verification_codes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `participant_id` bigint unsigned NOT NULL,
  `channel` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `destination` varchar(160) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `code_hash` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `expires_at` timestamp NOT NULL,
  `used_at` timestamp NULL DEFAULT NULL,
  `attempts` tinyint unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `ptp_verification_codes_participant_id_expires_at_index` (`participant_id`,`expires_at`) USING BTREE,
  CONSTRAINT `ptp_verification_codes_participant_id_foreign` FOREIGN KEY (`participant_id`) REFERENCES `ptp_participants` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ptp_verification_codes`
--

LOCK TABLES `ptp_verification_codes` WRITE;
/*!40000 ALTER TABLE `ptp_verification_codes` DISABLE KEYS */;
/*!40000 ALTER TABLE `ptp_verification_codes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rev_review_comments`
--

DROP TABLE IF EXISTS `rev_review_comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `rev_review_comments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `item_id` bigint unsigned NOT NULL,
  `author_name` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `author_side` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'customer',
  `body` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_resolved` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `rev_review_comments_item_id_id_index` (`item_id`,`id`) USING BTREE,
  CONSTRAINT `rev_review_comments_item_id_foreign` FOREIGN KEY (`item_id`) REFERENCES `rev_review_items` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rev_review_comments`
--

LOCK TABLES `rev_review_comments` WRITE;
/*!40000 ALTER TABLE `rev_review_comments` DISABLE KEYS */;
INSERT INTO `rev_review_comments` VALUES (10,68,'แอม','customer','เทส',0,'2026-08-12 01:54:13','2026-08-12 01:54:13'),(11,68,'แอมมี่','customer','เทส',0,'2026-08-12 01:54:24','2026-08-12 01:54:24'),(12,68,'แอมมี่','customer','เทส',0,'2026-08-12 01:54:29','2026-08-12 01:54:29');
/*!40000 ALTER TABLE `rev_review_comments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rev_review_items`
--

DROP TABLE IF EXISTS `rev_review_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `rev_review_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `round_id` bigint unsigned NOT NULL,
  `menu_key` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `screen` varchar(160) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `note` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'รอพัฒนา',
  `due_date` date DEFAULT NULL,
  `sort_order` smallint unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `rev_review_items_round_id_sort_order_index` (`round_id`,`sort_order`) USING BTREE,
  CONSTRAINT `rev_review_items_round_id_foreign` FOREIGN KEY (`round_id`) REFERENCES `rev_review_rounds` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=77 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rev_review_items`
--

LOCK TABLES `rev_review_items` WRITE;
/*!40000 ALTER TABLE `rev_review_items` DISABLE KEYS */;
INSERT INTO `rev_review_items` VALUES (56,3,'dashboard','ตัวเลขสรุป · กราฟผู้เข้าร่วม · แผนภาพพื้นที่ · ตัวกรองช่วงเวลา',NULL,'/admin/dashboard','รอพัฒนา','2026-08-19',1,'2026-08-11 23:39:52','2026-08-12 00:05:26'),(57,3,'activities','กลุ่มเมนูจัดการกิจกรรม',NULL,NULL,'รอพัฒนา',NULL,2,'2026-08-11 23:39:52','2026-08-11 23:39:52'),(58,3,'activities-list','ค้นหา · กรองสถานะ · เพิ่ม/แก้ไข/ลบกิจกรรม · แบ่งหน้า',NULL,'/admin/activities/list','ตรวจได้','2026-08-19',3,'2026-08-11 23:39:52','2026-08-16 03:08:05'),(59,3,'activities-registrants','รายชื่อผู้ลงทะเบียน · ค้นหา · ส่งออกข้อมูล',NULL,'/admin/activities/registrants.html','ตรวจได้',NULL,4,'2026-08-11 23:39:52','2026-08-16 03:08:11'),(60,3,'activities-checkin','เช็กอินหน้างาน · ค้นหาผู้เข้าร่วม',NULL,'/admin/activities/checkin.html','ตรวจได้',NULL,5,'2026-08-11 23:39:52','2026-08-16 03:08:12'),(61,3,'activities-responses','ผลประเมินรายกิจกรรม · สรุปคะแนน',NULL,'/admin/activities/responses.html','ตรวจได้',NULL,6,'2026-08-11 23:39:52','2026-08-16 03:08:14'),(62,3,'health-assessment','กลุ่มเมนูประเมินสุขภาพ',NULL,NULL,'รอพัฒนา',NULL,7,'2026-08-11 23:39:52','2026-08-11 23:39:52'),(63,3,'cohort','ทะเบียนกลุ่มตัวอย่าง · รอบติดตามรายคน · บันทึกผล',NULL,'/admin/cohort','ตรวจได้',NULL,8,'2026-08-11 23:39:52','2026-08-16 03:08:17'),(64,3,'evaluations-rounds','ตั้งช่วงเวลารอบติดตาม · ติดตามความคืบหน้า',NULL,'/admin/evaluations/rounds.html','ตรวจได้',NULL,9,'2026-08-11 23:39:52','2026-08-16 03:08:19'),(65,3,'evaluations-responses','คำตอบแบบประเมินรายคน · สรุปผล',NULL,'/admin/evaluations/responses.html','รอพัฒนา',NULL,10,'2026-08-11 23:39:52','2026-08-16 03:08:23'),(66,3,'evaluations','สร้างและแก้ไขแบบประเมิน · จัดชุดคำถาม',NULL,'/admin/evaluations/list.html','ตรวจได้',NULL,11,'2026-08-11 23:39:52','2026-08-16 03:08:27'),(67,3,'master-data','กลุ่มเมนูข้อมูลพื้นฐาน',NULL,NULL,'รอพัฒนา',NULL,12,'2026-08-11 23:39:52','2026-08-11 23:39:52'),(68,3,'master-data-areas','ค้นหา · กรอง · เพิ่ม/แก้ไข/ลบพื้นที่ · ผู้ประสานงาน · ผลรวมท้ายตาราง',NULL,'/admin/master/areas','ตรวจได้','2026-08-19',13,'2026-08-11 23:39:52','2026-08-12 01:53:58'),(69,3,'master-data-target-groups','ค้นหา · กรอง · เพิ่ม/แก้ไข/ลบกลุ่มเป้าหมาย · ผลรวมจำนวนเป้าหมาย',NULL,'/admin/master/target-groups','ตรวจได้','2026-08-19',14,'2026-08-11 23:39:52','2026-08-12 01:40:20'),(70,3,'master-data-programs','ค้นหา · กรอง · เพิ่ม/แก้ไขโปรแกรม · จัดการหลักสูตรย่อย',NULL,'/admin/master/programs','ตรวจได้','2026-08-19',15,'2026-08-11 23:39:52','2026-08-12 01:42:34'),(71,3,'master-data-instructors','ค้นหา · กรอง · เพิ่ม/แก้ไขวิทยากร · รูปภาพ · ประวัติการสอน',NULL,'/admin/master/instructors','ตรวจได้','2026-08-19',16,'2026-08-11 23:39:52','2026-08-12 01:40:02'),(72,3,'master-data-activity-formats','ค้นหา · กรอง · เพิ่ม/แก้ไขหมวดหมู่ · เลือกไอคอน',NULL,'/admin/master/activity-formats','ตรวจได้','2026-08-19',17,'2026-08-11 23:39:52','2026-08-11 23:39:52'),(73,3,'master-data-follow-up-rounds','ตั้งระยะห่างของรอบเป็นวัน · ทดลองคำนวณวันครบกำหนด',NULL,'/admin/master/follow-up-rounds','ตรวจได้','2026-08-17',18,'2026-08-11 23:39:52','2026-08-12 01:54:01'),(74,3,'users','กลุ่มเมนูผู้ใช้งาน',NULL,NULL,'รอพัฒนา',NULL,19,'2026-08-11 23:39:52','2026-08-11 23:39:52'),(75,3,'users-list','ค้นหา · กรองสถานะ · เพิ่ม/แก้ไขผู้ใช้ · ระงับ/คืนสิทธิ์ · บทบาทหลายค่า',NULL,'/admin/users','ตรวจได้','2026-08-19',20,'2026-08-11 23:39:52','2026-08-11 23:39:52'),(76,3,'users-roles','ค้นหา · กรอง · เพิ่ม/แก้ไขบทบาท · ตั้งสิทธิ์เข้าถึงเมนู',NULL,'/admin/users/roles','ตรวจได้','2026-08-19',21,'2026-08-11 23:39:52','2026-08-11 23:39:52');
/*!40000 ALTER TABLE `rev_review_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rev_review_rounds`
--

DROP TABLE IF EXISTS `rev_review_rounds`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `rev_review_rounds` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `round_no` smallint unsigned NOT NULL,
  `sender` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'TheFarmConcept',
  `project_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `project_start` date DEFAULT NULL,
  `project_end` date DEFAULT NULL,
  `action_plan_url` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `system_url` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `login_hint` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sent_at` date DEFAULT NULL,
  `due_at` date DEFAULT NULL,
  `is_open` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rev_review_rounds`
--

LOCK TABLES `rev_review_rounds` WRITE;
/*!40000 ALTER TABLE `rev_review_rounds` DISABLE KEYS */;
INSERT INTO `rev_review_rounds` VALUES (3,1,'TheFarmConcept','แผนงานพัฒนาระบบติดตามและประเมินผลการเปลี่ยนแปลงสุขภาพระดับบุคคล','2026-08-01','2026-08-20','https://docs.google.com/spreadsheets/d/1LktQcZ1Mk0_d3stfqh7hMASJ25vO9wrLlRLVzrWFnGs/edit?usp=sharing','http://157.85.104.53/login','admin01 / 1234','2026-08-12','2026-08-19',1,'2026-08-11 23:39:51','2026-08-11 23:48:25');
/*!40000 ALTER TABLE `rev_review_rounds` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `sessions_user_id_index` (`user_id`) USING BTREE,
  KEY `sessions_last_activity_index` (`last_activity`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sessions`
--

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
INSERT INTO `sessions` VALUES ('1ugTfqJhPkYPULJidTQ2mlqLICMZE3EnFhLHKyTw',NULL,'104.23.175.98','Mozilla/5.0 (iPhone; CPU iPhone OS 16_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.0 Mobile/15E148 Safari/604.1','eyJfdG9rZW4iOiJ5WGMzWGU5bEI0T2tJa0pHb29TUzhFMVRIQjJiQTY0dGdYc3B5c1ZsIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC90ZXN0LnRoZWZhcm1jb25jZXB0LmNvXC9yXC9qeHh3b2NkaHB0c2dyaHZnM3B5YncyaGgiLCJyb3V0ZSI6InB1YmxpYy5xci5yZWdpc3RyYXRpb24ifSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',1786951334),('3u7079byKEcSvr43kxDxPATjasHlrjcOVvaeWEAt',NULL,'172.64.213.158','facebookexternalhit/1.1;line-poker/1.0','eyJfdG9rZW4iOiJuSGJkWks1Q1VQODlCSVB3S3Fnak14ODZrNnZJTTZDSGdSYkMxTkw0IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC90ZXN0LnRoZWZhcm1jb25jZXB0LmNvIiwicm91dGUiOm51bGx9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',1786950420),('9UjYJkkQ4JNjOOnBJI3Hf1K9bHl9mEYqouO0sGdB',6,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Claude/1.30096.5 Chrome/148.0.7778.280 Electron/42.7.0 Safari/537.36 MSIX','eyJfdG9rZW4iOiJlTGJyTVVkS1pPam5XQlhCWDA0QWoyVW8wTjFMaVIwaHlEZXNGWEtNIiwidXJsIjpbXSwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cL2xvY2FsaG9zdDo4MDEwXC9hZG1pblwvYWN0aXZpdGllc1wvbGlzdCIsInJvdXRlIjoiYWRtaW4uYWN0aXZpdGllcy5pbmRleCJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX0sImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjo2fQ==',1786955474),('eMMSbgdLkWDgKg1kKTCgI3wBjcuaoBMDrTt1O8xK',NULL,'198.235.24.231','Hello from Palo Alto Networks, find out more about our scans in https://docs-cortex.paloaltonetworks.com/r/1/Cortex-Xpanse/Scanning-activity','eyJfdG9rZW4iOiJla1UxclN5VVd2S1M4MFQ2NjY2Wk9yUVduamZMR3BTaEtBZ3ZRUTJVIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzE1Ny44NS4xMDQuNTMiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',1786952212),('gs1BeMgV4nZMMDanvZwsk6CVxaadeBacXpiQuYnF',NULL,'45.198.224.26','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJCNERYeVAyNW04aEIxbjd3bWVJYmhnU0dkZTZaVmV6N2RQUVd6ZWhhIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzE1Ny44NS4xMDQuNTMiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',1786954789),('HlFKfVWLqfFCwbKFAjGRsj1MXwuZnW81lLZ3d57w',NULL,'94.154.43.114','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJvdHlOVlFQaUlqam5NbDVhdkhRTEZncG53RFdQMlplT0ZLQkJiS1B1IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzE1Ny44NS4xMDQuNTMiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',1786945130),('IGLlHXi62RVxEpMxvW6ypX7Msl4XKFi62KkLwfkM',6,'172.68.104.195','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJpTWE3b1ZCTG45bFRESG8zWVF5Nk5iRGM5ZFdsSTFmNnlzRENwS3Q1IiwidXJsIjpbXSwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC90ZXN0LnRoZWZhcm1jb25jZXB0LmNvXC9hZG1pblwvdHJhY2tpbmctcm91bmRzIiwicm91dGUiOiJhZG1pbi50cmFja2luZy1yb3VuZHMuaW5kZXgifSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119LCJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI6Nn0=',1786955738),('jBIp8SxMHOKULWGTYiRBKC7TDqNVrq7SyzFECACV',NULL,'198.235.24.51','Hello from Palo Alto Networks, find out more about our scans in https://docs-cortex.paloaltonetworks.com/r/1/Cortex-Xpanse/Scanning-activity','eyJfdG9rZW4iOiJCR3lOaU9vRGhNVWZqelNyTEpBNmswUlRSM0ZYMHcxd2RtWVhtOFhhIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzE1Ny44NS4xMDQuNTMiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',1786949866),('nX5qTDX166V71RHZ1EgwV56TyoqjGHQe5bCVpQV1',NULL,'175.6.54.21','Mozilla/5.0 zgrab/0.x','eyJfdG9rZW4iOiJ1MWs5UXc2UjRmZ1NQQ0FDTDRpUDZ0Z3ZwT1ZPZGFlZmg3aG9OMktxIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzE1Ny44NS4xMDQuNTMiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',1786948408),('Qv1e1RBPNlNStd0CrNpSoOGa2FlMFCTJ548xMdow',NULL,'172.71.24.139','facebookexternalhit/1.1;line-poker/1.0','eyJfdG9rZW4iOiJGb1hmR3ZEcTdLbk5mNFh5N2NCb1BGSXEyc3NUd3Zta0JCczl3TjZlIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC90ZXN0LnRoZWZhcm1jb25jZXB0LmNvIiwicm91dGUiOm51bGx9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',1786950176),('qV3SJ2ICn1E1R6EPt6EYxYcEPnJaH7V7gTTyahVu',NULL,'104.23.175.98','Mozilla/5.0 (Linux; Android 16; SM-A075F Build/BP2A.250605.031.A3; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/151.0.7922.83 Mobile Safari/537.36 Line/26.11.0/IAB','eyJfdG9rZW4iOiJTc3Vpc3dlZURvMk9LNUNYbGdPWk9hdlJ0eWRvRTZqbUpNVEhjT2xGIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC90ZXN0LnRoZWZhcm1jb25jZXB0LmNvXC9hY3Rpdml0aWVzXC9BQ1QtUFVCLTAwMVwvcmVnaXN0ZXIiLCJyb3V0ZSI6InB1YmxpYy5hY3Rpdml0aWVzLnJlZ2lzdGVyIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',1786950486),('t4RP13EgQXQqTzN5EwuNvC3DhQ3l81NqaQkoMp4f',NULL,'91.92.47.152','','eyJfdG9rZW4iOiIxZTFyYWw2a0dZbWRsZ3RqMHM4emhYdkpZQzNKTFRnUTIxbk5wcjJrIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzE1Ny44NS4xMDQuNTMiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',1786946436),('tiMFIewc5bdCY3pLwuo0sOvcE8a2jwtnQBS4LTZZ',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJhcmNyRjJaOGpEWkw5M1NDZVBCek5ic2l1T3JZdmtyenpqc09CcTN5IiwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119LCJfcHJldmlvdXMiOnsidXJsIjoiaHR0cDpcL1wvZmFybWNvbmNlcHQudGVzdFwvYWN0aXZpdGllcyIsInJvdXRlIjoicHVibGljLmFjdGl2aXRpZXMifX0=',1786955618),('TOULTOVKDPMIhMNzEqRUyKC9gycmQI2zgpeCKxKs',NULL,'66.132.224.81','Mozilla/5.0 (compatible; CensysInspect/1.1; +https://about.censys.io/)','eyJfdG9rZW4iOiJJZ3FTb0Z4TWFMem05cGZYTGZxVmtoOEdJUThuY1NqR0Z1V2tJZ0pMIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzE1Ny44NS4xMDQuNTNcL2xvZ2luIiwicm91dGUiOiJsb2dpbiJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',1786951905),('VXqtI5UKMl4ERBbEiZW7Idv7XkqKHrP5Vat3mfQv',NULL,'154.197.57.56','Mozilla/5.0 (X11; Linux x86_64; rv:12.0) Gecko/20100101 Firefox/12.0','eyJfdG9rZW4iOiJ5bEp0RExFTFBXMHk0ZUQzM0hyZTlLaHZzZWR0OGFFUUIxVWt5VFg3IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzE1Ny44NS4xMDQuNTMiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',1786946731),('WQteY63CequOwASL3GPIZX8yeSRd2quz8QYayPjP',NULL,'172.68.242.41','Mozilla/5.0 (iPhone; CPU iPhone OS 16_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.0 Mobile/15E148 Safari/604.1','eyJfdG9rZW4iOiJLQUhPTHdLcUJmSnlGczhlaDF1Sm5Tc3V2TmZhTGc2UTR0UTZDWjdQIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC90ZXN0LnRoZWZhcm1jb25jZXB0LmNvXC9yXC9qeHh3b2NkaHB0c2dyaHZnM3B5YncyaGgiLCJyb3V0ZSI6InB1YmxpYy5xci5yZWdpc3RyYXRpb24ifSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',1786951306);
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sys_activity_logs`
--

DROP TABLE IF EXISTS `sys_activity_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sys_activity_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `action` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject_type` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subject_id` bigint unsigned DEFAULT NULL,
  `detail` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `sys_activity_logs_user_id_foreign` (`user_id`) USING BTREE,
  KEY `sys_activity_logs_subject_type_subject_id_index` (`subject_type`,`subject_id`) USING BTREE,
  KEY `sys_activity_logs_created_at_index` (`created_at`) USING BTREE,
  CONSTRAINT `sys_activity_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=180 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sys_activity_logs`
--

LOCK TABLES `sys_activity_logs` WRITE;
/*!40000 ALTER TABLE `sys_activity_logs` DISABLE KEYS */;
INSERT INTO `sys_activity_logs` VALUES (1,6,'activity.deleted','activity',5,'ลบกิจกรรม ACT-2026-018 — ตลาดนัดผักปลอดสารประจำเดือน','2026-08-11 00:09:08'),(2,6,'activity.updated','activity',5,'แก้ไขกิจกรรม ACT-2026-018 (ไม่มีคอลัมน์หลักเปลี่ยน)','2026-08-11 00:16:23'),(3,6,'activity.updated','activity',5,'แก้ไขกิจกรรม ACT-2026-018 · ฟิลด์ที่เปลี่ยน: description, status, requires_registration, requires_checkin, has_post_survey, checkin_start_at, checkin_end_at, is_published, publish_start_at, publish_end_at, is_featured','2026-08-11 00:18:51'),(4,6,'activity.updated','activity',5,'แก้ไขกิจกรรม ACT-2026-018 · ฟิลด์ที่เปลี่ยน: description, status, requires_registration, requires_checkin, has_post_survey, is_published, is_featured','2026-08-11 00:19:47'),(5,6,'activity.deleted','activity',5,'ลบกิจกรรม ACT-2026-018 — ตลาดนัดผักปลอดสารประจำเดือน','2026-08-11 00:33:05'),(6,1,'activity.created','activity',6,'สร้างกิจกรรม ACT-2026-019 — อีเวนท์ทดสอบสร้างใหม่','2026-08-11 02:30:46'),(7,1,'activity.deleted','activity',6,'ลบกิจกรรม ACT-2026-019 — อีเวนท์ทดสอบสร้างใหม่','2026-08-11 02:30:46'),(8,1,'activity.created','activity',7,'สร้างกิจกรรม ACT-2026-019 — อีเวนท์ทดสอบสร้างใหม่','2026-08-11 02:30:56'),(9,1,'activity.created','activity',8,'สร้างกิจกรรม ACT-2026-020 — กิจกรรมทดสอบในอีเวนท์','2026-08-11 02:30:56'),(10,6,'activity.created','activity',9,'สร้างกิจกรรม ACT-2026-019 — ทดสอบ','2026-08-11 02:35:31'),(11,6,'activity.updated','activity',9,'แก้ไขกิจกรรม ACT-2026-019 (ไม่มีคอลัมน์หลักเปลี่ยน)','2026-08-11 02:36:19'),(12,6,'master.target_group.created',NULL,NULL,'เพิ่มกลุ่มเป้าหมาย TG-005 — ทดสอบกลุ่มใหม่','2026-08-11 02:55:50'),(13,6,'master.target_group.updated',NULL,NULL,'แก้ไขกลุ่มเป้าหมาย TG-005 — ทดสอบกลุ่มแก้แล้ว','2026-08-11 02:55:51'),(14,6,'master.target_group.deleted',NULL,NULL,'ลบกลุ่มเป้าหมาย TG-005 — ทดสอบกลุ่มแก้แล้ว','2026-08-11 02:55:51'),(15,6,'master.activity_format.created',NULL,NULL,'เพิ่มหมวดหมู่กิจกรรม FMT-006 — ทดสอบหมวด','2026-08-11 02:57:53'),(16,6,'master.activity_format.updated',NULL,NULL,'แก้ไขหมวดหมู่กิจกรรม FMT-006 — ทดสอบหมวดแก้แล้ว','2026-08-11 02:57:53'),(17,6,'master.activity_format.deleted',NULL,NULL,'ลบหมวดหมู่กิจกรรม FMT-006 — ทดสอบหมวดแก้แล้ว','2026-08-11 02:57:53'),(18,6,'master.target_group.created',NULL,NULL,'เพิ่มกลุ่มเป้าหมาย TG-005 — ทดสอบ','2026-08-11 03:08:43'),(19,6,'master.target_group.deleted',NULL,NULL,'ลบกลุ่มเป้าหมาย TG-005 — ทดสอบ','2026-08-11 03:08:53'),(20,6,'master.program.created',NULL,NULL,'เพิ่มโปรแกรมการเรียนรู้ PROG-005 — AAA ทดสอบ','2026-08-11 04:33:38'),(21,6,'master.program.updated',NULL,NULL,'แก้ไขโปรแกรมการเรียนรู้ PROG-005 — AAA ทดสอบ','2026-08-11 04:33:38'),(22,6,'master.program.deleted',NULL,NULL,'ลบโปรแกรมการเรียนรู้ PROG-005 — AAA ทดสอบ','2026-08-11 04:33:39'),(23,6,'master.instructor.created',NULL,NULL,'เพิ่มวิทยากร INS-006 — วิทยากรทดสอบ','2026-08-11 04:33:39'),(24,6,'master.instructor.deleted',NULL,NULL,'ลบวิทยากร INS-006 — วิทยากรทดสอบ','2026-08-11 04:33:39'),(25,6,'master.area.created',NULL,NULL,'เพิ่มพื้นที่ดำเนินงาน AREA-004 — พื้นที่ทดสอบ','2026-08-11 04:33:39'),(26,6,'master.area.deleted',NULL,NULL,'ลบพื้นที่ดำเนินงาน AREA-004 — พื้นที่ทดสอบ','2026-08-11 04:33:39'),(27,6,'master.follow_up_round_template.saved',NULL,NULL,'บันทึกการตั้งค่ารอบติดตาม 5 รอบ — FRT-1, FRT-2, FRT-3, FRT-4, FRT-005','2026-08-11 04:33:39'),(28,6,'master.follow_up_round_template.saved',NULL,NULL,'บันทึกการตั้งค่ารอบติดตาม 4 รอบ — FRT-1, FRT-2, FRT-3, FRT-4','2026-08-11 04:33:39'),(29,6,'master.follow_up_round_template.saved',NULL,NULL,'บันทึกการตั้งค่ารอบติดตาม 1 รอบ — FRT-1','2026-08-11 04:33:39'),(30,6,'master.program.created',NULL,NULL,'เพิ่มโปรแกรมการเรียนรู้ PROG-005 — AAA ทดสอบ','2026-08-11 04:41:44'),(31,6,'master.program.updated',NULL,NULL,'แก้ไขโปรแกรมการเรียนรู้ PROG-005 — AAA ทดสอบ','2026-08-11 04:41:44'),(32,6,'master.program.deleted',NULL,NULL,'ลบโปรแกรมการเรียนรู้ PROG-005 — AAA ทดสอบ','2026-08-11 04:41:44'),(33,6,'master.instructor.created',NULL,NULL,'เพิ่มวิทยากร INS-006 — วิทยากรทดสอบ','2026-08-11 04:41:44'),(34,6,'master.instructor.deleted',NULL,NULL,'ลบวิทยากร INS-006 — วิทยากรทดสอบ','2026-08-11 04:41:44'),(35,6,'master.area.created',NULL,NULL,'เพิ่มพื้นที่ดำเนินงาน AREA-004 — พื้นที่ทดสอบ','2026-08-11 04:41:44'),(36,6,'master.area.deleted',NULL,NULL,'ลบพื้นที่ดำเนินงาน AREA-004 — พื้นที่ทดสอบ','2026-08-11 04:41:44'),(37,6,'master.follow_up_round_template.saved',NULL,NULL,'บันทึกการตั้งค่ารอบติดตาม 5 รอบ — FRT-1, FRT-2, FRT-3, FRT-4, FRT-005','2026-08-11 04:41:44'),(38,6,'master.follow_up_round_template.saved',NULL,NULL,'บันทึกการตั้งค่ารอบติดตาม 4 รอบ — FRT-1, FRT-2, FRT-3, FRT-4','2026-08-11 04:41:44'),(39,6,'master.follow_up_round_template.saved',NULL,NULL,'บันทึกการตั้งค่ารอบติดตาม 1 รอบ — FRT-1','2026-08-11 04:41:44'),(40,6,'master.follow_up_round_template.saved',NULL,NULL,'บันทึกการตั้งค่ารอบติดตาม 4 รอบ — FRT-1, FRT-2, FRT-3, FRT-4','2026-08-11 04:43:35'),(41,6,'master.follow_up_round_template.saved',NULL,NULL,'บันทึกการตั้งค่ารอบติดตาม 4 รอบ — FRT-1, FRT-2, FRT-3, FRT-4','2026-08-11 04:43:35'),(42,6,'master.area.created',NULL,NULL,'เพิ่มพื้นที่ดำเนินงาน AREA-004 — The Farm Concpet 2','2026-08-11 04:49:24'),(43,6,'master.target_group.created',NULL,NULL,'เพิ่มกลุ่มเป้าหมาย TG-005 — เทส','2026-08-11 04:53:27'),(44,6,'master.target_group.deleted',NULL,NULL,'ลบกลุ่มเป้าหมาย TG-005 — เทส','2026-08-11 04:53:31'),(45,6,'master.instructor.created',NULL,NULL,'เพิ่มวิทยากร INS-006 — แอมมี่','2026-08-11 05:01:27'),(46,6,'master.activity_format.created',NULL,NULL,'เพิ่มหมวดหมู่กิจกรรม FMT-006 — เทส','2026-08-11 05:03:05'),(47,6,'master.program.created',NULL,NULL,'เพิ่มโปรแกรมการเรียนรู้ PROG-005 — AAA ทดสอบ','2026-08-11 05:15:51'),(48,6,'master.program.updated',NULL,NULL,'แก้ไขโปรแกรมการเรียนรู้ PROG-005 — AAA ทดสอบ','2026-08-11 05:15:51'),(49,6,'master.program.deleted',NULL,NULL,'ลบโปรแกรมการเรียนรู้ PROG-005 — AAA ทดสอบ','2026-08-11 05:15:51'),(50,6,'master.program.deleted',NULL,NULL,'ลบโปรแกรมการเรียนรู้ PROG-004 — โปรแกรมครัวสุขภาวะ','2026-08-11 05:15:51'),(51,6,'master.instructor.created',NULL,NULL,'เพิ่มวิทยากร INS-007 — วิทยากรทดสอบ','2026-08-11 05:15:51'),(52,6,'master.instructor.deleted',NULL,NULL,'ลบวิทยากร INS-007 — วิทยากรทดสอบ','2026-08-11 05:15:51'),(53,6,'master.instructor.deleted',NULL,NULL,'ลบวิทยากร INS-006 — แอมมี่','2026-08-11 05:15:51'),(54,6,'master.program.created',NULL,NULL,'เพิ่มโปรแกรมการเรียนรู้ PROG-005 — AAA ทดสอบ','2026-08-11 05:17:43'),(55,6,'master.program.updated',NULL,NULL,'แก้ไขโปรแกรมการเรียนรู้ PROG-005 — AAA ทดสอบ','2026-08-11 05:17:43'),(56,6,'master.program.deleted',NULL,NULL,'ลบโปรแกรมการเรียนรู้ PROG-005 — AAA ทดสอบ','2026-08-11 05:17:43'),(57,6,'master.instructor.created',NULL,NULL,'เพิ่มวิทยากร INS-006 — วิทยากรทดสอบ','2026-08-11 05:17:43'),(58,6,'master.instructor.deleted',NULL,NULL,'ลบวิทยากร INS-006 — วิทยากรทดสอบ','2026-08-11 05:17:43'),(59,6,'master.area.created',NULL,NULL,'เพิ่มพื้นที่ดำเนินงาน AREA-005 — พื้นที่ทดสอบ','2026-08-11 05:17:43'),(60,6,'master.area.deleted',NULL,NULL,'ลบพื้นที่ดำเนินงาน AREA-005 — พื้นที่ทดสอบ','2026-08-11 05:17:43'),(61,6,'master.area.deleted',NULL,NULL,'ลบพื้นที่ดำเนินงาน AREA-004 — The Farm Concpet 2','2026-08-11 07:10:40'),(62,6,'master.area.created',NULL,NULL,'เพิ่มพื้นที่ดำเนินงาน AREA-004 — เทส','2026-08-11 17:17:22'),(63,6,'master.area.created',NULL,NULL,'เพิ่มพื้นที่ดำเนินงาน AREA-005 — พื้นที่ทดสอบ id','2026-08-11 17:36:19'),(64,6,'master.area.deleted',NULL,NULL,'ลบพื้นที่ดำเนินงาน AREA-005 — พื้นที่ทดสอบ id','2026-08-11 17:36:20'),(65,6,'master.follow_up_round_template.saved',NULL,NULL,'บันทึกการตั้งค่ารอบติดตาม 4 รอบ — FRT-1, FRT-2, FRT-3, FRT-4','2026-08-11 17:39:18'),(66,6,'master.area.created',NULL,NULL,'เพิ่มพื้นที่ดำเนินงาน AREA-005 — พื้นที่ทดสอบ id','2026-08-11 17:39:28'),(67,6,'master.area.deleted',NULL,NULL,'ลบพื้นที่ดำเนินงาน AREA-005 — พื้นที่ทดสอบ id','2026-08-11 17:39:28'),(68,6,'master.activity_format.deleted',NULL,NULL,'ลบหมวดหมู่กิจกรรม FMT-006 — เทส','2026-08-11 18:24:49'),(69,6,'master.area.created',NULL,NULL,'เพิ่มพื้นที่ดำเนินงาน AREA-005 — สิ้นสุดวันเดียวกัน','2026-08-11 18:32:32'),(70,6,'master.area.created',NULL,NULL,'เพิ่มพื้นที่ดำเนินงาน AREA-006 — พื้นที่ทดสอบกฎใหม่','2026-08-11 18:32:32'),(71,6,'master.area.deleted',NULL,NULL,'ลบพื้นที่ดำเนินงาน AREA-005 — สิ้นสุดวันเดียวกัน','2026-08-11 18:32:32'),(72,6,'master.area.deleted',NULL,NULL,'ลบพื้นที่ดำเนินงาน AREA-006 — พื้นที่ทดสอบกฎใหม่','2026-08-11 18:32:32'),(73,6,'master.area.updated',NULL,NULL,'แก้ไขพื้นที่ดำเนินงาน AREA-003 — ชุมชนตึกร้าง','2026-08-11 18:37:48'),(74,6,'master.area.updated',NULL,NULL,'แก้ไขพื้นที่ดำเนินงาน AREA-004 — เทส','2026-08-11 18:41:27'),(75,6,'master.area.updated',NULL,NULL,'แก้ไขพื้นที่ดำเนินงาน AREA-001 — The Farm Concept','2026-08-11 18:44:28'),(76,6,'master.area.created',NULL,NULL,'เพิ่มพื้นที่ดำเนินงาน AREA-005 — พื้นที่ทดสอบลำดับ','2026-08-11 18:44:28'),(77,6,'master.area.deleted',NULL,NULL,'ลบพื้นที่ดำเนินงาน AREA-005 — พื้นที่ทดสอบลำดับ','2026-08-11 18:44:28'),(78,6,'master.area.deleted',NULL,NULL,'ลบพื้นที่ดำเนินงาน AREA-004 — เทส','2026-08-11 18:45:29'),(79,6,'master.instructor.updated',NULL,NULL,'แก้ไขวิทยากร INS-001 — ดร.กิตติพงศ์ วัฒนสุข','2026-08-11 19:01:42'),(80,6,'master.instructor.updated',NULL,NULL,'แก้ไขวิทยากร INS-001 — ดร.กิตติพงศ์ วัฒนสุข','2026-08-11 19:03:09'),(81,6,'master.area.created',NULL,NULL,'เพิ่มพื้นที่ดำเนินงาน AREA-004 — ทดสอบ รอดำเนินงาน','2026-08-11 19:03:27'),(82,6,'master.area.created',NULL,NULL,'เพิ่มพื้นที่ดำเนินงาน AREA-005 — ทดสอบ ดำเนินการอยู่','2026-08-11 19:03:27'),(83,6,'master.area.created',NULL,NULL,'เพิ่มพื้นที่ดำเนินงาน AREA-006 — ทดสอบ สิ้นสุดแล้ว','2026-08-11 19:03:27'),(84,6,'master.area.deleted',NULL,NULL,'ลบพื้นที่ดำเนินงาน AREA-004 — ทดสอบ รอดำเนินงาน','2026-08-11 19:03:27'),(85,6,'master.area.deleted',NULL,NULL,'ลบพื้นที่ดำเนินงาน AREA-005 — ทดสอบ ดำเนินการอยู่','2026-08-11 19:03:27'),(86,6,'master.area.deleted',NULL,NULL,'ลบพื้นที่ดำเนินงาน AREA-006 — ทดสอบ สิ้นสุดแล้ว','2026-08-11 19:03:27'),(87,6,'master.area.created',NULL,NULL,'เพิ่มพื้นที่ดำเนินงาน AREA-004 — เทส','2026-08-11 19:16:21'),(88,6,'master.target_group.updated',NULL,NULL,'แก้ไขกลุ่มเป้าหมาย TG-004 — กลุ่มเปราะบาง','2026-08-11 19:25:41'),(89,6,'master.target_group.updated',NULL,NULL,'แก้ไขกลุ่มเป้าหมาย TG-003 — ผู้สูงอายุ','2026-08-11 19:25:43'),(90,6,'master.target_group.updated',NULL,NULL,'แก้ไขกลุ่มเป้าหมาย TG-002 — วัยทำงาน','2026-08-11 19:25:45'),(91,6,'master.target_group.updated',NULL,NULL,'แก้ไขกลุ่มเป้าหมาย TG-001 — เด็กและเยาวชน','2026-08-11 19:25:47'),(92,6,'master.program.updated',NULL,NULL,'แก้ไขโปรแกรมการเรียนรู้ PROG-004 — โปรแกรมครัวสุขภาวะ','2026-08-11 19:25:51'),(93,6,'master.program.updated',NULL,NULL,'แก้ไขโปรแกรมการเรียนรู้ PROG-003 — โปรแกรม Food Literacy','2026-08-11 19:25:53'),(94,6,'master.program.updated',NULL,NULL,'แก้ไขโปรแกรมการเรียนรู้ PROG-002 — โปรแกรมปลูกกินเอง','2026-08-11 19:25:56'),(95,6,'master.program.updated',NULL,NULL,'แก้ไขโปรแกรมการเรียนรู้ PROG-001 — โปรแกรมกินดี อยู่ดี','2026-08-11 19:25:58'),(96,6,'master.instructor.updated',NULL,NULL,'แก้ไขวิทยากร INS-005 — คุณปกรณ์ชัย ใจดี','2026-08-11 19:26:02'),(97,6,'master.instructor.updated',NULL,NULL,'แก้ไขวิทยากร INS-004 — คุณกัญญารัตน์ มีสุข','2026-08-11 19:26:04'),(98,6,'master.instructor.updated',NULL,NULL,'แก้ไขวิทยากร INS-003 — คุณภูริณัฐ วงศ์สวัสดิ์','2026-08-11 19:26:07'),(99,6,'master.instructor.updated',NULL,NULL,'แก้ไขวิทยากร INS-002 — อาจารย์พิมพ์ชนก ศรีสมบัติ','2026-08-11 19:26:10'),(100,6,'master.instructor.updated',NULL,NULL,'แก้ไขวิทยากร INS-001 — ดร.กิตติพงศ์ วัฒนสุข','2026-08-11 19:26:12'),(101,6,'master.activity_format.updated',NULL,NULL,'แก้ไขหมวดหมู่กิจกรรม FMT-005 — COMMUNITY','2026-08-11 19:26:16'),(102,6,'master.activity_format.updated',NULL,NULL,'แก้ไขหมวดหมู่กิจกรรม FMT-004 — WORKSHOP','2026-08-11 19:26:18'),(103,6,'master.activity_format.updated',NULL,NULL,'แก้ไขหมวดหมู่กิจกรรม FMT-003 — FOOD','2026-08-11 19:26:20'),(104,6,'master.activity_format.updated',NULL,NULL,'แก้ไขหมวดหมู่กิจกรรม FMT-002 — MIND','2026-08-11 19:26:22'),(105,6,'master.activity_format.updated',NULL,NULL,'แก้ไขหมวดหมู่กิจกรรม FMT-001 — CRAFT','2026-08-11 19:26:24'),(106,6,'master.follow_up_round_template.saved',NULL,NULL,'บันทึกการตั้งค่ารอบประเมิน 4 รอบ — FRT-1, FRT-2, FRT-3, FRT-4','2026-08-11 19:26:27'),(107,6,'master.area.updated',NULL,NULL,'แก้ไขพื้นที่ดำเนินงาน AREA-004 — เทส','2026-08-11 19:27:15'),(108,6,'master.area.updated',NULL,NULL,'แก้ไขพื้นที่ดำเนินงาน AREA-004 — เทส','2026-08-11 19:27:20'),(109,6,'master.area.updated',NULL,NULL,'แก้ไขพื้นที่ดำเนินงาน AREA-004 — โรงเรียนบางเขน','2026-08-11 19:58:20'),(110,6,'activity.deleted','activity',4,'ลบกิจกรรม ACT-2026-017 — กิจกรรมฟื้นฟูสุขภาวะชุมชน','2026-08-11 22:21:58'),(112,6,'master.instructor.updated',NULL,NULL,'แก้ไขวิทยากร INS-001 — ดร.กิตติพงศ์ วัฒนสุข','2026-08-12 01:20:21'),(113,6,'master.area.created',NULL,NULL,'เพิ่มพื้นที่ดำเนินงาน AREA-005 — เทส','2026-08-12 01:43:48'),(114,6,'master.area.deleted',NULL,NULL,'ลบพื้นที่ดำเนินงาน AREA-005 — เทส','2026-08-12 01:43:52'),(121,6,'master.target_group.created',NULL,NULL,'เพิ่มกลุ่มเป้าหมาย TG-005 — ..','2026-08-12 04:01:36'),(122,6,'master.target_group.deleted',NULL,NULL,'ลบกลุ่มเป้าหมาย TG-005 — ..','2026-08-12 04:01:39'),(123,6,'activity.created','activity',12,'สร้างกิจกรรม ACT-2026-020 — ทดสอบ','2026-08-12 06:54:36'),(124,6,'activity.created','activity',13,'สร้างกิจกรรม ACT-2026-021 — ทดสอบ2','2026-08-12 14:16:34'),(125,6,'master.instructor.created',NULL,NULL,'เพิ่มวิทยากร INS-006 — ทดสอบ','2026-08-13 04:53:54'),(126,6,'master.instructor.updated',NULL,NULL,'แก้ไขวิทยากร INS-006 — ทดสอบ11','2026-08-13 04:54:00'),(127,6,'master.instructor.updated',NULL,NULL,'แก้ไขวิทยากร INS-006 — ทดสอบ11','2026-08-13 04:54:12'),(128,6,'activity.updated','activity',16,'แก้ไขกิจกรรม ACT-PUB-003 · ฟิลด์ที่เปลี่ยน: name, parent_event_id, program_id, course_id, venue_mode, registration_start_at','2026-08-13 06:07:01'),(129,6,'activity.updated','activity',14,'แก้ไขกิจกรรม ACT-PUB-001 · ฟิลด์ที่เปลี่ยน: name, parent_event_id, program_id, course_id, venue_mode, registration_start_at','2026-08-13 06:16:24'),(130,6,'master.instructor.deleted',NULL,NULL,'ลบวิทยากร INS-006 — ทดสอบ11','2026-08-13 06:41:45'),(131,6,'master.activity_format.updated',NULL,NULL,'แก้ไขหมวดหมู่กิจกรรม FMT-004 — WORKSHOP','2026-08-13 07:31:17'),(132,6,'master.instructor.created',NULL,NULL,'เพิ่มวิทยากร INS-006 — ปูเป้ - พี่ลดาคีย์ใหม่ค่ะ','2026-08-13 08:47:27'),(133,6,'master.payment_account.created',NULL,NULL,'เพิ่มข้อมูลการรับชำระ PAY-001 — The Farm Concept (TEST) 000-0-00000-0','2026-08-13 10:28:48'),(134,6,'master.consent_document.created',NULL,NULL,'เพิ่มเอกสารความยินยอม CNS-001 — เงื่อนไขการเข้าร่วมและการใช้งาน (หน้าลงทะเบียนเข้าร่วมกิจกรรม)','2026-08-13 10:29:46'),(135,6,'master.instructor.updated',NULL,NULL,'แก้ไขวิทยากร INS-001 — ดร.กิตติพงศ์ วัฒนสุข','2026-08-13 10:31:20'),(136,6,'master.consent_document.updated',NULL,NULL,'แก้ไขเอกสารความยินยอม CNS-001 — เงื่อนไขการเข้าร่วมและการใช้งาน (หน้าลงทะเบียนเข้าร่วมกิจกรรม)','2026-08-13 10:32:15'),(137,6,'master.consent_document.created',NULL,NULL,'เพิ่มเอกสารความยินยอม CNS-002 — PDPA','2026-08-13 10:35:23'),(138,6,'master.consent_document.updated',NULL,NULL,'แก้ไขเอกสารความยินยอม CNS-002 — PDPA','2026-08-13 10:35:32'),(139,6,'master.consent_document.created',NULL,NULL,'เพิ่มเอกสารความยินยอม CNS-003 — ยินยอมเก็บข้อมูลกลุ่มตัวอย่าง','2026-08-13 10:38:58'),(140,6,'master.consent_document.updated',NULL,NULL,'แก้ไขเอกสารความยินยอม CNS-002 — PDPA','2026-08-13 10:39:16'),(141,6,'master.consent_document.updated',NULL,NULL,'แก้ไขเอกสารความยินยอม CNS-001 — เงื่อนไขการเข้าร่วมและการใช้งาน','2026-08-13 10:39:21'),(142,6,'master.consent_document.updated',NULL,NULL,'แก้ไขเอกสารความยินยอม CNS-003 — ยินยอมเก็บข้อมูลกลุ่มตัวอย่าง','2026-08-13 10:39:29'),(143,6,'master.consent_document.updated',NULL,NULL,'แก้ไขเอกสารความยินยอม CNS-002 — PDPA','2026-08-13 10:39:31'),(144,6,'master.consent_document.updated',NULL,NULL,'แก้ไขเอกสารความยินยอม CNS-001 — เงื่อนไขการเข้าร่วมและการใช้งาน','2026-08-13 10:39:33'),(145,6,'activity.updated','activity',21,'แก้ไขกิจกรรม ACT-PUB-008 · ฟิลด์ที่เปลี่ยน: program_id, course_id, venue_mode, requires_registration, requires_checkin, capacity, start_date, end_date, checkin_start_at, checkin_end_at, registration_start_at, registration_end_at','2026-08-13 11:19:23'),(146,6,'activity.updated','activity',21,'แก้ไขกิจกรรม ACT-PUB-008 · ฟิลด์ที่เปลี่ยน: has_fee, fee','2026-08-13 11:23:20'),(147,6,'activity.updated','activity',21,'แก้ไขกิจกรรม ACT-PUB-008 · ฟิลด์ที่เปลี่ยน: start_date, end_date, checkin_start_at, checkin_end_at, registration_end_at','2026-08-13 12:07:35'),(148,6,'activity.updated','activity',21,'แก้ไขกิจกรรม ACT-PUB-008 · ฟิลด์ที่เปลี่ยน: checkin_end_at','2026-08-13 12:08:38'),(149,6,'activity.updated','activity',21,'แก้ไขกิจกรรม ACT-PUB-008 · ฟิลด์ที่เปลี่ยน: start_date, end_date, checkin_start_at, checkin_end_at, registration_start_at, registration_end_at, public_sort_order','2026-08-14 07:50:43'),(150,6,'activity.updated','activity',14,'แก้ไขกิจกรรม ACT-PUB-001 · ฟิลด์ที่เปลี่ยน: requires_checkin, start_date, end_date, checkin_start_at, checkin_end_at, registration_start_at, registration_end_at','2026-08-14 14:06:21'),(151,6,'activity.updated','activity',21,'แก้ไขกิจกรรม ACT-PUB-008 · ฟิลด์ที่เปลี่ยน: requires_checkin, start_date, end_date, registration_end_at','2026-08-15 05:27:43'),(152,6,'activity.updated','activity',21,'แก้ไขกิจกรรม ACT-PUB-008 · ฟิลด์ที่เปลี่ยน: requires_checkin, checkin_start_at, checkin_end_at','2026-08-15 05:28:01'),(153,6,'activity.created','activity',22,'สร้างกิจกรรม ACT-2026-022 — เปิดพื้นที่ “ตลาดสีเขียว” ทุกสุดสัปดาห์','2026-08-15 05:43:21'),(154,6,'activity.created','activity',23,'สร้างกิจกรรม ACT-2026-023 — เพิ่มพื้นที่เรียนรู้สวนผักสำหรับครอบครัว','2026-08-15 05:45:11'),(155,6,'activity.updated','activity',23,'แก้ไขกิจกรรม ACT-2026-023 (ไม่มีคอลัมน์หลักเปลี่ยน)','2026-08-15 05:45:23'),(156,6,'activity.updated','activity',22,'แก้ไขกิจกรรม ACT-2026-022 (ไม่มีคอลัมน์หลักเปลี่ยน)','2026-08-15 05:45:29'),(157,6,'activity.created','activity',24,'สร้างกิจกรรม ACT-2026-024 — ปรับเวลาเปิดให้บริการช่วงกิจกรรมพิเศษ','2026-08-15 05:46:48'),(158,6,'activity.updated','activity',20,'แก้ไขกิจกรรม ACT-PUB-007 · ฟิลด์ที่เปลี่ยน: venue_mode, is_featured','2026-08-15 05:50:30'),(159,6,'activity.updated','activity',24,'แก้ไขกิจกรรม ACT-2026-024 (ไม่มีคอลัมน์หลักเปลี่ยน)','2026-08-15 05:50:59'),(160,6,'activity.updated','activity',23,'แก้ไขกิจกรรม ACT-2026-023 (ไม่มีคอลัมน์หลักเปลี่ยน)','2026-08-15 05:51:28'),(161,6,'activity.updated','activity',22,'แก้ไขกิจกรรม ACT-2026-022 (ไม่มีคอลัมน์หลักเปลี่ยน)','2026-08-15 05:51:39'),(162,6,'activity.updated','activity',18,'แก้ไขกิจกรรม ACT-PUB-005 · ฟิลด์ที่เปลี่ยน: venue_mode, public_sort_order','2026-08-15 05:53:20'),(163,6,'activity.updated','activity',14,'แก้ไขกิจกรรม ACT-PUB-001 · ฟิลด์ที่เปลี่ยน: name','2026-08-15 05:54:22'),(164,6,'activity.updated','activity',15,'แก้ไขกิจกรรม ACT-PUB-002 · ฟิลด์ที่เปลี่ยน: venue_mode, registration_start_at, registration_end_at','2026-08-15 05:55:32'),(165,6,'activity.updated','activity',16,'แก้ไขกิจกรรม ACT-PUB-003 · ฟิลด์ที่เปลี่ยน: name','2026-08-15 05:56:13'),(166,6,'activity.updated','activity',17,'แก้ไขกิจกรรม ACT-PUB-004 · ฟิลด์ที่เปลี่ยน: venue_mode, registration_start_at','2026-08-15 05:56:54'),(167,6,'activity.updated','activity',14,'แก้ไขกิจกรรม ACT-PUB-001 · ฟิลด์ที่เปลี่ยน: checkin_start_at, registration_end_at','2026-08-15 10:46:14'),(168,6,'activity.updated','activity',14,'แก้ไขกิจกรรม ACT-PUB-001 · ฟิลด์ที่เปลี่ยน: has_post_survey, survey_start_at, survey_end_at','2026-08-15 10:48:35'),(169,6,'activity.updated','activity',14,'แก้ไขกิจกรรม ACT-PUB-001 · ฟิลด์ที่เปลี่ยน: registration_end_at','2026-08-15 10:53:43'),(170,6,'master.instructor.updated',NULL,NULL,'แก้ไขวิทยากร INS-006 — คุณปูเป้ สุพัตรา โชยชมภู (สวนผัก \"ปูเป้ ทำเอง\")','2026-08-15 11:08:41'),(171,6,'master.instructor.updated',NULL,NULL,'แก้ไขวิทยากร INS-006 — คุณปูเป้ สุพัตรา โชยชมภู (สวนผัก \"ปูเป้ ทำเอง\")','2026-08-15 11:09:02'),(172,6,'master.instructor.updated',NULL,NULL,'แก้ไขวิทยากร INS-006 — คุณปูเป้ สุพัตรา ไชยชมภู (สวนผัก \"ปูเป้ ทำเอง\")','2026-08-15 11:17:56'),(173,6,'activity.updated','activity',14,'แก้ไขกิจกรรม ACT-PUB-001 · ฟิลด์ที่เปลี่ยน: start_date, end_date, checkin_start_at, survey_start_at, registration_end_at','2026-08-16 03:17:47'),(174,6,'activity.updated','activity',14,'แก้ไขกิจกรรม ACT-PUB-001 · ฟิลด์ที่เปลี่ยน: capacity','2026-08-16 03:27:08'),(175,6,'activity.created','activity',25,'สร้างกิจกรรม ACT-2026-025 — เยี่ยมชมสวนที่ The Farm Concept Bearing','2026-08-17 07:19:01'),(176,6,'activity.created','activity',26,'สร้างกิจกรรม ACT-2026-026 — เยี่ยมชมสวน The Farm Concept Bearing','2026-08-17 07:20:14'),(177,6,'activity.created','activity',27,'สร้างกิจกรรม ACT-2026-027 — แบบประเมินกลุ่มเป้าหมาย The Farm Concept Bearing','2026-08-17 07:21:14'),(178,6,'activity.updated','activity',25,'แก้ไขกิจกรรม ACT-2026-025 · ฟิลด์ที่เปลี่ยน: description, has_post_survey, capacity, start_date, end_date, survey_start_at, survey_end_at, is_published, public_sort_order','2026-08-17 07:24:05'),(179,6,'activity.created','activity',28,'สร้างกิจกรรม ACT-2026-028 — สอบถามการเยี่ยมชมสวน','2026-08-17 08:31:13');
/*!40000 ALTER TABLE `sys_activity_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sys_notifications`
--

DROP TABLE IF EXISTS `sys_notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sys_notifications` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `title` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `detail` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'info',
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `sys_notifications_user_id_read_at_index` (`user_id`,`read_at`) USING BTREE,
  CONSTRAINT `sys_notifications_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sys_notifications`
--

LOCK TABLES `sys_notifications` WRITE;
/*!40000 ALTER TABLE `sys_notifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `sys_notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sys_settings`
--

DROP TABLE IF EXISTS `sys_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sys_settings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `setting_value` longtext COLLATE utf8mb4_unicode_ci,
  `setting_type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'text',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sys_settings_setting_key_unique` (`setting_key`),
  KEY `sys_settings_updated_by_foreign` (`updated_by`),
  CONSTRAINT `sys_settings_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sys_settings`
--

LOCK TABLES `sys_settings` WRITE;
/*!40000 ALTER TABLE `sys_settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `sys_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `username` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `avatar_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ใช้งานอยู่',
  `area_id` bigint unsigned DEFAULT NULL,
  `last_login_at` timestamp NULL DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `users_email_unique` (`email`) USING BTREE,
  UNIQUE KEY `users_code_unique` (`code`) USING BTREE,
  UNIQUE KEY `users_username_unique` (`username`) USING BTREE,
  KEY `users_status_index` (`status`) USING BTREE,
  KEY `users_area_id_foreign` (`area_id`) USING BTREE,
  CONSTRAINT `users_area_id_foreign` FOREIGN KEY (`area_id`) REFERENCES `mst_areas` (`id`) ON DELETE SET NULL ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'USR-001','สุนิสา แก้วมณี','sunisa01','sunisa@thefarmconcept.org',NULL,NULL,'ใช้งานอยู่',2,'2026-08-10 22:42:12',NULL,'$2y$12$6OVjIjk6p.pksdqVQ/XVXexrV9FpIFJILtkvhvMVIKs5eTAWoUBZa',NULL,'2026-08-10 22:41:51','2026-08-10 22:42:12'),(2,'USR-002','วีระ ศรีสมบัติ','weera02','weera@thefarmconcept.org',NULL,NULL,'ใช้งานอยู่',1,'2026-08-10 22:12:50',NULL,'$2y$12$KsIPFwaoJnTYkheLbQjsa.asjkiqoLB5GBkweFF9LR0tRXXfV3M76','wfPftD2XNdwM6xD1k8yWYcUT69SHiF9bnydK2uaXypBXYe4d9zCkY59NZ7Gg','2026-08-10 22:41:51','2026-08-10 22:41:51'),(3,'USR-003','ปิยะดา รุ่งเรือง','piyada03','piyada@thefarmconcept.org',NULL,NULL,'ใช้งานอยู่',3,NULL,NULL,'$2y$12$kGSL/bgjppzWTBMzZa6tGufut7Ngm9WNXk0eq8XFmJWvZUsgspbfG',NULL,'2026-08-10 22:41:51','2026-08-11 20:21:39'),(4,'USR-004','ธนากร ใจดี','thanakorn04','thanakorn@thefarmconcept.org',NULL,NULL,'ระงับการใช้งาน',3,NULL,NULL,'$2y$12$H3rGJJtXsgAgGrAKK2QGC.Zlyw5Ch8WZrwYSGbPgXEL72I0CnnEyK',NULL,'2026-08-10 22:41:52','2026-08-11 20:21:45'),(5,'USR-005','อรุณี ทองสุข','arunee05','arunee@thefarmconcept.org',NULL,NULL,'ใช้งานอยู่',NULL,'2026-08-10 22:15:29',NULL,'$2y$12$koE7SCsQT2sSfpZ.DL5ww.enKs3xMgO.rSPF3PWQhVIEQKbl0wlTG',NULL,'2026-08-10 22:41:52','2026-08-10 22:41:52'),(6,'USR-000','ผู้ดูแลระบบ','admin','admin@thefarmconcept.org','0925399788','/storage/avatars/gX0TrglMk35Vf7RF6Vz475aTKzLLvQrMg1SCp8Fj.jpg','ใช้งานอยู่',NULL,'2026-08-17 08:29:47',NULL,'$2y$12$uQi1iI62bng815XHLkq5g.l3d4lcVFBGjmOPOSCZm0MQxK12N4fUS','ZRSoaFdSo7e9ikPNGiCIJtnyoH2jRSLgnyT6Qy2ieQSmZOiAQ5YP2YMfYJfG','2026-08-10 22:41:51','2026-08-17 08:29:47'),(12,'USR-007','ผู้ดูแลโครงการ','admin01','admin02@farmconcept.local','0810766976','/storage/avatars/2T3eqMyUj7Bo5FzRCPLQGU0HhMYE992irVgaCYZp.png','ใช้งานอยู่',NULL,'2026-08-15 06:02:15',NULL,'$2y$12$sKhJJ5ueekGyFOkCrH0qeex.pSJsCXWjUtA0asH1cEsUPRthZNQby',NULL,'2026-08-11 23:43:37','2026-08-15 06:02:15');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usr_role_menu_permissions`
--

DROP TABLE IF EXISTS `usr_role_menu_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `usr_role_menu_permissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `role_id` bigint unsigned NOT NULL,
  `menu_key` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_allowed` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `usr_role_menu_permissions_role_id_menu_key_unique` (`role_id`,`menu_key`) USING BTREE,
  CONSTRAINT `usr_role_menu_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `usr_roles` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=271 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usr_role_menu_permissions`
--

LOCK TABLES `usr_role_menu_permissions` WRITE;
/*!40000 ALTER TABLE `usr_role_menu_permissions` DISABLE KEYS */;
INSERT INTO `usr_role_menu_permissions` VALUES (175,1,'dashboard',1),(176,1,'activities',1),(177,1,'activities-list',1),(178,1,'evaluations',1),(179,1,'health-assessment',1),(180,1,'cohort',1),(181,1,'evaluations-rounds',1),(182,1,'evaluations-responses',1),(183,1,'master-data',1),(184,1,'master-data-areas',1),(185,1,'master-data-target-groups',1),(186,1,'master-data-programs',1),(187,1,'master-data-instructors',1),(188,1,'master-data-activity-formats',1),(189,1,'master-data-follow-up-rounds',1),(190,1,'master-data-payment-accounts',1),(191,1,'master-data-registration-options',1),(192,1,'master-data-consents',1),(193,1,'master-data-system-settings',1),(194,1,'users',1),(195,1,'users-list',1),(196,1,'users-roles',1),(242,3,'dashboard',1),(243,3,'activities',1),(244,3,'activities-list',1),(245,3,'evaluations',1),(246,3,'health-assessment',1),(247,3,'cohort',1),(248,3,'evaluations-rounds',1),(249,3,'evaluations-responses',1),(250,2,'dashboard',1),(251,2,'activities',1),(252,2,'activities-list',1),(253,2,'evaluations',1),(254,2,'health-assessment',1),(255,2,'cohort',1),(256,2,'evaluations-rounds',1),(257,2,'evaluations-responses',1),(258,2,'master-data',1),(259,2,'master-data-areas',1),(260,2,'master-data-target-groups',1),(261,2,'master-data-programs',1),(262,2,'master-data-instructors',1),(263,2,'master-data-activity-formats',1),(264,2,'master-data-follow-up-rounds',1),(265,2,'master-data-payment-accounts',1),(266,2,'master-data-registration-options',1),(267,2,'master-data-consents',1),(268,2,'users',1),(269,2,'users-list',1),(270,2,'users-roles',1);
/*!40000 ALTER TABLE `usr_role_menu_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usr_role_user`
--

DROP TABLE IF EXISTS `usr_role_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `usr_role_user` (
  `user_id` bigint unsigned NOT NULL,
  `role_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`user_id`,`role_id`) USING BTREE,
  KEY `usr_role_user_role_id_foreign` (`role_id`) USING BTREE,
  CONSTRAINT `usr_role_user_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `usr_roles` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `usr_role_user_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usr_role_user`
--

LOCK TABLES `usr_role_user` WRITE;
/*!40000 ALTER TABLE `usr_role_user` DISABLE KEYS */;
INSERT INTO `usr_role_user` VALUES (5,1),(6,1),(2,2),(12,2),(1,3),(2,3),(3,3),(4,3);
/*!40000 ALTER TABLE `usr_role_user` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usr_roles`
--

DROP TABLE IF EXISTS `usr_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `usr_roles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `usr_roles_code_unique` (`code`) USING BTREE,
  KEY `usr_roles_updated_by_foreign` (`updated_by`) USING BTREE,
  CONSTRAINT `usr_roles_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usr_roles`
--

LOCK TABLES `usr_roles` WRITE;
/*!40000 ALTER TABLE `usr_roles` DISABLE KEYS */;
INSERT INTO `usr_roles` VALUES (1,'super_admin','ผู้ดูแลระบบสูงสุด','จัดการโครงการ ผู้ใช้งาน และข้อมูลกลางทั้งหมดของระบบ',1,'2026-08-10 22:41:50','2026-08-17 07:11:13',6),(2,'project_admin','ผู้ดูแลโครงการ','จัดการพื้นที่ กิจกรรม และรายงานภายในโครงการที่รับผิดชอบ',1,'2026-08-10 22:41:51','2026-08-15 11:07:08',6),(3,'staff','เจ้าหน้าที่โครงการ','จัดการกิจกรรม ลงทะเบียน ตรวจสอบการชำระเงิน และติดตามผลในพื้นที่ที่รับผิดชอบ',1,'2026-08-10 22:41:51','2026-08-17 07:11:26',6);
/*!40000 ALTER TABLE `usr_roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping routines for database 'farmconcept'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-08-17 15:36:04
