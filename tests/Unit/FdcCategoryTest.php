<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Services\Providers\FdcProvider;
use Services\Pantry\CategoryFormatter;

class FdcCategoryTest extends TestCase
{
    public function testBrandedStringCategory()
    {
        $this->assertSame(
            'Crackers & Biscotti',
            FdcProvider::categoryFrom(['brandedFoodCategory' => 'Crackers & Biscotti'])
        );
    }

    public function testFoundationObjectCategory()
    {
        // Shape returned for Foundation foods like Gala Apple (fdcId 1750341);
        // binding this array raw into PDO was the Array-to-string crash.
        $this->assertSame(
            'Fruits and Fruit Juices',
            FdcProvider::categoryFrom([
                'foodCategory' => ['id' => 9, 'code' => '0900', 'description' => 'Fruits and Fruit Juices'],
            ])
        );
    }

    public function testSurveyWweiaCategory()
    {
        $this->assertSame(
            'Apples',
            FdcProvider::categoryFrom([
                'wweiaFoodCategory' => ['wweiaFoodCategoryCode' => 6002, 'wweiaFoodCategoryDescription' => 'Apples'],
            ])
        );
    }

    public function testMissingOrEmptyCategoryIsNull()
    {
        $this->assertNull(FdcProvider::categoryFrom([]));
        $this->assertNull(FdcProvider::categoryFrom(['brandedFoodCategory' => '  ']));
        $this->assertNull(FdcProvider::categoryFrom(['foodCategory' => ['id' => 9]]));
    }

    public function testFormatterRendersFdcObjectAndItsJsonFallback()
    {
        $obj = ['id' => 9, 'code' => '0900', 'description' => 'Fruits and Fruit Juices'];

        // Direct array (e.g. raw payload passed to a view).
        $this->assertSame('Fruits and Fruit Juices', CategoryFormatter::stringify($obj));

        // JSON-encoded fallback as stored by the model safety net.
        $this->assertSame('Fruits and Fruit Juices', CategoryFormatter::stringify(json_encode($obj)));
    }
}
