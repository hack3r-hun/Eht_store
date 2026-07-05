# EK Yarn Co. — End-to-End QA Checklist

Run through this top-to-bottom before every release. Baseline: `php artisan test`
(65 tests) must pass before manual QA starts.

**Test accounts (local/seeded):** Admin `admin@ekyarnco.local` / `password` ·
Customer `customer@ekyarnco.local` / `password`

**Local setup:** `docker compose up -d` → `php artisan serve` → http://127.0.0.1:8000
Emails go to `storage/logs/laravel.log` (MAIL_MAILER=log).

---

## 0. Automated baseline

- [ ] `php artisan test` — all green
- [ ] `npm run build` — completes without errors
- [ ] `grep -riE "electromart|electronic" app/ resources/ config/ database/` — no results

## 1. Storefront — guest browsing

- [ ] Home: hero ("Handcrafted Crochet & Knit Pieces Made With Care"), featured picks, new arrivals, category tiles all render with images
- [ ] Brand check on every page: "EK Yarn Co." in header/footer/tab title; palette is oat background, terracotta buttons, sage accents, charcoal text
- [ ] Products page: all 16 seeded products listed with prices in Rs.
- [ ] Search: "bunny" finds Mini Bunny Amigurumi; gibberish shows empty state
- [ ] Category filter: pick "Amigurumi" → only 3 amigurumi products
- [ ] Price filter and sorting (price low→high, newest) reorder correctly
- [ ] Pagination works if page size < product count
- [ ] Product detail: images, price + sale price strikethrough, specs (Brand: EK Yarn Co., Origin: Karachi), related items, quantity selector
- [ ] Out-of-stock / low-stock display (set a product stock to 0 in admin, verify storefront)
- [ ] About page: EK Yarn Co. story content renders
- [ ] Contact page: form submits with valid data; validation errors on empty/invalid email; message appears in admin inbox

## 2. Cart

- [ ] Guest: add to cart from listing and from detail page (with quantity > 1)
- [ ] Cart badge count updates in header
- [ ] Update quantity and remove line items; totals recalc
- [ ] Shipping: flat Rs. 250 applied; verify subtotal + shipping = total (tax rate 0)
- [ ] Cart persists across page reloads (session)
- [ ] Guest cart merges into account cart after login

## 3. Auth

- [ ] Register new account → OTP email in `laravel.log` → verify with code
- [ ] Wrong OTP rejected; resend OTP works
- [ ] Login / logout; wrong password shows error, no user enumeration
- [ ] Password reset flow (link lands in log)
- [ ] Profile: update name/email; delete account requires password
- [ ] Addresses: add / edit / delete shipping address

## 4. Checkout & orders

- [ ] COD checkout as logged-in customer: address form, order summary correct, order placed → success page with order number
- [ ] Order confirmation email in log
- [ ] Stock decremented after order (check product in admin)
- [ ] Stripe (test keys, card `4242 4242 4242 4242`): payment succeeds → order marked paid
- [ ] Stripe declined card (`4000 0000 0000 0002`) shows error, no order marked paid
- [ ] Order history: order listed with correct status/total
- [ ] Invoice PDF downloads and shows EK Yarn Co. branding + correct line items
- [ ] Guest checkout (if enabled) or proper redirect to login

## 5. Admin panel (`/admin`)

- [ ] Customer account gets 403 on `/admin`; guest is redirected to login
- [ ] Dashboard: revenue, order count, low-stock alerts reflect real data
- [ ] Product CRUD: create with image upload, edit, deactivate; slug/SKU handling
- [ ] Product archive + bulk actions work (recent feature); removing an image from edit form does NOT delete the product (regression check)
- [ ] Category CRUD incl. nested child collections
- [ ] Orders: change status (pending → processing → shipped → delivered), status email if applicable, view invoice
- [ ] Customers list shows registered users
- [ ] Contact inbox: message from step 1 visible; reply sends email (check log)
- [ ] CMS pages: edit Home hero text / About content → changes appear on storefront
- [ ] Shop settings: change shop name/contact → reflected in storefront header/footer

## 6. Cross-cutting

- [ ] 404 page for bad URLs; no stack traces leak with APP_DEBUG=false
- [ ] CSRF: forms fail gracefully after session expiry
- [ ] Mobile viewport (375px): nav collapses, cards stack, checkout usable
- [ ] No mixed-content or console errors in browser dev tools
- [ ] Images: all Unsplash placeholders load; admin-uploaded image renders via storage link

## 7. Production (Railway) smoke test — after each deploy

- [ ] Home, products, product detail, cart, login all load over HTTPS
- [ ] Session survives refresh (no random logout)
- [ ] Place one COD test order end-to-end
- [ ] Admin login works; dashboard loads
- [ ] Upload a product image → still there after a redeploy (Volume mounted)
- [ ] `RUN_SEEDER=false` after first deploy (or data gets re-seeded)
- [ ] Demo passwords changed on production
- [ ] Stripe webhook endpoint reachable: `https://YOUR-APP.up.railway.app/webhooks/stripe`
- [ ] OTP/order emails actually deliver (SMTP configured)
