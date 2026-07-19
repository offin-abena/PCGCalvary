# PCG Calvary Church Management System (Laravel)

Laravel 12 port of the church management application, using the existing `church_management` MySQL database.

## Requirements

- PHP 8.2+
- MySQL (XAMPP)
- Composer

## Setup

1. **Configure environment** — edit `.env`:
   - `APP_URL=http://localhost/church_laravel/public` (or your URL)
   - `DB_DATABASE=church_management`
   - `DB_USERNAME=root`
   - `DB_PASSWORD=` (your MySQL password)

2. **Install dependencies** (if not already done):
   ```bash
   composer install
   ```

3. **Ensure uploads exist**:
   - Uploads are stored in `storage/app/uploads/`
   - Assets are in `public/assets/`

4. **Run the app**:

   **XAMPP Apache:** open `http://localhost/church_laravel/public/login`

   **Built-in server:**
   ```bash
   php artisan serve
   ```
   Then open `http://127.0.0.1:8000/login`

## Architecture

- **Controllers:** `app/Http/Controllers/` (ported from original app)
- **Models/Services:** `app/Models/`, `app/Services/` (PDO-based, same as original)
- **Views:** `resources/views/` (PHP templates)
- **Routes:** `routes/church_definitions.php` (143 routes, registered by `ChurchServiceProvider`)
- **Auth:** Session-based with existing `users` table (`password_hash` column)
- **Config:** `config/church.php`, `config/modules.php`, etc.

## Database

Uses the existing `church_management` database. Schema dump: `database/schema/church_management.sql`.

Laravel's default migrations (users, cache, jobs) are not required for the church app tables.

## SMS (optional)

Set in `.env`:
```
SMS_PROVIDER=wigal
SMS_ENABLED=true
WIGAL_USERNAME=
WIGAL_API_KEY=
WIGAL_SENDER_ID=CHURCH
```

## Notes

- Helper functions are prefixed where they conflict with Laravel (`church_asset`, `church_csrf_field`, `church_url`).
- Original app remains at `C:\xampp\htdocs\church` unchanged.
