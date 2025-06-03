# Tests Directory

This directory contains test files for the PantryPal application.

## Purpose

The tests directory contains automated tests that verify the functionality of the application. It:

- Ensures code quality and correctness
- Prevents regressions when making changes
- Documents expected behavior
- Facilitates refactoring with confidence

## Structure

- **Unit**: Tests for individual components in isolation
- **Integration**: Tests for interactions between components
- **Feature**: Tests for complete features from a user perspective
- **Fixtures**: Test data used by tests

## Example

```php
<?php
// tests/Unit/Models/ProductTest.php

use PHPUnit\Framework\TestCase;
use App\Models\Product;

class ProductTest extends TestCase {
    public function testCanCreateProduct() {
        $product = new Product();
        $product->setName('Test Product');
        $product->setPrice(19.99);
        
        $this->assertEquals('Test Product', $product->getName());
        $this->assertEquals(19.99, $product->getPrice());
    }
    
    public function testPriceCannotBeNegative() {
        $this->expectException(\InvalidArgumentException::class);
        
        $product = new Product();
        $product->setPrice(-10.00);
    }
    
    public function testSlugGeneration() {
        $product = new Product();
        $product->setName('Test Product Name');
        
        $this->assertEquals('test-product-name', $product->getSlug());
    }
}
```

## Running Tests

To run tests, you can use PHPUnit:

```bash
# Run all tests
./vendor/bin/phpunit tests

# Run a specific test file
./vendor/bin/phpunit tests/Unit/Models/ProductTest.php

# Run tests with a specific group
./vendor/bin/phpunit --group=models
```