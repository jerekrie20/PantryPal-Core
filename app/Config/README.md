# Config Directory

This directory contains configuration files for the PantryPal application.

## Purpose

Configuration files define settings and parameters that control how the application behaves. They:

- Separate configuration from code
- Allow for environment-specific settings
- Define constants and parameters used throughout the application
- Store sensitive information like database credentials (though these should be properly secured)

## Example Files

- **database.php**: Database connection settings
- **app.php**: General application settings
- **routes.php**: URL routing configuration
- **mail.php**: Email configuration

## Example

```php
<?php
// config/database.php

return [
    'host' => 'localhost',
    'database' => 'pantrypal',
    'username' => 'dbuser',
    'password' => 'dbpassword',
    'charset' => 'utf8mb4',
    'port' => 3306,
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ],
];
```

## Security Note

Never commit sensitive information like passwords or API keys directly in configuration files. Instead:

1. Use environment variables
2. Use .env files (excluded from version control)
3. Use separate configuration files for development and production