# Agriculture Information System

PHP 8.1+ / Supabase PostgreSQL / PDO / Bootstrap 5

## Included
- Login and role-based access (Administrator, Staff)
- Dashboard with live database summaries
- CRUD modules: Farmers, Farms, Crops, Planting, Harvest
- Search and pagination for record lists
- Relational dropdowns and JOIN-based displays
- Reports for farms, crops, planting status, harvest, quantity and estimated value
- PostgreSQL constraints, lookup tables, and audit timestamps

## 1. Requirements
- PHP 8.1 or newer
- PHP extensions: `pdo`, `pdo_pgsql`
- Supabase project with PostgreSQL access
- Apache/Nginx or PHP's built-in development server

## 2. Create the Supabase database
1. Open your Supabase project.
2. Go to **SQL Editor**.
3. Run `database/schema.sql`.
4. Run `database/seed.sql`.
5. In Supabase project settings, open Database connection details and copy the direct connection values.

The schema is intended to be run in a project database you control. It creates an `app_users` table for this PHP application's session-based login. It does not use Supabase Auth.

## 3. Configure environment
Copy `.env.example` to `.env` and enter your Supabase PostgreSQL connection values.

For Supabase, use the connection details shown in your project dashboard. Keep SSL enabled. Do not commit `.env` to GitHub.

## 4. Create the first administrator
From the project directory, run:

```bash
php setup_admin.php
```

It prompts for the administrator's name, email, and password, then inserts a password hash. Run this from a terminal, not a browser.

## 5. Run locally
Enable `pdo_pgsql` in your PHP configuration, then:

```bash
php -S localhost:8000
```

Open `http://localhost:8000`.

If using Apache, point the document root to this project directory for local development. For deployment, set the web root to the project directory and deny public access to `.env`, `database/`, and `setup_admin.php`, or move those files outside the document root.

## 6. Roles
- **Administrator:** all modules, create/update/delete, reports, user administration is not included in this starter.
- **Staff:** view and create/update records; deletion is restricted.

## Notes
- The supplied assignment requires Supabase PostgreSQL. This project connects directly through PDO PostgreSQL rather than exposing a Supabase service-role key in browser JavaScript.
- The app uses a separate `app_users` table. Passwords are stored with PHP `password_hash()`.
- Before final submission, test all forms against your actual Supabase project and capture screenshots of each module and report.
