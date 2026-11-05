# Visitor Photo Slideshow

A small, complete web application built with **Core PHP 8.1+, MySQL (PDO), HTML5, CSS3 and JavaScript** for an MCA college assignment.
Visitors enter their name and upload photos. An administrator approves them, and approved photos appear automatically in a full-screen slideshow that can run on a computer or a smart-TV browser.

## Features

**Visitors**
- Enter full name, upload one or many photos (JPG, JPEG, PNG, WEBP, max 5 MB each, up to 6 per upload)
- Drag-and-drop area with image previews and per-file error hints
- Consent checkbox (required)
- Unique **visitor code** (e.g. `VP-7K3M9QXA`) so two people with the same name are never mixed up
- "My Photos" page: enter the code to see all previous photos (pending / approved / rejected) and upload more

**Slideshow** (`slideshow.php`)
- Only admin-approved photos, 5 seconds each, smooth fade, endless loop
- Previous / Next / Play-Pause / Full-screen buttons (keyboard: Left, Right, Space, F)
- Shows visitor name, date and position; progress bar
- Picks up newly approved (or removed) photos automatically every 15 seconds - no reload needed
- Friendly empty screen when no photos are approved

**Admin** (`admin/login.php`)
- Secure login (bcrypt password hash, PHP sessions, brute-force lock-out, CSRF)
- Dashboard: total visitors, total photos, pending, approved, quick approval
- Visitors list with search, visitor details, delete visitor (also removes files)
- Photos gallery with status filter, pagination, large preview, approve / reject / delete

## Technologies
HTML5, CSS3 (custom design system), JavaScript (ES5, no framework), Core PHP 8.1+, MySQL via PDO prepared statements, Bootstrap 5 + Bootstrap Icons (loaded from CDN), XAMPP (Apache + MySQL), phpMyAdmin.

## Requirements
- Windows with **XAMPP** containing PHP 8.1 or newer (extensions `pdo_mysql` and `fileinfo` are enabled by default)
- A modern browser. Internet access is needed for Bootstrap/Bootstrap Icons (CDN) - the layout falls back to plain styling offline.

## Setup on XAMPP

1. Install and start **XAMPP Control Panel**. Start **Apache** and **MySQL**.
2. Copy the project folder to:
   `C:\xampp\htdocs\visitor-photo-slideshow`
   (the file `index.php` must be directly inside that folder).
3. Open **http://localhost/phpmyadmin** -> **Import** tab -> *Choose file* -> select `sql/database.sql` -> **Go**.
   This creates the database `visitor_photo_slideshow`, all tables and the default admin.
4. (Only if your MySQL root user has a password) edit `config/database.php`, **or** copy `.env.example` to `.env` and set `DB_PASS`.
5. Open the site: **http://localhost/visitor-photo-slideshow/**
6. Admin area: **http://localhost/visitor-photo-slideshow/admin/login.php**
7. Slideshow (open on the TV/computer): **http://localhost/visitor-photo-slideshow/slideshow.php**

### Default admin login
| Username | Password |
|----------|----------|
| `admin`  | `Admin@123` |

This is a demo password. To change it, generate a new hash and update the `admins` table in phpMyAdmin:
```php
<?php echo password_hash('YourNewPassword', PASSWORD_DEFAULT);
```

### Showing the slideshow on a smart TV
Find your PC's IP address (`ipconfig`), allow Apache through the Windows firewall, then open
`http://<PC-IP>/visitor-photo-slideshow/slideshow.php` in the TV browser and press the full-screen button.

## Project structure
```
visitor-photo-slideshow/
├── admin/            login, dashboard, visitors, photos, approve_photo, delete_visitor, logout
├── assets/           css/style.css, js/ (upload, slideshow, admin, copy), images/
├── config/           database.php (PDO connection + settings)
├── includes/         header, footer, admin_header, admin_footer, functions
├── sql/              database.sql
├── tests/            MANUAL_TEST_PLAN.md
├── uploads/          stored photos (script execution disabled by .htaccess)
├── index.php  upload.php  my_photos.php  slideshow.php  slideshow_data.php
└── README.md  ASSUMPTIONS.md  AI_LOG.md  ARCHITECTURE.md  .env.example
```

## Troubleshooting
| Problem | Fix |
|---------|-----|
| "Something went wrong" page | MySQL not running, or the SQL file was not imported. Set `APP_DEBUG=true` in `.env` to see the real error. |
| Cannot log in | Re-import `sql/database.sql` (it only inserts the admin if missing) or reset the hash as shown above. |
| Upload says file too large | Max 5 MB per photo and 6 photos per submit. XAMPP's `post_max_size` is 40 MB by default. |
| Images not shown after upload | Check that the `uploads/` folder exists and is writable. |
| Page looks unstyled | No internet - Bootstrap loads from a CDN. |
| Slideshow stays empty | No photos are *approved* yet. Approve some in the admin area. |
| Check PHP syntax | Run in a Windows terminal: `C:\xampp\php\php.exe -l index.php` (repeat per file) |
