# PantryPal Core

A vanilla PHP project with Vite for automatic refresh and Tailwind CSS for styling.

## Features

- Hot Module Replacement (HMR) with Vite
- Tailwind CSS for utility-first styling
- PHP for backend logic
- Automatic browser refresh on code changes

## Setup

1. Clone the repository
2. Install dependencies:
   ```
   npm install
   ```
3. Start the development server:
   ```
   npm run dev
   ```
4. Make sure your PHP server (e.g., XAMPP, WAMP) is running

## Development

- Edit PHP files in the root directory
- Edit JavaScript files in the `src/js` directory
- Edit CSS files in the `src/css` directory
- Tailwind CSS classes can be used directly in your HTML

## Building for Production

To build the project for production:

```
npm run build
```

This will create a `dist` directory with optimized assets. The PHP files will automatically load the correct assets in production mode.

## Project Structure

```
pantrypal_core/
├── dist/                  # Production build output (created after build)
├── node_modules/          # Node.js dependencies (created after npm install)
├── src/
│   ├── css/
│   │   └── style.css      # Main CSS file with Tailwind directives
│   └── js/
│       └── main.js        # Main JavaScript entry point
├── index.php              # Main PHP file
├── package.json           # Node.js dependencies and scripts
├── postcss.config.js      # PostCSS configuration
├── tailwind.config.js     # Tailwind CSS configuration
└── vite.config.js         # Vite configuration
```

## How It Works

- In development mode, Vite serves the assets and provides HMR
- PHP requests are proxied to your PHP server
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
