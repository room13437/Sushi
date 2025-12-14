-- สร้างตาราง redeem_codes สำหรับเก็บโค้ดแลก Point
-- รันคำสั่งนี้ใน phpMyAdmin

CREATE TABLE IF NOT EXISTS `redeem_codes` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `code` VARCHAR(50) NOT NULL UNIQUE,
    `points` INT(11) NOT NULL DEFAULT 10,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ตัวอย่างการเพิ่มโค้ด
-- INSERT INTO redeem_codes (code, points) VALUES ('WELCOME50', 50);
-- INSERT INTO redeem_codes (code, points) VALUES ('BONUS100', 100);
