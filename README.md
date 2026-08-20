# Book & Board (B&B)

Responsive web application for a UK high-street travel agency (trading since 1975). Built with **PHP, MySQL, vanilla JavaScript and CSS only** - no frameworks or third-party libraries.

Two independently runnable versions live in this repository:


| Directory | Phase                | Scope                                                                     |
| --------- | -------------------- | ------------------------------------------------------------------------- |
| `v1/`     | Basic prototype      | Informational site + staff offer management (**FR1–FR5**)                 |
| `v2/`     | Extended application | Everything in Version 1, plus customer accounts and search (**FR1–FR12**) |


`v2/` does not remove or break any `v1/` feature. Later phases (card payments, third-party data, social media, advertising, internal reporting) are **out of scope**.

## How the two versions relate

1. Version 1 is the informational website: offers, five locations (four branches + London HQ), a contact form, and a guarded admin area for London staff.
2. Version 2 started as a copy of Version 1. It adds registration, login, a customer dashboard, searchable flight / hotel inventory, and package requests that staff can confirm.

Both versions use the **same MySQL database**. Version 2’s schema is a superset (`users`, `bookings`, `flights`, `hotels` in addition to `branches`, `offers`, `admins`). Create one empty database in CloudPanel, then run setup **once**.

## Database setup

1. In CloudPanel, create a MySQL database and a user with full rights on that database.
2. Open **Version 2** in the browser: `https://yourdomain.com/v2/setup.php`
3. Enter host (`127.0.0.1`), port (`3306`), database name, user and password.
4. Leave **Create tables and load demo data** ticked and submit.

That writes `includes/config.local.php` (and the same file into `v1/` if both folders sit next to each other), creates the tables, loads the demo accounts, then **locks** so the page cannot be run again.

If you only uploaded Version 1, use `/v1/setup.php` instead.

To reinstall, delete `includes/setup.lock` and `includes/config.local.php` and visit setup again (this drops the Book & Board tables).

phpMyAdmin import of `sql/schema.sql` then `sql/seed.sql` is still possible if you prefer not to use the installer.

The `includes/` folder must be writable so setup can save `config.local.php`.

## Running locally

From the repository root:

```bash
php -S localhost:8001
```

Then open `http://localhost:8001/` and choose Version 1 or Version 2. That is the same layout as the live host (`/v1/` and `/v2/` next to each other).

On first run, open **Version 2** setup (`/v2/setup.php`) to connect MySQL and load demo data. On localhost the form is prefilled for the local Docker MySQL (`127.0.0.1`, port `3308`, database `bookandboard`, user `root`, password `root`).

On the server, upload this repository so `index.php`, `v1/` and `v2/` sit in the web root. PHP 8.0+ with the PDO MySQL extension is required.

- Chooser: `/`
- Version 1: `/v1/`
- Version 2: `/v2/`
- Staff: `/v1/admin/admin-login.php` or `/v2/admin/admin-login.php`

Customer pages sit in the version root (`index.php`, `login.php`, …). Staff pages stay under `/admin`.

## Test accounts


| Role     | Username / email       | Password     |
| -------- | ---------------------- | ------------ |
| Staff    | `admin`                | `admin123`   |
| Customer | `info@oscarmini.com`   | `demo123`    |
| Customer | `jane.doe@example.com` | `traveller1` |


Staff login uses a **username**, not an email. The customer Log in page is for customers only; there is a Staff login link in the footer. Passwords are stored only as `password_hash()` values.

## Version 1 — what to demonstrate (FR1–FR5)


| Code | What you can demonstrate                                                          |
| ---- | --------------------------------------------------------------------------------- |
| FR1  | Home page bestsellers + current offers; `/offers.php` lists current packages only |
| FR2  | `/branches.php` shows all five locations                                          |
| FR3  | `/contact.php` form + branch telephone/email                                      |
| FR4  | Mobile-first CSS; hamburger nav below 768px; 1→2→3 column grids                   |
| FR5  | `/admin/admin-login.php` then add / edit / delete offers                          |


Seed includes five locations, ten current offers (four bestsellers) plus two expired rows that must not appear on customer pages, and the staff account above.

## Version 2 — what was added (FR6–FR12)


| Code | Page                  | What you can demonstrate                                                                             |
| ---- | --------------------- | ---------------------------------------------------------------------------------------------------- |
| FR6  | `/register.php`       | Create an account; duplicate email rejected; password hashed                                         |
| FR7  | `/login.php`          | `password_verify()`; generic error on failure                                                        |
| FR8  | `/dashboard.php`      | Stored name, email, phone, address                                                                   |
| FR9  | `/dashboard.php`      | Packages on file; **request** an offer then staff **confirm** / **mark paid**                        |
| FR10 | `/search-flights.php` | Origin, destination, date                                                                            |
| FR11 | `/search-hotels.php`  | City, optional dates                                                                                 |
| FR12 | both search pages     | Max price, stops / stars, sort by price or time / rating (applied in SQL; toolbar submits on change) |


Logged-out visitors can search flights and hotels; they see Log in / Register. Signed-in customers see Account and Log out instead. Requesting a package still requires an account. All Version 1 pages remain.

Request a package from an offer (guests are sent to log in first). It appears on the account as **Requested**. Staff open `/admin/manage-bookings.php`, confirm it, then optionally **Mark paid** (payment in branch — no card checkout).

### Demo searches that return rows

- Flights: origin `London`, destination `Paris`, date `2026-08-20` — then refine on the results (Direct only, Under £100, Under 3 hours)
- Hotels: city `Barcelona` — then refine on the results (Under £150, 3 stars and above)
- Hotels: city `Paris` (two properties)

Empty-state demo: search flights London → Tokyo on `2026-08-20` (no row that day).

## Security (both versions)

- All SQL goes through one PDO layer (`includes/db.php`) with prepared statements.
- Passwords use `password_hash()` / `password_verify()`.
- All output is passed through `escape()` (`htmlspecialchars`).
- Admin pages and the customer dashboard are session-guarded.
- Every form is validated on the server; JavaScript validation is optional convenience only.

