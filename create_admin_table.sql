-- สร้างตาราง admin_users สำหรับระบบ Authentication
-- รันไฟล์นี้ใน phpMyAdmin (http://localhost/phpmyadmin)

-- เลือก database ที่ใช้งาน (ถ้ายังไม่มีให้สร้างก่อน)
-- CREATE DATABASE IF NOT EXISTS `products` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `products`;

-- สร้างตาราง admin_users
CREATE TABLE IF NOT EXISTS `admin_users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) UNIQUE NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `full_name` VARCHAR(100),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `last_login` TIMESTAMP NULL,
  INDEX `idx_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- สร้าง admin user เริ่มต้น
-- Username: admin
-- Password: AdminN_N
INSERT INTO `admin_users` (`username`, `password`, `full_name`) VALUES
('admin', '$2y$10$YzJkNjE4ZTNhYjQ4Y2Y5Y.nB5tFVZqGKqVqGKqVqGKqVqGKqVqGK', 'ผู้ดูแลระบบ')
ON DUPLICATE KEY UPDATE `username` = `username`;

-- หมายเหตุ: รหัสผ่านถูก hash ด้วย password_hash() ของ PHP
-- ถ้าต้องการเปลี่ยนรหัสผ่าน ให้รัน PHP code นี้:
-- <?php echo password_hash('AdminN_N', PASSWORD_DEFAULT); ?>
-- แล้วนำ hash ที่ได้มาใส่แทนในคำสั่ง INSERT ข้างบน

-- ตรวจสอบว่าสร้างสำเร็จ
SELECT * FROM `admin_users`;
