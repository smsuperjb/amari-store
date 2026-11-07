# Amari store

The online store for amariaestheticsph.com, served from `public_html/store` on Hostinger.

Pushes to `main` deploy automatically: GitHub Actions uploads changed files over FTP (`.github/workflows/deploy.yml`). FTP login is stored in the repo's Actions secrets/variables `FTP_SERVER`, `FTP_USERNAME`, `FTP_PASSWORD` (optional `FTP_DIR`, default `store/`, since the FTP login starts inside public_html).

## What is not in this repo

- **Database credentials.** `includes/config.php` loads them from `/shopapay_api/config/database.php`, which sits outside `public_html/store` and is not tracked here.
- **Product, banner and category images.** These are uploaded by the admin into `/shopapay_api/uploads/`, also outside this folder.
- **`debug.php`.** It printed server paths and database rows to anyone who opened it, so it is ignored and should not be on the live site.

## shopapay_api (app backend + admin)

Lives at `store/shopapay_api` (moved from `public_html/shopapay_api` on 2026-11-07), so it is served at `https://amariaestheticsph.com/store/shopapay_api/`. The store reads its database login and photos from here.

Server-only files (not in GitHub, upload by hand):
- `shopapay_api/config/database.php` (template: `database.example.php`)
- `shopapay_api/config/secrets.php` holds JWT_SECRET, RESET_SECRET, ADMIN_SECRET, SMTP_PASS (template: `secrets.example.php`)
- `shopapay_api/uploads/` photos
