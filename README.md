# EK Yarn Co. Storefront

A Laravel e-commerce application for handcrafted crochet and knitted products — amigurumi, keychains, coasters,
scrunchies, wallets, headbands, sweaters, shawls and custom gifts. The shop owner runs almost everything from the
admin dashboard: products, orders, page content, menus, invoices and shop features.

## Tech Stack

| Layer | Technology |
|-------|------------|
| Backend | Laravel 12, PHP 8.2+ |
| Frontend | Blade, Tailwind CSS, Alpine.js, Vite |
| Database | MySQL 8 |
| Auth | Laravel Breeze + Spatie Permission + Sanctum (API) |
| Payments | Stripe (PaymentIntents) + Cash on Delivery |
| PDF | DomPDF (invoices) |
| API docs | Scribe |

## Features

### Customer storefront
- Home, About, Contact and CMS-built pages (including shipping / returns / care policies)
- Product catalog with search, filters, sorting and pagination; product detail with variants, specs and related items
- Shopping cart (guest and logged-in) with AJAX quantity updates and a free-shipping progress bar
- Checkout with Cash on Delivery and Stripe card payment, optional gift wrap / gift message / hidden prices
- Accounts: registration, login, profile, addresses, order history, invoice PDF download
- Product reviews and ratings, wishlist (merged on login), recently viewed, low-stock badges, back-in-stock email alerts
- Custom order requests with reference photo, order tracking at `/track-order`, WhatsApp chat button
- Newsletter pop-up and an optional **promo pop-up** (sales, seasons, announcements)
- Mobile-first design with a bottom tab bar on phones

### Admin panel (`/admin`)
- **Dashboard** for today: sales and orders, a "needs your attention" list, recent orders, low stock, auto-refresh
- **Statistics** for analysis: period picker with comparison to the previous period, charts, CSV export
- Products (with images and variants), categories, orders with status updates and invoices
- Customers, including guest buyers, with a CSV export for people who opted in to offers
- Messages inbox, reviews moderation, custom orders, coupons, newsletter subscribers
- **CMS Pages**, **Navigation & Footer**, **Invoice** designer, **Modules**, **Settings**
- Sticky sidebar that stays in view while scrolling

## Dashboard → Modules

`Admin → Modules` switches shop features on or off. Each module with options has an **Edit** button that opens its own
screen for text, pictures and settings; modules with nothing to tune show only the switch. Settings can be prepared while a
module is off. The **Save switches** button is at the top right and stays visible while scrolling.

Available modules include: accepting orders (with a closed message), guest checkout, payment methods (names and
descriptions are editable), gift options, coupons, low-stock badges (limit is editable), reviews, wishlist, newsletter
pop-up, **promo pop-up**, back-in-stock alerts, recently viewed, custom orders, order tracking, WhatsApp and customer invoices.

### Promo pop-up
Shown to visitors when a page loads. Configure it entirely from `Admin → Modules → Promo pop-up`:
title, small label, message, picture, button text and link, delay, how often a visitor sees it (every load, once per
visit, once a day, or once until the message changes), where it appears (home only or every page) and optional start / end dates.
It never stacks on top of the newsletter pop-up.

### Adding a module or a setting
Describe it in `app/Support/Modules/ModuleRegistry.php` (field kinds: `toggle`, `number`, `text`, `textarea`, `select`,
`date`, `image`), then read it with `module_enabled('key')` and `module_value('key', 'field')`. Protect routes with
`->middleware('module:key')`. The edit screen and validation follow the registry automatically.

## CMS / Page Builder

`Admin → CMS Pages` edits every storefront page and creates new pages served at `/p/{slug}`.

- Pages are an ordered list of sections (hero, icon cards, stats, text, image + text, product grid, collections,
  testimonials, FAQ, gallery, call-to-action, divider, info card, quote). Reorder, hide, duplicate, delete, restyle and
  upload images from the editor.
- Until a layout is saved, a page shows its built-in design (`app/Support/Cms/PageDefaults.php`); **Reset to original** returns to it.
- New block type: describe it in `app/Support/Cms/SectionSchema.php` and add `resources/views/components/cms/{type}.blade.php`.
- `Admin → Navigation & Footer` controls the header menu, footer links and text, the top-bar message and social links.
- Uploaded images need the storage link: `php artisan storage:link`.

## Invoices

`Admin → Invoice` controls the PDF invoice: company details, tax number, logo, accent colour, paper size, number prefix,
show/hide switches, wording, bank details, terms and the copyright line. Preview with a sample or the latest order.
Rendering lives in `app/Services/InvoiceBuilder.php` and `resources/views/invoices/order.blade.php`.

## Brand Theme

- Warm Oat `#F5F5DC` · Sage Green `#8A9A5B` · Terracotta `#E2725B` · Charcoal `#36454F`

## Quick Start

```bash
composer install
cp .env.example .env
php artisan key:generate
# Set SEED_ADMIN_PASSWORD and SEED_CUSTOMER_PASSWORD in .env to strong values of your own BEFORE seeding
php artisan migrate:fresh --seed
php artisan storage:link
npm install
npm run dev        # or: npm run build
php artisan serve
```

Open http://127.0.0.1:8000. The admin panel is at `/admin` (sign in at `/login` with the admin account created by the seeder).

> If styles do not load during `npm run dev`, make sure Vite and the page use the same host (for example run
> `npm run dev -- --host 127.0.0.1`).

## Environment Variables

Copy `.env.example` to `.env` and fill in your own values. **Never commit `.env` or real keys.**

```env
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

# Stripe (leave empty to keep card payment off)
STRIPE_KEY=
STRIPE_SECRET=
STRIPE_WEBHOOK_SECRET=

# Mail (use a real SMTP service in production)
MAIL_MAILER=log

SHOP_NAME="EK Yarn Co."
SHOP_CURRENCY=PKR
SHOP_SHIPPING_FLAT=250
SHOP_GIFT_WRAP_FEE=150
SHOP_WHATSAPP=

# Passwords used only by the seeder — choose your own
SEED_ADMIN_PASSWORD=
SEED_CUSTOMER_PASSWORD=

# Prefer redis when available; file is a solid production fallback
# SESSION_DRIVER=redis
# CACHE_STORE=redis
# SESSION_ENCRYPT=true
# SANCTUM_TOKEN_EXPIRATION=10080
```

## REST API (`/api/v1`)

The Blade storefront is unchanged. Mobile and external clients can use the versioned JSON API alongside it.

**Base URL:** `/api/v1`

```http
POST /api/v1/auth/login
Content-Type: application/json

{"email":"you@example.com","password":"your-password"}
```

Send the returned token on protected routes:

```http
Authorization: Bearer {token}
Accept: application/json
```

Guests receive a `guest_cart_token` when adding to the cart (send it as `X-Guest-Cart-Token`) and a
`guest_access_token` after checkout (send it as `X-Guest-Order-Token`).

| Group | Prefix | Auth |
|-------|--------|------|
| Catalog | `/products`, `/categories`, `/pages/{slug}`, `/shop` | Public |
| Auth | `/auth/register`, `/auth/login`, `/auth/user` | Mixed |
| Cart / Checkout | `/cart`, `/checkout` | Guest token or Bearer |
| Account | `/profile`, `/addresses`, `/orders` | Bearer + verified email |
| Admin | `/admin/*` | Bearer + admin role |

Run `php artisan route:list --path=api` for the full route map. Generate the docs with `php artisan scribe:generate`
and open `/docs`.

## Running Tests

```bash
php artisan test
php artisan test --filter=ModulesAndInvoiceTest   # modules, promo pop-up, invoices
php artisan test --filter=Api                     # REST API
```

## Production Checklist

- `APP_ENV=production`, `APP_DEBUG=false`, HTTPS enabled
- Set strong passwords for the seeded accounts, or create your own admin and remove the demo customer
- MySQL configured; run `php artisan migrate --force`
- Real SMTP configured (`MAIL_MAILER` is `log` by default, which sends nothing) and a queue worker running: `php artisan queue:work`
- Stripe live keys and webhook (`POST /webhooks/stripe`) — or switch the Card payment module off in `Admin → Modules`
- Real phone, email and address under `Admin → Settings` (the seed data is placeholder text)
- `php artisan storage:link`, then `npm ci && npm run build`
- Prefer `CACHE_STORE=redis` and `SESSION_DRIVER=redis` (or `file`); set `SESSION_ENCRYPT=true`
- Cache framework files:
  ```bash
  php artisan config:cache
  php artisan route:cache
  php artisan view:cache
  php artisan event:cache
  ```
- Confirm security headers (CSP, HSTS) and Sanctum token expiry (`SANCTUM_TOKEN_EXPIRATION`, default 7 days)
