-- 20260411_fatsecret_cache.sql
-- FatSecret ToS-compliant 24-hour JSON cache and ENUM expansion.
-- Run this against the pantrypal database.

USE `pantrypal`;

-- FatSecret 24-hour response cache.
-- Full JSON payloads are never stored in permanent tables (ingredients/products/recipes).
-- The __oauth_token__ reserved key also uses this table to cache Bearer tokens.
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

-- Expand the recipes.api_source ENUM to include fatsecret.
-- Only api_id (recipe_id) is stored permanently; raw_payload stays NULL for FatSecret rows.
ALTER TABLE `recipes`
    MODIFY COLUMN `api_source`
    ENUM('fdc','off','manual','fatsecret')
    DEFAULT NULL;

-- Also remove removed providers from ingredients and products
ALTER TABLE `ingredients`
    MODIFY COLUMN `api_source`
    ENUM('fdc','off')
    DEFAULT NULL;

ALTER TABLE `products`
    MODIFY COLUMN `api_source`
    ENUM('fdc','off')
    DEFAULT NULL;
