-- 000_full_schema_with_sources.sql
-- MySQL 8.0+ recommended

CREATE DATABASE IF NOT EXISTS `pantrypal`;
USE `pantrypal`;

-- USERS (unchanged sample; keep yours if you already have it)
CREATE TABLE IF NOT EXISTS `users`
(
    `id`            INT(11)      NOT NULL AUTO_INCREMENT,
    `username`      VARCHAR(50)  NOT NULL,
    `email`         VARCHAR(255) NOT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `created_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP(),
    PRIMARY KEY (`id`),
    UNIQUE KEY `username` (`username`),
    UNIQUE KEY `email` (`email`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- INGREDIENTS: add api_source so multiple providers can coexist
CREATE TABLE IF NOT EXISTS `ingredients`
(
    `id`              INT(11)      NOT NULL AUTO_INCREMENT,
    `name`            VARCHAR(255) NOT NULL,
    `normalized_name` VARCHAR(255) NOT NULL,
    `brand`           VARCHAR(120)                           DEFAULT NULL,
    `api_source`      ENUM('fdc','off','fatsecret')                      DEFAULT NULL,
    `api_id`          BIGINT                                 DEFAULT NULL,
    `api_kind`        ENUM('ingredient','product','manual')  DEFAULT NULL,
    `image_url`       VARCHAR(255)                           DEFAULT NULL,
    `category`        VARCHAR(120)                           DEFAULT NULL,
    `nutrition_info`  JSON                                   DEFAULT NULL,
    `search_terms`    TEXT                                   DEFAULT NULL,
    `created_at`      TIMESTAMP    NOT NULL                  DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_norm_brand_kind` (`normalized_name`, `brand`, `api_kind`),
    UNIQUE KEY `uniq_ing_api_source_id` (`api_source`,`api_id`),
    KEY `idx_api_kind` (`api_kind`),
    KEY `idx_brand` (`brand`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- PRODUCTS: add api_source and make (api_source, api_id) unique
CREATE TABLE IF NOT EXISTS `products`
(
    `id`             INT(11)      NOT NULL AUTO_INCREMENT,
    `ingredient_id`  INT(11)      NULL,
    `api_source`     ENUM('fdc','off','fatsecret') DEFAULT NULL,
    `api_id`         BIGINT       NULL,
    `title`          VARCHAR(255) NOT NULL,
    `brand`          VARCHAR(120) NULL,
    `upc`            VARCHAR(64)  NULL,
    `size_text`      VARCHAR(120) NULL,
    `image_url`      VARCHAR(255) NULL,
    `category`       VARCHAR(120) NULL,
    `nutrition_info` JSON         NULL,
    `raw_payload`    JSON         NULL,
    `created_at`     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_product_api` (`api_source`,`api_id`),
    KEY `idx_brand` (`brand`),
    KEY `idx_ingredient_id` (`ingredient_id`),
    CONSTRAINT `products_fk_ingredient`
        FOREIGN KEY (`ingredient_id`) REFERENCES `ingredients`(`id`)
            ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `items`
(
    `id`              INT(11)        NOT NULL AUTO_INCREMENT,
    `user_id`         INT(11)        NOT NULL,
    `ingredient_id`   INT(11)                 DEFAULT NULL,
    `product_id`      INT(11)                 DEFAULT NULL,
    `quantity`        DECIMAL(10, 2) NOT NULL DEFAULT 1.00,
    `unit`            VARCHAR(50)             DEFAULT NULL,
    `purchase_date`   DATE                    DEFAULT NULL,
    `expiration_date` DATE                    DEFAULT NULL,
    `entered_name`    VARCHAR(255)            DEFAULT NULL,
    `entered_brand`   VARCHAR(120)            DEFAULT NULL,
    `created_at`      TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_user` (`user_id`),
    KEY `idx_ingredient_id` (`ingredient_id`),
    KEY `idx_product_id` (`product_id`),
    KEY `idx_expiration_date` (`expiration_date`),
    CONSTRAINT `items_fk_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
            ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `items_fk_ingredient`
        FOREIGN KEY (`ingredient_id`) REFERENCES `ingredients` (`id`)
            ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `items_fk_product`
        FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
            ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;

-- RECIPES (supports provider caching and user saves)
CREATE TABLE IF NOT EXISTS `recipes`
(
    `id`          INT(11)      NOT NULL AUTO_INCREMENT,
    `user_id`     INT(11)               DEFAULT NULL, -- owner (when user-created), NULL for global cache
    `api_source`  ENUM('fdc','off','manual','fatsecret') DEFAULT NULL,
    `api_id`      VARCHAR(191)         DEFAULT NULL,
    `title`       VARCHAR(255) NOT NULL,
    `description` TEXT                  NULL,
    `image_url`   VARCHAR(255)          NULL,
    `source_url`  VARCHAR(255)          NULL,
    `raw_payload` JSON                  NULL,
    `created_at`  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_recipe_provider` (`api_source`,`api_id`),
    KEY `idx_user` (`user_id`),
    KEY `idx_title` (`title`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Which recipes a user saved/bookmarked
CREATE TABLE IF NOT EXISTS `saved_recipes` (
    `user_id`  INT(11) NOT NULL,
    `recipe_id` INT(11) NOT NULL,
    `saved_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`user_id`, `recipe_id`),
    KEY `idx_recipe_id` (`recipe_id`),
    CONSTRAINT `sr_fk_user`   FOREIGN KEY (`user_id`)  REFERENCES `users`(`id`)   ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `sr_fk_recipe` FOREIGN KEY (`recipe_id`) REFERENCES `recipes`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Per-recipe nutrition (per serving), separate table to store normalized nutrients
CREATE TABLE IF NOT EXISTS `recipe_nutrition` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `recipe_id` INT(11) NOT NULL,
    `per_serving` JSON NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_recipe` (`recipe_id`),
    CONSTRAINT `rn_fk_recipe` FOREIGN KEY (`recipe_id`) REFERENCES `recipes`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- FatSecret 24-hour response cache (ToS-compliant — no permanent nutritional storage).
-- The reserved key __oauth_token__ is also stored here to cache Bearer tokens.
CREATE TABLE IF NOT EXISTS `fatsecret_cache` (
    `id`            INT(11)       NOT NULL AUTO_INCREMENT,
    `cache_key`     VARCHAR(64)   NOT NULL COMMENT 'SHA-256 of endpoint+sorted-params',
    `endpoint`      VARCHAR(120)  NOT NULL COMMENT 'API method, e.g. foods.search or __oauth_token__',
    `response_json` MEDIUMTEXT    NOT NULL,
    `created_at`    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_cache_key` (`cache_key`),
    KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `recipe_ingredients`
(
    `id`            INT(11)        NOT NULL AUTO_INCREMENT,
    `recipe_id`     INT(11)        NOT NULL,
    `ingredient_id` INT(11)        NULL,
    `product_id`    INT(11)        NULL,
    `amount`        DECIMAL(10, 2) NULL,
    `unit`          VARCHAR(50)    NULL,
    `notes`         VARCHAR(255)   NULL,
    PRIMARY KEY (`id`),
    KEY `idx_recipe` (`recipe_id`),
    KEY `idx_recipe_ing` (`recipe_id`, `ingredient_id`),
    KEY `idx_recipe_prod` (`recipe_id`, `product_id`),
    CONSTRAINT `ri_fk_recipe`     FOREIGN KEY (`recipe_id`)    REFERENCES `recipes`(`id`)     ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `ri_fk_ingredient` FOREIGN KEY (`ingredient_id`) REFERENCES `ingredients`(`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `ri_fk_product`    FOREIGN KEY (`product_id`)    REFERENCES `products`(`id`)    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
