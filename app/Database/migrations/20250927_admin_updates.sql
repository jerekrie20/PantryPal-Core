-- Add is_admin column to users and create updates table
ALTER TABLE `users`
    ADD COLUMN `is_admin` TINYINT(1) NOT NULL DEFAULT 0 AFTER `password_hash`;

CREATE TABLE IF NOT EXISTS `updates`
(
    `id`             INT(11)      NOT NULL AUTO_INCREMENT,
    `target_user_id` INT(11)               DEFAULT NULL, -- NULL means visible to all users
    `title`          VARCHAR(191) NOT NULL,
    `message`        TEXT         NOT NULL,
    `is_active`      TINYINT(1)   NOT NULL DEFAULT 1,
    `created_by`     INT(11)      NOT NULL, -- admin user id
    `created_at`     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_target_user_id` (`target_user_id`),
    KEY `idx_is_active` (`is_active`),
    CONSTRAINT `updates_fk_target_user`
        FOREIGN KEY (`target_user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `updates_fk_created_by`
        FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
