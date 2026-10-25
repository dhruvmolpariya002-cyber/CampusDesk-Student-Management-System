# CampusDesk — Student Management System
**Academic Project: BCA Semester 3 (PHP & MySQL)**

CampusDesk is a full-stack, role-based student and academic records management system built using **PHP 8.2+**, **MySQL / MariaDB**, **PDO with Prepared Statements**, and **Bootstrap 5**.

It provides two distinct access tiers:
1. **Administrative Portal**: Student directory CRUD, course and department administration, classroom roll-call attendance with percentage logic, examination scheduling and grading marksheets, fee installment ledgers, notice board publishing, and CSV data exports.
2. **Student Self-Service Portal**: Personal academic profile, lecture-by-lecture attendance history with 75% shortage alert, semester marksheets with SGPA/percentage calculations, fee dues and receipt references, campus circulars, and password management.

---

## Key Features

- **Enterprise Security**:
  - 100% PDO prepared statements for every database operation (SQL injection defense).
  - Passwords hashed using standard PHP `password_hash($pwd, PASSWORD_BCRYPT)`.
  - Timing-safe HMAC session CSRF token validation on every state-changing form.
  - Session regeneration (`session_regenerate_id(true)`) upon authentication to defeat session fixation.
  - Role-Based Access Control enforced on the server-side with strict redirects.
  - Output sanitization (`htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`) preventing Cross-Site Scripting (XSS).
- **Relational Integrity (3NF Normalized)**:
  - 10 structured tables (`users`, `departments`, `courses`, `students`, `subjects`, `examinations`, `results`, `attendance`, `fees`, `notices`).
  - Foreign key constraints with `ON DELETE RESTRICT` on vital course catalogs preventing accidental student record orphanages.
  - Unique composite constraints preventing duplicate roll numbers, duplicate attendance for the same student on the same date, and duplicate marks entries.
- **Accurate Academic Calculations**:
  - **Attendance % Formula**: $\text{Percentage} = \frac{\text{Present} + (0.5 \times \text{Late})}{\text{Total Classes}} \times 100$ with division-by-zero protection.
  - **Grading Matrix**: Automatically evaluates marks into letter grades (O, A+, A, B+, B, C, F) and Pass/Fail based on configurable 40% threshold.
  - **Fee Accounting**: Tracks total billed vs paid amounts, computes outstanding dues, and dynamically sets status (`Paid`, `Partial`, `Pending`).
- **Reporting & Export**:
  - Instant CSV exports for Student directory, Attendance logs, Examination gradecards, and Fee collections.

---

## Directory Structure

```
campusdesk/
├── index.php                 # Public landing page with architecture summary & links
├── login.php                 # Secure login page with CSRF and password verification
├── logout.php                # Session destruction and cleanup
├── setup_admin.php           # Safe first-run administrator provisioning wizard
├── database.sql              # Normalized MySQL schema and sample demonstration records
├── README.md                 # Complete setup guide and documentation
├── BCA_PROJECT_REPORT.md     # Full academic project documentation & viva defense guide
├── config/
│   └── database.php          # PDO connection with utf8mb4 and error logging
├── includes/
│   ├── auth.php              # Session management, RBAC, and login/logout handlers
│   ├── csrf.php              # CSRF token generation and validation helpers
│   ├── functions.php         # Output sanitization, grade logic, and percentage formulas
│   ├── header.php            # HTML head, Bootstrap 5 CSS, and responsive meta
│   ├── sidebar.php           # Role-specific sidebar navigation
│   ├── navbar.php            # Top header with user profile menu and flash alerts
│   └── footer.php            # Footer scripts (Bootstrap 5 bundle, Chart.js, app.js)
├── admin/
│   ├── dashboard.php         # Admin metrics, analytics charts, and recent records
│   ├── students.php          # Student list with search, filter, and pagination
│   ├── student_add.php       # Student enrollment form with automatic user account creation
│   ├── student_edit.php      # Student modification form
│   ├── departments.php       # Department management with referential checks
│   ├── courses.php           # Course catalog with enrolled student count badges
│   ├── subjects.php          # Subjects categorized by course and semester
│   ├── attendance.php        # Daily roll-call sheet and cumulative percentage calculation
│   ├── examinations.php      # Examination session scheduler
│   ├── results.php           # Marks entry form and grade calculation
│   ├── fees.php              # Fee invoicing and installment payment receipts
│   ├── notices.php           # Notice board publisher with expiry date handling
│   └── reports.php           # Filterable data reports with CSV download
├── student/
│   ├── dashboard.php         # Student personal dashboard with GPA & attendance
│   ├── profile.php           # Profile viewer and secure password changer
│   ├── attendance.php        # Detailed lecture attendance log and shortage alerts
│   ├── results.php           # Examination marksheets and semester transcripts
│   ├── fees.php              # Fee statements and transaction receipts
│   └── notices.php           # Published circulars and institutional notices
└── assets/
    ├── css/
    │   └── style.css         # Custom collegiate styles, tabular figures & layout
    └── js/
        └── app.js            # Confirmation prompts, sidebar toggle, and live search
```

---

## Step-by-Step Installation Guide (XAMPP on Windows/Mac/Linux)

### Step 1: Install XAMPP
Download and install [XAMPP](https://www.apachefriends.org/) with **PHP 8.2 or 8.3** and **MySQL/MariaDB**.

### Step 2: Start Apache and MySQL Services
1. Open the **XAMPP Control Panel**.
2. Click **Start** next to **Apache**.
3. Click **Start** next to **MySQL**.
Both modules should turn green.

### Step 3: Place Project Files in `htdocs`
1. Navigate to your XAMPP installation directory:
   - **Windows**: `C:\xampp\htdocs\`
   - **macOS**: `/Applications/XAMPP/xamppfiles/htdocs/`
   - **Linux**: `/opt/lampp/htdocs/`
2. Create or copy the project folder named `campusdesk` so that `index.php` is located directly at:
   `C:\xampp\htdocs\campusdesk\index.php`

### Step 4: Import Database (`campusdesk_db`) via phpMyAdmin
1. Open your web browser and navigate to:
   `http://localhost/phpmyadmin/`
2. In the left panel, click **New** to create a database.
3. Enter database name: `campusdesk_db` and collation: `utf8mb4_unicode_ci`, then click **Create**.
4. With `campusdesk_db` selected, click the **Import** tab at the top.
5. Click **Choose File** and select `database.sql` from your `campusdesk` folder.
6. Scroll down and click **Import** (or **Go**).
7. phpMyAdmin will execute the queries and display: *"Import has been successfully finished, 10 tables created."*

### Step 5: Verify Database Configuration
Open `config/database.php` in a code editor. By default, standard XAMPP settings are pre-configured:
```php
$db_host = 'localhost';
$db_port = '3306';
$db_name = 'campusdesk_db';
$db_user = 'root';
$db_pass = ''; // Default XAMPP MySQL root has an empty password
```
If your MySQL has a password, update `$db_pass`.

### Step 6: Access CampusDesk in Your Browser
Open your browser and navigate to:
```
http://localhost/campusdesk/
```

---

## Default Login Credentials (Demonstration)

The pre-seeded database contains secure demo accounts:

| Role | Username / Identifier | Password | Access Capabilities |
| :--- | :--- | :--- | :--- |
| **Administrator** | `admin` | `Password@123` | Full administrative control, admissions, marks, fees, notices |
| **Student (BCA)** | `bca2024001` | `Password@123` | Aarav Sharma (BCA Sem 3) — personal attendance, marks, fees |
| **Student (BCA)** | `bca2024002` | `Password@123` | Diya Patel (BCA Sem 3) — personal attendance, marks, fees |
| **Student (B.Sc IT)** | `it2024010` | `Password@123` | Rohan Mehta (B.Sc IT Sem 3) — personal attendance, fees |

> Note: All passwords are stored as bcrypt hashes. You can also provision a fresh administrator account at any time via `http://localhost/campusdesk/setup_admin.php`.

---

## Troubleshooting Common Setup Issues

1. **"The requested URL was not found on this server" (Apache 404 Error)**:
   - **Double-Folder Trap (Most Common)**: When unzipping, Windows often creates a folder inside a folder, e.g.:
     `C:\xampp\htdocs\campusdesk\campusdesk\index.php`.
     If so, visiting `http://localhost/campusdesk/` gives 404 because the actual URL is `http://localhost/campusdesk/campusdesk/`!
     **Solution**: Move all files out of the inner `campusdesk` folder up into `C:\xampp\htdocs\campusdesk\`, so that `index.php` is directly at `C:\xampp\htdocs\campusdesk\index.php`.
   - **Extracted Directly to htdocs Root**: If `index.php` is located directly in `C:\xampp\htdocs\index.php`, then your URL is `http://localhost/`.
     **Solution**: Create a folder named `campusdesk` in `C:\xampp\htdocs\` and move all project files inside it.
   - **Typo in Folder Name**: Ensure the folder inside `htdocs` is named exactly `campusdesk` (all lowercase, no spaces).
2. **"Access denied for user 'root'@'localhost'"**:
   - Check `config/database.php`. If you set a root password in phpMyAdmin or MySQL, enter it in `$db_pass`.
3. **"Unknown database 'campusdesk_db'"**:
   - Make sure you created the database named `campusdesk_db` in phpMyAdmin before importing `database.sql`.
4. **"Call to undefined function password_hash()"**:
   - Verify your XAMPP runs PHP 8.0+. CampusDesk requires PHP 8.x.

---

## Academic Verification Checklist

- [x] PHP 8.2 PDO with native prepared statements.
- [x] Session fixation defense and role-based access control.
- [x] Zero hardcoded plain-text passwords.
- [x] Student admission CRUD with validation and duplicate roll checks.
- [x] Roll-call attendance with percentage calculation and division-by-zero safety.
- [x] Examination marks entry with automatic grade and pass/fail evaluation.
- [x] Fee ledgers with installment tracking and outstanding balance calculation.
- [x] Notice board publisher with expiry date filtration.
- [x] Export to CSV for all academic records.
