import React, { useState } from 'react';
import { 
  Student, Course, Department, Subject, Examination, Result, AttendanceRecord, FeeRecord, Notice 
} from '../data/initialData';
import { 
  GraduationCap, Calendar, Award, Wallet, Bell, User, CheckCircle2, AlertTriangle, KeyRound
} from 'lucide-react';

interface StudentPortalProps {
  currentStudent: Student;
  courses: Course[];
  departments: Department[];
  subjects: Subject[];
  examinations: Examination[];
  results: Result[];
  attendance: AttendanceRecord[];
  fees: FeeRecord[];
  notices: Notice[];
  onSelectStudent: (studentId: number) => void;
  allStudents: Student[];
}

export const StudentPortal: React.FC<StudentPortalProps> = ({
  currentStudent,
  courses,
  departments,
  subjects,
  examinations,
  results,
  attendance,
  fees,
  notices,
  onSelectStudent,
  allStudents
}) => {
  const [activeTab, setActiveTab] = useState<'dashboard' | 'attendance' | 'results' | 'fees' | 'notices' | 'profile'>('dashboard');
  
  // Password change state
  const [currentPass, setCurrentPass] = useState('');
  const [newPass, setNewPass] = useState('');
  const [confirmPass, setConfirmPass] = useState('');
  const [passMsg, setPassMsg] = useState<{ type: 'success' | 'error'; text: string } | null>(null);

  // Student specific data
  const studentCourse = courses.find(c => c.course_id === currentStudent.course_id);
  const studentDept = departments.find(d => d.dept_id === studentCourse?.dept_id);

  // Attendance calculation
  const myAttendance = attendance.filter(a => a.student_id === currentStudent.student_id);
  const totalClasses = myAttendance.length;
  const presentCount = myAttendance.filter(a => a.status === 'Present').length;
  const lateCount = myAttendance.filter(a => a.status === 'Late').length;
  const absentCount = myAttendance.filter(a => a.status === 'Absent').length;
  const attendancePct = totalClasses > 0 ? ((presentCount + (lateCount * 0.5)) / totalClasses) * 100 : 0;

  // Results calculation
  const myResults = results.filter(r => r.student_id === currentStudent.student_id);
  const totalMax = myResults.reduce((acc, r) => acc + r.max_marks, 0);
  const totalObt = myResults.reduce((acc, r) => acc + r.obtained_marks, 0);
  const overallPct = totalMax > 0 ? (totalObt / totalMax) * 100 : 0;

  // Fees calculation
  const myFees = fees.filter(f => f.student_id === currentStudent.student_id);
  const totalBilled = myFees.reduce((acc, f) => acc + f.total_amount, 0);
  const totalPaid = myFees.reduce((acc, f) => acc + f.paid_amount, 0);
  const totalDue = totalBilled - totalPaid;

  const handlePasswordSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!currentPass || !newPass || !confirmPass) {
      setPassMsg({ type: 'error', text: 'All password fields are required.' });
      return;
    }
    if (newPass.length < 8) {
      setPassMsg({ type: 'error', text: 'New password must be at least 8 characters long.' });
      return;
    }
    if (newPass !== confirmPass) {
      setPassMsg({ type: 'error', text: 'New password and confirmation do not match.' });
      return;
    }
    setPassMsg({ type: 'success', text: 'Password successfully changed and updated in session!' });
    setCurrentPass('');
    setNewPass('');
    setConfirmPass('');
  };

  return (
    <div className="space-y-6">
      {/* Student Banner with Quick Selector for Viva Demonstrations */}
      <div className="bg-white border border-slate-200 rounded-xl p-5 shadow-xs flex flex-wrap items-center justify-between gap-4">
        <div className="flex items-center gap-4">
          <div className="w-12 h-12 rounded-xl bg-blue-600 text-white font-bold flex items-center justify-center text-lg shadow-sm">
            {currentStudent.full_name.charAt(0)}
          </div>
          <div>
            <div className="flex items-center gap-2">
              <h2 className="text-base font-bold text-slate-900">{currentStudent.full_name}</h2>
              <span className="font-mono text-xs px-2 py-0.5 rounded bg-blue-50 text-blue-700 font-bold">
                {currentStudent.student_code}
              </span>
            </div>
            <div className="text-xs text-slate-500 mt-0.5">
              {studentCourse?.course_name} ({studentCourse?.course_code}) · {studentDept?.dept_name}
            </div>
          </div>
        </div>

        {/* Switch Student Dropdown for Examiners */}
        <div className="flex items-center gap-2 text-xs">
          <span className="text-slate-500">Demo Account:</span>
          <select
            value={currentStudent.student_id}
            onChange={e => onSelectStudent(Number(e.target.value))}
            className="py-1 px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-slate-800 font-medium"
          >
            {allStudents.map(st => (
              <option key={st.student_id} value={st.student_id}>
                {st.student_code} — {st.full_name}
              </option>
            ))}
          </select>
        </div>
      </div>

      {/* Portal Navigation Tabs */}
      <div className="flex flex-wrap gap-1 bg-slate-100 p-1 rounded-lg w-fit">
        <button
          onClick={() => setActiveTab('dashboard')}
          className={`px-3 py-1.5 text-xs font-semibold rounded-md transition-colors ${activeTab === 'dashboard' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-600 hover:text-slate-900'}`}
        >
          My Academic Dashboard
        </button>
        <button
          onClick={() => setActiveTab('attendance')}
          className={`px-3 py-1.5 text-xs font-semibold rounded-md transition-colors ${activeTab === 'attendance' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-600 hover:text-slate-900'}`}
        >
          Lecture Attendance ({totalClasses})
        </button>
        <button
          onClick={() => setActiveTab('results')}
          className={`px-3 py-1.5 text-xs font-semibold rounded-md transition-colors ${activeTab === 'results' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-600 hover:text-slate-900'}`}
        >
          Examination Gradecards ({myResults.length})
        </button>
        <button
          onClick={() => setActiveTab('fees')}
          className={`px-3 py-1.5 text-xs font-semibold rounded-md transition-colors ${activeTab === 'fees' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-600 hover:text-slate-900'}`}
        >
          Fee Statements ({myFees.length})
        </button>
        <button
          onClick={() => setActiveTab('notices')}
          className={`px-3 py-1.5 text-xs font-semibold rounded-md transition-colors ${activeTab === 'notices' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-600 hover:text-slate-900'}`}
        >
          Campus Notices
        </button>
        <button
          onClick={() => setActiveTab('profile')}
          className={`px-3 py-1.5 text-xs font-semibold rounded-md transition-colors ${activeTab === 'profile' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-600 hover:text-slate-900'}`}
        >
          My Profile &amp; Password
        </button>
      </div>

      {/* DASHBOARD TAB */}
      {activeTab === 'dashboard' && (
        <div className="space-y-6">
          {/* Key Metrics */}
          <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div className="bg-white border border-slate-200 rounded-xl p-4 shadow-xs">
              <div className="flex items-center justify-between">
                <div>
                  <div className="text-xs font-semibold text-slate-500 uppercase tracking-wider">Attendance Standing</div>
                  <div className={`text-2xl font-bold font-mono mt-1 ${attendancePct >= 75 ? 'text-emerald-600' : 'text-rose-600'}`}>
                    {attendancePct.toFixed(1)}%
                  </div>
                </div>
                <div className={`p-2.5 rounded-lg ${attendancePct >= 75 ? 'bg-emerald-50 text-emerald-600' : 'bg-rose-50 text-rose-600'}`}>
                  <Calendar className="w-5 h-5" />
                </div>
              </div>
              <div className="text-xs text-slate-500 mt-2">
                {presentCount} of {totalClasses} classes attended
              </div>
            </div>

            <div className="bg-white border border-slate-200 rounded-xl p-4 shadow-xs">
              <div className="flex items-center justify-between">
                <div>
                  <div className="text-xs font-semibold text-slate-500 uppercase tracking-wider">Exam Performance</div>
                  <div className="text-2xl font-bold text-blue-600 font-mono mt-1">
                    {overallPct > 0 ? `${overallPct.toFixed(1)}%` : 'N/A'}
                  </div>
                </div>
                <div className="p-2.5 bg-blue-50 text-blue-600 rounded-lg">
                  <Award className="w-5 h-5" />
                </div>
              </div>
              <div className="text-xs text-slate-500 mt-2">
                {myResults.length} subject papers evaluated
              </div>
            </div>

            <div className="bg-white border border-slate-200 rounded-xl p-4 shadow-xs">
              <div className="flex items-center justify-between">
                <div>
                  <div className="text-xs font-semibold text-slate-500 uppercase tracking-wider">Outstanding Dues</div>
                  <div className={`text-2xl font-bold font-mono mt-1 ${totalDue > 0 ? 'text-rose-600' : 'text-emerald-600'}`}>
                    ₹{totalDue.toLocaleString()}
                  </div>
                </div>
                <div className={`p-2.5 rounded-lg ${totalDue > 0 ? 'bg-amber-50 text-amber-600' : 'bg-emerald-50 text-emerald-600'}`}>
                  <Wallet className="w-5 h-5" />
                </div>
              </div>
              <div className="text-xs text-slate-500 mt-2">
                {totalDue > 0 ? 'Tuition installment pending' : 'All accounts cleared'}
              </div>
            </div>
          </div>

          {/* 75% Attendance Shortage Alert */}
          {attendancePct < 75 && totalClasses > 0 && (
            <div className="bg-rose-50 border border-rose-200 rounded-xl p-4 flex items-start gap-3">
              <AlertTriangle className="w-5 h-5 text-rose-600 shrink-0 mt-0.5" />
              <div>
                <h4 className="text-xs font-bold text-rose-900">University Attendance Shortage Warning</h4>
                <p className="text-xs text-rose-700 mt-0.5">
                  Your cumulative attendance is at <strong>{attendancePct.toFixed(1)}%</strong>, which is below the mandatory 75% threshold required to appear for semester final examinations.
                </p>
              </div>
            </div>
          )}

          {/* Snapshot of Marksheets */}
          <div className="bg-white border border-slate-200 rounded-xl p-5 shadow-xs">
            <div className="flex items-center justify-between mb-4">
              <h3 className="text-sm font-bold text-slate-900">Latest Subject Results</h3>
              <button onClick={() => setActiveTab('results')} className="text-xs text-blue-600 hover:underline">
                View Full Gradecard &rarr;
              </button>
            </div>
            <div className="overflow-x-auto">
              <table className="w-full text-left text-xs">
                <thead className="bg-slate-50 border-b border-slate-200 text-slate-500 font-semibold uppercase">
                  <tr>
                    <th className="py-2.5 px-3">Subject</th>
                    <th className="py-2.5 px-3">Score</th>
                    <th className="py-2.5 px-3">Grade</th>
                    <th className="py-2.5 px-3">Status</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-100">
                  {myResults.map(res => {
                    const sb = subjects.find(s => s.subject_id === res.subject_id);
                    return (
                      <tr key={res.result_id} className="hover:bg-slate-50">
                        <td className="py-2.5 px-3">
                          <div className="font-semibold text-slate-900">{sb?.subject_name}</div>
                          <div className="text-slate-400 font-mono text-[11px]">{sb?.subject_code}</div>
                        </td>
                        <td className="py-2.5 px-3 font-mono">{res.obtained_marks} / {res.max_marks}</td>
                        <td className="py-2.5 px-3 font-bold">{res.grade}</td>
                        <td className="py-2.5 px-3">
                          <span className={`px-2 py-0.5 rounded font-medium ${res.status === 'Pass' ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700'}`}>
                            {res.status}
                          </span>
                        </td>
                      </tr>
                    );
                  })}
                </tbody>
              </table>
            </div>
          </div>
        </div>
      )}

      {/* ATTENDANCE TAB */}
      {activeTab === 'attendance' && (
        <div className="bg-white border border-slate-200 rounded-xl shadow-xs p-5 space-y-4">
          <div className="flex items-center justify-between pb-3 border-b border-slate-200">
            <div>
              <h3 className="text-sm font-bold text-slate-900">Lecture-by-Lecture Attendance History</h3>
              <p className="text-xs text-slate-500">Present = 1.0, Late = 0.5, Absent = 0.0</p>
            </div>
            <div className="text-right">
              <div className="text-xs text-slate-500">Current Standing</div>
              <div className={`text-base font-bold font-mono ${attendancePct >= 75 ? 'text-emerald-600' : 'text-rose-600'}`}>
                {attendancePct.toFixed(1)}%
              </div>
            </div>
          </div>

          <div className="overflow-x-auto">
            <table className="w-full text-left text-xs">
              <thead className="bg-slate-50 border-b border-slate-200 text-slate-500 font-semibold uppercase">
                <tr>
                  <th className="py-2.5 px-3">Date</th>
                  <th className="py-2.5 px-3">Status</th>
                  <th className="py-2.5 px-3">Faculty Remarks</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-100">
                {myAttendance.map(att => (
                  <tr key={att.attendance_id} className="hover:bg-slate-50">
                    <td className="py-2.5 px-3 font-mono font-semibold text-slate-900">{att.attendance_date}</td>
                    <td className="py-2.5 px-3">
                      <span className={`px-2 py-0.5 rounded font-medium ${att.status === 'Present' ? 'bg-emerald-50 text-emerald-700' : (att.status === 'Late' ? 'bg-amber-50 text-amber-700' : 'bg-rose-50 text-rose-700')}`}>
                        {att.status}
                      </span>
                    </td>
                    <td className="py-2.5 px-3 text-slate-500">{att.remarks || 'Regular class session'}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      )}

      {/* RESULTS TAB */}
      {activeTab === 'results' && (
        <div className="bg-white border border-slate-200 rounded-xl shadow-xs p-5 space-y-4">
          <div className="flex items-center justify-between pb-3 border-b border-slate-200">
            <div>
              <h3 className="text-sm font-bold text-slate-900">Official Semester Gradecard</h3>
              <p className="text-xs text-slate-500">Academic credentials evaluated under BCA Semester 3 curriculum</p>
            </div>
            <div className="text-right">
              <div className="text-xs text-slate-500">Aggregate Score</div>
              <div className="text-base font-bold font-mono text-blue-600">{overallPct.toFixed(1)}%</div>
            </div>
          </div>

          <div className="overflow-x-auto">
            <table className="w-full text-left text-xs">
              <thead className="bg-slate-50 border-b border-slate-200 text-slate-500 font-semibold uppercase">
                <tr>
                  <th className="py-2.5 px-3">Subject Code</th>
                  <th className="py-2.5 px-3">Subject Name</th>
                  <th className="py-2.5 px-3">Credits</th>
                  <th className="py-2.5 px-3">Max Marks</th>
                  <th className="py-2.5 px-3">Obtained</th>
                  <th className="py-2.5 px-3">Grade</th>
                  <th className="py-2.5 px-3">Status</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-100">
                {myResults.map(res => {
                  const sb = subjects.find(s => s.subject_id === res.subject_id);
                  return (
                    <tr key={res.result_id} className="hover:bg-slate-50">
                      <td className="py-2.5 px-3 font-mono font-bold text-blue-600">{sb?.subject_code}</td>
                      <td className="py-2.5 px-3 font-semibold text-slate-900">{sb?.subject_name}</td>
                      <td className="py-2.5 px-3 font-mono">{sb?.credits}</td>
                      <td className="py-2.5 px-3 font-mono">{res.max_marks}</td>
                      <td className="py-2.5 px-3 font-mono font-bold">{res.obtained_marks}</td>
                      <td className="py-2.5 px-3 font-bold">{res.grade}</td>
                      <td className="py-2.5 px-3">
                        <span className={`px-2 py-0.5 rounded font-medium ${res.status === 'Pass' ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700'}`}>
                          {res.status}
                        </span>
                      </td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          </div>
        </div>
      )}

      {/* FEES TAB */}
      {activeTab === 'fees' && (
        <div className="bg-white border border-slate-200 rounded-xl shadow-xs p-5 space-y-4">
          <div className="flex items-center justify-between pb-3 border-b border-slate-200">
            <div>
              <h3 className="text-sm font-bold text-slate-900">Fee Invoices &amp; Receipts</h3>
              <p className="text-xs text-slate-500">Official statement of institutional payments and outstanding dues</p>
            </div>
            <div className="text-right">
              <div className="text-xs text-slate-500">Balance Due</div>
              <div className={`text-base font-bold font-mono ${totalDue > 0 ? 'text-rose-600' : 'text-emerald-600'}`}>
                ₹{totalDue.toLocaleString()}
              </div>
            </div>
          </div>

          <div className="overflow-x-auto">
            <table className="w-full text-left text-xs">
              <thead className="bg-slate-50 border-b border-slate-200 text-slate-500 font-semibold uppercase">
                <tr>
                  <th className="py-2.5 px-3">Invoice #</th>
                  <th className="py-2.5 px-3">Description</th>
                  <th className="py-2.5 px-3">Billed</th>
                  <th className="py-2.5 px-3">Paid</th>
                  <th className="py-2.5 px-3">Balance</th>
                  <th className="py-2.5 px-3">Due Date</th>
                  <th className="py-2.5 px-3">Status</th>
                  <th className="py-2.5 px-3">Txn Reference</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-100">
                {myFees.map(f => {
                  const bal = f.total_amount - f.paid_amount;
                  return (
                    <tr key={f.fee_id} className="hover:bg-slate-50">
                      <td className="py-2.5 px-3 font-mono font-bold">#{f.fee_id}</td>
                      <td className="py-2.5 px-3 font-semibold text-slate-900">{f.fee_type}</td>
                      <td className="py-2.5 px-3 font-mono">₹{f.total_amount.toLocaleString()}</td>
                      <td className="py-2.5 px-3 font-mono text-emerald-600">₹{f.paid_amount.toLocaleString()}</td>
                      <td className="py-2.5 px-3 font-mono font-bold text-rose-600">₹{bal.toLocaleString()}</td>
                      <td className="py-2.5 px-3 font-mono text-slate-500">{f.due_date}</td>
                      <td className="py-2.5 px-3">
                        <span className={`px-2 py-0.5 rounded font-medium ${f.payment_status === 'Paid' ? 'bg-emerald-50 text-emerald-700' : (f.payment_status === 'Partial' ? 'bg-amber-50 text-amber-700' : 'bg-rose-50 text-rose-700')}`}>
                          {f.payment_status}
                        </span>
                      </td>
                      <td className="py-2.5 px-3 font-mono text-[11px] text-slate-500">
                        {f.reference_no || 'Pending Receipt'}
                      </td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          </div>
        </div>
      )}

      {/* NOTICES TAB */}
      {activeTab === 'notices' && (
        <div className="space-y-4">
          <div className="flex items-center justify-between">
            <h3 className="text-sm font-bold text-slate-900">Campus Circulars &amp; Notices</h3>
            <span className="text-xs text-slate-500">{notices.filter(n => n.status === 'Published').length} active announcements</span>
          </div>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            {notices.filter(n => n.status === 'Published').map(not => (
              <div key={not.notice_id} className="bg-white border border-slate-200 rounded-xl p-4 shadow-xs">
                <div className="flex items-center justify-between text-xs text-slate-500 mb-2">
                  <span className="font-semibold text-blue-700 px-2 py-0.5 rounded bg-blue-50">{not.category}</span>
                  <span className="font-mono">{not.publish_date}</span>
                </div>
                <h4 className="text-sm font-bold text-slate-900 mb-1">{not.title}</h4>
                <p className="text-xs text-slate-600 leading-relaxed">{not.content}</p>
              </div>
            ))}
          </div>
        </div>
      )}

      {/* PROFILE & PASSWORD TAB */}
      {activeTab === 'profile' && (
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <div className="bg-white border border-slate-200 rounded-xl p-5 shadow-xs space-y-4">
            <h3 className="text-sm font-bold text-slate-900 border-b border-slate-100 pb-2">Personal &amp; Enrollment Info</h3>
            <div className="grid grid-cols-2 gap-3 text-xs">
              <div>
                <div className="text-slate-400">Roll Number</div>
                <div className="font-mono font-bold text-blue-600 text-sm">{currentStudent.student_code}</div>
              </div>
              <div>
                <div className="text-slate-400">Status</div>
                <div className="font-semibold text-emerald-600">{currentStudent.enrollment_status}</div>
              </div>
              <div>
                <div className="text-slate-400">Full Name</div>
                <div className="font-semibold text-slate-800">{currentStudent.full_name}</div>
              </div>
              <div>
                <div className="text-slate-400">Gender</div>
                <div className="text-slate-800">{currentStudent.gender}</div>
              </div>
              <div>
                <div className="text-slate-400">Email Address</div>
                <div className="text-slate-800">{currentStudent.email}</div>
              </div>
              <div>
                <div className="text-slate-400">Phone Number</div>
                <div className="font-mono text-slate-800">{currentStudent.phone}</div>
              </div>
              <div className="col-span-2">
                <div className="text-slate-400">Address</div>
                <div className="text-slate-800">{currentStudent.address}</div>
              </div>
            </div>
          </div>

          <div className="bg-white border border-slate-200 rounded-xl p-5 shadow-xs space-y-4">
            <h3 className="text-sm font-bold text-slate-900 border-b border-slate-100 pb-2">Change Account Password</h3>
            {passMsg && (
              <div className={`p-2.5 rounded-lg text-xs font-medium ${passMsg.type === 'success' ? 'bg-emerald-50 text-emerald-800' : 'bg-rose-50 text-rose-800'}`}>
                {passMsg.text}
              </div>
            )}
            <form onSubmit={handlePasswordSubmit} className="space-y-3 text-xs">
              <div>
                <label className="block text-slate-600 font-medium mb-1">Current Password</label>
                <input
                  type="password"
                  placeholder="Enter current password"
                  value={currentPass}
                  onChange={e => setCurrentPass(e.target.value)}
                  className="w-full py-1.5 px-3 border border-slate-200 rounded-lg"
                  required
                />
              </div>
              <div>
                <label className="block text-slate-600 font-medium mb-1">New Password (min 8 chars)</label>
                <input
                  type="password"
                  placeholder="Enter new password"
                  value={newPass}
                  onChange={e => setNewPass(e.target.value)}
                  className="w-full py-1.5 px-3 border border-slate-200 rounded-lg"
                  required
                />
              </div>
              <div>
                <label className="block text-slate-600 font-medium mb-1">Confirm New Password</label>
                <input
                  type="password"
                  placeholder="Confirm new password"
                  value={confirmPass}
                  onChange={e => setConfirmPass(e.target.value)}
                  className="w-full py-1.5 px-3 border border-slate-200 rounded-lg"
                  required
                />
              </div>
              <button
                type="submit"
                className="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 rounded-lg transition-colors"
              >
                Update Password
              </button>
            </form>
          </div>
        </div>
      )}
    </div>
  );
};
