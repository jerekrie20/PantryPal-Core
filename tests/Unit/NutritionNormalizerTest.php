<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Services\Nutrition\Normalizer;

class NutritionNormalizerTest extends TestCase
{
    public function testGarbageInputReturnsNull()
    {
        $this->assertNull(Normalizer::normalize(null));
        $this->assertNull(Normalizer::normalize('string'));
        $this->assertNull(Normalizer::normalize([]));
        $this->assertNull(Normalizer::normalize(['foo' => 'bar']));
    }

    public function testAlreadyNormalizedShapePassesThrough()
    {
        $input = ['nutrients' => [['name' => 'Calories', 'amount' => 100, 'unit' => 'kcal']]];
        $this->assertSame($input, Normalizer::normalize($input));
    }

    public function testFdcLabelNutrients()
    {
        $out = Normalizer::normalize([
            'labelNutrients'  => [
                'calories' => ['value' => 250],
                'protein'  => ['value' => 12],
            ],
            'servingSize'     => 30,
            'servingSizeUnit' => 'g',
        ]);

        $this->assertCount(2, $out['nutrients']);
        $this->assertSame('30 g', $out['servings']['original']);
        $this->assertSame('Calories', $out['nutrients'][0]['name']);
        $this->assertSame(250.0, $out['nutrients'][0]['amount']);
    }

    public function testFdcFoodNutrientsMapsCoreNames()
    {
        $out = Normalizer::normalize([
            'foodNutrients' => [
                ['nutrient' => ['name' => 'Energy', 'unitName' => 'kcal'], 'amount' => 52],
                ['nutrient' => ['name' => 'Protein', 'unitName' => 'g'], 'amount' => 0.3],
            ],
        ]);

        $names = array_column($out['nutrients'], 'name');
        $this->assertContains('Calories', $names);
        $this->assertContains('Protein', $names);
    }

    public function testBareFdcFoodNutrientListGetsWrapped()
    {
        $out = Normalizer::normalize([
            ['nutrientName' => 'Energy', 'value' => 52, 'unitName' => 'kcal'],
        ]);

        $this->assertSame('Calories', $out['nutrients'][0]['name']);
    }

    public function testOffNutrimentsPer100g()
    {
        $out = Normalizer::normalize([
            'nutriments' => [
                'energy-kcal_100g'   => 539,
                'fat_100g'           => 30.9,
                'proteins_100g'      => 6.3,
            ],
        ]);

        $names = array_column($out['nutrients'], 'name');
        $this->assertContains('Calories', $names);
        $this->assertContains('Fat', $names);
        $this->assertSame('per 100 g', $out['servings']['original']);
    }

    public function testOffNutrimentsPreferServingScope()
    {
        $out = Normalizer::normalize([
            'nutriments' => [
                'energy-kcal_serving' => 140,
                'energy-kcal_100g'    => 539,
            ],
        ]);

        $this->assertSame(140.0, $out['nutrients'][0]['amount']);
        $this->assertSame('per serving', $out['servings']['original']);
    }

    public function testFatSecretSingleServingObjectGetsWrapped()
    {
        $out = Normalizer::normalize([
            'servings' => [
                'serving' => [
                    'serving_id'          => '1',
                    'serving_description' => '1 cup',
                    'calories'            => '150',
                    'protein'             => '8',
                ],
            ],
        ]);

        $this->assertCount(2, $out['nutrients']);
        $this->assertSame('1 cup', $out['servings']['original']);
    }

    public function testFatSecretPrefersDefaultServing()
    {
        $out = Normalizer::normalize([
            'servings' => [
                'serving' => [
                    ['serving_id' => '1', 'serving_description' => '1 oz', 'calories' => '100'],
                    ['serving_id' => '2', 'serving_description' => '1 cup', 'calories' => '400', 'is_default' => 1],
                ],
            ],
        ]);

        $this->assertSame('1 cup', $out['servings']['original']);
        $this->assertSame(400.0, $out['nutrients'][0]['amount']);
    }

    public function testFlatLabelShape()
    {
        $out = Normalizer::normalize([
            'calories' => ['value' => 90],
            'protein'  => ['value' => 3],
        ]);

        $this->assertCount(2, $out['nutrients']);
        $this->assertSame('per serving', $out['servings']['original']);
    }
}
