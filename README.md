# Apoint Translations

Apoint Translations is a multilingual translation and localization platform for individuals and businesses worldwide.

The platform will allow customers to:
- Explore translation and localization services.
- Request instant or manually reviewed quotations.
- Upload documents securely.
- Place and track translation orders.
- Make payments through supported payment providers.
- Access invoices and order updates.

## Technology Stack

- **Backend:** Laravel 13
- **Frontend:** React, TypeScript, Inertia.js
- **Styling:** Tailwind CSS
- **Database:** MySQL
- **Build Tool:** Vite

## Development Setup

### Requirements
- PHP 8.3+
- Composer
- Node.js and npm
- MySQL

### Installation

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Configure your database credentials in `.env`, then run:

```bash
php artisan migrate
```

### Run locally

Start the Laravel server:

```bash
php artisan serve
```

In another terminal, start Vite:

```bash
npm run dev
```

Visit http://127.0.0.1:8000.

## Project Status

Initial application foundation established. Feature development is in progress.
