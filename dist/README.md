# Public Directory

This directory contains publicly accessible files for the PantryPal application.

## Purpose

The public directory is the web server's document root and contains all files that should be directly accessible by users. It:

- Contains the entry point to the application (index.php)
- Stores compiled/processed assets (CSS, JavaScript, images)
- Provides a security boundary by keeping application code outside the web root
- Serves static files directly without PHP processing

## Structure

- **css**: Contains CSS stylesheets
- **js**: Contains JavaScript files
- **images**: Contains image files
- **fonts**: Contains font files
- **index.php**: The main entry point that bootstraps the application

## Security Note

The public directory should be the only directory exposed to the web. All other application code should be kept outside this directory to prevent direct access to sensitive files.

## Example

A typical web server configuration (Apache) might look like:

```apache
<VirtualHost *:80>
    ServerName pantrypal.local
    DocumentRoot /path/to/pantrypal_core/public
    
    <Directory /path/to/pantrypal_core/public>
        AllowOverride All
        Require all granted
    </Directory>
    
    # Deny access to files outside the public directory
    <Directory /path/to/pantrypal_core>
        Require all denied
    </Directory>
</VirtualHost>
```