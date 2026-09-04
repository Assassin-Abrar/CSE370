-- ===========================================================
-- BRACU Club Management System — Full Database (Schema + Seed Data)
-- Run this single file to create the "bracu_cms" database, all
-- tables, and realistic demo data in one go.
--
-- Import (XAMPP default: root user, no password):
--   C:\xampp\mysql\bin\mysql.exe -u root < database\bracu_cms.sql
--
-- All demo accounts created below use the password: password123
-- ===========================================================

CREATE DATABASE IF NOT EXISTS bracu_cms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE bracu_cms;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS audit_log;
DROP TABLE IF EXISTS announcements;
DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS tasks;
DROP TABLE IF EXISTS collaboration_responses;
DROP TABLE IF EXISTS collaboration_requests;
DROP TABLE IF EXISTS budget_audit_log;
DROP TABLE IF EXISTS budget_items;
DROP TABLE IF EXISTS budget_requests;
DROP TABLE IF EXISTS event_registrations;
DROP TABLE IF EXISTS events;
DROP TABLE IF EXISTS venues;
DROP TABLE IF EXISTS application_notes;
DROP TABLE IF EXISTS applications;
DROP TABLE IF EXISTS recruitment_campaigns;
DROP TABLE IF EXISTS club_interests;
DROP TABLE IF EXISTS club_skills;
DROP TABLE IF EXISTS club_members;
DROP TABLE IF EXISTS clubs;
DROP TABLE IF EXISTS student_interests;
DROP TABLE IF EXISTS student_skills;
DROP TABLE IF EXISTS interests;
DROP TABLE IF EXISTS skills;
DROP TABLE IF EXISTS student_profiles;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;

-- ===========================================================
-- SCHEMA
-- ===========================================================

-- ========== USERS ==========
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM('student','exec','admin') NOT NULL DEFAULT 'student',
  status ENUM('active','suspended') NOT NULL DEFAULT 'active',
  avatar_color VARCHAR(7) NOT NULL DEFAULT '#4f46e5',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE student_profiles (
  user_id INT PRIMARY KEY,
  student_id VARCHAR(20),
  department VARCHAR(100),
  semester VARCHAR(10),
  bio TEXT,
  phone VARCHAR(30),
  cv_path VARCHAR(255),
  availability VARCHAR(255),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ========== SKILLS / INTERESTS ==========
CREATE TABLE skills (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(80) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE interests (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(80) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE student_skills (
  user_id INT NOT NULL,
  skill_id INT NOT NULL,
  PRIMARY KEY (user_id, skill_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (skill_id) REFERENCES skills(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE student_interests (
  user_id INT NOT NULL,
  interest_id INT NOT NULL,
  PRIMARY KEY (user_id, interest_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (interest_id) REFERENCES interests(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ========== CLUBS ==========
CREATE TABLE clubs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  slug VARCHAR(160) NOT NULL UNIQUE,
  category VARCHAR(50) NOT NULL,
  description TEXT,
  mission TEXT,
  logo_color VARCHAR(7) NOT NULL DEFAULT '#4f46e5',
  email VARCHAR(150),
  social_link VARCHAR(255),
  recruitment_status ENUM('open','closed') NOT NULL DEFAULT 'closed',
  founded_year INT,
  status ENUM('active','suspended') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE club_skills (
  club_id INT NOT NULL,
  skill_id INT NOT NULL,
  PRIMARY KEY (club_id, skill_id),
  FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE,
  FOREIGN KEY (skill_id) REFERENCES skills(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE club_interests (
  club_id INT NOT NULL,
  interest_id INT NOT NULL,
  PRIMARY KEY (club_id, interest_id),
  FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE,
  FOREIGN KEY (interest_id) REFERENCES interests(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE club_members (
  id INT AUTO_INCREMENT PRIMARY KEY,
  club_id INT NOT NULL,
  user_id INT NOT NULL,
  position VARCHAR(100) DEFAULT 'General Member',
  member_role ENUM('member','exec','president') NOT NULL DEFAULT 'member',
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_club_user (club_id, user_id),
  FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ========== RECRUITMENT ==========
CREATE TABLE recruitment_campaigns (
  id INT AUTO_INCREMENT PRIMARY KEY,
  club_id INT NOT NULL,
  title VARCHAR(150) NOT NULL,
  description TEXT,
  positions_needed INT DEFAULT 1,
  deadline DATE,
  status ENUM('open','closed') NOT NULL DEFAULT 'open',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE applications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  campaign_id INT,
  club_id INT NOT NULL,
  user_id INT NOT NULL,
  motivation TEXT,
  skills_text TEXT,
  experience TEXT,
  portfolio_link VARCHAR(255),
  cv_path VARCHAR(255),
  availability VARCHAR(255),
  status ENUM('submitted','under_review','shortlisted','interview','accepted','rejected') NOT NULL DEFAULT 'submitted',
  applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (campaign_id) REFERENCES recruitment_campaigns(id) ON DELETE SET NULL,
  FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE application_notes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  application_id INT NOT NULL,
  author_user_id INT NOT NULL,
  note TEXT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
  FOREIGN KEY (author_user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ========== VENUES / EVENTS ==========
CREATE TABLE venues (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  capacity INT DEFAULT 50,
  location VARCHAR(150),
  status ENUM('available','maintenance') NOT NULL DEFAULT 'available'
) ENGINE=InnoDB;

CREATE TABLE events (
  id INT AUTO_INCREMENT PRIMARY KEY,
  club_id INT,
  title VARCHAR(150) NOT NULL,
  description TEXT,
  category VARCHAR(60),
  event_date DATE NOT NULL,
  start_time TIME NOT NULL,
  end_time TIME NOT NULL,
  venue_id INT,
  expected_attendance INT,
  required_equipment TEXT,
  budget_amount DECIMAL(12,2) DEFAULT 0,
  is_major_event TINYINT(1) NOT NULL DEFAULT 0,
  status ENUM('draft','submitted','approved','rejected','completed','cancelled') NOT NULL DEFAULT 'submitted',
  created_by INT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  reviewed_by INT,
  reviewed_at TIMESTAMP NULL,
  review_comment TEXT,
  FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE,
  FOREIGN KEY (venue_id) REFERENCES venues(id) ON DELETE SET NULL,
  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
  FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE event_registrations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  event_id INT NOT NULL,
  user_id INT NOT NULL,
  registered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  attended TINYINT(1) NOT NULL DEFAULT 0,
  UNIQUE KEY uniq_event_user (event_id, user_id),
  FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ========== BUDGET ==========
CREATE TABLE budget_requests (
  id INT AUTO_INCREMENT PRIMARY KEY,
  event_id INT,
  club_id INT NOT NULL,
  event_name VARCHAR(150) NOT NULL,
  event_date DATE,
  venue_id INT,
  expected_attendance INT,
  requested_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
  approved_amount DECIMAL(12,2),
  justification TEXT,
  status ENUM('draft','submitted','under_review','approved','rejected') NOT NULL DEFAULT 'submitted',
  submitted_by INT,
  submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  reviewed_by INT,
  reviewed_at TIMESTAMP NULL,
  review_comment TEXT,
  FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE SET NULL,
  FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE,
  FOREIGN KEY (venue_id) REFERENCES venues(id) ON DELETE SET NULL,
  FOREIGN KEY (submitted_by) REFERENCES users(id) ON DELETE SET NULL,
  FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE budget_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  budget_request_id INT NOT NULL,
  category VARCHAR(100) NOT NULL,
  amount DECIMAL(12,2) NOT NULL DEFAULT 0,
  FOREIGN KEY (budget_request_id) REFERENCES budget_requests(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE budget_audit_log (
  id INT AUTO_INCREMENT PRIMARY KEY,
  budget_request_id INT NOT NULL,
  action VARCHAR(100) NOT NULL,
  actor_user_id INT,
  notes TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (budget_request_id) REFERENCES budget_requests(id) ON DELETE CASCADE,
  FOREIGN KEY (actor_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ========== COLLABORATION ==========
CREATE TABLE collaboration_requests (
  id INT AUTO_INCREMENT PRIMARY KEY,
  club_id INT NOT NULL,
  request_type ENUM('equipment','human_resource','co_host','venue','media','other') NOT NULL,
  title VARCHAR(150) NOT NULL,
  description TEXT,
  required_date DATE,
  priority ENUM('low','medium','high') NOT NULL DEFAULT 'medium',
  status ENUM('open','responses_received','in_discussion','accepted','completed') NOT NULL DEFAULT 'open',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE collaboration_responses (
  id INT AUTO_INCREMENT PRIMARY KEY,
  collaboration_id INT NOT NULL,
  responding_club_id INT NOT NULL,
  responder_user_id INT NOT NULL,
  message TEXT,
  status ENUM('proposed','accepted','declined') NOT NULL DEFAULT 'proposed',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (collaboration_id) REFERENCES collaboration_requests(id) ON DELETE CASCADE,
  FOREIGN KEY (responding_club_id) REFERENCES clubs(id) ON DELETE CASCADE,
  FOREIGN KEY (responder_user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ========== TASKS ==========
CREATE TABLE tasks (
  id INT AUTO_INCREMENT PRIMARY KEY,
  club_id INT NOT NULL,
  event_id INT,
  title VARCHAR(150) NOT NULL,
  description TEXT,
  assigned_to INT,
  created_by INT,
  priority ENUM('low','medium','high') NOT NULL DEFAULT 'medium',
  deadline DATE,
  status ENUM('todo','in_progress','review','completed') NOT NULL DEFAULT 'todo',
  estimated_workload INT NOT NULL DEFAULT 3,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE,
  FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE SET NULL,
  FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ========== NOTIFICATIONS / ANNOUNCEMENTS / AUDIT ==========
CREATE TABLE notifications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  category VARCHAR(50) NOT NULL,
  title VARCHAR(255) NOT NULL,
  message TEXT,
  link VARCHAR(255),
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE announcements (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(200) NOT NULL,
  body TEXT NOT NULL,
  posted_by INT,
  audience ENUM('all','students','execs') NOT NULL DEFAULT 'all',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (posted_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE audit_log (
  id INT AUTO_INCREMENT PRIMARY KEY,
  actor_user_id INT,
  action VARCHAR(150) NOT NULL,
  entity_type VARCHAR(50),
  entity_id INT,
  details TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (actor_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE INDEX idx_events_venue_date ON events(venue_id, event_date);
CREATE INDEX idx_apps_club_status ON applications(club_id, status);
CREATE INDEX idx_tasks_assignee_status ON tasks(assigned_to, status);
CREATE INDEX idx_notifications_user_read ON notifications(user_id, is_read);

-- ===========================================================
-- DEMO SEED DATA
-- ===========================================================

-- ========== USERS (password for all: password123) ==========
SET @pw = '$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K';

INSERT INTO users (id, name, email, password, role, avatar_color) VALUES
(1,'Dr. Farhana Rahman','admin@bracu.ac.bd',@pw,'admin','#0f172a'),
(2,'Sarah Ahmed','sarah.ahmed@bracu.ac.bd',@pw,'exec','#4f46e5'),
(3,'Tanvir Ahmed','tanvir.ahmed@bracu.ac.bd',@pw,'exec','#0891b2'),
(4,'Rakib Hossain','rakib.hossain@bracu.ac.bd',@pw,'exec','#059669'),
(5,'Sadia Islam','sadia.islam@bracu.ac.bd',@pw,'exec','#0891b2'),
(6,'Imran Chowdhury','imran.chowdhury@bracu.ac.bd',@pw,'exec','#b45309'),
(7,'Mehjabin Noor','mehjabin.noor@bracu.ac.bd',@pw,'exec','#b45309'),
(8,'Farhan Kabir','farhan.kabir@bracu.ac.bd',@pw,'exec','#be185d'),
(9,'Anika Tabassum','anika.tabassum@bracu.ac.bd',@pw,'exec','#be185d'),
(10,'Shuvo Roy','shuvo.roy@bracu.ac.bd',@pw,'exec','#7c3aed'),
(11,'Priya Das','priya.das@bracu.ac.bd',@pw,'exec','#7c3aed'),
(12,'Rezwan Hasan','rezwan.hasan@bracu.ac.bd',@pw,'exec','#ea580c'),
(13,'Nusrat Jahan','nusrat.jahan@bracu.ac.bd',@pw,'exec','#ea580c'),
(14,'Kamrul Islam','kamrul.islam@bracu.ac.bd',@pw,'exec','#16a34a'),
(15,'Tasnim Ara','tasnim.ara@bracu.ac.bd',@pw,'exec','#16a34a'),
(16,'Adnan Siddique','adnan.siddique@bracu.ac.bd',@pw,'exec','#4338ca'),
(17,'Farzana Yasmin','farzana.yasmin@bracu.ac.bd',@pw,'exec','#4338ca'),
(18,'Rakibul Islam','rakibul.islam@bracu.ac.bd',@pw,'student','#4f46e5'),
(19,'Nafis Rahman','nafis.rahman@bracu.ac.bd',@pw,'student','#0891b2'),
(20,'Jannatul Ferdous','jannatul.ferdous@bracu.ac.bd',@pw,'student','#be185d'),
(21,'Mahin Chowdhury','mahin.chowdhury@bracu.ac.bd',@pw,'student','#4f46e5'),
(22,'Rifat Hasan','rifat.hasan@bracu.ac.bd',@pw,'student','#059669'),
(23,'Nabila Sultana','nabila.sultana@bracu.ac.bd',@pw,'student','#4f46e5'),
(24,'Omar Faruq','omar.faruq@bracu.ac.bd',@pw,'student','#b45309'),
(25,'Sabrina Alam','sabrina.alam@bracu.ac.bd',@pw,'student','#059669'),
(26,'Tahsin Ridwan','tahsin.ridwan@bracu.ac.bd',@pw,'student','#4f46e5'),
(27,'Lamia Akter','lamia.akter@bracu.ac.bd',@pw,'student','#059669'),
(28,'Naeem Chowdhury','naeem.chowdhury@bracu.ac.bd',@pw,'student','#ea580c'),
(29,'Ishrat Jahan','ishrat.jahan@bracu.ac.bd',@pw,'student','#ea580c'),
(30,'Fahim Muntasir','fahim.muntasir@bracu.ac.bd',@pw,'student','#4338ca'),
(31,'Ruma Begum','ruma.begum@bracu.ac.bd',@pw,'student','#16a34a'),
(32,'Shakil Ahmed','shakil.ahmed@bracu.ac.bd',@pw,'student','#be185d'),
(33,'Tania Islam','tania.islam@bracu.ac.bd',@pw,'student','#7c3aed'),
(34,'Arif Rahman','arif.rahman@bracu.ac.bd',@pw,'student','#4f46e5'),
(35,'Nadia Hossain','nadia.hossain@bracu.ac.bd',@pw,'student','#be185d'),
(36,'Zubair Khan','zubair.khan@bracu.ac.bd',@pw,'student','#0891b2'),
(37,'Mim Akhter','mim.akhter@bracu.ac.bd',@pw,'student','#7c3aed');

INSERT INTO student_profiles (user_id, student_id, department, semester, bio, phone, availability) VALUES
(2,'21101001','CSE','9th','Passionate about building developer communities and hackathons.','01710000002','Weekdays after 5pm'),
(3,'21101002','CSE','9th','Full-stack dev, loves mentoring juniors.','01710000003','Flexible'),
(4,'20101010','EEE','11th','Robotics enthusiast, FIRST competition alum.','01710000004','Weekends'),
(5,'20101011','CSE','11th','ML and embedded systems.','01710000005','Weekends'),
(6,'19201001','ENH','13th','National-level debater, 5 championships.','01710000006','Evenings'),
(7,'20201002','ENH','11th','Parliamentary debate trainer.','01710000007','Evenings'),
(8,'20301001','MNS','11th','Street photographer, Sony Alpha shooter.','01710000008','Flexible'),
(9,'21301002','ARC','9th','Visual storyteller and editor.','01710000009','Weekends'),
(10,'19401001','BBA','13th','Cultural program organizer.','01710000010','Flexible'),
(11,'20401002','BBA','11th','Dance and music coordinator.','01710000011','Evenings'),
(12,'19501001','BBA','13th','Startup founder, pitch coach.','01710000012','Flexible'),
(13,'20501002','ECO','11th','Growth marketing for student ventures.','01710000013','Weekdays'),
(14,'19601001','PSS','13th','Community organizer, blood drive lead.','01710000014','Flexible'),
(15,'20601002','PSS','11th','Volunteer coordinator.','01710000015','Weekends'),
(16,'20701001','ENH','11th','Toastmasters certified speaker coach.','01710000016','Evenings'),
(17,'21701002','ENH','9th','Content and social media lead.','01710000017','Flexible'),
(18,'22101045','CSE','5th','Aspiring full-stack developer who also loves photography and debate.','01710000018','Evenings & weekends'),
(19,'22101046','CSE','5th','Backend-leaning developer, enjoys competitive programming.','01710000019','Weekdays'),
(20,'22301012','ARC','5th','Aspiring cinematographer and photo editor.','01710000020','Weekends'),
(21,'22101050','CSE','5th','Web developer exploring UI/UX.','01710000021','Flexible'),
(22,'21101070','EEE','7th','Loves building small robots and IoT gadgets.','01710000022','Weekends'),
(23,'22101055','CSE','5th','Public speaking hobbyist, wants to debate more.','01710000023','Evenings'),
(24,'21201005','ENH','7th','Debate club regular, MUN delegate.','01710000024','Evenings'),
(25,'21101090','EEE','7th','Robotics + programming double interest.','01710000025','Weekends'),
(26,'22101060','CSE','5th','Frontend developer, React hobbyist.','01710000026','Flexible'),
(27,'22101061','CSE','5th','Graphic design and branding enthusiast.','01710000027','Flexible'),
(28,'20501020','ECO','11th','Marketing and business case competitions.','01710000028','Weekdays'),
(29,'22501011','ECO','5th','Aspiring entrepreneur, pitch deck design.','01710000029','Flexible'),
(30,'21701020','ENH','7th','Content writer and podcast host.','01710000030','Evenings'),
(31,'22601010','PSS','5th','Volunteering for social causes since freshman year.','01710000031','Weekends'),
(32,'22101065','CSE','5th','Video editing and motion graphics.','01710000032','Flexible'),
(33,'22401015','BBA','5th','Cultural dance performer.','01710000033','Evenings'),
(34,'22101070','CSE','5th','Competitive programmer, ML curious.','01710000034','Weekdays'),
(35,'22301020','ARC','5th','Illustrator and digital artist.','01710000035','Flexible'),
(36,'22101075','CSE','5th','Cybersecurity hobbyist.','01710000036','Weekends'),
(37,'22201010','ENH','5th','Environmental volunteer and writer.','01710000037','Flexible');

-- ========== SKILLS & INTERESTS ==========
INSERT INTO skills (id, name) VALUES
(1,'Graphic Design'),(2,'Photoshop'),(3,'Illustrator'),(4,'Programming'),(5,'Web Development'),
(6,'Photography'),(7,'Video Editing'),(8,'Public Speaking'),(9,'Event Management'),(10,'Marketing'),
(11,'Content Writing'),(12,'Social Media Management');

INSERT INTO interests (id, name) VALUES
(1,'Technology'),(2,'Debate'),(3,'Business'),(4,'Photography'),(5,'Culture'),
(6,'Entrepreneurship'),(7,'Environment'),(8,'Volunteering'),(9,'Media'),(10,'Sports');

-- Student skills/interests (subset per student for realistic matching)
INSERT INTO student_skills (user_id, skill_id) VALUES
(18,4),(18,5),(18,1),(18,6),
(19,4),(19,5),
(20,6),(20,7),(20,2),
(21,5),(21,1),
(22,4),(22,9),
(23,8),(23,11),
(24,8),(24,11),
(25,4),(25,9),
(26,5),(26,4),
(27,1),(27,3),
(28,10),(28,8),
(29,10),(29,11),
(30,11),(30,12),
(31,9),(31,12),
(32,7),(32,2),
(33,9),(33,8),
(34,4),(34,5),
(35,3),(35,1),
(36,4),(36,5),
(37,11),(37,9);

INSERT INTO student_interests (user_id, interest_id) VALUES
(18,1),(18,4),(18,2),
(19,1),
(20,4),(20,9),
(21,1),
(22,1),
(23,2),
(24,2),
(25,1),
(26,1),
(27,9),
(28,3),(28,6),
(29,6),(29,3),
(30,9),
(31,8),(31,7),
(32,9),
(33,5),
(34,1),
(35,9),
(36,1),
(37,7),(37,8);

-- ========== CLUBS ==========
INSERT INTO clubs (id, name, slug, category, description, mission, logo_color, email, social_link, recruitment_status, founded_year, status) VALUES
(1,'BRAC University Computer Club','bracu-computer-club','Co-Curricular','The largest tech community at BRACU, running hackathons, workshops and dev bootcamps for 15+ years.','Empower every BRACU student to build real software.','#4f46e5','buc.club@bracu.ac.bd','fb.com/bracuccc','open',2009,'active'),
(2,'BRAC University Robotics Club','bracu-robotics-club','Technology','Design, build and compete with robots across national and international competitions.','Advance hands-on robotics and embedded systems learning.','#0891b2','robotics@bracu.ac.bd','fb.com/bracurobotics','open',2012,'active'),
(3,'BRAC University Debate Club','bracu-debate-club','Debate','Bangladesh''s most decorated university debate society, training parliamentary and MUN debaters.','Cultivate critical thinking and eloquent public discourse.','#b45309','debate@bracu.ac.bd','fb.com/bracudebate','closed',2005,'active'),
(4,'BRAC University Photography Club (Aperture)','bracu-photography-club','Media','A collective of visual storytellers documenting campus life and beyond.','Nurture photographic art and campus visual journalism.','#be185d','aperture@bracu.ac.bd','fb.com/apertureBRACU','open',2014,'active'),
(5,'BRAC University Cultural Club (Uddipana)','bracu-cultural-club','Cultural','Home of music, dance, drama and festival celebrations at BRACU.','Celebrate and preserve Bengali culture on campus.','#7c3aed','uddipana@bracu.ac.bd','fb.com/uddipana','closed',2008,'active'),
(6,'BRAC University Entrepreneurship Development Club','bracu-edc','Entrepreneurship','Supporting student founders with mentorship, pitch competitions and startup bootcamps.','Turn student ideas into viable ventures.','#ea580c','edc@bracu.ac.bd','fb.com/bracuedc','open',2016,'active'),
(7,'BRAC University Social Service Club','bracu-social-service-club','Social Service','Organizing blood drives, relief campaigns and community volunteering.','Build a culture of service among BRACU students.','#16a34a','ssc@bracu.ac.bd','fb.com/bracussc','closed',2007,'active'),
(8,'BRAC University Communication & Public Speaking Club','bracu-communication-club','Professional','Building confident communicators through workshops, Toastmasters-style sessions and content labs.','Develop professional communication skills campus-wide.','#4338ca','commclub@bracu.ac.bd','fb.com/bracucomm','open',2018,'active');

INSERT INTO club_skills (club_id, skill_id) VALUES
(1,4),(1,5),
(2,4),(2,9),
(3,8),(3,11),
(4,6),(4,7),(4,2),
(5,9),(5,8),
(6,10),(6,8),(6,11),
(7,9),(7,12),
(8,8),(8,11),(8,12);

INSERT INTO club_interests (club_id, interest_id) VALUES
(1,1),
(2,1),
(3,2),
(4,4),(4,9),
(5,5),
(6,6),(6,3),
(7,8),(7,7),
(8,2),(8,9);

INSERT INTO club_members (club_id, user_id, position, member_role, status) VALUES
(1,2,'President','president','active'),
(1,3,'General Secretary','exec','active'),
(1,19,'General Member','member','active'),
(1,26,'General Member','member','active'),
(2,4,'President','president','active'),
(2,5,'General Secretary','exec','active'),
(2,22,'General Member','member','active'),
(3,6,'President','president','active'),
(3,7,'General Secretary','exec','active'),
(3,24,'General Member','member','active'),
(4,8,'President','president','active'),
(4,9,'General Secretary','exec','active'),
(4,20,'General Member','member','active'),
(5,10,'President','president','active'),
(5,11,'General Secretary','exec','active'),
(5,33,'General Member','member','active'),
(6,12,'President','president','active'),
(6,13,'General Secretary','exec','active'),
(6,28,'General Member','member','active'),
(7,14,'President','president','active'),
(7,15,'General Secretary','exec','active'),
(7,31,'General Member','member','active'),
(8,16,'President','president','active'),
(8,17,'General Secretary','exec','active');

-- ========== RECRUITMENT ==========
INSERT INTO recruitment_campaigns (id, club_id, title, description, positions_needed, deadline, status) VALUES
(1,1,'Fall 2026 Developer Recruitment','Looking for frontend, backend and mobile developers to join our core dev team.',5,'2026-09-15','open'),
(2,2,'Robotics Engineers Wanted','Recruiting members for our national robotics competition team.',4,'2026-09-10','open'),
(3,4,'Creative Photographers Needed','Join our visual team covering campus events all semester.',3,'2026-09-20','open'),
(4,6,'Growth & Marketing Team Recruitment','Recruiting marketing, content and outreach members for startup season.',4,'2026-09-12','open'),
(5,8,'Speakers & Content Team Recruitment','Recruiting for our public speaking coaching and content team.',3,'2026-09-18','open');

INSERT INTO applications (id, campaign_id, club_id, user_id, motivation, skills_text, experience, portfolio_link, availability, status, applied_at) VALUES
(1,1,1,18,'I want to contribute to real hackathon infrastructure and grow as a full-stack developer.','Programming, Web Development, Graphic Design','Built 3 personal web apps, TA for CSE220 lab.','github.com/rakibulislam','Evenings & weekends','shortlisted','2026-08-10 10:00:00'),
(2,3,4,18,'Photography has been my hobby for 3 years and I want to shoot official BRACU events.','Photography','Shot 2 campus events informally.','instagram.com/rakib.frames','Evenings & weekends','submitted','2026-08-18 14:20:00'),
(3,1,1,21,'Eager to learn backend systems and contribute to club tools.','Web Development, Graphic Design','Personal portfolio site, React basics.','github.com/mahinc','Flexible','under_review','2026-08-12 09:00:00'),
(4,1,1,23,'Want to build something impactful with the dev team.','Public Speaking, Content Writing','Wrote technical blog posts.','','Evenings','interview','2026-08-08 11:00:00'),
(5,2,2,25,'Robotics is my passion since high school FIRST competitions.','Programming, Event Management','2x national robotics competition finalist.','','Weekends','accepted','2026-08-05 08:30:00'),
(6,2,2,27,'Interested in learning embedded systems and PCB design.','Graphic Design, Illustrator','Some Arduino tinkering.','','Flexible','rejected','2026-08-06 13:00:00'),
(7,4,6,29,'I want to launch my own startup and this club can help me get there.','Marketing, Content Writing','Ran a small online store.','','Flexible','submitted','2026-08-19 16:00:00'),
(8,5,8,30,'I want to become a confident public speaker and content creator.','Content Writing, Social Media Management','Hosts a small podcast.','','Evenings','shortlisted','2026-08-14 10:15:00'),
(9,3,4,32,'Video editing is my strength and I want to help produce club content.','Video Editing, Photoshop','Edits YouTube videos as a hobby.','youtube.com/@shakiledits','Flexible','under_review','2026-08-16 12:00:00');

INSERT INTO application_notes (application_id, author_user_id, note) VALUES
(1,2,'Strong portfolio, moved to shortlist for interview round.'),
(4,2,'Great communicator, scheduling interview for next week.'),
(5,4,'Excellent competition record, fast-track accepted.'),
(6,4,'Skillset does not match current robotics needs this cycle.');

-- ========== VENUES ==========
INSERT INTO venues (id, name, capacity, location, status) VALUES
(1,'Multipurpose Hall',3000,'Ground Floor','available'),
(2,'Auditorium',1500,'Ground Floor','available'),
(3,'Club Room 1',60,'6th Floor, H Block','available'),
(4,'Club Room 2',60,'6th Floor, H Block','available'),
(5,'Club Room 3',60,'6th Floor, H Block','available'),
(6,'Club Room 4',60,'6th Floor, H Block','available'),
(7,'Lecture Theater (9G-31T)',300,'9th Floor, G Block','available'),
(8,'Lecture Theater (9C-16T)',300,'9th Floor, C Block','available');

-- ========== EVENTS ==========
INSERT INTO events (id, club_id, title, description, category, event_date, start_time, end_time, venue_id, expected_attendance, required_equipment, budget_amount, is_major_event, status, created_by, reviewed_by, reviewed_at, review_comment) VALUES
(1,1,'TechFest Hackathon 2026','24-hour campus-wide hackathon with industry judges and prizes.','Technology','2026-09-05','10:00:00','18:00:00',1,400,'Projectors, extension cords, wifi routers',120000,0,'approved',2,1,'2026-08-20 10:00:00','Approved — flagship event, budget under separate review.'),
(2,4,'Photo Walk Exhibition','End-of-semester exhibition of the best student photography.','Media','2026-08-30','15:00:00','19:00:00',6,150,'Display boards, lighting',25000,0,'approved',8,1,'2026-08-15 10:00:00','Approved.'),
(3,2,'Robo Workshop','Hands-on Arduino and sensor workshop for new members.','Technology','2026-09-12','14:00:00','17:00:00',4,60,'Arduino kits, laptops',30000,0,'submitted',4,NULL,NULL,NULL),
(4,6,'Startup Pitch Night','Student founders pitch to a panel of investors and alumni.','Entrepreneurship','2026-09-05','15:00:00','18:00:00',1,300,'Stage mic, projector, banners',60000,0,'submitted',12,NULL,NULL,NULL),
(5,NULL,'BRACU Convocation 2026','University-wide convocation ceremony.','University','2026-09-05','09:00:00','14:00:00',2,1200,'Stage setup, live stream',0,1,'approved',1,1,'2026-07-01 10:00:00','University flagship event.'),
(6,3,'Inter-University Debate Championship','Two-day parliamentary debate championship hosting 20+ universities.','Debate','2026-09-20','09:00:00','17:00:00',2,300,'Mic system, scoreboards',85000,0,'approved',6,1,'2026-08-10 09:00:00','Approved.'),
(7,5,'Pohela Falgun Celebration','Spring festival with music, dance and food stalls.','Cultural','2026-08-28','11:00:00','16:00:00',1,350,'Sound system, stage decoration',75000,0,'approved',10,1,'2026-08-05 09:00:00','Approved with adjusted budget.'),
(8,7,'Blood Donation Camp','Semester blood donation drive with Red Crescent.','Social Service','2026-09-02','10:00:00','15:00:00',3,100,'Medical camp setup',15000,0,'approved',14,1,'2026-08-12 09:00:00','Approved.'),
(9,8,'Public Speaking Bootcamp','Full-day intensive workshop on public speaking fundamentals.','Professional','2026-09-08','10:00:00','13:00:00',5,50,'Mic, projector',12000,0,'submitted',16,NULL,NULL,NULL),
(10,1,'Intro to Web Dev Workshop','Beginner-friendly HTML/CSS/JS workshop.','Technology','2026-08-10','14:00:00','17:00:00',4,35,'Laptops, projector',8000,0,'completed',2,1,'2026-08-01 09:00:00','Approved.'),
(11,2,'Drone Racing Show','Live drone racing demonstration on the rooftop.','Technology','2026-09-25','16:00:00','19:00:00',6,120,'Drone arena netting',40000,0,'rejected',4,1,'2026-08-18 10:00:00','Insufficient safety plan submitted; please resubmit with a documented safety and insurance plan.');

INSERT INTO event_registrations (event_id, user_id, attended) VALUES
(1,18,0),(1,21,0),(1,23,0),(1,26,0),(1,19,0),
(2,18,0),(2,20,0),(2,27,0),
(6,18,0),(6,24,0),
(7,33,0),(7,18,0),
(10,19,1),(10,21,1),(10,26,1),(10,18,1);

-- ========== BUDGET REQUESTS ==========
INSERT INTO budget_requests (id, event_id, club_id, event_name, event_date, venue_id, expected_attendance, requested_amount, approved_amount, justification, status, submitted_by, submitted_at, reviewed_by, reviewed_at, review_comment) VALUES
(1,7,5,'Pohela Falgun Celebration','2026-08-28',1,350,75000,70000,'Annual spring festival — decorations, stage, food stalls and marketing for expected 350 attendees.','approved',10,'2026-08-02 10:00:00',1,'2026-08-05 09:00:00','Approved with 5,000 trim on decoration budget.'),
(3,4,6,'Startup Pitch Night','2026-09-05',1,300,60000,NULL,'Investor panel event requiring stage, banners and light catering.','submitted',12,'2026-08-20 11:00:00',NULL,NULL,NULL),
(4,3,2,'Robo Workshop','2026-09-12',4,60,30000,NULL,'Arduino kits and sensors for hands-on workshop.','draft',4,'2026-08-21 08:00:00',NULL,NULL,NULL),
(5,8,7,'Blood Donation Camp','2026-09-02',3,100,15000,NULL,'Medical camp setup and refreshments for donors.','rejected',14,'2026-08-09 10:00:00',1,'2026-08-12 09:00:00','Please provide vendor quotations before resubmission.');

INSERT INTO budget_items (budget_request_id, category, amount) VALUES
(1,'Decorations',20000),(1,'Marketing',10000),(1,'Equipment',15000),(1,'Food',25000),(1,'Miscellaneous',5000),
(3,'Stage & AV',25000),(3,'Marketing',15000),(3,'Food',15000),(3,'Miscellaneous',5000),
(4,'Arduino Kits',20000),(4,'Sensors',7000),(4,'Miscellaneous',3000),
(5,'Medical Supplies',8000),(5,'Refreshments',5000),(5,'Miscellaneous',2000);

INSERT INTO budget_audit_log (budget_request_id, action, actor_user_id, notes) VALUES
(1,'submitted',10,'Initial submission.'),
(1,'reviewed',1,'Approved with 5,000 trim on decorations.'),
(3,'submitted',12,'Initial submission.'),
(5,'submitted',14,'Initial submission.'),
(5,'reviewed',1,'Rejected — vendor quotations required.');

-- ========== COLLABORATION BOARD ==========
INSERT INTO collaboration_requests (id, club_id, request_type, title, description, required_date, priority, status, created_at) VALUES
(1,4,'equipment','Need DSLR camera for photography coverage.','Our club camera is under repair — need a DSLR for TechFest coverage on Sept 5.','2026-09-05','high','open','2026-08-20 09:00:00'),
(2,1,'human_resource','Looking for 2 graphic designers for our upcoming event.','Need designers to help with TechFest branding, posters and social posts.','2026-09-01','medium','responses_received','2026-08-15 10:00:00'),
(3,6,'co_host','Looking for a club interested in co-hosting a technology conference.','EDC wants to co-host a startup-meets-tech conference this semester.','2026-10-01','medium','in_discussion','2026-08-10 11:00:00'),
(4,5,'venue','Need access to sound equipment.','Sound system needed for Pohela Falgun stage performances.','2026-08-28','high','accepted','2026-08-01 09:00:00'),
(5,7,'human_resource','Need volunteers for blood donation camp registration desk.','Looking for 4-5 volunteers to help manage donor registration.','2026-09-02','low','completed','2026-07-28 09:00:00'),
(6,8,'media','Looking for videographer to cover our bootcamp.','Need someone to film and edit highlights from the public speaking bootcamp.','2026-09-08','medium','open','2026-08-19 12:00:00');

INSERT INTO collaboration_responses (collaboration_id, responding_club_id, responder_user_id, message, status) VALUES
(2,4,8,'We can offer one of our designers for the TechFest branding work.','accepted'),
(2,8,16,'Happy to help with poster and social copy design.','proposed'),
(3,1,2,'Computer Club is interested in co-hosting the tech conference with EDC.','proposed'),
(4,2,4,'Robotics Club can lend our sound equipment for the festival.','accepted'),
(5,3,6,'Debate Club can send 5 volunteers for the registration desk.','accepted');

-- ========== TASKS ==========
-- Sarah Ahmed (id 2) — overloaded exec demo: 12 active tasks, 4 high priority, 2 overdue (today = 2026-08-24)
INSERT INTO tasks (club_id, event_id, title, description, assigned_to, created_by, priority, deadline, status, estimated_workload) VALUES
(1,1,'Design event poster','Create the main TechFest poster and social banners.',2,2,'high','2026-08-20','in_progress',8),
(1,1,'Contact sponsors','Reach out to 10 potential sponsors for TechFest.',2,2,'high','2026-08-22','todo',7),
(1,1,'Book venue confirmation','Finalize Multipurpose Hall booking paperwork.',2,2,'high','2026-08-28','in_progress',4),
(1,1,'Prepare judge invitations','Draft and send invitations to hackathon judges.',2,2,'high','2026-08-30','todo',3),
(1,1,'Coordinate with catering','Finalize food vendor for 400 attendees.',2,2,'medium','2026-09-01','todo',5),
(1,1,'Arrange photography','Confirm Aperture club coverage for the event.',2,2,'medium','2026-09-02','todo',3),
(1,1,'Social media promotion plan','Plan the 2-week promo content calendar.',2,2,'medium','2026-08-29','in_progress',6),
(1,1,'Prepare certificates','Design participation and winner certificates.',2,2,'medium','2026-09-03','todo',3),
(1,1,'Manage registration portal','Set up and monitor the online registration form.',2,2,'medium','2026-08-27','review',5),
(1,1,'Guest coordination','Coordinate logistics for keynote guest speaker.',2,2,'low','2026-09-04','todo',3),
(1,1,'Budget preparation','Prepare the detailed budget breakdown for OCA.',2,2,'medium','2026-08-26','in_progress',6),
(1,1,'Volunteer briefing','Brief the 15 event-day volunteers.',2,2,'low','2026-09-04','todo',2),
-- Tanvir (id 3) — light load
(1,1,'Update Computer Club website','Refresh events page with TechFest details.',3,2,'low','2026-09-10','todo',3),
(1,1,'Prepare workshop slides','Slides for the pre-hackathon intro workshop.',3,2,'medium','2026-09-08','in_progress',4),
(1,NULL,'Venue booking confirmed','Confirmed Seminar Hall 1 for the web dev workshop.',3,2,'low','2026-08-15','completed',2),
-- Nafis Rahman (id 19) — light load
(1,1,'Test hackathon registration system','QA the registration form and payment flow.',19,2,'medium','2026-09-05','todo',4),
(1,1,'Draft workshop content','Prepare content outline for intro workshop.',19,2,'medium','2026-09-15','todo',3),
(1,1,'Review sponsor contracts','Legal review pass on sponsor agreement templates.',19,2,'low','2026-09-20','todo',2),
-- Other clubs — light seeding
(4,2,'Edit exhibition photos','Select and edit final photo set for exhibition.',9,8,'medium','2026-08-25','completed',5),
(6,4,'Prepare pitch deck template','Design the standard pitch deck template for founders.',13,12,'medium','2026-09-01','in_progress',4),
(2,3,'Order Arduino kits','Place the supplier order for workshop kits.',5,4,'high','2026-08-29','todo',3),
(3,6,'Recruit championship volunteers','Recruit 10 volunteers for the debate championship.',7,6,'medium','2026-09-10','todo',4),
(7,8,'Coordinate with Red Crescent','Confirm medical staff and supplies with partner.',15,14,'high','2026-08-28','in_progress',5),
(8,9,'Book guest speaker','Confirm keynote speaker for public speaking bootcamp.',17,16,'medium','2026-08-30','todo',3);

-- ========== NOTIFICATIONS ==========
INSERT INTO notifications (user_id, category, title, message, link, is_read, created_at) VALUES
(18,'application','Application shortlisted','Your application to BRAC University Computer Club has been shortlisted.','/student/my_applications.php',0,'2026-08-19 09:00:00'),
(18,'event','Event registration confirmed','You are registered for TechFest Hackathon 2026 on Sep 5.','/student/events.php',0,'2026-08-20 10:00:00'),
(18,'deadline','Recruitment deadline approaching','BRACU Photography Club recruitment closes Sep 20.','/student/clubs.php',1,'2026-08-15 08:00:00'),
(2,'application','New application received','Nabila Sultana applied to Computer Club developer recruitment.','/exec/recruitment.php',0,'2026-08-08 11:05:00'),
(2,'collaboration','Collaboration response received','BRACU Photography Club offered a designer for your request.','/exec/collaboration.php',0,'2026-08-16 09:00:00'),
(2,'budget','Budget under review','Your TechFest Hackathon budget request is now under OCA review.','/exec/budget.php',1,'2026-08-15 09:30:00'),
(2,'task','2 tasks overdue','You have 2 overdue tasks for TechFest Hackathon 2026.','/exec/tasks.php',0,'2026-08-23 07:00:00'),
(1,'event','Event proposal awaiting review','Startup Pitch Night (EDC) is awaiting your approval.','/admin/events.php',0,'2026-08-20 12:00:00'),
(1,'budget','Budget request pending','Robo Workshop budget request needs review.','/admin/budgets.php',0,'2026-08-21 08:30:00'),
(1,'venue','Venue conflict detected','TechFest Hackathon and Startup Pitch Night both request Multipurpose Hall on Sep 5.','/admin/venues.php',0,'2026-08-20 12:05:00'),
(12,'event','Event submitted','Your Startup Pitch Night proposal was submitted for OCA review.','/exec/events.php',1,'2026-08-20 11:30:00'),
(14,'budget','Budget rejected','Your Blood Donation Camp budget was rejected — quotations required.','/exec/budget.php',0,'2026-08-12 09:05:00');

-- ========== ANNOUNCEMENTS ==========
INSERT INTO announcements (title, body, posted_by, audience, created_at) VALUES
('Fall 2026 Club Registration Deadline','All clubs must submit their fall semester registration renewal by September 1, 2026.',1,'all','2026-08-15 09:00:00'),
('Annual Budget Submission Guidelines Updated','Club executives should review the updated budget category templates before submitting new requests.',1,'execs','2026-08-10 09:00:00'),
('OCA Office Hours This Week','OCA office hours this week are 2-4pm, Sun-Thu, UB2 room 301.',1,'all','2026-08-22 09:00:00');

-- ========== AUDIT LOG ==========
INSERT INTO audit_log (actor_user_id, action, entity_type, entity_id, details) VALUES
(1,'approved_event','event',1,'Approved TechFest Hackathon 2026.'),
(1,'approved_budget','budget_request',1,'Approved Pohela Falgun Celebration budget at 70,000.'),
(1,'rejected_budget','budget_request',5,'Rejected Blood Donation Camp budget — quotations required.'),
(1,'rejected_event','event',11,'Rejected Drone Racing Show — insufficient safety plan.');

-- ===========================================================
-- ADDITIONAL CLUBS + SUPPORTING SKILLS/INTERESTS
-- (appended so a fresh import includes the full 38-club roster)
-- ===========================================================
-- ========== NEW SKILLS (used as "requirements to join" tags) ==========
INSERT IGNORE INTO skills (name) VALUES
('Leadership'),
('Research & Analysis'),
('Financial Analysis'),
('Foreign Language'),
('Acting & Directing'),
('First Aid & Safety'),
('Strategic Thinking'),
('Legal Research');

-- ========== NEW INTERESTS ==========
INSERT IGNORE INTO interests (name) VALUES
('Adventure & Outdoors'),
('Research'),
('Law'),
('Finance'),
('Science'),
('Performing Arts'),
('Gaming & Strategy'),
('Religion & Spirituality'),
('Leadership');

-- ========== NEW CLUBS ==========
-- Note: "Computer Club (BUCC)" is intentionally omitted — it already exists
-- as "BRAC University Computer Club" (same real club, same abbreviation).
INSERT INTO clubs (name, slug, category, description, mission, logo_color, email, social_link, recruitment_status, founded_year, status) VALUES
('BRAC University Adventure Club (BUAC)','adventure-club-buac','Extra Curricular','Organizes hiking, camping, trekking and outdoor expeditions for students who love adventure and the outdoors.','Build resilience, teamwork and a love for nature through outdoor adventure.','#4f46e5','buac@bracu.ac.bd','fb.com/buac','open',2011,'active'),
('BRAC University Art & Photography Society (BUAPS)','art-photography-society-buaps','Extra Curricular','A creative space for painters, sketch artists and photographers to showcase and refine their visual art.','Nurture artistic talent and visual storytelling across mediums.','#be185d','buaps@bracu.ac.bd','fb.com/buaps','open',2015,'active'),
('BRAC University Community Service Club (BUCSC)','community-service-club-bucsc','Extra Curricular','Runs community outreach programs, charity drives and volunteering initiatives across Dhaka.','Foster a culture of giving back among BRACU students.','#16a34a','bucsc@bracu.ac.bd','fb.com/bucsc','open',2009,'active'),
('BRAC University Cultural Club (BUCuC)','cultural-club-bucuc','Extra Curricular','Celebrates Bangladeshi heritage through music, dance and festival events on campus.','Keep Bangladeshi cultural traditions alive on campus.','#7c3aed','bucuc@bracu.ac.bd','fb.com/bucuc','closed',2008,'active'),
('BRAC University Debating Club (BUDC)','debating-club-budc','Extra Curricular','Trains students in parliamentary and Asian-style debate for national and international competitions.','Sharpen critical thinking and persuasive speaking through competitive debate.','#b45309','budc@bracu.ac.bd','fb.com/budc','open',2010,'active'),
('BRAC University Drama and Theater Forum (BUDTF)','drama-theater-forum-budtf','Extra Curricular','Stages original and adapted plays, and trains members in acting, direction and stagecraft.','Bring stories to life on stage and build confident performers.','#9333ea','budtf@bracu.ac.bd','fb.com/budtf','closed',2012,'active'),
('BRAC University Entrepreneurship Forum (BUEDF)','entrepreneurship-forum-buedf','Extra Curricular','Connects aspiring student founders with mentors, workshops and pitch competitions.','Turn student business ideas into real ventures.','#ea580c','buedf@bracu.ac.bd','fb.com/buedf','open',2017,'active'),
('BRAC University Film Club (BUFC)','film-club-bufc','Extra Curricular','Produces short films and documentaries and hosts screenings of student and world cinema.','Champion student filmmaking and visual storytelling.','#dc2626','bufc@bracu.ac.bd','fb.com/bufc','open',2014,'active'),
('BRAC University Response Team (BURT)','response-team-burt','Extra Curricular','A trained volunteer team providing first-aid and emergency support at campus events.','Keep the BRACU community safe through rapid, trained emergency response.','#0d9488','burt@bracu.ac.bd','fb.com/burt','closed',2013,'active'),
('BRAC University Association of Business Communicators (IABC)','association-business-communicators-iabc','Extra Curricular','Builds professional communication and public relations skills for future business leaders.','Develop confident, polished business communicators.','#4338ca','iabc@bracu.ac.bd','fb.com/iabc','open',2016,'active'),
('BRAC University MONON Club','monon-club','Extra Curricular','BRACU''s puzzle-hunt and treasure-hunt club — runs campus-wide quiz and mystery-solving events.','Challenge minds through puzzles, riddles and real-world treasure hunts.','#0891b2','monon@bracu.ac.bd','fb.com/mononclub','open',2018,'active'),
('BRAC University Leadership Development Forum (BULDF)','leadership-development-forum-buldf','Extra Curricular','Runs leadership workshops, mentorship circles and personal development sessions.','Shape the next generation of student leaders.','#059669','buldf@bracu.ac.bd','fb.com/buldf','closed',2015,'active'),
('BRAC University Chess Club (BUCHC)','chess-club-buchc','Extra Curricular','Hosts chess tournaments and coaching sessions for players of every skill level.','Grow a competitive and welcoming chess community at BRACU.','#4f46e5','buchc@bracu.ac.bd','fb.com/buchc','open',2011,'active'),
('BRAC University Communication & Language Club (BUCLC)','communication-language-buclc','Extra Curricular','Helps students master English, foreign languages and everyday communication skills.','Build confident communicators across languages and cultures.','#be185d','buclc@bracu.ac.bd','fb.com/buclc','open',2013,'active'),
('BRAC University Research for Development Club (BURed)','bracu-research-development-club-bured','Extra Curricular','Engages students in research projects on social and economic development issues.','Promote evidence-based research culture among undergraduates.','#7c3aed','bured@bracu.ac.bd','fb.com/bured','closed',2019,'active'),
('BRAC University Peace Café (PCBU)','peace-cafe-bracu-pcbu','Extra Curricular','Hosts interfaith and cross-cultural dialogue sessions to promote peace and mutual understanding.','Build bridges of understanding across faiths and cultures.','#16a34a','pcbu@bracu.ac.bd','fb.com/pcbu','open',2012,'active'),
('BRAC University Multicultural Club (BUMC)','multicultural-club-bumc','Extra Curricular','Celebrates the diversity of international and local students through cultural exchange events.','Create a home away from home for students of every background.','#ea580c','bumc@bracu.ac.bd','fb.com/bumc','closed',2017,'active'),
('BRAC University Business & Economics Forum (BUBeF)','business-economics-forum-bubef','Co-Curricular','Hosts case competitions, guest lectures and panel discussions on business and economic trends.','Bridge classroom theory with real-world business and economic insight.','#4338ca','bubef@bracu.ac.bd','fb.com/bubef','open',2014,'active'),
('BRAC University Business Club (BIZBEE)','business-club-bizbee','Co-Curricular','Connects students with the corporate world through networking events and business simulations.','Prepare students for real-world business careers.','#0891b2','bizbee@bracu.ac.bd','fb.com/bizbee','open',2016,'active'),
('BRAC University Finance and Accounting Club (BUFIN)','finance-accounting-club-bufin','Co-Curricular','Runs workshops on financial modeling, accounting standards and investment analysis.','Build strong financial literacy and analytical skills among students.','#059669','bufin@bracu.ac.bd','fb.com/bufin','closed',2018,'active'),
('BRAC University Economics Club (BUEC)','economics-club-buec','Co-Curricular','Explores micro and macroeconomic theory through debates, workshops and research projects.','Deepen economic literacy and analytical rigor among students.','#b45309','buec@bracu.ac.bd','fb.com/buec','open',2010,'active'),
('BRAC University Electrical & Electronic Club (BUEEC)','electrical-electronic-club-bueec','Co-Curricular','Hands-on club for circuit design, embedded systems and electronics projects.','Turn EEE theory into hands-on engineering projects.','#9333ea','bueec@bracu.ac.bd','fb.com/bueec','open',2012,'active'),
('BRAC University Law Society (BULC)','law-society-bulc','Co-Curricular','Runs moot courts, legal debates and workshops for aspiring lawyers.','Cultivate legal reasoning and advocacy skills among students.','#dc2626','bulc@bracu.ac.bd','fb.com/bulc','closed',2015,'active'),
('BRAC University Marketing Association (BUMA)','marketing-association-buma','Co-Curricular','Teaches brand strategy, digital marketing and campaign design through live projects.','Turn marketing theory into real campaign experience.','#0d9488','buma@bracu.ac.bd','fb.com/buma','open',2017,'active'),
('BRAC University Natural Science (BUNSC)','natural-science-bunsc','Co-Curricular','Explores biology, chemistry and physics through experiments, seminars and science fairs.','Spark curiosity in the natural sciences beyond the classroom.','#4338ca','bunsc@bracu.ac.bd','fb.com/bunsc','open',2013,'active'),
('BRAC University Pharmacy Society (BUPS)','pharmacy-society-bups','Co-Curricular','Supports pharmacy students with study circles, guest lectures and health-awareness campaigns.','Advance pharmaceutical knowledge and public health awareness.','#0891b2','bups@bracu.ac.bd','fb.com/bups','closed',2014,'active'),
('BRAC University Robotics Club (ROBU)','robotics-club-robu','Co-Curricular','Designs and competes with robots at national and international robotics competitions.','Push the boundaries of student-built robotics.','#059669','robu@bracu.ac.bd','fb.com/robu','open',2016,'active'),
('BRAC University Cricket Club (CBU)','cricket-club-cbu','Sports','BRACU''s official cricket team, competing in inter-university tournaments and running regular practice sessions.','Build a competitive, disciplined cricket team representing BRACU.','#b45309','cbu@bracu.ac.bd','fb.com/cbu','open',2009,'active'),
('BRAC University Football Club (FCBU)','football-club-fcbu','Sports','BRACU''s football team, training and competing in inter-university leagues.','Compete with pride and sportsmanship on the football field.','#9333ea','fcbu@bracu.ac.bd','fb.com/fcbu','open',2009,'active'),
('BRAC University Indoor Games Club (BUIGC)','indoor-games-club-buigc','Sports','Organizes table tennis, carrom, badminton and other indoor game tournaments on campus.','Bring friendly indoor sports competition to every BRACU student.','#dc2626','buigc@bracu.ac.bd','fb.com/buigc','closed',2011,'active');

-- ========== CLUB SKILLS (requirements to join, by name lookup) ==========
INSERT INTO club_skills (club_id, skill_id)
SELECT c.id, s.id FROM clubs c JOIN skills s ON s.name IN ('Event Management','First Aid & Safety') WHERE c.slug='adventure-club-buac';
INSERT INTO club_skills (club_id, skill_id)
SELECT c.id, s.id FROM clubs c JOIN skills s ON s.name IN ('Graphic Design','Photography','Photoshop') WHERE c.slug='art-photography-society-buaps';
INSERT INTO club_skills (club_id, skill_id)
SELECT c.id, s.id FROM clubs c JOIN skills s ON s.name IN ('Event Management','Social Media Management') WHERE c.slug='community-service-club-bucsc';
INSERT INTO club_skills (club_id, skill_id)
SELECT c.id, s.id FROM clubs c JOIN skills s ON s.name IN ('Event Management','Public Speaking') WHERE c.slug='cultural-club-bucuc';
INSERT INTO club_skills (club_id, skill_id)
SELECT c.id, s.id FROM clubs c JOIN skills s ON s.name IN ('Public Speaking','Content Writing') WHERE c.slug='debating-club-budc';
INSERT INTO club_skills (club_id, skill_id)
SELECT c.id, s.id FROM clubs c JOIN skills s ON s.name IN ('Acting & Directing','Event Management') WHERE c.slug='drama-theater-forum-budtf';
INSERT INTO club_skills (club_id, skill_id)
SELECT c.id, s.id FROM clubs c JOIN skills s ON s.name IN ('Marketing','Public Speaking') WHERE c.slug='entrepreneurship-forum-buedf';
INSERT INTO club_skills (club_id, skill_id)
SELECT c.id, s.id FROM clubs c JOIN skills s ON s.name IN ('Video Editing','Photography') WHERE c.slug='film-club-bufc';
INSERT INTO club_skills (club_id, skill_id)
SELECT c.id, s.id FROM clubs c JOIN skills s ON s.name IN ('First Aid & Safety','Event Management') WHERE c.slug='response-team-burt';
INSERT INTO club_skills (club_id, skill_id)
SELECT c.id, s.id FROM clubs c JOIN skills s ON s.name IN ('Public Speaking','Content Writing','Social Media Management') WHERE c.slug='association-business-communicators-iabc';
INSERT INTO club_skills (club_id, skill_id)
SELECT c.id, s.id FROM clubs c JOIN skills s ON s.name IN ('Strategic Thinking','Research & Analysis') WHERE c.slug='monon-club';
INSERT INTO club_skills (club_id, skill_id)
SELECT c.id, s.id FROM clubs c JOIN skills s ON s.name IN ('Leadership','Public Speaking') WHERE c.slug='leadership-development-forum-buldf';
INSERT INTO club_skills (club_id, skill_id)
SELECT c.id, s.id FROM clubs c JOIN skills s ON s.name IN ('Strategic Thinking') WHERE c.slug='chess-club-buchc';
INSERT INTO club_skills (club_id, skill_id)
SELECT c.id, s.id FROM clubs c JOIN skills s ON s.name IN ('Foreign Language','Public Speaking') WHERE c.slug='communication-language-buclc';
INSERT INTO club_skills (club_id, skill_id)
SELECT c.id, s.id FROM clubs c JOIN skills s ON s.name IN ('Research & Analysis','Content Writing') WHERE c.slug='bracu-research-development-club-bured';
INSERT INTO club_skills (club_id, skill_id)
SELECT c.id, s.id FROM clubs c JOIN skills s ON s.name IN ('Public Speaking','Event Management') WHERE c.slug='peace-cafe-bracu-pcbu';
INSERT INTO club_skills (club_id, skill_id)
SELECT c.id, s.id FROM clubs c JOIN skills s ON s.name IN ('Event Management','Social Media Management') WHERE c.slug='multicultural-club-bumc';
INSERT INTO club_skills (club_id, skill_id)
SELECT c.id, s.id FROM clubs c JOIN skills s ON s.name IN ('Research & Analysis','Public Speaking') WHERE c.slug='business-economics-forum-bubef';
INSERT INTO club_skills (club_id, skill_id)
SELECT c.id, s.id FROM clubs c JOIN skills s ON s.name IN ('Marketing','Event Management') WHERE c.slug='business-club-bizbee';
INSERT INTO club_skills (club_id, skill_id)
SELECT c.id, s.id FROM clubs c JOIN skills s ON s.name IN ('Financial Analysis','Research & Analysis') WHERE c.slug='finance-accounting-club-bufin';
INSERT INTO club_skills (club_id, skill_id)
SELECT c.id, s.id FROM clubs c JOIN skills s ON s.name IN ('Research & Analysis') WHERE c.slug='economics-club-buec';
INSERT INTO club_skills (club_id, skill_id)
SELECT c.id, s.id FROM clubs c JOIN skills s ON s.name IN ('Programming','Research & Analysis') WHERE c.slug='electrical-electronic-club-bueec';
INSERT INTO club_skills (club_id, skill_id)
SELECT c.id, s.id FROM clubs c JOIN skills s ON s.name IN ('Legal Research','Public Speaking') WHERE c.slug='law-society-bulc';
INSERT INTO club_skills (club_id, skill_id)
SELECT c.id, s.id FROM clubs c JOIN skills s ON s.name IN ('Marketing','Social Media Management') WHERE c.slug='marketing-association-buma';
INSERT INTO club_skills (club_id, skill_id)
SELECT c.id, s.id FROM clubs c JOIN skills s ON s.name IN ('Research & Analysis') WHERE c.slug='natural-science-bunsc';
INSERT INTO club_skills (club_id, skill_id)
SELECT c.id, s.id FROM clubs c JOIN skills s ON s.name IN ('Research & Analysis','Event Management') WHERE c.slug='pharmacy-society-bups';
INSERT INTO club_skills (club_id, skill_id)
SELECT c.id, s.id FROM clubs c JOIN skills s ON s.name IN ('Programming','Research & Analysis') WHERE c.slug='robotics-club-robu';
INSERT INTO club_skills (club_id, skill_id)
SELECT c.id, s.id FROM clubs c JOIN skills s ON s.name IN ('Event Management') WHERE c.slug='cricket-club-cbu';
INSERT INTO club_skills (club_id, skill_id)
SELECT c.id, s.id FROM clubs c JOIN skills s ON s.name IN ('Event Management') WHERE c.slug='football-club-fcbu';
INSERT INTO club_skills (club_id, skill_id)
SELECT c.id, s.id FROM clubs c JOIN skills s ON s.name IN ('Event Management') WHERE c.slug='indoor-games-club-buigc';

-- ========== CLUB INTERESTS (by name lookup) ==========
INSERT INTO club_interests (club_id, interest_id)
SELECT c.id, i.id FROM clubs c JOIN interests i ON i.name IN ('Adventure & Outdoors','Environment') WHERE c.slug='adventure-club-buac';
INSERT INTO club_interests (club_id, interest_id)
SELECT c.id, i.id FROM clubs c JOIN interests i ON i.name IN ('Photography','Media') WHERE c.slug='art-photography-society-buaps';
INSERT INTO club_interests (club_id, interest_id)
SELECT c.id, i.id FROM clubs c JOIN interests i ON i.name IN ('Volunteering','Environment') WHERE c.slug='community-service-club-bucsc';
INSERT INTO club_interests (club_id, interest_id)
SELECT c.id, i.id FROM clubs c JOIN interests i ON i.name IN ('Culture') WHERE c.slug='cultural-club-bucuc';
INSERT INTO club_interests (club_id, interest_id)
SELECT c.id, i.id FROM clubs c JOIN interests i ON i.name IN ('Debate') WHERE c.slug='debating-club-budc';
INSERT INTO club_interests (club_id, interest_id)
SELECT c.id, i.id FROM clubs c JOIN interests i ON i.name IN ('Performing Arts','Culture') WHERE c.slug='drama-theater-forum-budtf';
INSERT INTO club_interests (club_id, interest_id)
SELECT c.id, i.id FROM clubs c JOIN interests i ON i.name IN ('Entrepreneurship','Business') WHERE c.slug='entrepreneurship-forum-buedf';
INSERT INTO club_interests (club_id, interest_id)
SELECT c.id, i.id FROM clubs c JOIN interests i ON i.name IN ('Media','Performing Arts') WHERE c.slug='film-club-bufc';
INSERT INTO club_interests (club_id, interest_id)
SELECT c.id, i.id FROM clubs c JOIN interests i ON i.name IN ('Volunteering') WHERE c.slug='response-team-burt';
INSERT INTO club_interests (club_id, interest_id)
SELECT c.id, i.id FROM clubs c JOIN interests i ON i.name IN ('Business','Media') WHERE c.slug='association-business-communicators-iabc';
INSERT INTO club_interests (club_id, interest_id)
SELECT c.id, i.id FROM clubs c JOIN interests i ON i.name IN ('Gaming & Strategy') WHERE c.slug='monon-club';
INSERT INTO club_interests (club_id, interest_id)
SELECT c.id, i.id FROM clubs c JOIN interests i ON i.name IN ('Leadership','Business') WHERE c.slug='leadership-development-forum-buldf';
INSERT INTO club_interests (club_id, interest_id)
SELECT c.id, i.id FROM clubs c JOIN interests i ON i.name IN ('Gaming & Strategy','Sports') WHERE c.slug='chess-club-buchc';
INSERT INTO club_interests (club_id, interest_id)
SELECT c.id, i.id FROM clubs c JOIN interests i ON i.name IN ('Culture','Media') WHERE c.slug='communication-language-buclc';
INSERT INTO club_interests (club_id, interest_id)
SELECT c.id, i.id FROM clubs c JOIN interests i ON i.name IN ('Research','Environment') WHERE c.slug='bracu-research-development-club-bured';
INSERT INTO club_interests (club_id, interest_id)
SELECT c.id, i.id FROM clubs c JOIN interests i ON i.name IN ('Religion & Spirituality','Volunteering') WHERE c.slug='peace-cafe-bracu-pcbu';
INSERT INTO club_interests (club_id, interest_id)
SELECT c.id, i.id FROM clubs c JOIN interests i ON i.name IN ('Culture','Media') WHERE c.slug='multicultural-club-bumc';
INSERT INTO club_interests (club_id, interest_id)
SELECT c.id, i.id FROM clubs c JOIN interests i ON i.name IN ('Business','Finance') WHERE c.slug='business-economics-forum-bubef';
INSERT INTO club_interests (club_id, interest_id)
SELECT c.id, i.id FROM clubs c JOIN interests i ON i.name IN ('Business','Entrepreneurship') WHERE c.slug='business-club-bizbee';
INSERT INTO club_interests (club_id, interest_id)
SELECT c.id, i.id FROM clubs c JOIN interests i ON i.name IN ('Finance','Business') WHERE c.slug='finance-accounting-club-bufin';
INSERT INTO club_interests (club_id, interest_id)
SELECT c.id, i.id FROM clubs c JOIN interests i ON i.name IN ('Finance','Research') WHERE c.slug='economics-club-buec';
INSERT INTO club_interests (club_id, interest_id)
SELECT c.id, i.id FROM clubs c JOIN interests i ON i.name IN ('Technology') WHERE c.slug='electrical-electronic-club-bueec';
INSERT INTO club_interests (club_id, interest_id)
SELECT c.id, i.id FROM clubs c JOIN interests i ON i.name IN ('Law','Debate') WHERE c.slug='law-society-bulc';
INSERT INTO club_interests (club_id, interest_id)
SELECT c.id, i.id FROM clubs c JOIN interests i ON i.name IN ('Business','Media') WHERE c.slug='marketing-association-buma';
INSERT INTO club_interests (club_id, interest_id)
SELECT c.id, i.id FROM clubs c JOIN interests i ON i.name IN ('Science','Research') WHERE c.slug='natural-science-bunsc';
INSERT INTO club_interests (club_id, interest_id)
SELECT c.id, i.id FROM clubs c JOIN interests i ON i.name IN ('Science','Volunteering') WHERE c.slug='pharmacy-society-bups';
INSERT INTO club_interests (club_id, interest_id)
SELECT c.id, i.id FROM clubs c JOIN interests i ON i.name IN ('Technology') WHERE c.slug='robotics-club-robu';
INSERT INTO club_interests (club_id, interest_id)
SELECT c.id, i.id FROM clubs c JOIN interests i ON i.name IN ('Sports') WHERE c.slug='cricket-club-cbu';
INSERT INTO club_interests (club_id, interest_id)
SELECT c.id, i.id FROM clubs c JOIN interests i ON i.name IN ('Sports') WHERE c.slug='football-club-fcbu';
INSERT INTO club_interests (club_id, interest_id)
SELECT c.id, i.id FROM clubs c JOIN interests i ON i.name IN ('Sports','Gaming & Strategy') WHERE c.slug='indoor-games-club-buigc';

-- ========== RECRUITMENT CAMPAIGNS (explicit "requirements to join" text, open clubs only) ==========
INSERT INTO recruitment_campaigns (club_id, title, description, positions_needed, deadline, status)
SELECT id, 'Fall 2026 Adventure Recruitment', 'Open to all BRACU students who love the outdoors. No prior trekking/camping experience required — just enthusiasm, physical fitness and a willingness to travel on club trips.', 15, '2026-09-25', 'open' FROM clubs WHERE slug='adventure-club-buac';
INSERT INTO recruitment_campaigns (club_id, title, description, positions_needed, deadline, status)
SELECT id, 'Artists & Photographers Wanted', 'Looking for students skilled in drawing, painting, digital art or photography. Submit 2-3 samples of your work with your application.', 10, '2026-09-20', 'open' FROM clubs WHERE slug='art-photography-society-buaps';
INSERT INTO recruitment_campaigns (club_id, title, description, positions_needed, deadline, status)
SELECT id, 'Volunteers Needed for Fall Drives', 'Open to all students passionate about social causes. No prior volunteering experience needed — training provided before every drive.', 20, '2026-09-22', 'open' FROM clubs WHERE slug='community-service-club-bucsc';
INSERT INTO recruitment_campaigns (club_id, title, description, positions_needed, deadline, status)
SELECT id, 'Debaters Wanted — All Levels', 'Open to beginners and experienced debaters alike. Strong English communication skills preferred; debate experience is a plus but not mandatory.', 12, '2026-09-18', 'open' FROM clubs WHERE slug='debating-club-budc';
INSERT INTO recruitment_campaigns (club_id, title, description, positions_needed, deadline, status)
SELECT id, 'Founders & Innovators Wanted', 'For students with a business idea or interest in entrepreneurship. No existing venture required — just drive and willingness to learn.', 10, '2026-09-15', 'open' FROM clubs WHERE slug='entrepreneurship-forum-buedf';
INSERT INTO recruitment_campaigns (club_id, title, description, positions_needed, deadline, status)
SELECT id, 'Filmmakers & Editors Wanted', 'Looking for students interested in directing, cinematography, or video editing. A short portfolio or sample reel is encouraged but not required.', 8, '2026-09-19', 'open' FROM clubs WHERE slug='film-club-bufc';
INSERT INTO recruitment_campaigns (club_id, title, description, positions_needed, deadline, status)
SELECT id, 'Business Communicators Recruitment', 'Open to students who want to build professional communication and PR skills. No prior experience required — training sessions provided.', 10, '2026-09-21', 'open' FROM clubs WHERE slug='association-business-communicators-iabc';
INSERT INTO recruitment_campaigns (club_id, title, description, positions_needed, deadline, status)
SELECT id, 'Puzzle Solvers Wanted', 'Open to all students who enjoy riddles, logic puzzles and treasure hunts. No prior experience needed — just curiosity and persistence.', 15, '2026-09-24', 'open' FROM clubs WHERE slug='monon-club';
INSERT INTO recruitment_campaigns (club_id, title, description, positions_needed, deadline, status)
SELECT id, 'Fall Chess Recruitment', 'Open to players of all skill levels, from beginners to tournament veterans. Bring your own chess rating if you have one — not required to join.', 20, '2026-09-17', 'open' FROM clubs WHERE slug='chess-club-buchc';
INSERT INTO recruitment_campaigns (club_id, title, description, positions_needed, deadline, status)
SELECT id, 'Language Enthusiasts Wanted', 'Open to students interested in improving English or learning a foreign language. All proficiency levels welcome.', 12, '2026-09-23', 'open' FROM clubs WHERE slug='communication-language-buclc';
INSERT INTO recruitment_campaigns (club_id, title, description, positions_needed, deadline, status)
SELECT id, 'Interfaith Dialogue Volunteers', 'Open to students of all faiths and backgrounds interested in promoting peace and mutual respect. No prior experience required.', 10, '2026-09-16', 'open' FROM clubs WHERE slug='peace-cafe-bracu-pcbu';
INSERT INTO recruitment_campaigns (club_id, title, description, positions_needed, deadline, status)
SELECT id, 'Case Competition & Research Team', 'Looking for students with an interest in business/economics case analysis. Strong analytical and presentation skills preferred.', 10, '2026-09-14', 'open' FROM clubs WHERE slug='business-economics-forum-bubef';
INSERT INTO recruitment_campaigns (club_id, title, description, positions_needed, deadline, status)
SELECT id, 'Business Club Fall Recruitment', 'Open to all students interested in business and corporate networking. No prior work experience required.', 15, '2026-09-13', 'open' FROM clubs WHERE slug='business-club-bizbee';
INSERT INTO recruitment_campaigns (club_id, title, description, positions_needed, deadline, status)
SELECT id, 'Economics Research Recruitment', 'For students with a strong interest in economic theory and data analysis. Basic Excel/statistics knowledge is a plus.', 8, '2026-09-26', 'open' FROM clubs WHERE slug='economics-club-buec';
INSERT INTO recruitment_campaigns (club_id, title, description, positions_needed, deadline, status)
SELECT id, 'EEE Project Team Recruitment', 'Open to students (any department) with an interest in circuits, embedded systems or electronics projects. Basic programming knowledge is helpful.', 10, '2026-09-27', 'open' FROM clubs WHERE slug='electrical-electronic-club-bueec';
INSERT INTO recruitment_campaigns (club_id, title, description, positions_needed, deadline, status)
SELECT id, 'Marketing Team Recruitment', 'Looking for students with an interest in branding, digital marketing or social media strategy. Portfolio not required — enthusiasm is.', 10, '2026-09-28', 'open' FROM clubs WHERE slug='marketing-association-buma';
INSERT INTO recruitment_campaigns (club_id, title, description, positions_needed, deadline, status)
SELECT id, 'Science Enthusiasts Wanted', 'Open to students curious about biology, chemistry or physics beyond the classroom. No prior lab experience required.', 12, '2026-09-29', 'open' FROM clubs WHERE slug='natural-science-bunsc';
INSERT INTO recruitment_campaigns (club_id, title, description, positions_needed, deadline, status)
SELECT id, 'Robotics Team Recruitment', 'Open to students interested in building and programming robots for competitions. Basic programming knowledge preferred; training provided for beginners.', 12, '2026-09-30', 'open' FROM clubs WHERE slug='robotics-club-robu';
INSERT INTO recruitment_campaigns (club_id, title, description, positions_needed, deadline, status)
SELECT id, 'Cricket Team Trials', 'Open to all students with cricket playing experience. Trials will assess batting, bowling and fielding skills.', 18, '2026-09-12', 'open' FROM clubs WHERE slug='cricket-club-cbu';
INSERT INTO recruitment_campaigns (club_id, title, description, positions_needed, deadline, status)
SELECT id, 'Football Team Trials', 'Open to all students with football playing experience. Trials will be held on the university field — bring your own kit.', 22, '2026-09-11', 'open' FROM clubs WHERE slug='football-club-fcbu';

-- ========== NOTIFY ALL STUDENTS ABOUT THE EXPANDED CLUB DIRECTORY ==========
INSERT INTO announcements (title, body, posted_by, audience, created_at)
SELECT 'New Clubs Added — Explore the Expanded Directory', 'The club directory now lists 31 official BRACU clubs across Sports, Co-Curricular and Extra Curricular categories. Head to Explore Clubs to find your match.', id, 'all', NOW() FROM users WHERE role='admin' LIMIT 1;

-- ===========================================================
-- Remove original demo clubs that don't belong to Sports,
-- Co-Curricular, or Extra Curricular (per admin request).
-- Cascades to their members/events/budgets/tasks/applications/
-- campaigns/collaboration requests automatically via FK constraints.
-- ===========================================================
DELETE FROM clubs WHERE slug IN (
  'bracu-robotics-club','bracu-debate-club','bracu-photography-club','bracu-cultural-club',
  'bracu-edc','bracu-social-service-club','bracu-communication-club'
);

-- ===========================================================
-- CLUB MEMBERS + GOVERNING BODIES for previously empty clubs
-- (auto-generated realistic student rosters, Computer Club topped up)
-- ===========================================================
USE bracu_cms;

INSERT INTO users (id, name, email, password, role, avatar_color) VALUES
(38,'Tanjib Islam','tanjib.islam@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','exec','#4f46e5'),
(39,'Nusrat Faruq','nusrat.faruq@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','exec','#b45309'),
(40,'Shuvo Akter','shuvo.akter@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','exec','#0891b2'),
(41,'Nusrat Faruq','nusrat.faruq2@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','exec','#4338ca'),
(42,'Sadia Das','sadia.das@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','exec','#9333ea'),
(43,'Shuvo Akhter','shuvo.akhter@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','exec','#0891b2'),
(44,'Imran Khan','imran.khan@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','student','#9333ea'),
(45,'Shirin Das','shirin.das@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','student','#0d9488'),
(46,'Nabila Akter','nabila.akter@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','student','#7c3aed'),
(47,'Mahin Ahmed','mahin.ahmed@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','exec','#7c3aed'),
(48,'Wasif Miah','wasif.miah@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','exec','#0d9488'),
(49,'Mahiya Akter','mahiya.akter@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','exec','#16a34a'),
(50,'Promi Reza','promi.reza@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','exec','#4338ca'),
(51,'Tamim Anwar','tamim.anwar@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','student','#be185d'),
(52,'Sumaiya Khan','sumaiya.khan@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','student','#9333ea'),
(53,'Ahnaf Rahman','ahnaf.rahman@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','student','#be185d'),
(54,'Nusaiba Sarker','nusaiba.sarker@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','student','#4f46e5'),
(55,'Turin Iqbal','turin.iqbal@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','exec','#0d9488'),
(56,'Shovon Talukder','shovon.talukder@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','exec','#be185d'),
(57,'Zubair Khan','zubair.khan2@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','exec','#16a34a'),
(58,'Hasib Yasmin','hasib.yasmin@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','exec','#4338ca'),
(59,'Promi Mahmud','promi.mahmud@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','student','#dc2626'),
(60,'Nusrat Roy','nusrat.roy@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','student','#9333ea'),
(61,'Tasfia Islam','tasfia.islam@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','student','#be185d'),
(62,'Mishu Uddin','mishu.uddin@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','exec','#4338ca'),
(63,'Sumaiya Siddique','sumaiya.siddique@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','exec','#b45309'),
(64,'Farhan Hasan','farhan.hasan@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','exec','#9333ea'),
(65,'Rezwan Siddique','rezwan.siddique@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','exec','#7c3aed'),
(66,'Wasif Yasmin','wasif.yasmin@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','student','#0891b2'),
(67,'Tahsin Iqbal','tahsin.iqbal@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','student','#4338ca'),
(68,'Tanjila Mahmud','tanjila.mahmud@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','student','#0891b2'),
(69,'Anisha Molla','anisha.molla@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','exec','#0d9488'),
(70,'Rumana Mahmud','rumana.mahmud@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','exec','#9333ea'),
(71,'Sadia Siddique','sadia.siddique@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','exec','#0d9488'),
(72,'Jubayer Das','jubayer.das@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','exec','#ea580c'),
(73,'Adnan Yasmin','adnan.yasmin@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','student','#4338ca'),
(74,'Jannatul Noor','jannatul.noor@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','student','#9333ea'),
(75,'Anisha Akhter','anisha.akhter@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','student','#4338ca'),
(76,'Shahriar Roy','shahriar.roy@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','exec','#16a34a'),
(77,'Shakil Uddin','shakil.uddin@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','exec','#dc2626'),
(78,'Farzana Ahmed','farzana.ahmed@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','exec','#0d9488'),
(79,'Ahnaf Akter','ahnaf.akter@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','exec','#9333ea'),
(80,'Kamrul Rahman','kamrul.rahman@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','student','#0d9488'),
(81,'Ishika Kabir','ishika.kabir@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','student','#4f46e5'),
(82,'Nazifa Ahmed','nazifa.ahmed@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','student','#0d9488'),
(83,'Mim Kabir','mim.kabir@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','exec','#b45309'),
(84,'Zara Iqbal','zara.iqbal@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','exec','#dc2626'),
(85,'Kamrul Reza','kamrul.reza@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','exec','#0d9488'),
(86,'Tania Islam','tania.islam2@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','exec','#be185d'),
(87,'Adnan Faruq','adnan.faruq@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','student','#ea580c'),
(88,'Afsana Reza','afsana.reza@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','student','#be185d'),
(89,'Mizan Siddique','mizan.siddique@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','student','#ea580c'),
(90,'Rumana Haque','rumana.haque@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','exec','#4f46e5'),
(91,'Faisal Akter','faisal.akter@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','exec','#ea580c'),
(92,'Shakil Bhuiyan','shakil.bhuiyan@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','exec','#0891b2'),
(93,'Nazifa Sultana','nazifa.sultana@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','exec','#b45309'),
(94,'Sumaiya Alam','sumaiya.alam@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','student','#b45309'),
(95,'Omar Uddin','omar.uddin@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','student','#dc2626'),
(96,'Arif Hasan','arif.hasan@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','student','#9333ea'),
(97,'Shakil Uddin','shakil.uddin2@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','exec','#16a34a'),
(98,'Ayesha Mahmud','ayesha.mahmud@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','exec','#059669'),
(99,'Ridwan Hasan','ridwan.hasan@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','exec','#4338ca'),
(100,'Priya Reza','priya.reza@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','exec','#16a34a'),
(101,'Shahriar Siddique','shahriar.siddique@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','student','#0891b2'),
(102,'Farzana Noor','farzana.noor@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','student','#dc2626'),
(103,'Imran Rahman','imran.rahman@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','student','#16a34a'),
(104,'Shakil Kabir','shakil.kabir@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','exec','#4338ca'),
(105,'Rifat Anwar','rifat.anwar@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','exec','#059669'),
(106,'Anika Kabir','anika.kabir@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','exec','#4338ca'),
(107,'Sabbir Alam','sabbir.alam@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','exec','#0d9488'),
(108,'Sadman Chowdhury','sadman.chowdhury@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','student','#ea580c'),
(109,'Ishrat Akhter','ishrat.akhter@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','student','#059669'),
(110,'Farzana Islam','farzana.islam@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','student','#7c3aed'),
(111,'Rayhan Roy','rayhan.roy@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','exec','#0891b2'),
(112,'Ishika Islam','ishika.islam@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','exec','#ea580c'),
(113,'Ayesha Chowdhury','ayesha.chowdhury@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','exec','#0891b2'),
(114,'Afsana Anwar','afsana.anwar@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','exec','#9333ea'),
(115,'Hasib Reza','hasib.reza@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','student','#b45309'),
(116,'Proma Hossain','proma.hossain@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','student','#0891b2'),
(117,'Rakib Sultana','rakib.sultana@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','student','#b45309'),
(118,'Tamim Iqbal','tamim.iqbal@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','student','#4338ca'),
(119,'Priya Islam','priya.islam@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','exec','#dc2626'),
(120,'Jubayer Anwar','jubayer.anwar@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','exec','#0d9488'),
(121,'Hasib Talukder','hasib.talukder@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','exec','#9333ea'),
(122,'Jannatul Yasmin','jannatul.yasmin@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','exec','#7c3aed'),
(123,'Jubayer Uddin','jubayer.uddin@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','student','#0d9488'),
(124,'Zara Iqbal','zara.iqbal2@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','student','#16a34a'),
(125,'Zawad Uddin','zawad.uddin@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','student','#4f46e5'),
(126,'Rifah Iqbal','rifah.iqbal@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','exec','#b45309'),
(127,'Proma Talukder','proma.talukder@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','exec','#16a34a'),
(128,'Tanjib Yasmin','tanjib.yasmin@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','exec','#b45309'),
(129,'Shirin Sultana','shirin.sultana@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','exec','#9333ea'),
(130,'Rifah Roy','rifah.roy@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','student','#059669'),
(131,'Sadia Akter','sadia.akter@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','student','#ea580c'),
(132,'Farzana Faruq','farzana.faruq@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','student','#4338ca'),
(133,'Anisha Akter','anisha.akter@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','exec','#be185d'),
(134,'Ishika Islam','ishika.islam2@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','exec','#9333ea'),
(135,'Ridika Ahmed','ridika.ahmed@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','exec','#4f46e5'),
(136,'Rahat Sultana','rahat.sultana@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','exec','#16a34a'),
(137,'Tasnim Islam','tasnim.islam@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','student','#b45309'),
(138,'Nazifa Faruq','nazifa.faruq@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','student','#0d9488'),
(139,'Nadia Chowdhury','nadia.chowdhury@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','student','#dc2626'),
(140,'Tanvir Hossain','tanvir.hossain@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','student','#b45309'),
(141,'Ruma Anwar','ruma.anwar@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','exec','#4338ca'),
(142,'Orin Reza','orin.reza@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','exec','#059669'),
(143,'Nabila Islam','nabila.islam@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','exec','#0891b2'),
(144,'Kamrul Haque','kamrul.haque@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','exec','#0d9488'),
(145,'Shuvo Ahmed','shuvo.ahmed@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','student','#b45309'),
(146,'Tania Kabir','tania.kabir@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','student','#be185d'),
(147,'Hasib Talukder','hasib.talukder2@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','student','#4f46e5'),
(148,'Mishu Reza','mishu.reza@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','exec','#0891b2'),
(149,'Zubair Uddin','zubair.uddin@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','exec','#4338ca'),
(150,'Hasib Yasmin','hasib.yasmin2@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','exec','#ea580c'),
(151,'Nusrat Haque','nusrat.haque@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','exec','#059669'),
(152,'Nusrat Alam','nusrat.alam@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','student','#4338ca'),
(153,'Rayhan Bhuiyan','rayhan.bhuiyan@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','student','#7c3aed'),
(154,'Sadia Roy','sadia.roy@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','student','#16a34a'),
(155,'Ishrat Noor','ishrat.noor@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','exec','#b45309'),
(156,'Rahat Siddique','rahat.siddique@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','exec','#dc2626'),
(157,'Shahriar Faruq','shahriar.faruq@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','exec','#0891b2'),
(158,'Ayesha Rahman','ayesha.rahman@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','exec','#059669'),
(159,'Mahiya Islam','mahiya.islam@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','student','#059669'),
(160,'Proma Rahman','proma.rahman@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','student','#9333ea'),
(161,'Mahin Hasan','mahin.hasan@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','student','#4f46e5'),
(162,'Mahin Karim','mahin.karim@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','exec','#0d9488'),
(163,'Priya Alam','priya.alam@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','exec','#059669'),
(164,'Tasfia Faruq','tasfia.faruq@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','exec','#16a34a'),
(165,'Adnan Talukder','adnan.talukder@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','exec','#ea580c'),
(166,'Emon Alam','emon.alam@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','student','#0d9488'),
(167,'Tamim Iqbal','tamim.iqbal2@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','student','#9333ea'),
(168,'Ayesha Reza','ayesha.reza@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','student','#16a34a'),
(169,'Ruma Kabir','ruma.kabir@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','exec','#4f46e5'),
(170,'Turin Islam','turin.islam@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','exec','#059669'),
(171,'Rifah Siddique','rifah.siddique@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','exec','#4338ca'),
(172,'Tanjib Siddique','tanjib.siddique@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','exec','#7c3aed'),
(173,'Shovon Yasmin','shovon.yasmin@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','student','#0d9488'),
(174,'Hasib Ahmed','hasib.ahmed@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','student','#4f46e5'),
(175,'Nabila Anwar','nabila.anwar@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','student','#4338ca'),
(176,'Sabbir Anwar','sabbir.anwar@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','student','#0891b2'),
(177,'Shirin Mahmud','shirin.mahmud@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','exec','#dc2626'),
(178,'Ahnaf Alam','ahnaf.alam@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','exec','#7c3aed'),
(179,'Priya Akter','priya.akter@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','exec','#4338ca'),
(180,'Shakil Akhter','shakil.akhter@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','exec','#059669'),
(181,'Arif Reza','arif.reza@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','student','#4f46e5'),
(182,'Imran Talukder','imran.talukder@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','student','#be185d'),
(183,'Faisal Sultana','faisal.sultana@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','student','#4f46e5'),
(184,'Ishrat Faruq','ishrat.faruq@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','student','#dc2626'),
(185,'Tasnim Sarker','tasnim.sarker@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','exec','#0891b2'),
(186,'Jannatul Ahmed','jannatul.ahmed@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','exec','#9333ea'),
(187,'Tania Akter','tania.akter@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','exec','#0d9488'),
(188,'Ishika Mahmud','ishika.mahmud@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','exec','#dc2626'),
(189,'Fardin Khan','fardin.khan@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','student','#ea580c'),
(190,'Nazifa Karim','nazifa.karim@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','student','#be185d'),
(191,'Jannatul Uddin','jannatul.uddin@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','student','#4f46e5'),
(192,'Ahnaf Karim','ahnaf.karim@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','exec','#9333ea'),
(193,'Mahin Islam','mahin.islam@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','exec','#0d9488'),
(194,'Mishu Yasmin','mishu.yasmin@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','exec','#7c3aed'),
(195,'Afsana Mahmud','afsana.mahmud@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','exec','#4f46e5'),
(196,'Rifah Haque','rifah.haque@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','student','#7c3aed'),
(197,'Nayeem Akhter','nayeem.akhter@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','student','#0891b2'),
(198,'Sadia Molla','sadia.molla@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','student','#be185d'),
(199,'Tanvir Das','tanvir.das@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','student','#dc2626'),
(200,'Zara Haque','zara.haque@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','exec','#7c3aed'),
(201,'Samia Reza','samia.reza@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','exec','#0d9488'),
(202,'Fahim Sultana','fahim.sultana@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','exec','#9333ea'),
(203,'Ishika Hossain','ishika.hossain@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','exec','#be185d'),
(204,'Rahat Reza','rahat.reza@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','student','#b45309'),
(205,'Mehjabin Chowdhury','mehjabin.chowdhury@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','student','#dc2626'),
(206,'Tahsin Hossain','tahsin.hossain@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','student','#16a34a'),
(207,'Jubayer Iqbal','jubayer.iqbal@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','student','#7c3aed'),
(208,'Ridwan Reza','ridwan.reza@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','exec','#4f46e5'),
(209,'Rakib Yasmin','rakib.yasmin@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','exec','#be185d'),
(210,'Sadia Faruq','sadia.faruq@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','exec','#16a34a'),
(211,'Anika Khan','anika.khan@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','exec','#be185d'),
(212,'Nabila Mahmud','nabila.mahmud@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','student','#4f46e5'),
(213,'Jubayer Haque','jubayer.haque@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','student','#059669'),
(214,'Ruma Anwar','ruma.anwar2@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','student','#ea580c'),
(215,'Rayhan Faruq','rayhan.faruq@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','exec','#059669'),
(216,'Jubayer Mahmud','jubayer.mahmud@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','exec','#be185d'),
(217,'Rahat Talukder','rahat.talukder@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','exec','#059669'),
(218,'Tabassum Faruq','tabassum.faruq@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','exec','#9333ea'),
(219,'Tanjila Noor','tanjila.noor@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','student','#b45309'),
(220,'Rifah Uddin','rifah.uddin@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','student','#ea580c'),
(221,'Zubair Reza','zubair.reza@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','student','#4338ca'),
(222,'Rahat Sarker','rahat.sarker@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','student','#dc2626'),
(223,'Sabrina Chowdhury','sabrina.chowdhury@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','exec','#4338ca'),
(224,'Orin Islam','orin.islam@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','exec','#0d9488'),
(225,'Tania Roy','tania.roy@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','exec','#16a34a'),
(226,'Anika Mahmud','anika.mahmud@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','exec','#ea580c'),
(227,'Orin Chowdhury','orin.chowdhury@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','student','#0d9488'),
(228,'Sohan Reza','sohan.reza@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','student','#4f46e5'),
(229,'Mahiya Karim','mahiya.karim@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','student','#be185d'),
(230,'Sumaiya Molla','sumaiya.molla@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','student','#ea580c'),
(231,'Rakib Bhuiyan','rakib.bhuiyan@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','exec','#0891b2'),
(232,'Tanvir Karim','tanvir.karim@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','exec','#0891b2'),
(233,'Priya Kabir','priya.kabir@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','exec','#059669'),
(234,'Orin Iqbal','orin.iqbal@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','exec','#dc2626'),
(235,'Sadman Sultana','sadman.sultana@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','student','#be185d'),
(236,'Ishika Rahman','ishika.rahman@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','student','#4f46e5'),
(237,'Mahiya Akter','mahiya.akter2@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','student','#be185d'),
(238,'Ridika Miah','ridika.miah@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','exec','#b45309'),
(239,'Shovon Reza','shovon.reza@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','exec','#059669'),
(240,'Rifah Mahmud','rifah.mahmud@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','exec','#be185d'),
(241,'Anika Hossain','anika.hossain@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','exec','#0d9488'),
(242,'Imran Karim','imran.karim@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','student','#4f46e5'),
(243,'Faisal Khan','faisal.khan@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','student','#059669'),
(244,'Jubayer Sultana','jubayer.sultana@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','student','#0891b2'),
(245,'Sohan Sarker','sohan.sarker@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','student','#16a34a'),
(246,'Rifah Rahman','rifah.rahman@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','exec','#0d9488'),
(247,'Omar Talukder','omar.talukder@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','exec','#059669'),
(248,'Sadia Mahmud','sadia.mahmud@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','exec','#9333ea'),
(249,'Naeem Reza','naeem.reza@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','exec','#059669'),
(250,'Sajid Iqbal','sajid.iqbal@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','student','#7c3aed'),
(251,'Shahriar Akhter','shahriar.akhter@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','student','#b45309'),
(252,'Zara Siddique','zara.siddique@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','student','#0d9488'),
(253,'Proma Hossain','proma.hossain2@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','exec','#16a34a'),
(254,'Farhan Ahmed','farhan.ahmed@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','exec','#b45309'),
(255,'Fardin Anwar','fardin.anwar@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','exec','#7c3aed'),
(256,'Nusaiba Noor','nusaiba.noor@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','exec','#16a34a'),
(257,'Rezwan Reza','rezwan.reza@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','student','#dc2626'),
(258,'Emon Chowdhury','emon.chowdhury@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','student','#0d9488'),
(259,'Nusrat Uddin','nusrat.uddin@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','student','#0891b2'),
(260,'Imran Rahman','imran.rahman2@bracu.ac.bd','$2y$10$cSBZ4pUTunlCZ605z8IlqeQTsgGqH8OMn3GhzLx/451GCyy.T/n/K','student','#4f46e5');

INSERT INTO student_profiles (user_id, student_id, department, semester, bio, phone, availability) VALUES
(38,'2300253','CSE','6th','Dedicated to growing the club''s presence on campus.','01719130320','Flexible'),
(39,'1900154','CSE','4th','Dedicated to growing the club''s presence on campus.','01717617276','Weekdays after 5pm'),
(40,'2300870','PSS','4th','Brings creativity and energy to every club activity.','01715316358','Flexible'),
(41,'2300407','MNS','6th','Active participant in campus extracurricular life.','01714465815','Weekends'),
(42,'2300295','MNS','6th','Enthusiastic member always ready to help organize events.','01717436191','Weekdays after 5pm'),
(43,'2200426','ECO','8th','Passionate about contributing new ideas to the club.','01713654273','Flexible'),
(44,'2100945','EEE','5th','Enthusiastic member always ready to help organize events.','01719008551','Weekdays after 5pm'),
(45,'2100085','BBA','7th','Brings creativity and energy to every club activity.','01715544296','Flexible'),
(46,'2300199','CSE','4th','Dedicated to growing the club''s presence on campus.','01710186766','Flexible'),
(47,'1900783','BBA','4th','Loves collaborating with the team on new initiatives.','01718480580','Weekends'),
(48,'2300944','CSE','6th','Dedicated to growing the club''s presence on campus.','01710584744','Weekends'),
(49,'2000777','ENH','8th','Brings creativity and energy to every club activity.','01714816663','Flexible'),
(50,'2200706','BBA','6th','Brings creativity and energy to every club activity.','01713741345','Weekends'),
(51,'2100029','ENH','6th','Enthusiastic member always ready to help organize events.','01715120806','Weekdays after 5pm'),
(52,'2200895','MNS','6th','Loves collaborating with the team on new initiatives.','01718107241','Evenings & weekends'),
(53,'2300343','ARC','8th','Active participant in campus extracurricular life.','01711246556','Flexible'),
(54,'2200818','ARC','4th','Dedicated to growing the club''s presence on campus.','01714071369','Evenings & weekends'),
(55,'2100680','CSE','6th','Active participant in campus extracurricular life.','01713819870','Weekdays after 5pm'),
(56,'1900236','PSS','5th','Dedicated to growing the club''s presence on campus.','01718517278','Weekends'),
(57,'2200402','PSS','3rd','Enthusiastic member always ready to help organize events.','01712102545','Flexible'),
(58,'2000505','BBA','4th','Loves collaborating with the team on new initiatives.','01718389606','Flexible'),
(59,'1900048','CSE','3rd','Brings creativity and energy to every club activity.','01714155297','Evenings & weekends'),
(60,'2300469','EEE','4th','Enthusiastic member always ready to help organize events.','01712822526','Flexible'),
(61,'1900376','ENH','5th','Dedicated to growing the club''s presence on campus.','01712115157','Evenings'),
(62,'1900940','CSE','5th','Brings creativity and energy to every club activity.','01712949626','Flexible'),
(63,'2100557','PSS','7th','Loves collaborating with the team on new initiatives.','01716831826','Weekdays after 5pm'),
(64,'2200455','PHR','3rd','Passionate about contributing new ideas to the club.','01718943880','Evenings & weekends'),
(65,'2200156','EEE','8th','Dedicated to growing the club''s presence on campus.','01718033004','Flexible'),
(66,'2100816','PHR','7th','Enthusiastic member always ready to help organize events.','01712835797','Flexible'),
(67,'2200263','CSE','8th','Passionate about contributing new ideas to the club.','01711081980','Flexible'),
(68,'2200462','BBA','8th','Passionate about contributing new ideas to the club.','01712682808','Flexible'),
(69,'1900359','PSS','3rd','Dedicated to growing the club''s presence on campus.','01711500798','Evenings & weekends'),
(70,'1900687','MNS','7th','Passionate about contributing new ideas to the club.','01711715157','Weekdays after 5pm'),
(71,'2100667','PHR','5th','Brings creativity and energy to every club activity.','01717720117','Evenings & weekends'),
(72,'2000319','ENH','6th','Dedicated to growing the club''s presence on campus.','01715607249','Weekends'),
(73,'2100221','EEE','3rd','Loves collaborating with the team on new initiatives.','01719562955','Evenings'),
(74,'2000318','EEE','4th','Brings creativity and energy to every club activity.','01711408513','Evenings & weekends'),
(75,'2200119','ECO','8th','Brings creativity and energy to every club activity.','01717282410','Evenings'),
(76,'2200662','BBA','3rd','Passionate about contributing new ideas to the club.','01715658767','Weekdays after 5pm'),
(77,'1900328','PSS','3rd','Brings creativity and energy to every club activity.','01710664608','Evenings & weekends'),
(78,'2100341','PSS','4th','Active participant in campus extracurricular life.','01712744512','Weekdays after 5pm'),
(79,'1900764','PSS','6th','Dedicated to growing the club''s presence on campus.','01715809935','Weekends'),
(80,'2200647','ENH','6th','Enthusiastic member always ready to help organize events.','01717282644','Flexible'),
(81,'1900931','PHR','8th','Enthusiastic member always ready to help organize events.','01714077007','Weekdays after 5pm'),
(82,'2200521','EEE','3rd','Active participant in campus extracurricular life.','01711884098','Flexible'),
(83,'2300598','ECO','8th','Loves collaborating with the team on new initiatives.','01714576672','Evenings'),
(84,'2000001','ECO','5th','Dedicated to growing the club''s presence on campus.','01715208130','Weekdays after 5pm'),
(85,'2200839','BBA','5th','Loves collaborating with the team on new initiatives.','01713461725','Evenings'),
(86,'1900079','PHR','7th','Dedicated to growing the club''s presence on campus.','01715993349','Evenings'),
(87,'1900089','ECO','8th','Active participant in campus extracurricular life.','01714092470','Weekends'),
(88,'2200033','BBA','6th','Passionate about contributing new ideas to the club.','01716313179','Flexible'),
(89,'1900254','ARC','5th','Brings creativity and energy to every club activity.','01717412711','Weekdays after 5pm'),
(90,'2300388','ARC','5th','Dedicated to growing the club''s presence on campus.','01718875411','Weekends'),
(91,'2100244','PHR','6th','Active participant in campus extracurricular life.','01718063085','Evenings'),
(92,'1900565','MNS','7th','Passionate about contributing new ideas to the club.','01718034969','Weekdays after 5pm'),
(93,'1900052','PSS','8th','Dedicated to growing the club''s presence on campus.','01711853489','Weekdays after 5pm'),
(94,'2100161','PHR','3rd','Enthusiastic member always ready to help organize events.','01713840533','Flexible'),
(95,'2200925','EEE','6th','Loves collaborating with the team on new initiatives.','01716375898','Evenings'),
(96,'2000367','LLB','7th','Passionate about contributing new ideas to the club.','01713652870','Weekends'),
(97,'2200448','ENH','6th','Active participant in campus extracurricular life.','01715121126','Weekends'),
(98,'2000717','BBA','8th','Active participant in campus extracurricular life.','01716669181','Evenings'),
(99,'2000525','ENH','8th','Brings creativity and energy to every club activity.','01711720347','Weekends'),
(100,'2200438','PSS','5th','Brings creativity and energy to every club activity.','01713419563','Evenings & weekends'),
(101,'2100298','BBA','6th','Passionate about contributing new ideas to the club.','01714409053','Weekdays after 5pm'),
(102,'2200280','ARC','8th','Dedicated to growing the club''s presence on campus.','01713828485','Flexible'),
(103,'1900998','EEE','8th','Dedicated to growing the club''s presence on campus.','01712363485','Evenings & weekends'),
(104,'2300966','ARC','3rd','Passionate about contributing new ideas to the club.','01714453936','Weekdays after 5pm'),
(105,'2100462','MNS','6th','Enthusiastic member always ready to help organize events.','01710450077','Flexible'),
(106,'2000428','PSS','7th','Loves collaborating with the team on new initiatives.','01714426543','Flexible'),
(107,'2100198','PSS','3rd','Active participant in campus extracurricular life.','01715184262','Evenings'),
(108,'2200931','ARC','5th','Active participant in campus extracurricular life.','01714568087','Flexible'),
(109,'2300596','PSS','8th','Passionate about contributing new ideas to the club.','01717943412','Flexible'),
(110,'2200902','ECO','5th','Active participant in campus extracurricular life.','01713874541','Weekdays after 5pm'),
(111,'2000415','CSE','6th','Passionate about contributing new ideas to the club.','01715376835','Evenings & weekends'),
(112,'2000175','MNS','6th','Brings creativity and energy to every club activity.','01719975206','Weekends'),
(113,'2200892','CSE','8th','Active participant in campus extracurricular life.','01715793899','Evenings'),
(114,'1900311','LLB','7th','Passionate about contributing new ideas to the club.','01714666717','Evenings'),
(115,'2200353','LLB','4th','Loves collaborating with the team on new initiatives.','01716561302','Flexible'),
(116,'2100016','PSS','4th','Loves collaborating with the team on new initiatives.','01710885720','Evenings & weekends'),
(117,'1900936','BBA','4th','Active participant in campus extracurricular life.','01711657004','Evenings & weekends'),
(118,'1900343','ARC','7th','Active participant in campus extracurricular life.','01716870902','Weekdays after 5pm'),
(119,'1900801','BBA','7th','Passionate about contributing new ideas to the club.','01719236625','Weekdays after 5pm'),
(120,'2300085','PHR','3rd','Dedicated to growing the club''s presence on campus.','01719998751','Flexible'),
(121,'2100136','LLB','3rd','Enthusiastic member always ready to help organize events.','01719594278','Flexible'),
(122,'2000818','MNS','5th','Active participant in campus extracurricular life.','01718505575','Evenings & weekends'),
(123,'2100122','EEE','8th','Enthusiastic member always ready to help organize events.','01713297530','Evenings'),
(124,'2000089','EEE','8th','Loves collaborating with the team on new initiatives.','01712164924','Evenings & weekends'),
(125,'2300046','CSE','6th','Dedicated to growing the club''s presence on campus.','01711821448','Weekends'),
(126,'2200501','CSE','7th','Dedicated to growing the club''s presence on campus.','01711638316','Evenings & weekends'),
(127,'2300986','PHR','6th','Brings creativity and energy to every club activity.','01712451317','Flexible'),
(128,'2200256','MNS','5th','Brings creativity and energy to every club activity.','01717571148','Evenings & weekends'),
(129,'2000056','ENH','7th','Dedicated to growing the club''s presence on campus.','01718922868','Evenings & weekends'),
(130,'1900177','EEE','7th','Brings creativity and energy to every club activity.','01716192888','Weekdays after 5pm'),
(131,'2000619','BBA','4th','Active participant in campus extracurricular life.','01717429689','Evenings & weekends'),
(132,'2300610','ENH','5th','Enthusiastic member always ready to help organize events.','01710674791','Weekends'),
(133,'2000652','LLB','7th','Active participant in campus extracurricular life.','01717610201','Weekdays after 5pm'),
(134,'2200491','PSS','8th','Dedicated to growing the club''s presence on campus.','01713282847','Weekdays after 5pm'),
(135,'1900664','PHR','6th','Enthusiastic member always ready to help organize events.','01719886727','Evenings'),
(136,'1900229','BBA','5th','Loves collaborating with the team on new initiatives.','01710771401','Weekends'),
(137,'2000132','ECO','5th','Passionate about contributing new ideas to the club.','01710128527','Evenings & weekends'),
(138,'1900968','LLB','7th','Dedicated to growing the club''s presence on campus.','01712384319','Weekends'),
(139,'2200975','PSS','7th','Loves collaborating with the team on new initiatives.','01711941748','Flexible'),
(140,'1900310','MNS','7th','Enthusiastic member always ready to help organize events.','01713690924','Evenings'),
(141,'2100327','LLB','8th','Brings creativity and energy to every club activity.','01717011557','Weekdays after 5pm'),
(142,'2000107','ECO','6th','Enthusiastic member always ready to help organize events.','01716565702','Weekends'),
(143,'1900068','ARC','3rd','Dedicated to growing the club''s presence on campus.','01714160597','Weekends'),
(144,'2100199','ARC','4th','Loves collaborating with the team on new initiatives.','01710043984','Flexible'),
(145,'2100649','PSS','6th','Active participant in campus extracurricular life.','01711968704','Weekends'),
(146,'2300929','ECO','8th','Loves collaborating with the team on new initiatives.','01716230571','Evenings'),
(147,'1900830','PHR','5th','Dedicated to growing the club''s presence on campus.','01712178028','Evenings'),
(148,'2000723','BBA','6th','Enthusiastic member always ready to help organize events.','01717047455','Weekdays after 5pm'),
(149,'2200929','LLB','3rd','Passionate about contributing new ideas to the club.','01717544481','Weekdays after 5pm'),
(150,'2100441','MNS','6th','Passionate about contributing new ideas to the club.','01718787860','Evenings'),
(151,'2200482','EEE','8th','Enthusiastic member always ready to help organize events.','01718464974','Evenings & weekends'),
(152,'2300086','LLB','4th','Dedicated to growing the club''s presence on campus.','01712016321','Weekends'),
(153,'2000542','ARC','7th','Passionate about contributing new ideas to the club.','01710646624','Weekends'),
(154,'2200759','LLB','5th','Passionate about contributing new ideas to the club.','01715988981','Weekends'),
(155,'2300940','LLB','5th','Dedicated to growing the club''s presence on campus.','01719442711','Weekends'),
(156,'2000363','ECO','7th','Enthusiastic member always ready to help organize events.','01719655856','Weekends'),
(157,'2200569','LLB','8th','Passionate about contributing new ideas to the club.','01716254734','Weekends'),
(158,'1900018','ENH','7th','Passionate about contributing new ideas to the club.','01718500191','Flexible'),
(159,'2100142','ENH','3rd','Loves collaborating with the team on new initiatives.','01712125275','Evenings'),
(160,'1900866','ENH','3rd','Passionate about contributing new ideas to the club.','01710834859','Evenings'),
(161,'1900974','BBA','4th','Loves collaborating with the team on new initiatives.','01718743317','Evenings'),
(162,'2200235','MNS','8th','Enthusiastic member always ready to help organize events.','01716564563','Weekdays after 5pm'),
(163,'2200336','ENH','5th','Active participant in campus extracurricular life.','01711165710','Evenings'),
(164,'2300635','PSS','7th','Enthusiastic member always ready to help organize events.','01716121203','Weekends'),
(165,'2300044','ECO','7th','Enthusiastic member always ready to help organize events.','01714410925','Evenings'),
(166,'2100952','ARC','8th','Enthusiastic member always ready to help organize events.','01718209995','Flexible'),
(167,'2100183','ENH','3rd','Brings creativity and energy to every club activity.','01715015681','Weekdays after 5pm'),
(168,'2100874','ARC','5th','Dedicated to growing the club''s presence on campus.','01717961024','Flexible'),
(169,'2000133','PHR','5th','Active participant in campus extracurricular life.','01714748293','Weekdays after 5pm'),
(170,'2000919','CSE','8th','Active participant in campus extracurricular life.','01711882786','Flexible'),
(171,'2100779','BBA','7th','Brings creativity and energy to every club activity.','01715528833','Flexible'),
(172,'2100886','PSS','4th','Passionate about contributing new ideas to the club.','01711837012','Weekdays after 5pm'),
(173,'2000731','ARC','4th','Enthusiastic member always ready to help organize events.','01714181428','Evenings & weekends'),
(174,'2000793','LLB','3rd','Loves collaborating with the team on new initiatives.','01716817051','Evenings'),
(175,'2200782','ARC','3rd','Dedicated to growing the club''s presence on campus.','01713671079','Weekdays after 5pm'),
(176,'2000473','PSS','7th','Enthusiastic member always ready to help organize events.','01717729402','Weekends'),
(177,'1900512','ARC','6th','Enthusiastic member always ready to help organize events.','01718505744','Weekends'),
(178,'2100483','BBA','6th','Enthusiastic member always ready to help organize events.','01718118138','Weekdays after 5pm'),
(179,'2000055','ENH','3rd','Enthusiastic member always ready to help organize events.','01716139688','Weekdays after 5pm'),
(180,'2200662','PSS','5th','Enthusiastic member always ready to help organize events.','01714115947','Weekdays after 5pm'),
(181,'2000808','ECO','4th','Enthusiastic member always ready to help organize events.','01715188205','Flexible'),
(182,'2300317','MNS','6th','Active participant in campus extracurricular life.','01715354369','Evenings'),
(183,'2100622','BBA','4th','Loves collaborating with the team on new initiatives.','01710122780','Evenings'),
(184,'2200690','BBA','5th','Active participant in campus extracurricular life.','01719445655','Evenings'),
(185,'2100027','ECO','7th','Brings creativity and energy to every club activity.','01712958321','Evenings & weekends'),
(186,'2200461','PSS','4th','Loves collaborating with the team on new initiatives.','01719838137','Evenings & weekends'),
(187,'2300363','ENH','3rd','Active participant in campus extracurricular life.','01715565945','Weekdays after 5pm'),
(188,'1900734','BBA','6th','Enthusiastic member always ready to help organize events.','01715963748','Flexible'),
(189,'2100443','PSS','8th','Brings creativity and energy to every club activity.','01715493402','Evenings & weekends'),
(190,'1900159','CSE','7th','Passionate about contributing new ideas to the club.','01718639899','Weekends'),
(191,'2000799','LLB','8th','Active participant in campus extracurricular life.','01716897742','Evenings'),
(192,'2300690','EEE','5th','Passionate about contributing new ideas to the club.','01717578900','Weekends'),
(193,'1900892','ENH','3rd','Enthusiastic member always ready to help organize events.','01711195524','Weekends'),
(194,'2000737','EEE','5th','Passionate about contributing new ideas to the club.','01715971525','Weekends'),
(195,'2300669','ECO','7th','Brings creativity and energy to every club activity.','01712707596','Weekdays after 5pm'),
(196,'2300533','ECO','5th','Dedicated to growing the club''s presence on campus.','01713635176','Evenings & weekends'),
(197,'2200131','EEE','7th','Active participant in campus extracurricular life.','01712362300','Evenings & weekends'),
(198,'2000367','ECO','7th','Enthusiastic member always ready to help organize events.','01716735571','Evenings'),
(199,'2300858','ARC','7th','Loves collaborating with the team on new initiatives.','01712449000','Flexible'),
(200,'2300430','ARC','3rd','Dedicated to growing the club''s presence on campus.','01719179854','Evenings & weekends'),
(201,'2200763','LLB','5th','Loves collaborating with the team on new initiatives.','01716864011','Weekdays after 5pm'),
(202,'2300011','BBA','8th','Enthusiastic member always ready to help organize events.','01718256261','Evenings & weekends'),
(203,'2200293','LLB','5th','Enthusiastic member always ready to help organize events.','01714564182','Flexible'),
(204,'2300335','BBA','8th','Active participant in campus extracurricular life.','01715417372','Evenings & weekends'),
(205,'2300752','EEE','8th','Active participant in campus extracurricular life.','01719475685','Weekdays after 5pm'),
(206,'2000691','LLB','5th','Active participant in campus extracurricular life.','01716636133','Evenings'),
(207,'2200743','CSE','6th','Loves collaborating with the team on new initiatives.','01716016515','Flexible'),
(208,'2000774','LLB','4th','Enthusiastic member always ready to help organize events.','01710450529','Weekdays after 5pm'),
(209,'2000079','CSE','6th','Enthusiastic member always ready to help organize events.','01712747954','Evenings'),
(210,'2200781','BBA','7th','Active participant in campus extracurricular life.','01715498677','Weekdays after 5pm'),
(211,'2300402','BBA','6th','Passionate about contributing new ideas to the club.','01712941945','Weekends'),
(212,'1900158','MNS','6th','Loves collaborating with the team on new initiatives.','01711918207','Evenings & weekends'),
(213,'2100110','EEE','6th','Brings creativity and energy to every club activity.','01718215418','Evenings'),
(214,'2200756','PHR','6th','Active participant in campus extracurricular life.','01713618795','Evenings'),
(215,'2200043','PSS','8th','Enthusiastic member always ready to help organize events.','01719328792','Weekdays after 5pm'),
(216,'2200157','PSS','7th','Dedicated to growing the club''s presence on campus.','01710496391','Weekdays after 5pm'),
(217,'2000136','BBA','8th','Dedicated to growing the club''s presence on campus.','01716789392','Evenings & weekends'),
(218,'2100366','PHR','5th','Passionate about contributing new ideas to the club.','01710569491','Weekdays after 5pm'),
(219,'2200621','PSS','8th','Dedicated to growing the club''s presence on campus.','01713688014','Flexible'),
(220,'1900340','MNS','3rd','Active participant in campus extracurricular life.','01717232719','Evenings'),
(221,'2000844','ARC','6th','Loves collaborating with the team on new initiatives.','01712413294','Weekdays after 5pm'),
(222,'2000740','ARC','8th','Dedicated to growing the club''s presence on campus.','01714413828','Evenings'),
(223,'2200535','PHR','8th','Brings creativity and energy to every club activity.','01714484492','Weekends'),
(224,'2200819','MNS','7th','Passionate about contributing new ideas to the club.','01719929588','Evenings & weekends'),
(225,'2000921','PSS','3rd','Brings creativity and energy to every club activity.','01715006588','Evenings'),
(226,'1900363','MNS','3rd','Active participant in campus extracurricular life.','01716101480','Weekdays after 5pm'),
(227,'2000462','CSE','7th','Loves collaborating with the team on new initiatives.','01715002256','Flexible'),
(228,'2300357','PSS','7th','Dedicated to growing the club''s presence on campus.','01717368586','Flexible'),
(229,'1900094','MNS','4th','Passionate about contributing new ideas to the club.','01717741252','Weekends'),
(230,'1900674','ECO','7th','Dedicated to growing the club''s presence on campus.','01717991471','Flexible'),
(231,'2300712','ECO','7th','Enthusiastic member always ready to help organize events.','01717685145','Weekdays after 5pm'),
(232,'2000350','EEE','6th','Loves collaborating with the team on new initiatives.','01718845288','Weekdays after 5pm'),
(233,'2100268','ARC','4th','Dedicated to growing the club''s presence on campus.','01716460197','Flexible'),
(234,'1900342','ARC','3rd','Enthusiastic member always ready to help organize events.','01712161203','Weekends'),
(235,'2200831','BBA','6th','Dedicated to growing the club''s presence on campus.','01714849300','Weekends'),
(236,'1900475','EEE','3rd','Loves collaborating with the team on new initiatives.','01718151539','Evenings & weekends'),
(237,'2300509','ENH','3rd','Enthusiastic member always ready to help organize events.','01717323035','Flexible'),
(238,'2300733','ENH','4th','Loves collaborating with the team on new initiatives.','01719387624','Evenings & weekends'),
(239,'2100601','ENH','8th','Brings creativity and energy to every club activity.','01712812511','Weekends'),
(240,'1900287','PSS','4th','Active participant in campus extracurricular life.','01719874746','Evenings & weekends'),
(241,'2200288','ENH','3rd','Active participant in campus extracurricular life.','01710671689','Flexible'),
(242,'2200529','ENH','8th','Loves collaborating with the team on new initiatives.','01710422458','Flexible'),
(243,'2000965','MNS','5th','Passionate about contributing new ideas to the club.','01717549895','Evenings'),
(244,'2100801','BBA','8th','Loves collaborating with the team on new initiatives.','01716725545','Flexible'),
(245,'2300603','MNS','3rd','Loves collaborating with the team on new initiatives.','01711058851','Weekends'),
(246,'2100247','LLB','3rd','Loves collaborating with the team on new initiatives.','01713143386','Evenings & weekends'),
(247,'2000833','PSS','4th','Loves collaborating with the team on new initiatives.','01718607445','Weekends'),
(248,'2100687','MNS','3rd','Enthusiastic member always ready to help organize events.','01719673606','Weekdays after 5pm'),
(249,'2300622','ECO','5th','Dedicated to growing the club''s presence on campus.','01715773939','Weekdays after 5pm'),
(250,'2100730','MNS','8th','Dedicated to growing the club''s presence on campus.','01719178012','Evenings'),
(251,'2300216','LLB','8th','Active participant in campus extracurricular life.','01714433688','Evenings & weekends'),
(252,'1900100','CSE','8th','Passionate about contributing new ideas to the club.','01715460968','Flexible'),
(253,'2200628','PHR','3rd','Dedicated to growing the club''s presence on campus.','01713898561','Evenings'),
(254,'2100469','PSS','4th','Dedicated to growing the club''s presence on campus.','01718795722','Weekdays after 5pm'),
(255,'2200070','PHR','4th','Brings creativity and energy to every club activity.','01717429127','Evenings'),
(256,'2200059','ECO','5th','Dedicated to growing the club''s presence on campus.','01714808834','Weekdays after 5pm'),
(257,'2100588','ECO','8th','Passionate about contributing new ideas to the club.','01712889282','Weekends'),
(258,'2300052','CSE','6th','Brings creativity and energy to every club activity.','01716803015','Flexible'),
(259,'1900489','PHR','4th','Brings creativity and energy to every club activity.','01711347187','Flexible'),
(260,'1900896','ENH','7th','Passionate about contributing new ideas to the club.','01716974062','Evenings');

INSERT INTO student_skills (user_id, skill_id) VALUES
(38,3),
(38,9),
(39,3),
(39,9),
(40,12),
(40,2),
(41,9),
(41,2),
(42,9),
(42,12),
(43,2),
(43,12),
(44,9),
(44,2),
(45,2),
(45,12),
(46,12),
(46,2),
(47,20),
(47,1),
(48,2),
(48,20),
(49,20),
(49,2),
(50,2),
(50,20),
(51,2),
(51,1),
(52,1),
(52,2),
(53,20),
(53,1),
(54,20),
(54,2),
(55,20),
(55,11),
(56,13),
(56,20),
(57,13),
(57,20),
(58,13),
(58,20),
(59,11),
(59,13),
(60,11),
(60,13),
(61,11),
(61,13),
(62,14),
(62,12),
(63,1),
(63,14),
(64,14),
(64,1),
(65,12),
(65,1),
(66,14),
(66,1),
(67,12),
(67,14),
(68,1),
(68,12),
(69,16),
(69,15),
(70,15),
(70,20),
(71,15),
(71,16),
(72,15),
(72,20),
(73,16),
(73,15),
(74,15),
(74,20),
(75,20),
(75,15),
(76,10),
(76,9),
(77,7),
(77,9),
(78,7),
(78,9),
(79,10),
(79,7),
(80,7),
(80,9),
(81,9),
(81,7),
(82,10),
(82,7),
(83,1),
(83,20),
(84,20),
(84,16),
(85,20),
(85,1),
(86,16),
(86,1),
(87,16),
(87,20),
(88,1),
(88,16),
(89,1),
(89,16),
(90,16),
(90,5),
(91,16),
(91,4),
(92,5),
(92,16),
(93,16),
(93,5),
(94,16),
(94,4),
(95,16),
(95,4),
(96,16),
(96,4),
(97,9),
(97,6),
(98,14),
(98,9),
(99,6),
(99,14),
(100,14),
(100,9),
(101,6),
(101,14),
(102,9),
(102,6),
(103,14),
(103,6),
(104,2),
(104,4),
(105,2),
(105,9),
(106,9),
(106,4),
(107,2),
(107,4),
(108,4),
(108,2),
(109,9),
(109,4),
(110,4),
(110,2),
(111,5),
(111,16),
(112,10),
(112,5),
(113,16),
(113,5),
(114,10),
(114,5),
(115,10),
(115,16),
(116,16),
(116,10),
(117,10),
(117,16),
(118,10),
(118,5),
(119,10),
(119,9),
(120,9),
(120,4),
(121,4),
(121,10),
(122,10),
(122,9),
(123,9),
(123,4),
(124,9),
(124,10),
(125,4),
(125,9),
(126,7),
(126,4),
(127,17),
(127,7),
(128,17),
(128,7),
(129,7),
(129,4),
(130,17),
(130,7),
(131,7),
(131,4),
(132,7),
(132,17),
(133,20),
(133,8),
(134,8),
(134,20),
(135,18),
(135,20),
(136,20),
(136,8),
(137,20),
(137,8),
(138,20),
(138,8),
(139,8),
(139,18),
(140,20),
(140,18),
(141,16),
(141,17),
(142,17),
(142,16),
(143,17),
(143,13),
(144,13),
(144,16),
(145,13),
(145,16),
(146,16),
(146,13),
(147,16),
(147,13),
(148,9),
(148,13),
(149,13),
(149,9),
(150,11),
(150,13),
(151,9),
(151,13),
(152,11),
(152,13),
(153,9),
(153,11),
(154,11),
(154,13),
(155,2),
(155,11),
(156,11),
(156,2),
(157,2),
(157,11),
(158,2),
(158,19),
(159,11),
(159,2),
(160,19),
(160,2),
(161,11),
(161,2),
(162,11),
(162,1),
(163,1),
(163,11),
(164,11),
(164,1),
(165,11),
(165,12),
(166,12),
(166,11),
(167,1),
(167,11),
(168,11),
(168,12),
(169,18),
(169,3),
(170,15),
(170,18),
(171,15),
(171,18),
(172,15),
(172,18),
(173,3),
(173,15),
(174,15),
(174,18),
(175,3),
(175,18),
(176,18),
(176,3),
(177,2),
(177,6),
(178,6),
(178,11),
(179,2),
(179,11),
(180,2),
(180,11),
(181,11),
(181,2),
(182,11),
(182,2),
(183,2),
(183,6),
(184,11),
(184,2),
(185,16),
(185,6),
(186,16),
(186,14),
(187,14),
(187,16),
(188,14),
(188,6),
(189,14),
(189,6),
(190,14),
(190,16),
(191,16),
(191,14),
(192,4),
(192,3),
(193,4),
(193,17),
(194,17),
(194,4),
(195,4),
(195,17),
(196,17),
(196,4),
(197,3),
(197,17),
(198,17),
(198,4),
(199,4),
(199,17),
(200,5),
(200,18),
(201,5),
(201,18),
(202,3),
(202,18),
(203,3),
(203,18),
(204,5),
(204,18),
(205,18),
(205,3),
(206,18),
(206,3),
(207,18),
(207,5),
(208,20),
(208,11),
(209,3),
(209,20),
(210,20),
(210,11),
(211,3),
(211,20),
(212,11),
(212,20),
(213,11),
(213,3),
(214,11),
(214,3),
(215,3),
(215,11),
(216,3),
(216,17),
(217,17),
(217,11),
(218,11),
(218,3),
(219,3),
(219,17),
(220,11),
(220,3),
(221,3),
(221,17),
(222,3),
(222,11),
(223,1),
(223,13),
(224,14),
(224,1),
(225,13),
(225,14),
(226,1),
(226,14),
(227,13),
(227,14),
(228,13),
(228,14),
(229,13),
(229,14),
(230,14),
(230,13),
(231,15),
(231,20),
(232,18),
(232,15),
(233,18),
(233,20),
(234,20),
(234,15),
(235,15),
(235,20),
(236,15),
(236,20),
(237,18),
(237,15),
(238,20),
(238,16),
(239,16),
(239,20),
(240,20),
(240,16),
(241,20),
(241,16),
(242,16),
(242,20),
(243,20),
(243,15),
(244,16),
(244,20),
(245,20),
(245,16),
(246,2),
(246,15),
(247,15),
(247,2),
(248,15),
(248,16),
(249,16),
(249,15),
(250,2),
(250,16),
(251,15),
(251,16),
(252,15),
(252,16),
(253,9),
(253,5),
(254,7),
(254,5),
(255,9),
(255,7),
(256,7),
(256,9),
(257,9),
(257,5),
(258,7),
(258,9),
(259,7),
(259,5),
(260,9),
(260,5);

INSERT INTO student_interests (user_id, interest_id) VALUES
(38,16),
(39,16),
(40,10),
(41,17),
(42,10),
(43,17),
(44,17),
(45,10),
(46,10),
(47,15),
(48,6),
(49,15),
(50,6),
(51,15),
(52,15),
(53,6),
(54,6),
(55,13),
(56,13),
(57,13),
(58,12),
(59,12),
(60,13),
(61,12),
(62,2),
(63,18),
(64,18),
(65,18),
(66,18),
(67,18),
(68,18),
(69,2),
(70,7),
(71,2),
(72,7),
(73,7),
(74,7),
(75,7),
(76,19),
(77,18),
(78,19),
(79,19),
(80,19),
(81,19),
(82,18),
(83,10),
(84,10),
(85,19),
(86,10),
(87,19),
(88,19),
(89,10),
(90,5),
(91,5),
(92,5),
(93,5),
(94,1),
(95,1),
(96,5),
(97,18),
(98,5),
(99,5),
(100,18),
(101,18),
(102,18),
(103,5),
(104,4),
(105,14),
(106,4),
(107,14),
(108,14),
(109,4),
(110,14),
(111,6),
(112,6),
(113,6),
(114,5),
(115,6),
(116,5),
(117,6),
(118,6),
(119,1),
(120,19),
(121,1),
(122,1),
(123,1),
(124,19),
(125,19),
(126,12),
(127,3),
(128,12),
(129,12),
(130,3),
(131,12),
(132,3),
(133,8),
(134,2),
(135,8),
(136,2),
(137,8),
(138,8),
(139,8),
(140,8),
(141,12),
(142,12),
(143,12),
(144,19),
(145,19),
(146,12),
(147,19),
(148,3),
(149,1),
(150,3),
(151,1),
(152,3),
(153,1),
(154,3),
(155,13),
(156,13),
(157,13),
(158,13),
(159,13),
(160,13),
(161,13),
(162,4),
(163,4),
(164,14),
(165,14),
(166,4),
(167,14),
(168,14),
(169,11),
(170,7),
(171,7),
(172,7),
(173,11),
(174,11),
(175,11),
(176,7),
(177,18),
(178,10),
(179,18),
(180,10),
(181,18),
(182,10),
(183,18),
(184,18),
(185,8),
(186,3),
(187,3),
(188,3),
(189,8),
(190,8),
(191,8),
(192,3),
(193,4),
(194,3),
(195,3),
(196,4),
(197,3),
(198,4),
(199,3),
(200,2),
(201,2),
(202,9),
(203,9),
(204,9),
(205,2),
(206,2),
(207,2),
(208,8),
(209,2),
(210,8),
(211,2),
(212,2),
(213,2),
(214,2),
(215,13),
(216,19),
(217,13),
(218,19),
(219,13),
(220,19),
(221,19),
(222,13),
(223,10),
(224,1),
(225,1),
(226,10),
(227,1),
(228,10),
(229,1),
(230,1),
(231,5),
(232,5),
(233,5),
(234,4),
(235,5),
(236,5),
(237,4),
(238,17),
(239,17),
(240,16),
(241,16),
(242,17),
(243,16),
(244,17),
(245,17),
(246,1),
(247,9),
(248,1),
(249,1),
(250,1),
(251,9),
(252,1),
(253,12),
(254,13),
(255,12),
(256,12),
(257,12),
(258,12),
(259,13),
(260,13);

INSERT INTO club_members (club_id, user_id, position, member_role, status) VALUES
(1,38,'Vice President','exec','active'),
(1,39,'Treasurer','exec','active'),
(9,40,'President','president','active'),
(9,41,'Vice President','exec','active'),
(9,42,'General Secretary','exec','active'),
(9,43,'Treasurer','exec','active'),
(9,44,'General Member','member','active'),
(9,45,'General Member','member','active'),
(9,46,'General Member','member','active'),
(10,47,'President','president','active'),
(10,48,'Vice President','exec','active'),
(10,49,'General Secretary','exec','active'),
(10,50,'Treasurer','exec','active'),
(10,51,'General Member','member','active'),
(10,52,'General Member','member','active'),
(10,53,'General Member','member','active'),
(10,54,'General Member','member','active'),
(11,55,'President','president','active'),
(11,56,'Vice President','exec','active'),
(11,57,'General Secretary','exec','active'),
(11,58,'Treasurer','exec','active'),
(11,59,'General Member','member','active'),
(11,60,'General Member','member','active'),
(11,61,'General Member','member','active'),
(12,62,'President','president','active'),
(12,63,'Vice President','exec','active'),
(12,64,'General Secretary','exec','active'),
(12,65,'Treasurer','exec','active'),
(12,66,'General Member','member','active'),
(12,67,'General Member','member','active'),
(12,68,'General Member','member','active'),
(13,69,'President','president','active'),
(13,70,'Vice President','exec','active'),
(13,71,'General Secretary','exec','active'),
(13,72,'Treasurer','exec','active'),
(13,73,'General Member','member','active'),
(13,74,'General Member','member','active'),
(13,75,'General Member','member','active'),
(14,76,'President','president','active'),
(14,77,'Vice President','exec','active'),
(14,78,'General Secretary','exec','active'),
(14,79,'Treasurer','exec','active'),
(14,80,'General Member','member','active'),
(14,81,'General Member','member','active'),
(14,82,'General Member','member','active'),
(15,83,'President','president','active'),
(15,84,'Vice President','exec','active'),
(15,85,'General Secretary','exec','active'),
(15,86,'Treasurer','exec','active'),
(15,87,'General Member','member','active'),
(15,88,'General Member','member','active'),
(15,89,'General Member','member','active'),
(16,90,'President','president','active'),
(16,91,'Vice President','exec','active'),
(16,92,'General Secretary','exec','active'),
(16,93,'Treasurer','exec','active'),
(16,94,'General Member','member','active'),
(16,95,'General Member','member','active'),
(16,96,'General Member','member','active'),
(17,97,'President','president','active'),
(17,98,'Vice President','exec','active'),
(17,99,'General Secretary','exec','active'),
(17,100,'Treasurer','exec','active'),
(17,101,'General Member','member','active'),
(17,102,'General Member','member','active'),
(17,103,'General Member','member','active'),
(18,104,'President','president','active'),
(18,105,'Vice President','exec','active'),
(18,106,'General Secretary','exec','active'),
(18,107,'Treasurer','exec','active'),
(18,108,'General Member','member','active'),
(18,109,'General Member','member','active'),
(18,110,'General Member','member','active'),
(19,111,'President','president','active'),
(19,112,'Vice President','exec','active'),
(19,113,'General Secretary','exec','active'),
(19,114,'Treasurer','exec','active'),
(19,115,'General Member','member','active'),
(19,116,'General Member','member','active'),
(19,117,'General Member','member','active'),
(19,118,'General Member','member','active'),
(20,119,'President','president','active'),
(20,120,'Vice President','exec','active'),
(20,121,'General Secretary','exec','active'),
(20,122,'Treasurer','exec','active'),
(20,123,'General Member','member','active'),
(20,124,'General Member','member','active'),
(20,125,'General Member','member','active'),
(21,126,'President','president','active'),
(21,127,'Vice President','exec','active'),
(21,128,'General Secretary','exec','active'),
(21,129,'Treasurer','exec','active'),
(21,130,'General Member','member','active'),
(21,131,'General Member','member','active'),
(21,132,'General Member','member','active'),
(22,133,'President','president','active'),
(22,134,'Vice President','exec','active'),
(22,135,'General Secretary','exec','active'),
(22,136,'Treasurer','exec','active'),
(22,137,'General Member','member','active'),
(22,138,'General Member','member','active'),
(22,139,'General Member','member','active'),
(22,140,'General Member','member','active'),
(23,141,'President','president','active'),
(23,142,'Vice President','exec','active'),
(23,143,'General Secretary','exec','active'),
(23,144,'Treasurer','exec','active'),
(23,145,'General Member','member','active'),
(23,146,'General Member','member','active'),
(23,147,'General Member','member','active'),
(24,148,'President','president','active'),
(24,149,'Vice President','exec','active'),
(24,150,'General Secretary','exec','active'),
(24,151,'Treasurer','exec','active'),
(24,152,'General Member','member','active'),
(24,153,'General Member','member','active'),
(24,154,'General Member','member','active'),
(25,155,'President','president','active'),
(25,156,'Vice President','exec','active'),
(25,157,'General Secretary','exec','active'),
(25,158,'Treasurer','exec','active'),
(25,159,'General Member','member','active'),
(25,160,'General Member','member','active'),
(25,161,'General Member','member','active'),
(26,162,'President','president','active'),
(26,163,'Vice President','exec','active'),
(26,164,'General Secretary','exec','active'),
(26,165,'Treasurer','exec','active'),
(26,166,'General Member','member','active'),
(26,167,'General Member','member','active'),
(26,168,'General Member','member','active'),
(27,169,'President','president','active'),
(27,170,'Vice President','exec','active'),
(27,171,'General Secretary','exec','active'),
(27,172,'Treasurer','exec','active'),
(27,173,'General Member','member','active'),
(27,174,'General Member','member','active'),
(27,175,'General Member','member','active'),
(27,176,'General Member','member','active'),
(28,177,'President','president','active'),
(28,178,'Vice President','exec','active'),
(28,179,'General Secretary','exec','active'),
(28,180,'Treasurer','exec','active'),
(28,181,'General Member','member','active'),
(28,182,'General Member','member','active'),
(28,183,'General Member','member','active'),
(28,184,'General Member','member','active'),
(29,185,'President','president','active'),
(29,186,'Vice President','exec','active'),
(29,187,'General Secretary','exec','active'),
(29,188,'Treasurer','exec','active'),
(29,189,'General Member','member','active'),
(29,190,'General Member','member','active'),
(29,191,'General Member','member','active'),
(30,192,'President','president','active'),
(30,193,'Vice President','exec','active'),
(30,194,'General Secretary','exec','active'),
(30,195,'Treasurer','exec','active'),
(30,196,'General Member','member','active'),
(30,197,'General Member','member','active'),
(30,198,'General Member','member','active'),
(30,199,'General Member','member','active'),
(31,200,'President','president','active'),
(31,201,'Vice President','exec','active'),
(31,202,'General Secretary','exec','active'),
(31,203,'Treasurer','exec','active'),
(31,204,'General Member','member','active'),
(31,205,'General Member','member','active'),
(31,206,'General Member','member','active'),
(31,207,'General Member','member','active'),
(32,208,'President','president','active'),
(32,209,'Vice President','exec','active'),
(32,210,'General Secretary','exec','active'),
(32,211,'Treasurer','exec','active'),
(32,212,'General Member','member','active'),
(32,213,'General Member','member','active'),
(32,214,'General Member','member','active'),
(33,215,'President','president','active'),
(33,216,'Vice President','exec','active'),
(33,217,'General Secretary','exec','active'),
(33,218,'Treasurer','exec','active'),
(33,219,'General Member','member','active'),
(33,220,'General Member','member','active'),
(33,221,'General Member','member','active'),
(33,222,'General Member','member','active'),
(34,223,'President','president','active'),
(34,224,'Vice President','exec','active'),
(34,225,'General Secretary','exec','active'),
(34,226,'Treasurer','exec','active'),
(34,227,'General Member','member','active'),
(34,228,'General Member','member','active'),
(34,229,'General Member','member','active'),
(34,230,'General Member','member','active'),
(35,231,'President','president','active'),
(35,232,'Vice President','exec','active'),
(35,233,'General Secretary','exec','active'),
(35,234,'Treasurer','exec','active'),
(35,235,'General Member','member','active'),
(35,236,'General Member','member','active'),
(35,237,'General Member','member','active'),
(36,238,'President','president','active'),
(36,239,'Vice President','exec','active'),
(36,240,'General Secretary','exec','active'),
(36,241,'Treasurer','exec','active'),
(36,242,'General Member','member','active'),
(36,243,'General Member','member','active'),
(36,244,'General Member','member','active'),
(36,245,'General Member','member','active'),
(37,246,'President','president','active'),
(37,247,'Vice President','exec','active'),
(37,248,'General Secretary','exec','active'),
(37,249,'Treasurer','exec','active'),
(37,250,'General Member','member','active'),
(37,251,'General Member','member','active'),
(37,252,'General Member','member','active'),
(38,253,'President','president','active'),
(38,254,'Vice President','exec','active'),
(38,255,'General Secretary','exec','active'),
(38,256,'Treasurer','exec','active'),
(38,257,'General Member','member','active'),
(38,258,'General Member','member','active'),
(38,259,'General Member','member','active'),
(38,260,'General Member','member','active');
