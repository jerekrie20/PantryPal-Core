-- 20260704_ingredient_search_terms.sql
-- Permanent cache of AI-canonicalized ingredient search terms.
-- Maps raw pantry item names ("Red Seedless Grapes") to concise recipe-search
-- terms ("grapes"). Each unique name is translated by the AI once, ever.
-- Run this against the pantrypal database.

USE `pantrypal`;

CREATE TABLE IF NOT EXISTS `ingredient_search_terms` (
    `id`          INT(11)      NOT NULL AUTO_INCREMENT,
    `raw_hash`    VARCHAR(64)  NOT NULL COMMENT 'SHA-256 of lowercased trimmed raw name',
    `raw_name`    VARCHAR(255) NOT NULL,
    `search_term` VARCHAR(100) NOT NULL COMMENT 'AI-canonicalized recipe search term',
    `created_at`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_raw_hash` (`raw_hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
