<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Services\Pantry\PantryTermNormalizer;

class PantryTermNormalizerTest extends TestCase
{
    public function testEmptyStringStaysEmpty()
    {
        $this->assertSame('', PantryTermNormalizer::normalize(''));
        $this->assertSame('', PantryTermNormalizer::normalize('   '));
    }

    public function testLowercasesAndTrims()
    {
        $this->assertSame('bananas', PantryTermNormalizer::normalize('  Bananas  '));
    }

    public function testStripsQuotesAndParentheses()
    {
        $this->assertSame('butter', PantryTermNormalizer::normalize('"Butter (salted)"'));
    }

    public function testTakesFirstCommaSegment()
    {
        $this->assertSame('carrots', PantryTermNormalizer::normalize('Carrots, peeled, diced'));
    }

    public function testStripsStopwordsAndPackTokens()
    {
        $this->assertSame(
            'water',
            PantryTermNormalizer::normalize('Purified Drinking Water 24 pack')
        );
    }

    public function testAppleCultivarsCollapseToApple()
    {
        $this->assertSame('apple', PantryTermNormalizer::normalize('Honeycrisp Apples'));
        $this->assertSame('apple', PantryTermNormalizer::normalize('Gala apples'));
        $this->assertSame('apple', PantryTermNormalizer::normalize('apples'));
    }

    public function testChocolateVariantsCollapse()
    {
        $this->assertSame('chocolate', PantryTermNormalizer::normalize('Milk Chocolate Chips'));
        $this->assertSame('chocolate', PantryTermNormalizer::normalize('semisweet chocolate'));
    }

    public function testMeatCutsKeepLastTwoTokens()
    {
        $this->assertSame(
            'chicken thighs',
            PantryTermNormalizer::normalize('Organic Boneless Skinless Chicken Thighs')
        );
    }

    public function testLongInputCappedAt64Chars()
    {
        $out = PantryTermNormalizer::normalize(str_repeat('longword ', 20));
        $this->assertLessThanOrEqual(64, strlen($out));
    }
}
