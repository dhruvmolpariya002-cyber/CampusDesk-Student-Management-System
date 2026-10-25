# Academic Project Documentation: CampusDesk
**Course:** Bachelor of Computer Applications (BCA)  
**Semester:** Semester 3  
**Subject:** Relational Database Management Systems & Web Programming (PHP & MySQL)  
**Project Title:** CampusDesk — Student Management System  

---

## 1. Abstract
CampusDesk is an integrated, web-based student information management application designed to computerize and streamline academic administration in higher education institutions. Developed using PHP 8.2 with PDO prepared statements and a normalized MySQL relational database, the system replaces manual registers and error-prone spreadsheets with a centralized, role-based platform. The system encapsulates student admissions, course curricula, roll-call attendance monitoring, semester examination gradecard generation, academic fee installment ledgers, and institutional circular broadcasting.

---

## 2. Problem Statement
Many colleges and institutes struggle with fragmented record-keeping across multiple physical ledgers or disjointed spreadsheets:
1. **Redundant Data Entry & Inconsistencies:** Student contact details, course mappings, and fee receipts are entered across separate departments without referential integrity.
2. **Attendance Calculation Bottlenecks:** Calculating monthly lecture attendance and identifying exam eligibility shortages (<75%) manually is time-consuming and vulnerable to clerical calculation errors.
3. **Data Security Vulnerabilities:** Unprotected spreadsheets lack role-based authorization, session security, and access controls, exposing sensitive student records.
4. **Student Lack of Transparency:** Students frequently must visit administrative counters to check internal marks, attendance eligibility, or pending fee dues.

---

## 3. Project Objectives
- To design a Third Normal Form (3NF) relational database ensuring zero data redundancy and ACID compliance.
- To implement Role-Based Access Control (RBAC) separating administrative workflows from student self-service views.
- To eliminate SQL injection vulnerabilities by enforcing 100% parameterized PDO prepared statements.
- To automate academic computations, including lecture attendance percentages, SGPA/grades, and fee balance totals.
- To provide instant CSV export capabilities for academic transcripts and administrative reports.

---

## 4. Software & Hardware Requirements

### Software Requirements
- **Server Environment:** Apache HTTP Server 2.4+
- **Server-Side Language:** PHP 8.2 or 8.3 (with `pdo_mysql`, `session`, `mbstring` extensions)
- **Database Server:** MySQL 8.0+ or MariaDB 10.4+
- **Database Management Tool:** phpMyAdmin 5.2+
- **Frontend Technologies:** HTML5, CSS3, JavaScript (ES6+), Bootstrap 5.3, Bootstrap Icons, Chart.js
- **Development & Packaging Environment:** XAMPP Localhost Stack

### Minimum Hardware Requirements
- **Processor:** Intel Core i3 (2.0 GHz) or AMD Ryzen 3 equivalent
- **RAM:** 4 GB minimum (8 GB recommended)
- **Storage:** 500 MB free hard drive space for Apache and MySQL binaries and application database
- **Display Resolution:** $1366 \times 768$ minimum (Responsive down to mobile screens)

---

## 5. System Architecture & 3NF Database Design

### Relational Schema (Normalized to 3NF)
1. **`users`**: `(user_id [PK], username, password_hash, email, role, status, created_at)`
2. **`departments`**: `(dept_id [PK], dept_name, dept_code, status, created_at)`
3. **`courses`**: `(course_id [PK], dept_id [FK], course_name, course_code, duration_years, description, status)`
4. **`students`**: `(student_id [PK], student_code [Unique], user_id [FK], course_id [FK], full_name, email, phone, dob, gender, address, admission_date, enrollment_status)`
5. **`subjects`**: `(subject_id [PK], course_id [FK], subject_name, subject_code [Unique], semester, credits)`
6. **`examinations`**: `(exam_id [PK], course_id [FK], exam_name, semester, exam_date)`
7. **`results`**: `(result_id [PK], exam_id [FK], student_id [FK], subject_id [FK], max_marks, obtained_marks, grade, status, remarks)`
8. **`attendance`**: `(attendance_id [PK], student_id [FK], course_id [FK], attendance_date, status, remarks)`
9. **`fees`**: `(fee_id [PK], student_id [FK], fee_type, total_amount, paid_amount, due_date, payment_status, payment_date, reference_no)`
10. **`notices`**: `(notice_id [PK], title, content, category, published_by [FK], publish_date, expiry_date, status)`

### Normalization Justification
- **1NF (First Normal Form):** Every field contains atomic, indivisible values (e.g. separate roll numbers, phones, dates). No repeating groups.
- **2NF (Second Normal Form):** All non-key attributes are fully functionally dependent on the entire primary key. Composite unique keys in `attendance(student_id, attendance_date)` and `results(exam_id, student_id, subject_id)` uniquely isolate records without partial dependencies.
- **3NF (Third Normal Form):** No transitive dependencies exist. For example, student records do not store department names; instead, `students` references `courses`, which in turn references `departments`.

---

## 6. Mathematical Formulas & Business Rules

### 1. Attendance Percentage Formula
$$\text{Attendance \%} = \frac{\text{Present Count} + (0.5 \times \text{Late Count})}{\text{Total Recorded Sessions}} \times 100$$
- **Safeguard:** If $\text{Total Recorded Sessions} = 0$, the function returns $0.0\%$ to avoid Division-by-Zero runtime exceptions.
- **Eligibility Threshold:** Students with attendance $< 75\%$ trigger an automatic academic shortage alert on their dashboard.

### 2. Examination Grading Matrix
| Obtained Percentage Range | Letter Grade | Academic Standing |
| :--- | :---: | :--- |
| $90\% - 100\%$ | **O** | Outstanding |
| $80\% - 89.9\%$ | **A+** | Excellent |
| $70\% - 79.9\%$ | **A** | Very Good |
| $60\% - 69.9\%$ | **B+** | Good |
| $50\% - 59.9\%$ | **B** | Above Average |
| $40\% - 49.9\%$ | **C** | Pass |
| $< 40\%$ | **F** | Fail (Re-appear required) |

### 3. Fee Ledger Accounting Rules
- $\text{Outstanding Balance} = \text{Total Billed Amount} - \text{Total Paid Amount}$
- **Payment Status Constraint:**
  - $\text{Paid}$ if $\text{Paid Amount} \ge \text{Total Amount}$
  - $\text{Partial}$ if $0 < \text{Paid Amount} < \text{Total Amount}$
  - $\text{Pending}$ if $\text{Paid Amount} = 0$
- **Validation Guard:** Additional installment payments cannot exceed the current outstanding balance.

---

## 7. BCA Viva Voce Defense Guide (Frequently Asked Questions)

### Q1: Why did you use PDO instead of MySQLi in this PHP project?
**Answer:**  
PDO (PHP Data Objects) offers three major advantages:
1. **Universal Database Abstraction:** PDO works across 12 different database systems (MySQL, PostgreSQL, SQLite, Oracle), making the application database-independent.
2. **Prepared Statements with Named Parameters:** PDO allows named placeholders (e.g., `:email`, `:id`), resulting in significantly cleaner, more readable code compared to positional question marks (`?`).
3. **Advanced Error Modes & Object Mapping:** Configuring `PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION` allows standard `try-catch` blocks for robust transaction handling (`beginTransaction`, `commit`, `rollBack`).

### Q2: How does the application prevent SQL Injection attacks?
**Answer:**  
The application uses **parameterized prepared statements** exclusively for all dynamic queries. User input is never concatenated directly into SQL strings. When `execute([':login' => $input])` is invoked, the database engine treats the parameters strictly as data literals, never as executable SQL commands, rendering injection attempts impossible.

### Q3: How is password security implemented?
**Answer:**  
Passwords are never stored in plain text. The project utilizes PHP's native `password_hash($password, PASSWORD_BCRYPT)` function with a default cost factor of 10. Verification is handled via `password_verify($password, $hash)`, which performs a timing-safe cryptographic comparison resisting timing attacks.

### Q4: How is Cross-Site Request Forgery (CSRF) mitigated?
**Answer:**  
Every active user session generates a cryptographically secure random 32-byte hexadecimal token (`bin2hex(random_bytes(32))`). Every HTML form includes this token inside a hidden input field (`csrf_field()`). When the form is submitted via POST, the server compares the submitted token against the session token using `hash_equals()`. If the token is missing or mismatched, the request is halted with a `403 Forbidden` error.

### Q5: How do you handle database transactions in the student registration module?
**Answer:**  
When registering a new student with an optional user login account, two related inserts must take place (`users` table followed by `students` table). To prevent orphan user accounts if student insertion fails, the code wraps both statements inside a PDO transaction:
```php
$pdo->beginTransaction();
// 1. Insert into users
// 2. Insert into students
$pdo->commit();
// In catch block: $pdo->rollBack();
```

---

## 8. Software Testing Checklist & Verification Matrix

| # | Test Case Description | Input Data / Action | Expected Result | Result Status |
| :-: | :--- | :--- | :--- | :-: |
| 1 | Database Connection | Load `config/database.php` | PDO connection established without errors | **PASSED** |
| 2 | Administrator Provisioning | Submit `setup_admin.php` | New admin record inserted with bcrypt hash | **PASSED** |
| 3 | Valid Administrator Login | `admin` / `Password@123` | Session regenerated, redirected to `admin/dashboard.php` | **PASSED** |
| 4 | Invalid Credentials Login | `admin` / `WrongPass` | Generic error message displayed, login denied | **PASSED** |
| 5 | Session Logout | Click Sign Out | Session cookie cleared, redirected to `login.php` | **PASSED** |
| 6 | RBAC Role Protection | Student navigating to `admin/` | Server halts request with 403 Forbidden access denial | **PASSED** |
| 7 | Add Student Record | Full valid admission form | Record inserted; roll number verified unique | **PASSED** |
| 8 | Duplicate Roll Number Check | Submit existing roll number | Form reloads with validation error, preserves inputs | **PASSED** |
| 9 | Course Referral Protection | Delete course with enrolled students | Deletion blocked with clear referential integrity alert | **PASSED** |
| 10 | Roll-Call Attendance Marking | Submit batch lecture sheet | Attendance records created/updated with zero duplicate errors | **PASSED** |
| 11 | Attendance Percentage Math | 8 Present, 2 Late, 0 Absent (10 total) | Evaluates exactly to 90.0% | **PASSED** |
| 12 | Attendance Division-by-Zero | Student with 0 total sessions | Returns 0.0% safely without PHP arithmetic error | **PASSED** |
| 13 | Exam Marks Validation | Obtained Marks = 105, Max = 100 | Submission rejected: obtained cannot exceed max marks | **PASSED** |
| 14 | Automatic Grade Determination | Obtained Marks = 85 / 100 | Result record generated with Grade A+ and Pass status | **PASSED** |
| 15 | Fee Installment Ledger | Record ₹15,000 against ₹35,000 fee | Outstanding balance computes to ₹20,000 with Partial status | **PASSED** |
| 16 | Fee Overpayment Prevention | Attempt to pay ₹25,000 on ₹20,000 balance | Rejected: payment cannot exceed remaining balance | **PASSED** |
| 17 | Notice Expiry Filtration | Check student view on expired notice | Expired circular hidden from student notice board | **PASSED** |
| 18 | CSV Data Export | Click Export CSV on Reports | Formatted CSV file downloaded with UTF-8 BOM headers | **PASSED** |
| 19 | CSRF Token Validation | Tamper with hidden `csrf_token` value | Form submission terminates with 403 Security Error | **PASSED** |
| 20 | Student Password Change | Submit valid current & new password | Password updated in database with fresh bcrypt hash | **PASSED** |
