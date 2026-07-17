JAPAN Fes Internship & Volunteer Portal
========================================

Traditional Japanese-style PHP web application with magazine-style photo collage background.

QUICK SETUP (XAMPP)
-------------------

1. Copy the entire `applicant_portal` folder into your XAMPP htdocs directory:
     C:\xampp\htdocs\applicant_portal\

2. Start Apache and MySQL from the XAMPP Control Panel.

3. Open phpMyAdmin (http://localhost/phpmyadmin) and:
     a) Create a database called `applicant_portal`
     b) Run your original `schema.sql` (creates applicant, application, notification tables)
     c) Run `management_schema.sql` (creates the management table)

4. Visit http://localhost/applicant_portal/management_register.php
   to create your first admin account.

5. Go to http://localhost/applicant_portal/ and start using the portal.


FILE STRUCTURE
--------------
applicant_portal/
  index.html                  Home page
  register.html / .php        Applicant registration
  submit_application.html/.php  Submit a new application
  track_application.php       View / modify your applications
  notifications.php           View notifications

  management_login.html/.php  Admin login
  management_logout.php       Admin logout
  management_auth.php         Auth check (included by other admin pages)
  management_register.php     Create admin account (setup utility)
  management_dashboard.php    Admin dashboard with stats
  management_review.php       Review/modify any application
  management_notify.php       Send notifications to applicants
  management_process.php      Bulk workflow status updates
  management_data.php         Import/Export CSV data

  style.css                   Single shared stylesheet
  management_schema.sql       SQL for the management table

  images/
    logo.png                  JAPAN Fes logo (top of pages)
    hero.png                  JAPAN Fes logo (home page banner)
    collage.jpg               Magazine-style photo collage background (color)
    collage_bw.jpg            Same collage in black & white (optional alternative)


DESIGN NOTES
------------
- Theme: Traditional Japanese (washi paper background, sumi ink text)
- Font: Yu Mincho with fallbacks (Hiragino Mincho, Noto Serif JP, etc.)
- Accent color: Deep traditional red (#8b1a1a), used sparingly
- Background: Magazine-style collage of festival photos at 28% opacity,
  overlaid with a paper-tone fade so text stays highly readable
- Each page features a kanji watermark (e.g., 申込 for "Submit Application")


DATABASE CREDENTIALS
--------------------
All PHP files connect with:
  host:     localhost
  user:     root
  password: (empty)
  db name:  applicant_portal

If your XAMPP MySQL uses different credentials, search the PHP files for
"new mysqli" and update the connection string in each.


SWITCHING TO BLACK & WHITE COLLAGE
-----------------------------------
If you prefer the collage in black and white (more traditional feel),
edit style.css and change:
    background-image: url('images/collage.jpg');
to:
    background-image: url('images/collage_bw.jpg');
