<?php
namespace Services;

interface FoodProvider {
    public function getSource(): string; // 'fdc' | 'off'
    public function searchIngredients(string $q, int $limit = 5): array; // [{api_id,name,brand?,image_url?,type:'ingredient'}]
    public function fetchIngredient(int|string $id): ?array;            // ['name','image_url','category','nutrition_info','raw']
    public function searchProducts(string $q, int $limit = 5): array;    // [{api_id,name,brand?,image_url?,type:'product'}]
    public function fetchProduct(int|string $id): ?array;                // ['name','brand','upc','size_text','image_url','category','nutrition_info','raw']
}
