# App Directory

This directory contains the core application code for PantryPal.

## Structure

- **Models**: Contains data models and business logic
- **Controllers**: Contains controller classes that handle requests and responses
- **Views**: Contains view templates and UI components
  - **Components**: Reusable UI components
  - **Layouts**: Page layout templates
- **Modules**: Contains feature modules that can be plugged into the application
- **Helpers**: Contains utility functions and helper classes
- **Config**: Contains configuration files
- **Database**: Contains database-related code

## Security

- Do not store secrets in this directory or in versioned config files.
- Use environment variables from the project `.env` (see `.env.example` in the root) for API keys and credentials.
- Ensure `.env` is not committed; it is already ignored in `.gitignore`.