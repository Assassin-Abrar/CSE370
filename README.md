# BRACU Club Management System

A full-stack club, event, budget, recruitment, and collaboration management platform for BRAC University. It uses plain PHP with PDO, MySQL/MariaDB, HTML, CSS, and vanilla JavaScript. There is no framework or build step; the application runs directly on XAMPP.

## Features

- Role-based dashboards for students, club executives, and OCA administrators
- Club discovery with student interest and skill matching
- Event planning with venue conflict detection
- Recruitment pipeline and application management
- Task assignment, workload analysis, and notifications
- Budget requests with an auditable status history
- Cross-club collaboration requests and responses
- CSRF-protected JSON endpoints and password-based authentication

## Requirements

- XAMPP (Apache, PHP 7.4+ with PDO MySQL, and MySQL/MariaDB)
- A browser with JavaScript enabled

## Setup

1. Copy the project into the XAMPP web root, for example `C:\xampp\htdocs\F_shitz`.
2. Start **Apache** and **MySQL** in the XAMPP Control Panel.
3. Import the schema and demo data:

   ```powershell
   C:\xampp\mysql\bin\mysql.exe -u root < database\bracu_cms.sql
   ```

   The SQL file creates the `bracu_cms` database and can be rerun to reset the demo data. If your MySQL installation uses a password, update `config\db.php`.
4. Open [http://localhost/F_shitz/](http://localhost/F_shitz/) in a browser.

## Demo accounts

All demo accounts use the password **`password123`**.

| Role | Email | Notes |
| --- | --- | --- |
| Student | `rakibul.islam@bracu.ac.bd` | Profile, club matches, and applications |
| Club executive | `sarah.ahmed@bracu.ac.bd` | Computer Club president and recruitment demo |
| Club executive | `shuvo.roy@bracu.ac.bd` | Cultural Club president and budget demo |
| OCA administrator | `admin@bracu.ac.bd` | Campus-wide management and analytics |

Students can also create an account at `auth/register.php`.

## Project structure

```text
config/        Database connection and application configuration
includes/      Authentication, shared helpers, layouts, and icons
assets/        CSS and vanilla JavaScript
api/            Role- and CSRF-protected JSON endpoints
auth/           Login, registration, and logout
student/        Student-facing pages
exec/           Club executive dashboards and tools
admin/          OCA administrator dashboards and tools
database/       Schema and demo seed data
report-assets/  EER and database schema diagrams
```

## Security notes

- Change the database credentials and demo passwords before deploying outside a local development environment.
- Keep uploaded files and local editor settings out of version control; `.gitignore` includes the local upload and settings paths.
- Do not enable `display_errors` in a production deployment.
