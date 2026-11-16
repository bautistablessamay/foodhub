ABIA'S EATERY (PHP + MySQL, XAMPP)

SETUP
1. Copy this "foodhub" folder to C:\xampp\htdocs\
2. Start Apache and MySQL in the XAMPP Control Panel.
3. phpMyAdmin (http://localhost/phpmyadmin) > Import > database/cute_ako.sql > Go   (creates the ONE database, cute_ako, with all tables and the admin account)
4. Open http://localhost/foodhub/   Admin login: admin / admin123 (the admin is created by the same SQL file)

FORGOT PASSWORD / OTP (any real email)
 Users must verify their email with a 6-digit code when they register, and can use "Forgot password?" on the login page later.
 The 6-digit code is emailed to that address. There is no phone/SMS option and the code is never shown on screen.
 To make it send, set these in includes/config.php:
   GMAIL_USER      = the Gmail account that SENDS the codes (e.g. karinderya2026@gmail.com)
   GMAIL_APP_PASS  = 16-character App password
                     (Google Account > Security > turn on 2-Step Verification > App passwords)
 Until both are filled in, "Send code" shows "We could not send the code" (details go to the PHP error log).
 The PHP server needs internet access and the OpenSSL extension (enabled by default in XAMPP).
 Accounts created before this update have no email, so they cannot reset until one is added in phpMyAdmin (users.email).

If you rename the folder, change BASE_URL in includes/config.php.

STRUCTURE
 index.php, login.php, register.php, forgot_password.php, logout.php
 includes/  config, notify (Gmail OTP sender), auth (helpers + CSRF + role check), header, footer, orders (status + chat), sales (reports)
 admin/     dashboard, users (activate/deactivate), sales (sold foods + earnings history)
 seller/    dashboard (earnings), menu (add/delete food + photo), orders (status updates + chat)
 customer/  dashboard, menu (today's menu + order), orders (cancel + chat)
 assets/    css/style.css, js/main.js (auto-refresh)
 database/  cute_ako.sql (the only database file)   uploads/  food photos


ROLES
 New registrations are always customers. The admin opens Users and clicks "Make seller" (or "Make customer") on an account.
 Sellers receive 100% of every completed order; Admin > Sales & Earnings lists the payout per seller.
