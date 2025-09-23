# PantryPal Core

A vanilla PHP project with Vite for automatic refresh and Tailwind CSS for styling.

## Features

- Hot Module Replacement (HMR) with Vite
- Tailwind CSS for utility-first styling
- PHP for backend logic
- Automatic browser refresh on code changes

## Quick Start

1. Clone the repository
2. Copy .env.example to .env and fill in values (do NOT commit .env):
   ```powershell
   Copy-Item .env.example .env
   # then edit .env to add your keys and credentials
   ```
3. Install dependencies:
   ```
   npm install
   composer install
   ```
4. Start the development servers:
   ```
   npm run dev
   # Ensure your PHP server (e.g., XAMPP) points DocumentRoot to public/
   ```

## Environment & Security

Important security practices for this project:

- Never commit secrets: .env is already ignored by .gitignore. Keep real API keys and passwords only in your local .env.
- Use .env.example as a template. Replace placeholder values locally and keep the example file safe to share.
- If a secret was ever committed or shared, rotate it immediately with the provider (generate a new key, revoke the old one).
- Avoid posting real keys in screenshots/issues. Redact values before sharing.
- Prefer non-personal contact info in OFF user agent (e.g., contact@example.com) instead of a private email.

Environment variables used (see .env.example):
- SPOONACULAR_API_KEY, API_NINJAS_KEY, FDC_API_KEY
- OFF_USER_AGENT (polite identifier for Open Food Facts requests)
- DB_* for database connection (if applicable in your setup)

## Development

- Edit PHP files under `app/`
- Edit JavaScript files in the `src/js` directory
- Edit CSS files in the `src/css` directory
- Tailwind CSS classes can be used directly in your HTML

## Building for Production

To build the frontend assets for production:

```
npm run build
```

This will create a `dist` directory with optimized assets. The PHP files will automatically load the correct assets in production mode.

## Project Structure

The project follows a structured organization to separate concerns and maintain clean code:

```
pantrypal_core/
├── app/                  # Application code
│   ├── Config/           # Configuration files
│   ├── Controllers/      # Controller classes
│   ├── Database/         # Database-related code
│   ├── Helpers/          # Utility functions and helper classes
│   ├── Models/           # Data models and business logic
│   ├── Modules/          # Feature modules
│   └── Views/            # View templates
│       ├── Components/   # Reusable UI components
│       └── Layouts/      # Page layout templates
├── dist/                 # Production build output (created after build)
├── docs/                 # Documentation
├── node_modules/         # Node.js dependencies (created after npm install)
├── public/               # Publicly accessible files
│   ├── css/              # CSS stylesheets
│   ├── fonts/            # Font files
│   ├── images/           # Image files
│   ├── js/               # JavaScript files
│   └── index.php         # Application entry point
├── src/                  # Source files for frontend assets
│   ├── css/
│   │   └── style.css     # Main CSS file with Tailwind directives
│   └── js/
│       └── main.js       # Main JavaScript entry point
├── ssl/                  # SSL certificates for local development
├── tests/                # Test files
├── package.json          # Node.js dependencies and scripts
├── postcss.config.js     # PostCSS configuration
├── tailwind.config.js    # Tailwind CSS configuration
└── vite.config.js        # Vite configuration
```

## How It Works

- In development mode, Vite serves the assets and provides HMR
- PHP requests are proxied/served by your local PHP server
- In production mode, the built assets are loaded from the `dist` directory
- The PHP code detects whether to use development or production assets

## HTTPS Setup with mkcert

For a better development experience with HTTPS, we use mkcert to generate locally-trusted certificates.

### Automatic Setup (Recommended)

Run the setup script to install mkcert and generate certificates:

```
npm run setup-mkcert
```

This script will:
1. Check if Chocolatey is installed
2. Install mkcert using Chocolatey
3. Install the local CA in the system trust store
4. Create an SSL directory in the project root
5. Generate certificates for localhost and other domains
6. Copy the root CA certificate

After running the script, you'll need to update your hosts file (C:\Windows\System32\drivers\etc\hosts) to include:
```
127.0.0.1 pantrypal.local
```

### Manual Setup

If you prefer to set up mkcert manually:

1. Install Chocolatey (Windows package manager) if you don't have it already:
   ```
   Set-ExecutionPolicy Bypass -Scope Process -Force; [System.Net.ServicePointManager]::SecurityProtocol = [System.Net.ServicePointManager]::SecurityProtocol -bor 3072; iex ((New-Object System.Net.WebClient).DownloadString('https://community.chocolatey.org/install.ps1'))
   ```

2. Install mkcert using Chocolatey:
   ```
   choco install mkcert
   ```

3. Install the local CA in the system trust store:
   ```
   mkcert -install
   ```

4. Create an SSL directory in the project root:
   ```
   mkdir ssl
   ```

5. Generate certificates for localhost and other domains you need:
   ```
   cd ssl
   mkcert localhost 127.0.0.1 ::1 pantrypal.local
   ```

6. Copy the root CA certificate:
   ```
   copy "%USERPROFILE%\AppData\Local\mkcert\rootCA.pem" .
   ```

7. Update your hosts file (C:\Windows\System32\drivers\etc\hosts) to include:
   ```
   127.0.0.1 pantrypal.local
   ```

The certificates will be used by Vite for HTTPS development.
