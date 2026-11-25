# Student Information System using AI AGILE IDE v1

A PHP and SQLite student directory with sign-in and admin user management.

## Run locally

PHP 8.0 or later with PDO SQLite and mbstring is required. Before starting the server, create the first administrator:

```sh
php setup-admin.php
php -S localhost:8000
```

Enter a username and a password of at least 12 bytes when prompted. The password is visible while typing. Open http://localhost:8000 and sign in. Visitors can register regular user accounts from the sign-in page. Administrators can add and remove users through Manage users. Regular users can manage student records but cannot manage accounts. Removing an account prevents its existing sessions from accessing the application on their next request.

The application stores its data in students.sqlite; the project directory must be writable by PHP. Back up this file to preserve records and accounts. This application is intended for local use or a trusted environment.