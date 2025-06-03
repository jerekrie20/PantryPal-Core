# Database Directory

This directory contains database-related code for the PantryPal application.

## Purpose

The Database directory manages database connections, migrations, and queries. It:

- Provides a consistent interface for database operations
- Manages database schema changes through migrations
- Handles database connections and transactions
- May include query builders or ORM functionality

## Structure

- **Migrations**: Contains database migration files that define schema changes
- **Seeds**: Contains seed files for populating the database with initial data
- **Connection.php**: Manages database connections
- **QueryBuilder.php**: Provides a fluent interface for building SQL queries

## Example

```php
<?php

namespace App\Database;

class Connection {
    private static $instance = null;
    private $pdo;
    
    private function __construct() {
        $config = require '../Config/database.php';
        
        $dsn = "mysql:host={$config['host']};dbname={$config['database']};charset={$config['charset']}";
        
        try {
            $this->pdo = new \PDO($dsn, $config['username'], $config['password'], $config['options']);
        } catch (\PDOException $e) {
            throw new \Exception("Database connection failed: " . $e->getMessage());
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
    public function getPdo() {
        return $this->pdo;
    }
}
```