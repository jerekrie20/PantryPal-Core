# Controllers Directory

This directory contains controller classes for the PantryPal application.

## Purpose

Controllers handle the application's request-response cycle. They are responsible for:

- Receiving and processing user input
- Interacting with models to retrieve or modify data
- Selecting the appropriate view to render
- Returning responses to the client

## Example

```php
<?php

namespace App\Controllers;

use App\Models\Product;

class ProductController {
    public function index() {
        // Get all products
        $products = Product::all();
        
        // Render the products view with the products data
        include '../Views/products/index.php';
    }
    
    public function show($id) {
        // Get a specific product
        $product = Product::findById($id);
        
        // Render the product detail view
        include '../Views/products/show.php';
    }
    
    public function create() {
        // Render the product creation form
        include '../Views/products/create.php';
    }
    
    public function store() {
        // Create a new product from form data
        $product = new Product();
        $product->setName($_POST['name']);
        $product->setDescription($_POST['description']);
        $product->setPrice($_POST['price']);
        $product->save();
        
        // Redirect to the product list
        header('Location: /products');
    }
}
```