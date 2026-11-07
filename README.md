# Amari store

The online store for amariaestheticsph.com, served from `public_html/store` on Hostinger.

Pushes to `main` deploy automatically: GitHub Actions uploads changed files over FTP (`.github/workflows/deploy.yml`). FTP login is stored in the repo's Actions secrets/variables `FTP_SERVER`, `FTP_USERNAME`, `FTP_PASSWORD` (optional `FTP_DIR`, default `store/`, since the FTP login starts inside public_html).

## What is not in this repo

- **Database credentials.** `includes/config.php` loads them from `/shopapay_api/config/database.php`, which sits outside `public_html/store` and is not tracked here.
- **Product, banner and category images.** These are uploaded by the admin into `/shopapay_api/uploads/`, also outside this folder.
- **`debug.php`.** It printed server paths and database rows to anyone who opened it, so it is ignored and should not be on the live site.
