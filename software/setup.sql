-- setup.sql — 在 phpMyAdmin 或 MySQL 命令行执行一次即可
-- 使用前修改数据库名（默认 rental）

CREATE DATABASE IF NOT EXISTS `rental`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `rental`;

-- ── 设备表 ──
CREATE TABLE IF NOT EXISTS `items` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `qr_code`    VARCHAR(128) NOT NULL,
    `name`       VARCHAR(128) NOT NULL,
    `status`     ENUM('in_stock','rented','returned','inspected','disinfected')
                 NOT NULL DEFAULT 'in_stock',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_qr_code` (`qr_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── 站点表 ──
CREATE TABLE IF NOT EXISTS `stations` (
    `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `device_id`    VARCHAR(64)  NOT NULL,
    `api_key`      VARCHAR(64)  NOT NULL,
    `station_name` VARCHAR(128) NOT NULL,
    `bound_status` ENUM('in_stock','rented','returned','inspected','disinfected') NOT NULL,
    `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_device_id` (`device_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── 日志表 ──
CREATE TABLE IF NOT EXISTS `logs` (
    `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `item_id`     INT UNSIGNED NOT NULL,
    `station_id`  INT UNSIGNED,
    `from_status` ENUM('in_stock','rented','returned','inspected','disinfected') NOT NULL,
    `to_status`   ENUM('in_stock','rented','returned','inspected','disinfected') NOT NULL,
    `scanned_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_log_item`
        FOREIGN KEY (`item_id`) REFERENCES `items`(`id`)
        ON DELETE CASCADE,
    CONSTRAINT `fk_log_station`
        FOREIGN KEY (`station_id`) REFERENCES `stations`(`id`)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── 测试数据（可选，验证完删掉）──
INSERT INTO `items` (qr_code, name, status) VALUES
('ITEM_A001', '血压计 #1', 'in_stock'),
('ITEM_A002', '血糖仪 #1', 'in_stock');

INSERT INTO `stations` (device_id, api_key, station_name, bound_status) VALUES
('ESP_001', 'testapikey0000000000000000000001', '出租台',  'rented'),
('ESP_002', 'testapikey0000000000000000000002', '回收台',  'returned'),
('ESP_003', 'testapikey0000000000000000000003', '检查台',  'inspected'),
('ESP_004', 'testapikey0000000000000000000004', '消毒间',  'disinfected'),
('ESP_005', 'testapikey0000000000000000000005', '入库台',  'in_stock');
