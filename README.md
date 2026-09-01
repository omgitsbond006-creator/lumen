# Lumen Capital

A full-stack digital-asset investment platform built in plain PHP + MySQL — user accounts, deposits, plan-based investing with daily ROI, withdrawals, referrals, notifications, support tickets, and a complete admin back office.

Built as an academic capstone project. **No real money, cryptocurrency, or financial instruments are transacted anywhere in this codebase** — deposits and withdrawals are simulated and require manual admin confirmation, exactly like a sandboxed teaching environment should.

---

## 1. Tech stack

- **PHP 8.1+** — plain procedural/functional style, no framework, `PDO` for MySQL with prepared statements everywhere
- **MySQL / MariaDB 10.3+**
- **Vanilla JS** for interactivity (no build step, no npm required to *run* the site)
- **Chart.js, Font Awesome, AOS, Inter/Sora fonts** — all vendored locally under `assets/vendor/`, so the site works **fully offline** with zero external network calls at runtime
- Sessions for auth, `password_hash()`/`password_verify()`, CSRF tokens on every state-changing form, login rate-limiting/lockout

No Composer, no build tooling, no `.env` juggling — unzip it into a web root, import one SQL file, and it runs.

---

## 2. Quick start (XAMPP / WAMP / MAMP)

1. Copy the `lumen-capital` folder into your server's web root (e.g. `htdocs/lumen-capital`).
2. Open **phpMyAdmin**, create nothing manually — just import `database/schema.sql` (it creates the `lumen_capital` database itself and seeds demo data).
3. Open `config/config.php` and confirm the `DB_*` constants match your setup. The defaults (`root` / no password / `localhost`) work out of the box on a stock XAMPP install.
4. Visit `http://localhost/lumen-capital/` in your browser.

That's it — the app auto-detects its own base URL, so it works regardless of what you name the folder.

### Alternative: PHP's built-in server (no Apache needed)

```bash
cd lumen-capital
mysql -u root < database/schema.sql
php -S localhost:8000
```

Then visit `http://localhost:8000/`.

---

## 3. Demo accounts

| Role | Email | Password |
|---|---|---|
| Admin | `admin@lumencapital.test` | `Admin@2026!` |
| Investor (with history) | `demo@lumencapital.test` | `Demo@2026!` |
| Investor (referred by demo) | `sarah@lumencapital.test` | `Sarah@2026!` |

The demo investor accounts come pre-loaded with deposits, an active investment, a completed/matured investment, transaction history, and a pending deposit — so the dashboards look "alive" immediately after import, without needing to manually click through every flow first. The admin account starts with a pending deposit and a pending withdrawal already queued up in the review screens.

---

## 4. Feature tour

### Public site
- Marketing homepage with live, database-driven stats (not hardcoded numbers)
- Plan comparison page with an interactive return calculator
- About, FAQ (with risk disclosure), and a contact form that lands in the admin support inbox

### Investor dashboard
- Register / login / logout, forgot-password flow (see note on email below)
- Deposit funds against admin-configured crypto/bank addresses, with a pending-approval queue
- Browse and invest in plans, with a live return preview before confirming
- **My Investments**: live countdown timers to maturity, progress bars, paid-out tracking
- Withdraw funds (reserved from balance immediately, released on admin approval)
- Full personal transaction ledger with filtering and pagination
- Referral program: unique referral link, referred-user list, bonus tracking
- In-app notifications, a support ticket thread, and profile/password management

### Admin dashboard
- Overview with live charts (14-day deposits vs. withdrawals, plan popularity) and platform-wide KPIs
- **User management**: search/filter, suspend/reactivate, promote to admin, manually credit/debit balances with an audit reason, per-user activity view
- **Plan management**: full CRUD on investment plans (ROI %, duration, min/max, featured flag, visibility)
- **Deposit & withdrawal queues**: approve/reject with one click; approving a deposit auto-credits the referring user's bonus on that investor's *first* approved deposit
- **Investment oversight**: force-cancel an active position with an automatic principal refund
- **Payment methods**: manage the crypto/bank addresses shown to investors
- **Support inbox**: reply to and close tickets
- **Activity log**: a full audit trail of every admin action
- **Site settings**: referral %, withdrawal fee %, deposit/withdrawal minimums, maintenance flag
- **Payout engine**: a one-click "Run Payouts Now" button (see below)

---

## 5. The payout engine (daily ROI + maturity)

The core simulation logic lives in `cron/process_payouts.php`. On each run it:

1. Credits daily ROI to every active investment whose `next_payout_at` is due.
2. Matures any investment whose term has ended — returning the full principal (plus any profit not yet paid out) to the investor's balance — and notifies them.

**In production** you'd point a real cron job at it:

```
* * * * * php /path/to/lumen-capital/cron/process_payouts.php >> /path/to/logfile.log 2>&1
```

**For grading/demo purposes**, there's no need to set up cron at all — log in as admin and click **"Run Payouts Now"** on the Overview page (or `admin/run-payouts.php`) to process everything that's currently due, on demand.

---

## 6. A note on email

This project doesn't assume you have an SMTP server configured. Instead of failing silently or requiring mail setup, every "email" (welcome messages, password reset links) is logged to a `sent_emails` table and surfaced in two places:

- The password reset page shows the reset link directly on screen (clearly labeled as demo behavior) instead of just saying "check your email."
- Admins can review the full outbound log under **Site Settings → Outbound Email Log**.

---

## 7. Project structure

```
lumen-capital/
├── admin/              Admin dashboard pages
├── user/                Investor dashboard pages
├── partials/            Shared header/footer/sidebar includes
├── includes/             Core: db connection, auth, CSRF, helpers, bootstrap
├── config/config.php     Database credentials & app constants
├── cron/process_payouts.php   Daily ROI + maturity engine
├── database/schema.sql   Full schema + seed data
├── assets/
│   ├── css/style.css     Design system
│   ├── js/app.js         Shared front-end interactivity
│   └── vendor/           Locally-hosted Chart.js, Font Awesome, AOS, fonts
├── index.php, plans.php, about.php, faq.php, contact.php   Public pages
├── login.php, register.php, logout.php, forgot/reset-password.php
└── README.md
```

---

## 8. Security notes

- Every database query uses parameterized/prepared statements — no string-concatenated SQL.
- Passwords are hashed with `password_hash()` (bcrypt); never stored or logged in plain text.
- Every POST form carries a CSRF token, verified server-side before any state change.
- Sessions are regenerated on login; cookies are `HttpOnly`/`SameSite=Lax`.
- Repeated failed logins temporarily lock an account (5 attempts / 15-minute lockout).
- Role checks (`require_login()` / `require_admin()`) gate every protected page server-side — the UI never relies on hiding a link as its only protection.
- All user-supplied output is escaped with `htmlspecialchars()` before rendering.

---

## 9. Disclaimer

This is a software engineering demonstration, not a financial product. Yields, plans, and "crypto" branding are illustrative. Do not connect this codebase to a real payment processor or represent it as a real investment opportunity.
