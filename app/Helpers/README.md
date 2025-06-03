# Helpers Directory

This directory contains utility functions and helper classes for the PantryPal application.

## Purpose

Helpers provide common functionality that can be used throughout the application. They:

- Simplify repetitive tasks
- Provide utility functions for common operations
- Are generally stateless and focused on a single responsibility
- Can be used by models, controllers, views, or other components

## Example

```php
<?php

namespace App\Helpers;

class StringHelper {
    /**
     * Convert a string to slug format
     * 
     * @param string $text The text to convert
     * @return string The slugified text
     */
    public static function slugify($text) {
        // Convert to lowercase
        $text = strtolower($text);
        
        // Replace non-alphanumeric characters with hyphens
        $text = preg_replace('/[^a-z0-9]+/', '-', $text);
        
        // Remove leading and trailing hyphens
        $text = trim($text, '-');
        
        return $text;
    }
    
    /**
     * Truncate a string to a specified length
     * 
     * @param string $text The text to truncate
     * @param int $length The maximum length
     * @param string $suffix The suffix to add if truncated
     * @return string The truncated text
     */
    public static function truncate($text, $length = 100, $suffix = '...') {
        if (strlen($text) <= $length) {
            return $text;
        }
        
        return substr($text, 0, $length) . $suffix;
    }
}
```