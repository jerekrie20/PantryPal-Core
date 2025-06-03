# Views Directory

This directory contains view templates and UI components for the PantryPal application.

## Purpose

Views are responsible for presenting data to the user. They are:

- Templates that define the structure and layout of pages
- Focused on presentation logic
- Separated from business logic
- Reusable across different parts of the application

## Structure

- **Components**: Reusable UI components that can be included in multiple views
- **Layouts**: Page layout templates that define the overall structure of pages

## Example

```php
<!-- views/products/index.php -->
<?php include '../Layouts/header.php'; ?>

<div class="container">
    <h1>Products</h1>
    
    <div class="product-list">
        <?php foreach ($products as $product): ?>
            <div class="product-card">
                <h2><?= htmlspecialchars($product->getName()) ?></h2>
                <p><?= htmlspecialchars($product->getDescription()) ?></p>
                <p class="price">$<?= number_format($product->getPrice(), 2) ?></p>
                <a href="/products/<?= $product->getId() ?>" class="btn">View Details</a>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php include '../Layouts/footer.php'; ?>
```