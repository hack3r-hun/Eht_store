# EK Yarn Co. Storefront

A Laravel e-commerce application for handcrafted crochet and knitted products, including amigurumi, keychains, coasters, scrunchies, wallets, headbands, sweaters, shawls, and custom gifts.

## Tech Stack

| Layer | Technology |
|-------|------------|
| Backend | Laravel 12, PHP 8.2+ |
| Frontend | Blade, Tailwind CSS, Alpine.js, Vite |
| Database | MySQL 8 |
| Auth | Laravel Breeze + Spatie Permission |
| Payments | Stripe (PaymentIntents) + Cash on Delivery |
| PDF | DomPDF (invoices) |

## Features

### Customer Storefront
- Home, About Us, Contact Us
- Product catalog with search, filters, sorting, pagination
- Product detail with specs and related items
- Shopping cart with guest and logged-in support
- Checkout with COD and Stripe card payment
- User registration, login, profile, addresses, order history
- Invoice PDF download

### Admin Panel (`/admin`)
- Dashboard with revenue, orders, low-stock alerts
- Product CRUD with image upload
- Category CRUD with nested collections
- Order management with status updates and invoices
- Customer list
- Contact message inbox and replies
- CMS page editor for Home/About content
- Shop settings for name, tax, shipping, and contact info

## Brand Theme

- Warm Oat: `#F5F5DC`
- Sage Green: `#8A9A5B`
- Terracotta: `#E2725B`
- Charcoal: `#36454F`

## Quick Start

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed
php artisan storage:link
npm install
npm run build
php artisan serve
```

Visit: http://127.0.0.1:8000

## Environment Variables

```env
STRIPE_KEY=pk_test_...
STRIPE_SECRET=sk_test_...
STRIPE_WEBHOOK_SECRET=whsec_...

SHOP_NAME="EK Yarn Co."
SHOP_CURRENCY=PKR
SHOP_SHIPPING_FLAT=250
```

## Demo Accounts

| Role | Email | Password |
|------|-------|----------|
| Admin | admin@ekyarnco.local | password |
| Customer | customer@ekyarnco.local | password |

## Running Tests

```bash
php artisan test
```

## Production Checklist

- Set `APP_ENV=production`, `APP_DEBUG=false`
- Configure MySQL database
- Set Stripe live keys and webhook endpoint: `POST /webhooks/stripe`
- Configure SMTP for order/contact emails
- Run `php artisan config:cache` and `php artisan route:cache`
- Set up queue worker for mail: `php artisan queue:work`
- Enable HTTPS
