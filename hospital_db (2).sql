
DROP DATABASE IF EXISTS hospital_db;
CREATE DATABASE hospital_db DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE hospital_db;
CREATE TABLE users (
  id INT(11) NOT NULL AUTO_INCREMENT,
  username VARCHAR(50) NOT NULL UNIQUE,
  email VARCHAR(100) DEFAULT NULL,
  password VARCHAR(255) NOT NULL,
  role ENUM('patient', 'doctor', 'admin') NOT NULL,
  specialty VARCHAR(100) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE doctor_availability (
  id INT(11) NOT NULL AUTO_INCREMENT,
  doctor_id INT(11) NOT NULL,
  day_of_week TINYINT(1) NOT NULL COMMENT '0=الأحد, 6=السبت',
  start_time TIME NOT NULL,
  end_time TIME NOT NULL,
  PRIMARY KEY (id),
  FOREIGN KEY (doctor_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE appointments (
  id INT(11) NOT NULL AUTO_INCREMENT,
  patient_id INT(11) NOT NULL,
  doctor_id INT(11) NOT NULL,
  appointment_date DATETIME NOT NULL,
  reason TEXT NOT NULL,
  status ENUM('Pending', 'Confirmed', 'Cancelled') DEFAULT 'Pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (doctor_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE medical_records (
  id INT(11) NOT NULL AUTO_INCREMENT,
  patient_id INT(11) NOT NULL,
  doctor_id INT(11) NOT NULL,
  diagnosis TEXT NOT NULL,
  treatment TEXT NOT NULL,
  notes TEXT,
  record_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (doctor_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE messages (
  id INT(11) NOT NULL AUTO_INCREMENT,
  sender_id INT(11) NOT NULL,
  receiver_id INT(11) NOT NULL,
  message_content TEXT,
  file_path VARCHAR(255) DEFAULT NULL,
  file_type VARCHAR(50) DEFAULT NULL,
  sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT INTO users (username, email, password, role) 
VALUES ('admin', NULL, 'admin', 'admin');
INSERT INTO users (username, email, password, role, specialty) VALUES
('ahmed', NULL, '123456', 'doctor', 'طب عام'),
('mohamed', NULL, '123456', 'doctor', 'طب أطفال'),
('ali', NULL, '123456', 'doctor', 'أمراض القلب'),
('fatima', NULL, '123456', 'doctor', 'طب نساء'),
('khalid', NULL, '123456', 'doctor', 'جراحة عامة'),
('sara', NULL, '123456', 'doctor', 'جراحة عظام'),
('amina', NULL, '123456', 'doctor', 'أمراض جلدية'),
('youssef', NULL, '123456', 'doctor', 'طب عيون'),
('nadia', NULL, '123456', 'doctor', 'أنف وأذن'),
('brahim', NULL, '123456', 'doctor', 'طب أعصاب'),
('hasna', NULL, '123456', 'doctor', 'طب نفسي'),
('karim', NULL, '123456', 'doctor', 'مسالك بولية'),
('samira', NULL, '123456', 'doctor', 'أمراض سكري'),
('rachid', NULL, '123456', 'doctor', 'جهاز هضمي'),
('laila', NULL, '123456', 'doctor', 'طب شيخوخة'),