-- PantryPal Core: Initial schema (MySQL 8.0+)
-- NOTE: This file is kept for reference only. Your canonical schema is app/Database/migrations/database.sql.
-- Prefer applying database.sql for fresh installs. Then, optionally run 20250923_prod_indexes.sql to add extra indexes.
-- Safe to run multiple times (uses CREATE TABLE IF NOT EXISTS). Additional indexes are defined in separate migration files.

-- Users
CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  username VARCHAR(50) NOT NULL,
  email VARCHAR(255) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  is_admin TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uniq_users_email (email),
  UNIQUE KEY uniq_users_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ingredients (generic or manual entries)
CREATE TABLE IF NOT EXISTS ingredients (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(255) NOT NULL,
  normalized_name VARCHAR(255) DEFAULT NULL,
  brand VARCHAR(255) DEFAULT NULL,
  api_source VARCHAR(32) DEFAULT NULL,
  api_id VARCHAR(64) DEFAULT NULL,
  api_kind VARCHAR(32) DEFAULT NULL, -- e.g., ingredient|manual|off|fdc
  image_url VARCHAR(1024) DEFAULT NULL,
  category JSON DEFAULT NULL,
  nutrition_info JSON DEFAULT NULL,
  search_terms TEXT DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_ingredients_name (name),
  KEY idx_ingredients_norm (normalized_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Products (branded items)
CREATE TABLE IF NOT EXISTS products (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  title VARCHAR(255) NOT NULL,
  brand VARCHAR(255) DEFAULT NULL,
  api_source VARCHAR(32) DEFAULT NULL,
  api_id VARCHAR(64) DEFAULT NULL,
  image_url VARCHAR(1024) DEFAULT NULL,
  category JSON DEFAULT NULL,
  nutrition_info JSON DEFAULT NULL,
  upc VARCHAR(64) DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_products_title (title)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Recipes (normalized provider data)
CREATE TABLE IF NOT EXISTS recipes (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED NULL,
  api_source VARCHAR(32) DEFAULT NULL, -- e.g., spoonacular, suggestic, api_ninjas, manual
  api_id VARCHAR(64) DEFAULT NULL,
  title VARCHAR(255) NOT NULL,
  description TEXT DEFAULT NULL,
  image_url VARCHAR(1024) DEFAULT NULL,
  source_url VARCHAR(1024) DEFAULT NULL,
  raw_payload JSON DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_recipes_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Items (user pantry items)
CREATE TABLE IF NOT EXISTS items (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED NOT NULL,
  ingredient_id INT UNSIGNED NULL,
  product_id INT UNSIGNED NULL,
  quantity DECIMAL(10,2) NOT NULL DEFAULT 1.00,
  unit VARCHAR(10) DEFAULT NULL,
  purchase_date DATE DEFAULT NULL,
  expiration_date DATE DEFAULT NULL,
  entered_name VARCHAR(255) DEFAULT NULL,
  entered_brand VARCHAR(255) DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_items_user (user_id),
  KEY idx_items_exp (expiration_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Saved recipes (user bookmarks) with composite PK
CREATE TABLE IF NOT EXISTS saved_recipes (
  user_id INT UNSIGNED NOT NULL,
  recipe_id INT UNSIGNED NOT NULL,
  saved_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id, recipe_id),
  KEY idx_recipe_id (recipe_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Foreign keys (created conditionally; do not error if already exist)
-- MySQL lacks IF NOT EXISTS for constraints, but running twice will error.
-- Apply once in fresh environments; subsequent runs can ignore errors.
ALTER TABLE items
  ADD CONSTRAINT fk_items_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  ADD CONSTRAINT fk_items_ingredient FOREIGN KEY (ingredient_id) REFERENCES ingredients(id) ON DELETE SET NULL,
  ADD CONSTRAINT fk_items_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL;

ALTER TABLE recipes
  ADD CONSTRAINT fk_recipes_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL;

ALTER TABLE saved_recipes
  ADD CONSTRAINT fk_saved_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  ADD CONSTRAINT fk_saved_recipe FOREIGN KEY (recipe_id) REFERENCES recipes(id) ON DELETE CASCADE;
