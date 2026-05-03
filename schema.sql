-- ============================================================
-- Maison 1815 — Database Schema
-- Run: mysql -u root -p < schema.sql
-- After import, run admin/setup.php once to create the admin user,
-- then DELETE admin/setup.php.
-- ============================================================

CREATE DATABASE IF NOT EXISTS maison1815
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE maison1815;

-- ------------------------------------------------------------
-- 1. users
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
    `id`         INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `username`   VARCHAR(80)     NOT NULL,
    `password`   VARCHAR(255)    NOT NULL COMMENT 'bcrypt hash via password_hash()',
    `created_at` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_users_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 2. video_projects
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `video_projects` (
    `id`          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `slug`        VARCHAR(255)    NOT NULL,
    `client`      VARCHAR(255)    NOT NULL DEFAULT '',
    `title`       VARCHAR(255)    NOT NULL DEFAULT '',
    `description` TEXT,
    `director`    VARCHAR(255)    NOT NULL DEFAULT '',
    `video_path`  VARCHAR(500)    DEFAULT NULL COMMENT 'path relative to UPLOAD_PATH',
    `clip_start`  FLOAT           NOT NULL DEFAULT 0  COMMENT 'preview clip start in seconds',
    `clip_end`    FLOAT           NOT NULL DEFAULT 10 COMMENT 'preview clip end in seconds',
    `sort_order`  INT             NOT NULL DEFAULT 0,
    `is_active`   TINYINT(1)      NOT NULL DEFAULT 1,
    `created_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_video_projects_slug` (`slug`),
    KEY `idx_video_projects_is_active`  (`is_active`),
    KEY `idx_video_projects_sort_order` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 3. video_project_teams
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `video_project_teams` (
    `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `project_id`  INT UNSIGNED NOT NULL,
    `first_name`  VARCHAR(120) NOT NULL DEFAULT '',
    `last_name`   VARCHAR(120) NOT NULL DEFAULT '',
    PRIMARY KEY (`id`),
    KEY `idx_vpt_project_id` (`project_id`),
    CONSTRAINT `fk_vpt_project`
        FOREIGN KEY (`project_id`)
        REFERENCES `video_projects` (`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 4. photo_projects
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `photo_projects` (
    `id`          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `slug`        VARCHAR(255)    NOT NULL,
    `client`      VARCHAR(255)    NOT NULL DEFAULT '',
    `title`       VARCHAR(255)    NOT NULL DEFAULT '',
    `description` TEXT,
    `director`    VARCHAR(255)    NOT NULL DEFAULT '',
    `cover_photo` VARCHAR(500)    DEFAULT NULL COMMENT 'path relative to UPLOAD_PATH',
    `sort_order`  INT             NOT NULL DEFAULT 0,
    `is_active`   TINYINT(1)      NOT NULL DEFAULT 1,
    `created_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_photo_projects_slug` (`slug`),
    KEY `idx_photo_projects_is_active`  (`is_active`),
    KEY `idx_photo_projects_sort_order` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 5. photo_project_images
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `photo_project_images` (
    `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `project_id`  INT UNSIGNED NOT NULL,
    `image_path`  VARCHAR(500) NOT NULL COMMENT 'path relative to UPLOAD_PATH',
    `sort_order`  INT          NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `idx_ppi_project_id`  (`project_id`),
    KEY `idx_ppi_sort_order`  (`sort_order`),
    CONSTRAINT `fk_ppi_project`
        FOREIGN KEY (`project_id`)
        REFERENCES `photo_projects` (`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 6. photo_project_teams
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `photo_project_teams` (
    `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `project_id`  INT UNSIGNED NOT NULL,
    `first_name`  VARCHAR(120) NOT NULL DEFAULT '',
    `last_name`   VARCHAR(120) NOT NULL DEFAULT '',
    PRIMARY KEY (`id`),
    KEY `idx_ppt_project_id` (`project_id`),
    CONSTRAINT `fk_ppt_project`
        FOREIGN KEY (`project_id`)
        REFERENCES `photo_projects` (`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 7. team_members
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `team_members` (
    `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `first_name`  VARCHAR(120) NOT NULL DEFAULT '',
    `last_name`   VARCHAR(120) NOT NULL DEFAULT '',
    `role_fr`     VARCHAR(255) NOT NULL DEFAULT '',
    `role_de`     VARCHAR(255) NOT NULL DEFAULT '',
    `role_en`     VARCHAR(255) NOT NULL DEFAULT '',
    `email`       VARCHAR(255) NOT NULL DEFAULT '',
    `photo`       VARCHAR(500) DEFAULT NULL COMMENT 'path relative to UPLOAD_PATH',
    `sort_order`  INT          NOT NULL DEFAULT 0,
    `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_team_members_sort_order` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 8. about_page (single-row config)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `about_page` (
    `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `image_path`  VARCHAR(500) DEFAULT NULL COMMENT 'path relative to UPLOAD_PATH',
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed the single about_page row on first import
INSERT INTO `about_page` (`id`) VALUES (1)
    ON DUPLICATE KEY UPDATE `id` = `id`;

-- ------------------------------------------------------------
-- 9. talents
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `talents` (
    `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `first_name`  VARCHAR(120) NOT NULL DEFAULT '',
    `last_name`   VARCHAR(120) NOT NULL DEFAULT '',
    `photo`       VARCHAR(500) DEFAULT NULL COMMENT 'path relative to UPLOAD_PATH',
    `is_active`   TINYINT(1)   NOT NULL DEFAULT 1,
    `sort_order`  INT          NOT NULL DEFAULT 0,
    `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_talents_is_active`  (`is_active`),
    KEY `idx_talents_sort_order` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Admin user — run admin/setup.php to insert with proper bcrypt hash.
-- DO NOT store plaintext passwords here.
-- ------------------------------------------------------------
