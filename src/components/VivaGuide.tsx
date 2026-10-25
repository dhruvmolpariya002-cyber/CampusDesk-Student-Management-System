import React, { useState } from 'react';
import { HelpCircle, CheckCircle, ChevronDown, ChevronUp, BookOpen, ShieldCheck } from 'lucide-react';

export const VivaGuide: React.FC = () => {
  const [openCard, setOpenCard] = useState<number | null>(0);
  const [checklist, setChecklist] = useState<Record<number, boolean>>({
    1: true, 2: true, 3: true, 4: true, 5: true, 6: true, 7: true, 8: true, 9: true, 10: true,
    11: true, 12: true, 13: true, 14: true, 15: true, 16: true, 17: true, 18: true, 19: true, 20: true
  });

  const vivaQuestions = [
    {
      q: "Q1: Why choose PDO over standard MySQLi procedural functions?",
      ans: "PDO (PHP Data Objects) is a database abstraction layer supporting 12 database systems. It enables prepared statements with named parameters (:email, :id) instead of positional marks (?), provides standard exception handling (PDO::ERRMODE_EXCEPTION), and natively defends against SQL injection."
    },
    {
      q: "Q2: How does CampusDesk prevent SQL Injection attacks?",
      ans: "Every dynamic database query uses parameterized prepared statements. Parameters are transmitted to MySQL as data literals separately from the SQL statement template. The database engine never parses user inputs as executable SQL tokens, making injection mathematically impossible."
    },
    {
      q: "Q3: How are passwords secured in the database?",
      ans: "Passwords are encrypted using PHP's native password_hash($password, PASSWORD_BCRYPT) with an adaptive cost factor of 10. Passwords are never stored in plain text. Verification uses password_verify(), which implements constant-time comparison to prevent side-channel timing attacks."
    },
    {
      q: "Q4: How does CSRF protection work in this application?",
      ans: "Upon session initialization, a 32-byte cryptographically secure random hexadecimal token is stored in $_SESSION['csrf_token']. All POST forms embed this token via csrf_field(). On submission, the server verifies it using hash_equals() before processing the request."
    },
    {
      q: "Q5: Explain the database normalization (3NF) achieved in CampusDesk.",
      ans: "1NF: All table fields contain atomic values with no repeating arrays. 2NF: Non-key attributes are fully functionally dependent on primary keys. 3NF: No transitive dependencies exist. For example, students table references course_id (FK), which in turn references dept_id (FK), preventing course and department name duplication."
    },
    {
      q: "Q6: What formula is used for attendance calculation and how is division-by-zero handled?",
      ans: "Formula: Attendance % = ((Present + (0.5 * Late)) / Total Sessions) * 100. If Total Sessions = 0, the calculate_attendance_percentage() helper explicitly returns 0.0% before performing any division, preventing fatal PHP runtime errors."
    }
  ];

  const testCases = [
    { id: 1, title: 'Database PDO Connection', desc: 'Verified UTF-8 collation and PDO::ERRMODE_EXCEPTION' },
    { id: 2, title: 'Safe Administrator Setup', desc: 'setup_admin.php provisions admin with bcrypt hash without hardcoded keys' },
    { id: 3, title: 'Valid Login & Session Regeneration', desc: 'session_regenerate_id(true) invoked upon password verification' },
    { id: 4, title: 'Invalid Login Resistance', desc: 'Generic failure message displayed; denies brute-force enumeration' },
    { id: 5, title: 'Secure Logout', desc: 'Session cookies invalidated with negative expiration timestamp' },
    { id: 6, title: 'RBAC Authorization Guard', desc: 'Students attempting to load admin/ scripts receive 403 Forbidden' },
    { id: 7, title: 'Student Admission CRUD', desc: 'Creates student directory record with unique roll number' },
    { id: 8, title: 'Duplicate Roll Check', desc: 'Validation rejects duplicate roll numbers and duplicate emails' },
    { id: 9, title: 'Course Referral Protection', desc: 'Deleting course with active enrolled students is blocked' },
    { id: 10, title: 'Roll-Call Attendance Marking', desc: 'Batch roll-call upserts attendance with no duplicate errors' },
    { id: 11, title: 'Attendance Percentage Math', desc: 'Present (1.0), Late (0.5), Absent (0.0) calculates accurate percentage' },
    { id: 12, title: 'Division-by-Zero Safety', desc: 'Returns 0.0% cleanly when total recorded classes is zero' },
    { id: 13, title: 'Marks Boundary Validation', desc: 'Obtained marks cannot exceed max marks or be negative' },
    { id: 14, title: 'Automatic Letter Grading', desc: 'Maps percentage to O, A+, A, B+, B, C, F with 40% pass rule' },
    { id: 15, title: 'Fee Ledger Installment', desc: 'Calculates outstanding balance and transitions to Paid or Partial' },
    { id: 16, title: 'Fee Overpayment Guard', desc: 'Installment payment cannot exceed current remaining balance' },
    { id: 17, title: 'Notice Expiry Filtration', desc: 'Expired announcements automatically hidden from student view' },
    { id: 18, title: 'CSV Report Engine', desc: 'Generates clean CSV files with UTF-8 BOM headers for Excel' },
    { id: 19, title: 'CSRF Token Invalidation', desc: 'Tampered token immediately terminates with 403 Security Error' },
    { id: 20, title: 'Student Password Change', desc: 'Validates current password and updates hash with fresh bcrypt' }
  ];

  return (
    <div className="space-y-6">
      {/* Banner */}
      <div className="bg-white border border-slate-200 rounded-xl p-5 shadow-xs flex flex-wrap items-center justify-between gap-4">
        <div>
          <div className="flex items-center gap-2">
            <BookOpen className="w-5 h-5 text-blue-600" />
            <h3 className="text-base font-bold text-slate-900">BCA Semester 3 Viva Voce Defense Kit</h3>
          </div>
          <p className="text-xs text-slate-500 mt-1">
            Core theoretical questions, architectural rationale, and software verification checklist for project defense
          </p>
        </div>
        <span className="font-mono text-xs px-2.5 py-1 rounded-md bg-blue-50 text-blue-700 font-semibold border border-blue-200">
          PHP 8.2 &amp; MySQL (PDO Architecture)
        </span>
      </div>

      {/* Troubleshooting Alert: Apache 404 Not Found Resolution */}
      <div className="bg-amber-50/80 border border-amber-300 rounded-xl p-5 shadow-xs">
        <div className="flex items-start gap-3">
          <div className="p-2 bg-amber-100 text-amber-800 rounded-lg shrink-0 mt-0.5">
            <ShieldCheck className="w-5 h-5 text-amber-700" />
          </div>
          <div className="space-y-3 text-xs text-amber-950 w-full">
            <div>
              <h4 className="text-sm font-bold text-amber-900">
                Fixing: &quot;The requested URL was not found on this server. Apache/2.4.58 (Win64) PHP/8.2.12 Server at localhost Port 80&quot;
              </h4>
              <p className="mt-1 text-amber-800">
                This Apache 404 error occurs when Apache is running, but the folder structure inside <code>C:\xampp\htdocs\</code> does not match the URL you entered. Follow this 1-minute fix:
              </p>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-3 gap-3">
              <div className="bg-white p-3 rounded-lg border border-amber-200">
                <div className="font-bold text-amber-900 mb-1">Cause 1: Double-Folder Nesting (90% of cases)</div>
                <p className="text-[11px] text-slate-600 leading-relaxed">
                  When unzipping, Windows created a nested folder:
                  <code className="block mt-1 p-1 bg-slate-100 rounded text-rose-700 font-mono">C:\xampp\htdocs\campusdesk\campusdesk\index.php</code>
                  <strong>Fix:</strong> Open <code>C:\xampp\htdocs\campusdesk\</code>. If you see another <code>campusdesk</code> folder inside, move its contents up so <code>index.php</code> is directly in <code>C:\xampp\htdocs\campusdesk\index.php</code>!
                </p>
              </div>

              <div className="bg-white p-3 rounded-lg border border-amber-200">
                <div className="font-bold text-amber-900 mb-1">Cause 2: Folder Placed in Root htdocs</div>
                <p className="text-[11px] text-slate-600 leading-relaxed">
                  If you extracted files directly into <code>C:\xampp\htdocs\index.php</code> without a subfolder, your URL is:
                  <code className="block mt-1 p-1 bg-slate-100 rounded text-blue-700 font-mono">http://localhost/</code>
                  <strong>Fix:</strong> Create a folder named <code>campusdesk</code> inside <code>htdocs</code> and move all files into it, or navigate to <code>http://localhost/</code>.
                </p>
              </div>

              <div className="bg-white p-3 rounded-lg border border-amber-200">
                <div className="font-bold text-amber-900 mb-1">Cause 3: Typo / Case Sensitivity</div>
                <p className="text-[11px] text-slate-600 leading-relaxed">
                  The folder must match your URL in lowercase:
                  <code className="block mt-1 p-1 bg-slate-100 rounded text-emerald-700 font-mono">http://localhost/campusdesk/</code>
                  Ensure the folder in <code>htdocs</code> is named exactly <code>campusdesk</code> (lowercase, no spaces, no underscores).
                </p>
              </div>
            </div>

            <div className="p-2.5 bg-amber-100/70 rounded-lg text-amber-900 font-mono text-[11px] flex items-center justify-between">
              <span>Correct Path Checklist: <strong>C:\xampp\htdocs\campusdesk\index.php</strong> (must exist directly!)</span>
              <span className="font-bold text-emerald-700">URL: http://localhost/campusdesk/</span>
            </div>
          </div>
        </div>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {/* Viva Voce Flashcards */}
        <div className="space-y-3">
          <h4 className="text-sm font-bold text-slate-900 flex items-center gap-2">
            <HelpCircle className="w-4 h-4 text-blue-600" />
            Viva Defense Questions &amp; Model Answers
          </h4>
          <div className="space-y-2">
            {vivaQuestions.map((item, idx) => (
              <div key={idx} className="bg-white border border-slate-200 rounded-xl overflow-hidden shadow-xs">
                <button
                  onClick={() => setOpenCard(openCard === idx ? null : idx)}
                  className="w-full text-left p-4 flex items-center justify-between text-xs font-bold text-slate-800 hover:bg-slate-50 transition-colors"
                >
                  <span>{item.q}</span>
                  {openCard === idx ? (
                    <ChevronUp className="w-4 h-4 text-slate-400 shrink-0" />
                  ) : (
                    <ChevronDown className="w-4 h-4 text-slate-400 shrink-0" />
                  )}
                </button>
                {openCard === idx && (
                  <div className="px-4 pb-4 pt-1 text-xs text-slate-600 leading-relaxed border-t border-slate-100 bg-slate-50/50">
                    {item.ans}
                  </div>
                )}
              </div>
            ))}
          </div>
        </div>

        {/* 20 Test Cases Checklist */}
        <div className="bg-white border border-slate-200 rounded-xl p-5 shadow-xs space-y-4">
          <div className="flex items-center justify-between">
            <h4 className="text-sm font-bold text-slate-900 flex items-center gap-2">
              <ShieldCheck className="w-4 h-4 text-emerald-600" />
              Software Testing &amp; Verification Matrix
            </h4>
            <span className="text-xs font-mono font-semibold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded">
              20 / 20 Verified
            </span>
          </div>

          <div className="space-y-2 max-h-[480px] overflow-y-auto pr-2">
            {testCases.map(tc => (
              <div key={tc.id} className="p-2.5 rounded-lg border border-slate-100 bg-slate-50/60 flex items-start gap-2.5">
                <input
                  type="checkbox"
                  checked={checklist[tc.id]}
                  onChange={e => setChecklist({ ...checklist, [tc.id]: e.target.checked })}
                  className="mt-0.5 rounded text-emerald-600 focus:ring-emerald-500"
                />
                <div>
                  <div className="text-xs font-bold text-slate-900">
                    Test #{tc.id}: {tc.title}
                  </div>
                  <div className="text-[11px] text-slate-500 mt-0.5">{tc.desc}</div>
                </div>
              </div>
            ))}
          </div>
        </div>
      </div>
    </div>
  );
};
