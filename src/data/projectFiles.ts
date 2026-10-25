export interface ProjectFile {
  path: string;
  name: string;
  category: 'core' | 'config' | 'includes' | 'admin' | 'student' | 'assets' | 'docs';
  language: 'php' | 'sql' | 'css' | 'javascript' | 'markdown';
  description: string;
  content: string;
}

// Dynamically load every real, complete file from /campusdesk/ using Vite glob
const rawFiles = import.meta.glob('/campusdesk/**/*', { 
  query: '?raw', 
  import: 'default', 
  eager: true 
}) as Record<string, string>;

export const projectFiles: ProjectFile[] = Object.entries(rawFiles).map(([fullPath, content]) => {
  // Strip leading '/campusdesk/' to get relative path
  const relativePath = fullPath.replace(/^\/campusdesk\//, '');
  const fileName = relativePath.split('/').pop() || relativePath;

  let category: ProjectFile['category'] = 'core';
  if (relativePath.startsWith('config/')) category = 'config';
  else if (relativePath.startsWith('includes/')) category = 'includes';
  else if (relativePath.startsWith('admin/')) category = 'admin';
  else if (relativePath.startsWith('student/')) category = 'student';
  else if (relativePath.startsWith('assets/')) category = 'assets';
  else if (relativePath.endsWith('.md')) category = 'docs';

  let language: ProjectFile['language'] = 'php';
  if (relativePath.endsWith('.sql')) language = 'sql';
  else if (relativePath.endsWith('.css')) language = 'css';
  else if (relativePath.endsWith('.js')) language = 'javascript';
  else if (relativePath.endsWith('.md') || relativePath === '.htaccess') language = 'markdown';

  let description = `${fileName} component`;
  if (fileName === 'index.php') description = 'Main application entry point and dashboard redirect controller';
  else if (fileName === 'login.php') description = 'Secure authentication login portal with CSRF and password verification';
  else if (fileName === 'database.sql') description = 'Complete 3NF normalized MySQL database schema with 10 tables and demo records';
  else if (fileName === 'setup_admin.php') description = 'Safe first-run administrator provisioning wizard';
  else if (fileName === 'database.php') description = 'PDO database configuration with exception handling';
  else if (fileName === 'auth.php') description = 'Session management, RBAC, and login state';
  else if (fileName === 'csrf.php') description = 'Cryptographic CSRF token verification';
  else if (fileName === 'functions.php') description = 'Sanitization, grading rules, and percentage calculations';
  else if (fileName === 'header.php') description = 'Common HTML header, Bootstrap 5 CSS, and responsive meta';
  else if (fileName === 'sidebar.php') description = 'Role-based sidebar navigation';
  else if (fileName === 'navbar.php') description = 'Contextual top navigation header';
  else if (fileName === 'footer.php') description = 'Bootstrap 5 and Chart.js bundle scripts';
  else if (fileName === 'dashboard.php') description = 'Overview metrics, summary cards, and charts';
  else if (fileName === 'students.php') description = 'Student management directory with search, filter, and delete';
  else if (fileName === 'student_add.php') description = 'Student admission form with automatic account creation';
  else if (fileName === 'student_edit.php') description = 'Student details editing form';
  else if (fileName === 'courses.php') description = 'Course catalog with enrolled student count badges';
  else if (fileName === 'departments.php') description = 'Academic departments management';
  else if (fileName === 'attendance.php') description = 'Roll-call attendance marking and percentage calculation';
  else if (fileName === 'examinations.php') description = 'Examination schedule manager';
  else if (fileName === 'results.php') description = 'Marksheet evaluation with letter grades';
  else if (fileName === 'fees.php') description = 'Fee ledgers and installment receipts';
  else if (fileName === 'notices.php') description = 'Campus circular broadcasting board';
  else if (fileName === 'reports.php') description = 'Academic reports and CSV export generator';
  else if (fileName === 'profile.php') description = 'Student profile and password change form';
  else if (fileName === '.htaccess') description = 'Apache directory index configuration for XAMPP';
  else if (fileName === 'README.md') description = 'XAMPP setup guide, credentials, and troubleshooting';
  else if (fileName === 'BCA_PROJECT_REPORT.md') description = 'Complete BCA Semester 3 Project Documentation';

  return {
    path: relativePath,
    name: fileName,
    category,
    language,
    description,
    content
  };
}).sort((a, b) => {
  // Put index.php, login.php, database.sql, config/ first
  const priority = ['index.php', 'login.php', '.htaccess', 'database.sql', 'config/database.php'];
  const aIdx = priority.indexOf(a.path);
  const bIdx = priority.indexOf(b.path);
  if (aIdx !== -1 && bIdx !== -1) return aIdx - bIdx;
  if (aIdx !== -1) return -1;
  if (bIdx !== -1) return 1;
  return a.path.localeCompare(b.path);
});
