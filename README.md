# 3Z Shop API

Laravel backend for the [3Z Shop storefront](https://github.com/Zakaria-ocd/ecom-project). It exposes JSON endpoints for buyer authentication, product and category data, product choices and images, carts, and orders.

For the combined project overview, frontend integration, and current implementation notes, see the [storefront README](https://github.com/Zakaria-ocd/ecom-project#readme).

## Stack

- PHP 8.2+
- Laravel 11
- Laravel Sanctum for API tokens
- Eloquent ORM
- SQLite by default for local development

## API areas

The route declarations are in `routes/api.php`.

- Buyer registration, login, current-user, and logout routes.
- Product catalog, category, configurable product-choice, and image routes.
- Authenticated cart read/add/update/remove/merge routes.
- Authenticated buyer cart and order create/list/detail routes.
- Admin-only catalog, user, and order management routes, plus dashboard statistics.

Protected API requests use a Sanctum bearer token. API authentication failures return JSON `401` responses rather than redirecting to a web login route. Admin endpoints additionally require the authenticated user's `admin` role.

Order totals and delivery/payment statuses are persisted using the migration-defined `total_amount`, `delivery_status`, and `payment_status` columns. The API also exposes `total_price` and `status` aliases for existing storefront consumers. Product prices and stock are held on product choice values, not on the products table.

## Local setup

### Prerequisites

- PHP 8.2 or newer with PDO SQLite enabled.
- Composer.

From PowerShell:

```powershell
composer install
Copy-Item .env.example .env
if (-not (Test-Path .\database\database.sqlite)) {
  New-Item -ItemType File -Path .\database\database.sqlite | Out-Null
}
php artisan key:generate
```

In `.env`, use the default SQLite connection and set the local application URL:

```dotenv
APP_URL=http://localhost:8000
DB_CONNECTION=sqlite
```

Seed a complete local demo catalog and accounts, then start Laravel:

```powershell
php artisan migrate:fresh --seed
php artisan serve --host=127.0.0.1 --port=8000
```

The demo accounts are:

| Role | Email | Password |
| --- | --- | --- |
| Admin | `admin@example.com` | `admin123` |
| Buyer | `buyer@example.com` | `password123` |

`php artisan db:seed` can be run again to refresh the demo fixtures without adding duplicate records. Do not use the demo credentials outside local development.

Uploaded product and user images are stored through Laravel's configured local filesystem and served by API image routes.

## Testing

```powershell
php artisan test
vendor\bin\pint --test
```
