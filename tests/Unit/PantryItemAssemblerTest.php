<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Services\Pantry\PantryItemAssembler;

class PantryItemAssemblerTest extends TestCase
{
    // ---------- expirationStatus ----------

    public function testNoDateIsInStock()
    {
        $s = PantryItemAssembler::expirationStatus(null);
        $this->assertSame('In Stock', $s['status']);
        $this->assertSame('badge-success', $s['badge']);
        $this->assertFalse($s['expired']);
    }

    public function testExpiredYesterday()
    {
        $date = (new \DateTimeImmutable('yesterday'))->format('Y-m-d');
        $s = PantryItemAssembler::expirationStatus($date);
        $this->assertSame('Expired 1 day ago', $s['status']);
        $this->assertSame('badge-danger', $s['badge']);
        $this->assertTrue($s['expired']);
    }

    public function testExpiresToday()
    {
        $date = (new \DateTimeImmutable('today'))->format('Y-m-d');
        $s = PantryItemAssembler::expirationStatus($date);
        $this->assertSame('Expires today', $s['status']);
        $this->assertSame('badge-warning', $s['badge']);
        $this->assertFalse($s['expired']);
    }

    public function testExpiresWithinThreeDaysIsWarning()
    {
        $date = (new \DateTimeImmutable('+2 days'))->format('Y-m-d');
        $s = PantryItemAssembler::expirationStatus($date);
        $this->assertSame('Expires in 2 days', $s['status']);
        $this->assertSame('badge-warning', $s['badge']);
    }

    public function testExpiresLaterIsNeutral()
    {
        $date = (new \DateTimeImmutable('+10 days'))->format('Y-m-d');
        $s = PantryItemAssembler::expirationStatus($date);
        $this->assertSame('Expires in 10 days', $s['status']);
        $this->assertSame('badge-neutral', $s['badge']);
    }

    public function testGarbageDateKeepsDefaults()
    {
        $s = PantryItemAssembler::expirationStatus('not-a-date');
        $this->assertSame('In Stock', $s['status']);
        $this->assertFalse($s['expired']);
    }

    // ---------- summary ----------

    public function testSummaryIngredientRow()
    {
        $s = PantryItemAssembler::summary([
            'id' => 7,
            'ingredient_id' => 3,
            'ingredient_name' => 'Gala Apple',
            'ingredient_category' => 'Fruits',
            'ingredient_image_url' => 'https://img/apple.jpg',
        ]);

        $this->assertSame('ingredient', $s['kind']);
        $this->assertSame('Gala Apple', $s['name']);
        $this->assertSame('Fruits', $s['category']);
        $this->assertSame('/items/view/7', $s['url']);
        $this->assertSame('In Stock', $s['status']);
    }

    public function testSummaryProductRow()
    {
        $s = PantryItemAssembler::summary([
            'id' => 9,
            'product_id' => 4,
            'product_title' => 'Ritz Crackers',
            'product_category' => 'Snacks',
        ]);

        $this->assertSame('product', $s['kind']);
        $this->assertSame('Ritz Crackers', $s['name']);
        $this->assertSame('/items/view/9', $s['url']);
    }

    public function testSummaryFallsBackToEnteredNameThenPlaceholder()
    {
        $s = PantryItemAssembler::summary(['id' => 5, 'ingredient_id' => 2, 'entered_name' => 'my apples']);
        $this->assertSame('my apples', $s['name']);

        $s2 = PantryItemAssembler::summary(['id' => 5, 'ingredient_id' => 2]);
        $this->assertSame('Ingredient #5', $s2['name']);

        $s3 = PantryItemAssembler::summary(['id' => 6, 'product_id' => 2]);
        $this->assertSame('Product #6', $s3['name']);
    }

    public function testSummaryLegacyRowWithoutCatalogLinks()
    {
        $s = PantryItemAssembler::summary(['id' => 11, 'entered_name' => 'mystery jar']);
        $this->assertSame('ingredient', $s['kind']);
        $this->assertSame('/items/view/11', $s['url']);
        $this->assertSame('Uncategorized', $s['category']);
    }

    // ---------- detail (paths that don't hit FoodService) ----------

    public function testDetailUsesStoredIngredientNutrition()
    {
        $row = [
            'id' => 3,
            'ingredient_id' => 1,
            'ingredient_name' => 'Oats',
            'ingredient_category' => 'Grains',
            'ingredient_nutrition_info' => json_encode([
                'labelNutrients' => ['calories' => ['value' => 150]],
                'servingSize' => 40, 'servingSizeUnit' => 'g',
            ]),
            'quantity' => 2, 'unit' => 'cup',
        ];

        $item = (new PantryItemAssembler())->detail($row);

        $this->assertSame('Oats', $item['name']);
        $this->assertSame('Grains', $item['category']);
        $this->assertNotNull($item['nutrition']);
        $this->assertSame('Calories', $item['nutrition']['nutrients'][0]['name']);
        $this->assertSame('40 g', $item['nutrition']['servings']['original']);
    }

    public function testDetailUsesOffRawPayloadForProducts()
    {
        $row = [
            'id' => 4,
            'product_id' => 2,
            'product_title' => 'Choc Bar',
            'product_upc' => '123456',
            'product_raw_payload' => json_encode([
                'product' => [
                    'brands' => 'Choco Co',
                    'code' => '123456',
                    'image_url' => 'https://img/bar.jpg',
                    'nutriments' => ['energy-kcal_100g' => 539, 'fat_100g' => 30.9],
                ],
            ]),
        ];

        $item = (new PantryItemAssembler())->detail($row);

        $this->assertNotNull($item['nutrition']);
        $this->assertSame('Choco Co', $item['product_raw']['brand']);
        $this->assertSame('123456', $item['product_raw']['upc']);
        $this->assertSame('https://img/bar.jpg', $item['image']);
    }

    public function testDetailFallsBackToProductNutritionInfo()
    {
        $row = [
            'id' => 5,
            'product_id' => 2,
            'product_title' => 'Soup',
            'product_nutrition_info' => json_encode([
                'calories' => ['value' => 90],
                'protein'  => ['value' => 3],
            ]),
        ];

        $item = (new PantryItemAssembler())->detail($row);
        $this->assertCount(2, $item['nutrition']['nutrients']);
    }

    public function testDetailBrandPrefersProductThenIngredientThenEntered()
    {
        $assembler = new PantryItemAssembler();

        $both = $assembler->detail(['id' => 1, 'product_brand' => 'P', 'ingredient_brand' => 'I', 'entered_brand' => 'E']);
        $this->assertSame('P', $both['brand']);

        $ing = $assembler->detail(['id' => 1, 'ingredient_brand' => 'I', 'entered_brand' => 'E']);
        $this->assertSame('I', $ing['brand']);

        $entered = $assembler->detail(['id' => 1, 'entered_brand' => 'E']);
        $this->assertSame('E', $entered['brand']);
    }

    public function testDetailHandlesDoubleEncodedNutritionJson()
    {
        $inner = json_encode(['labelNutrients' => ['calories' => ['value' => 200]]]);
        $row = [
            'id' => 6,
            'ingredient_id' => 1,
            'ingredient_name' => 'Rice',
            'ingredient_nutrition_info' => json_encode($inner), // JSON string inside JSON
        ];

        $item = (new PantryItemAssembler())->detail($row);
        $this->assertNotNull($item['nutrition']);
        $this->assertSame(200.0, $item['nutrition']['nutrients'][0]['amount']);
    }
}
