-- Shopping list items — one global list per user.
-- Run against the pantrypal database.

USE `pantrypal`;

CREATE TABLE IF NOT EXISTS `shopping_list_items`
(
    `id`           INT(11)      NOT NULL AUTO_INCREMENT,
    `user_id`      INT(11)      NOT NULL,
    `name`         VARCHAR(255) NOT NULL,
    `quantity`     VARCHAR(100)          DEFAULT NULL,
    `recipe_id`    INT(11)               DEFAULT NULL,
    `recipe_title` VARCHAR(255)          DEFAULT NULL,
    `created_at`   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP(),
    PRIMARY KEY (`id`),
    KEY `idx_sli_user` (`user_id`),
    CONSTRAINT `fk_sli_user`   FOREIGN KEY (`user_id`)   REFERENCES `users`(`id`)    ON DELETE CASCADE,
    CONSTRAINT `fk_sli_recipe` FOREIGN KEY (`recipe_id`) REFERENCES `recipes`(`id`)  ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
