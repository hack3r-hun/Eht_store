# Deploy EK Yarn Co. on Railway

This guide deploys the full Laravel app on Railway: storefront, admin panel, queue worker, scheduled tasks, and uploaded media support.

## 1. Push Code To GitHub

```bash
git init
git add .
git commit -m "Prepare EK Yarn Co. for Railway"
git branch -M main
git remote add origin https://github.com/YOUR_USERNAME/ek-yarn-co.git
git push -u origin main
```

## 2. Create Railway Project

1. Open [railway.app](https://railway.app) and create a new project.
2. Choose **Deploy from GitHub repo**.
3. Select this repository.
4. Railway will detect the `Dockerfile`.

## 3. Add MySQL

Add a Railway MySQL database and set these variables on the web service:

| Variable | Value |
|----------|-------|
| `DB_CONNECTION` | `mysql` |
| `DB_HOST` | `${{MySQL.MYSQLHOST}}` |
| `DB_PORT` | `${{MySQL.MYSQLPORT}}` |
| `DB_DATABASE` | `${{MySQL.MYSQLDATABASE}}` |
| `DB_USERNAME` | `${{MySQL.MYSQLUSER}}` |
| `DB_PASSWORD` | `${{MySQL.MYSQLPASSWORD}}` |

## 4. Required Variables

Generate an app key locally:

```bash
php artisan key:generate --show
```

| Variable | Value |
|----------|-------|
| `APP_NAME` | `EK Yarn Co.` |
| `APP_ENV` | `production` |
| `APP_KEY` | `base64:...` |
| `APP_DEBUG` | `false` |
| `APP_URL` | Your Railway URL |
| `LOG_CHANNEL` | `stderr` |
| `SESSION_DRIVER` | `database` |
| `CACHE_STORE` | `database` |
| `SESSION_SECURE_COOKIE` | `true` |
| `FILESYSTEM_DISK` | `public` |
| `SHOP_NAME` | `EK Yarn Co.` |
| `SHOP_TAGLINE` | `Handcrafted crochet, knitted gifts, and cozy yarn creations` |
| `SHOP_CONTACT_ADDRESS` | `Karachi, Pakistan` |
| `RUN_SEEDER` | `true` on first deploy only |
| `SEED_ADMIN_PASSWORD` | strong password for the admin account (applied on every deploy) |
| `SEED_CUSTOMER_PASSWORD` | strong password for the demo customer account |

After the first successful deploy, set `RUN_SEEDER=false`.

## Optional Services

Configure SMTP variables for OTP, order, and contact emails. Configure `STRIPE_KEY`, `STRIPE_SECRET`, and `STRIPE_WEBHOOK_SECRET` for live card payments.

Webhook URL:

```text
https://YOUR-APP.up.railway.app/webhooks/stripe
```

## Demo Logins

| Role | Email | Password |
|------|-------|----------|
| Admin | `admin@ekyarnco.local` | set via `SEED_ADMIN_PASSWORD` env var |
| Customer | `customer@ekyarnco.local` | set via `SEED_CUSTOMER_PASSWORD` env var |

## Uploaded Images

Admin-uploaded product photos are saved to `storage/app/public`. Railway container disks are wiped on redeploy unless you add a Volume.

Add a Railway Volume mounted at:

```text
/var/www/html/storage/app/public
```

Then keep `FILESYSTEM_DISK=public` and make sure `APP_URL` includes `https://`.

## Troubleshooting

| Issue | Fix |
|-------|-----|
| 500 error | Check deploy logs and confirm `APP_KEY`, `APP_URL`, and `DB_*` are set. |
| Uploaded images missing | Add the Railway Volume, redeploy, then re-upload product photos. |
| Logged out on refresh | Use `SESSION_DRIVER=database`, `SESSION_SECURE_COOKIE=true`, and leave `SESSION_DOMAIN` empty. |
| CSS/JS missing | Redeploy so Docker runs `npm run build`. |
| OTP email not sent | Configure `MAIL_*` SMTP variables. |
