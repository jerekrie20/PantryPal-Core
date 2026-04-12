-- PantryPal Core: Production index and constraint recommendations
-- Use AFTER applying app/Database/migrations/database.sql (canonical schema).

USE `pantrypal`;

-- MySQL 8.0 (no IF NOT EXISTS for CREATE INDEX). Rerunning may error if indexes already exist; ignore such errors.

-- Items: common list and counts
CREATE INDEX idx_items_user_created ON items (user_id, created_at);
CREATE INDEX idx_items_user_expiration ON items (user_id, expiration_date);

-- Recipes: provider identity and title lookup
CREATE UNIQUE INDEX uniq_recipes_source_apiid ON recipes (api_source, api_id);
CREATE INDEX idx_recipes_title ON recipes (title);

-- Saved recipes: uniqueness and lookups (composite PK should already exist)
CREATE UNIQUE INDEX uniq_saved_user_recipe ON saved_recipes (user_id, recipe_id);
CREATE INDEX idx_saved_recipe ON saved_recipes (recipe_id);

-- Ingredients/Product lookup helpers
CREATE INDEX idx_ingredients_norm_brand ON ingredients (normalized_name, brand);
CREATE INDEX idx_products_title_brand ON products (title, brand);

-- Foreign keys with ON DELETE CASCADE (uncomment if you want automatic cleanup)
-- ALTER TABLE items
--   ADD CONSTRAINT fk_items_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
--   ADD CONSTRAINT fk_items_ingredient FOREIGN KEY (ingredient_id) REFERENCES ingredients(id) ON DELETE SET NULL,
--   ADD CONSTRAINT fk_items_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL;
 ALTER TABLE saved_recipes
  ADD CONSTRAINT fk_saved_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  ADD CONSTRAINT fk_saved_recipe FOREIGN KEY (recipe_id) REFERENCES recipes(id) ON DELETE CASCADE;


ALTER TABLE users ADD COLUMN is_premium TINYINT(1) DEFAULT 0;