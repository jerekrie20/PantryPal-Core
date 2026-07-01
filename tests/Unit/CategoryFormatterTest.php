<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Services\Pantry\CategoryFormatter;

class CategoryFormatterTest extends TestCase
{
    public function testNullAndEmptyReturnNull()
    {
        $this->assertNull(CategoryFormatter::stringify(null));
        $this->assertNull(CategoryFormatter::stringify(''));
    }

    public function testPlainStringPassesThrough()
    {
        $this->assertSame('Dairy', CategoryFormatter::stringify('Dairy'));
    }

    public function testJsonEncodedCategoryPathCollapses()
    {
        $this->assertSame(
            'Snacks › Chips',
            CategoryFormatter::stringify('{"categoryPath":["Snacks","Chips"]}')
        );
    }

    public function testArrayWithCategoryPathKey()
    {
        $this->assertSame(
            'Produce › Fruit',
            CategoryFormatter::stringify(['categoryPath' => ['Produce', 'Fruit']])
        );
    }

    public function testStringyArrayCollapses()
    {
        $this->assertSame('Produce › Fruit', CategoryFormatter::stringify(['Produce', 'Fruit']));
    }

    public function testArrayOfNonStringsReturnsNull()
    {
        $this->assertNull(CategoryFormatter::stringify([1, 2, 3]));
    }

    public function testMalformedJsonTreatedAsPlainString()
    {
        $this->assertSame('{not json', CategoryFormatter::stringify('{not json'));
    }

    public function testNonStringNonArrayReturnsNull()
    {
        $this->assertNull(CategoryFormatter::stringify(42));
    }
}
