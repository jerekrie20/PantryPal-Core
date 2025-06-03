# Models Directory

This directory contains data models and business logic for the PantryPal application.

## Purpose

Models represent the data structures and business rules of the application. They are responsible for:

- Defining the structure of data entities
- Implementing business logic
- Interacting with the database
- Validating data

## Example

```php
<?php

namespace App\Models;

class Product {
    private $id;
    private $name;
    private $description;
    private $price;
    
    // Constructor, getters, setters, and other methods
    
    public function save() {
        // Logic to save the product to the database
    }
    
    public static function findById($id) {
        // Logic to find a product by ID
    }
}
```