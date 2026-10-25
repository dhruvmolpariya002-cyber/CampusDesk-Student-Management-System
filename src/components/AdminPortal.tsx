import React, { useState } from 'react';
import { 
  Users, BookOpen, Calendar, Award, Wallet, Megaphone, FileSpreadsheet,
  Plus, Trash2, Edit2, CheckCircle2, AlertTriangle, Search, Filter, Download
} from 'lucide-react';
import { 
  Student, Course, Department, Subject, Examination, Result, AttendanceRecord, FeeRecord, Notice 
} from '../data/initialData';

interface AdminPortalProps {
  students: Student[];
  courses: Course[];
  departments: Department[];
  subjects: Subject[];
  examinations: Examination[];
  results: Result[];
  attendance: AttendanceRecord[];
  fees: FeeRecord[];
  notices: Notice[];
  onAddStudent: (student: Omit<Student, 'student_id'>) => void;
  onDeleteStudent: (id: number) => void;
  onAddAttendance: (records: Omit<AttendanceRecord, 'attendance_id'>[]) => void;
  onAddResult: (result: Omit<Result, 'result_id'>) => void;
  onAddFee: (fee: Omit<FeeRecord, 'fee_id'>) => void;
  onPayFee: (feeId: number, amount: number, refNo: string) => void;
  onAddNotice: (notice: Omit<Notice, 'notice_id'>) => void;
  onDeleteNotice: (id: number) => void;
}

export const AdminPortal: React.FC<AdminPortalProps> = ({
  students,
  courses,
  departments,
  subjects,
  examinations,
  results,
  attendance,
  fees,
  notices,
  onAddStudent,
  onDeleteStudent,
  onAddAttendance,
  onAddResult,
  onAddFee,
  onPayFee,
  onAddNotice,
  onDeleteNotice
}) => {
  const [activeTab, setActiveTab] = useState<'overview' | 'students' | 'attendance' | 'results' | 'fees' | 'notices' | 'reports'>('overview');
  
  // Student filter state
  const [studentSearch, setStudentSearch] = useState('');
  const [courseFilter, setCourseFilter] = useState<number>(0);
  const [showAddStudentModal, setShowAddStudentModal] = useState(false);

  // New Student Form state
  const [newRoll, setNewRoll] = useState(`BCA2024${Math.floor(100 + Math.random() * 900)}`);
  const [newName, setNewName] = useState('');
  const [newEmail, setNewEmail] = useState('');
  const [newPhone, setNewPhone] = useState('9876543210');
  const [newCourseId, setNewCourseId] = useState(courses[0]?.course_id || 1);
  const [newGender, setNewGender] = useState<'Male' | 'Female' | 'Other'>('Male');
  const [newAddress, setNewAddress] = useState('Bangalore, India');
  const [newDob, setNewDob] = useState('2004-05-10');

  // Attendance state
  const [attCourseId, setAttCourseId] = useState(courses[0]?.course_id || 1);
  const [attDate, setAttDate] = useState('2024-10-15');
  const [batchStatus, setBatchStatus] = useState<Record<number, 'Present' | 'Absent' | 'Late'>>({});

  // Marks state
  const [markExamId, setMarkExamId] = useState(examinations[0]?.exam_id || 1);
  const [markStudentId, setMarkStudentId] = useState(students[0]?.student_id || 1);
  const [markSubjectId, setMarkSubjectId] = useState(subjects[0]?.subject_id || 1);
  const [markMax, setMarkMax] = useState(100);
  const [markObt, setMarkObt] = useState(85);
  const [markRemarks, setMarkRemarks] = useState('');

  // Fee payment modal
  const [payFeeId, setPayFeeId] = useState<number | null>(null);
  const [payAmount, setPayAmount] = useState(0);
  const [payRef, setPayRef] = useState('');

  // Notice form
  const [noticeTitle, setNoticeTitle] = useState('');
  const [noticeContent, setNoticeContent] = useState('');
  const [noticeCategory, setNoticeCategory] = useState<'Academic' | 'Examination' | 'Event' | 'Urgent' | 'General'>('Academic');

  // Filtered students
  const filteredStudents = students.filter(st => {
    const matchSearch = st.full_name.toLowerCase().includes(studentSearch.toLowerCase()) ||
                        st.student_code.toLowerCase().includes(studentSearch.toLowerCase()) ||
                        st.email.toLowerCase().includes(studentSearch.toLowerCase());
    const matchCourse = courseFilter === 0 || st.course_id === courseFilter;
    return matchSearch && matchCourse;
  });

  // Calculate metrics
  const totalBilledFees = fees.reduce((acc, f) => acc + f.total_amount, 0);
  const totalPaidFees = fees.reduce((acc, f) => acc + f.paid_amount, 0);
  const totalOutstanding = totalBilledFees - totalPaidFees;

  // Handle student creation
  const handleCreateStudent = (e: React.FormEvent) => {
    e.preventDefault();
    if (!newName.trim() || !newEmail.trim() || !newRoll.trim()) return;
    onAddStudent({
      student_code: newRoll,
      full_name: newName,
      email: newEmail,
      phone: newPhone,
      course_id: Number(newCourseId),
      dob: newDob,
      gender: newGender,
      address: newAddress,
      admission_date: new Date().toISOString().split('T')[0],
      enrollment_status: 'Active'
    });
    setShowAddStudentModal(false);
    setNewName('');
    setNewEmail('');
    setNewRoll(`BCA2024${Math.floor(100 + Math.random() * 900)}`);
  };

  // Handle batch attendance save
  const handleSaveAttendance = () => {
    const recordsToSave: Omit<AttendanceRecord, 'attendance_id'>[] = [];
    const courseStudents = students.filter(s => s.course_id === attCourseId);
    
    courseStudents.forEach(st => {
      const status = batchStatus[st.student_id] || 'Present';
      recordsToSave.push({
        student_id: st.student_id,
        course_id: attCourseId,
        attendance_date: attDate,
        status: status,
        remarks: 'Live Session Marking'
      });
    });

    onAddAttendance(recordsToSave);
    alert(`Saved attendance records for ${recordsToSave.length} students on ${attDate}`);
  };

  // Handle result submission
  const handleSaveResult = (e: React.FormEvent) => {
    e.preventDefault();
    if (markObt > markMax) {
      alert(`Error: Obtained marks (${markObt}) cannot exceed maximum marks (${markMax})`);
      return;
    }
    const pct = (markObt / markMax) * 100;
    let grade = 'F';
    let status: 'Pass' | 'Fail' = 'Fail';
    if (pct >= 90) { grade = 'O'; status = 'Pass'; }
    else if (pct >= 80) { grade = 'A+'; status = 'Pass'; }
    else if (pct >= 70) { grade = 'A'; status = 'Pass'; }
    else if (pct >= 60) { grade = 'B+'; status = 'Pass'; }
    else if (pct >= 50) { grade = 'B'; status = 'Pass'; }
    else if (pct >= 40) { grade = 'C'; status = 'Pass'; }

    onAddResult({
      exam_id: Number(markExamId),
      student_id: Number(markStudentId),
      subject_id: Number(markSubjectId),
      max_marks: Number(markMax),
      obtained_marks: Number(markObt),
      grade,
      status,
      remarks: markRemarks || 'Evaluated'
    });
    alert(`Mark record saved: Grade ${grade} (${status})`);
  };

  // CSV export function
  const exportToCSV = (type: string) => {
    let csvContent = "data:text/csv;charset=utf-8,\uFEFF";
    if (type === 'students') {
      csvContent += "Roll No,Name,Email,Phone,Course,Status\n";
      students.forEach(s => {
        const c = courses.find(cr => cr.course_id === s.course_id)?.course_code || '';
        csvContent += `"${s.student_code}","${s.full_name}","${s.email}","${s.phone}","${c}","${s.enrollment_status}"\n`;
      });
    } else if (type === 'fees') {
      csvContent += "Invoice ID,Student,Fee Type,Total (INR),Paid (INR),Due (INR),Status\n";
      fees.forEach(f => {
        const s = students.find(st => st.student_id === f.student_id)?.full_name || '';
        const bal = f.total_amount - f.paid_amount;
        csvContent += `"${f.fee_id}","${s}","${f.fee_type}",${f.total_amount},${f.paid_amount},${bal},"${f.payment_status}"\n`;
      });
    }
    const encodedUri = encodeURI(csvContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", `campusdesk_${type}_${new Date().toISOString().split('T')[0]}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  };

  return (
    <div className="space-y-6">
      {/* Top Secondary Navigation Tabs */}
      <div className="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 pb-3">
        <div className="flex flex-wrap gap-1 bg-slate-100 p-1 rounded-lg">
          <button
            onClick={() => setActiveTab('overview')}
            className={`px-3 py-1.5 text-xs font-semibold rounded-md transition-colors ${activeTab === 'overview' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-600 hover:text-slate-900'}`}
          >
            Overview
          </button>
          <button
            onClick={() => setActiveTab('students')}
            className={`px-3 py-1.5 text-xs font-semibold rounded-md transition-colors ${activeTab === 'students' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-600 hover:text-slate-900'}`}
          >
            Students Directory ({students.length})
          </button>
          <button
            onClick={() => setActiveTab('attendance')}
            className={`px-3 py-1.5 text-xs font-semibold rounded-md transition-colors ${activeTab === 'attendance' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-600 hover:text-slate-900'}`}
          >
            Roll-Call Attendance
          </button>
          <button
            onClick={() => setActiveTab('results')}
            className={`px-3 py-1.5 text-xs font-semibold rounded-md transition-colors ${activeTab === 'results' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-600 hover:text-slate-900'}`}
          >
            Marks &amp; Results
          </button>
          <button
            onClick={() => setActiveTab('fees')}
            className={`px-3 py-1.5 text-xs font-semibold rounded-md transition-colors ${activeTab === 'fees' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-600 hover:text-slate-900'}`}
          >
            Fee Ledgers
          </button>
          <button
            onClick={() => setActiveTab('notices')}
            className={`px-3 py-1.5 text-xs font-semibold rounded-md transition-colors ${activeTab === 'notices' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-600 hover:text-slate-900'}`}
          >
            Notices ({notices.length})
          </button>
          <button
            onClick={() => setActiveTab('reports')}
            className={`px-3 py-1.5 text-xs font-semibold rounded-md transition-colors ${activeTab === 'reports' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-600 hover:text-slate-900'}`}
          >
            Reports &amp; CSV
          </button>
        </div>

        <div className="flex items-center gap-2">
          <button
            onClick={() => setShowAddStudentModal(true)}
            className="inline-flex items-center gap-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold px-3 py-1.5 rounded-md transition-colors shadow-sm"
          >
            <Plus className="w-3.5 h-3.5" />
            Add Student
          </button>
        </div>
      </div>

      {/* TAB 1: OVERVIEW */}
      {activeTab === 'overview' && (
        <div className="space-y-6">
          {/* Key Metric Cards */}
          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div className="bg-white border border-slate-200 rounded-xl p-4 shadow-xs">
              <div className="flex items-center justify-between">
                <div>
                  <div className="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Students</div>
                  <div className="text-2xl font-bold text-slate-900 font-mono mt-1">{students.length}</div>
                </div>
                <div className="p-2.5 bg-blue-50 text-blue-600 rounded-lg">
                  <Users className="w-5 h-5" />
                </div>
              </div>
              <div className="text-xs text-slate-500 mt-2">Active college admissions</div>
            </div>

            <div className="bg-white border border-slate-200 rounded-xl p-4 shadow-xs">
              <div className="flex items-center justify-between">
                <div>
                  <div className="text-xs font-semibold text-slate-500 uppercase tracking-wider">Active Courses</div>
                  <div className="text-2xl font-bold text-slate-900 font-mono mt-1">{courses.length}</div>
                </div>
                <div className="p-2.5 bg-emerald-50 text-emerald-600 rounded-lg">
                  <BookOpen className="w-5 h-5" />
                </div>
              </div>
              <div className="text-xs text-slate-500 mt-2">BCA, B.Sc IT, MCA programs</div>
            </div>

            <div className="bg-white border border-slate-200 rounded-xl p-4 shadow-xs">
              <div className="flex items-center justify-between">
                <div>
                  <div className="text-xs font-semibold text-slate-500 uppercase tracking-wider">Attendance Logs</div>
                  <div className="text-2xl font-bold text-slate-900 font-mono mt-1">{attendance.length}</div>
                </div>
                <div className="p-2.5 bg-sky-50 text-sky-600 rounded-lg">
                  <Calendar className="w-5 h-5" />
                </div>
              </div>
              <div className="text-xs text-slate-500 mt-2">Recorded classroom sessions</div>
            </div>

            <div className="bg-white border border-slate-200 rounded-xl p-4 shadow-xs">
              <div className="flex items-center justify-between">
                <div>
                  <div className="text-xs font-semibold text-slate-500 uppercase tracking-wider">Fee Outstanding</div>
                  <div className="text-2xl font-bold text-rose-600 font-mono mt-1">₹{totalOutstanding.toLocaleString()}</div>
                </div>
                <div className="p-2.5 bg-rose-50 text-rose-600 rounded-lg">
                  <Wallet className="w-5 h-5" />
                </div>
              </div>
              <div className="text-xs text-slate-500 mt-2">Pending tuition installments</div>
            </div>
          </div>

          {/* Recent Records Grid */}
          <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {/* Recent Students */}
            <div className="bg-white border border-slate-200 rounded-xl p-5 shadow-xs">
              <div className="flex items-center justify-between mb-4">
                <h3 className="text-sm font-bold text-slate-900">Recent Enrolled Students</h3>
                <button onClick={() => setActiveTab('students')} className="text-xs text-blue-600 hover:underline">
                  View All &rarr;
                </button>
              </div>
              <div className="divide-y divide-slate-100">
                {students.slice(0, 4).map(st => {
                  const course = courses.find(c => c.course_id === st.course_id);
                  return (
                    <div key={st.student_id} className="py-2.5 flex items-center justify-between">
                      <div>
                        <div className="text-sm font-semibold text-slate-900">{st.full_name}</div>
                        <div className="text-xs text-slate-500 font-mono">{st.student_code} · {course?.course_code}</div>
                      </div>
                      <span className="text-xs font-semibold px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700">
                        {st.enrollment_status}
                      </span>
                    </div>
                  );
                })}
              </div>
            </div>

            {/* Recent Notices */}
            <div className="bg-white border border-slate-200 rounded-xl p-5 shadow-xs">
              <div className="flex items-center justify-between mb-4">
                <h3 className="text-sm font-bold text-slate-900">Latest Circulars</h3>
                <button onClick={() => setActiveTab('notices')} className="text-xs text-blue-600 hover:underline">
                  Manage Notices &rarr;
                </button>
              </div>
              <div className="space-y-3">
                {notices.slice(0, 3).map(not => (
                  <div key={not.notice_id} className="p-3 bg-slate-50 rounded-lg border border-slate-100">
                    <div className="flex items-center justify-between text-xs text-slate-500 mb-1">
                      <span className="font-semibold text-blue-700">{not.category}</span>
                      <span>{not.publish_date}</span>
                    </div>
                    <div className="text-sm font-semibold text-slate-900">{not.title}</div>
                    <div className="text-xs text-slate-600 mt-1 line-clamp-1">{not.content}</div>
                  </div>
                ))}
              </div>
            </div>
          </div>
        </div>
      )}

      {/* TAB 2: STUDENTS DIRECTORY */}
      {activeTab === 'students' && (
        <div className="bg-white border border-slate-200 rounded-xl shadow-xs overflow-hidden">
          {/* Search & Filter Header */}
          <div className="p-4 border-b border-slate-200 flex flex-wrap items-center justify-between gap-3 bg-slate-50/50">
            <div className="flex flex-wrap items-center gap-2 flex-grow max-w-lg">
              <div className="relative flex-grow">
                <Search className="w-4 h-4 absolute left-3 top-2.5 text-slate-400" />
                <input
                  type="text"
                  placeholder="Search by name, roll no, email..."
                  value={studentSearch}
                  onChange={e => setStudentSearch(e.target.value)}
                  className="w-full text-xs pl-9 pr-3 py-2 bg-white border border-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-blue-500"
                />
              </div>
              <select
                value={courseFilter}
                onChange={e => setCourseFilter(Number(e.target.value))}
                className="text-xs py-2 px-3 bg-white border border-slate-200 rounded-lg focus:outline-none"
              >
                <option value={0}>All Courses</option>
                {courses.map(c => (
                  <option key={c.course_id} value={c.course_id}>{c.course_code}</option>
                ))}
              </select>
            </div>
            <div className="text-xs text-slate-500">
              Showing <strong>{filteredStudents.length}</strong> of {students.length} students
            </div>
          </div>

          {/* Table */}
          <div className="overflow-x-auto">
            <table className="w-full text-left text-xs">
              <thead className="bg-slate-50 border-b border-slate-200 text-slate-500 font-semibold uppercase tracking-wider">
                <tr>
                  <th className="py-3 px-4">Roll Number</th>
                  <th className="py-3 px-4">Student Name</th>
                  <th className="py-3 px-4">Course</th>
                  <th className="py-3 px-4">Contact</th>
                  <th className="py-3 px-4">Admission Date</th>
                  <th className="py-3 px-4">Status</th>
                  <th className="py-3 px-4 text-right">Actions</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-100">
                {filteredStudents.map(st => {
                  const course = courses.find(c => c.course_id === st.course_id);
                  return (
                    <tr key={st.student_id} className="hover:bg-slate-50/80 transition-colors">
                      <td className="py-3 px-4 font-mono font-bold text-blue-600">{st.student_code}</td>
                      <td className="py-3 px-4">
                        <div className="font-semibold text-slate-900">{st.full_name}</div>
                        <div className="text-slate-400 text-[11px]">{st.address}</div>
                      </td>
                      <td className="py-3 px-4">
                        <span className="font-mono text-xs px-2 py-0.5 rounded bg-slate-100 text-slate-700">
                          {course?.course_code}
                        </span>
                      </td>
                      <td className="py-3 px-4">
                        <div>{st.email}</div>
                        <div className="text-slate-400 font-mono">{st.phone}</div>
                      </td>
                      <td className="py-3 px-4 font-mono text-slate-500">{st.admission_date}</td>
                      <td className="py-3 px-4">
                        <span className="font-medium text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded">
                          {st.enrollment_status}
                        </span>
                      </td>
                      <td className="py-3 px-4 text-right">
                        <button
                          onClick={() => {
                            if (confirm(`Are you sure you want to delete ${st.full_name}?`)) {
                              onDeleteStudent(st.student_id);
                            }
                          }}
                          className="p-1.5 text-slate-400 hover:text-rose-600 rounded hover:bg-rose-50 transition-colors"
                          title="Delete Student"
                        >
                          <Trash2 className="w-4 h-4" />
                        </button>
                      </td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          </div>
        </div>
      )}

      {/* TAB 3: ROLL-CALL ATTENDANCE */}
      {activeTab === 'attendance' && (
        <div className="bg-white border border-slate-200 rounded-xl shadow-xs p-5 space-y-5">
          <div className="flex flex-wrap items-center justify-between gap-3 pb-4 border-b border-slate-200">
            <div>
              <h3 className="text-sm font-bold text-slate-900">Roll-Call Session Sheet</h3>
              <p className="text-xs text-slate-500">Record daily classroom attendance with Present (1.0), Late (0.5), and Absent (0.0) weights</p>
            </div>
            <div className="flex items-center gap-3">
              <select
                value={attCourseId}
                onChange={e => setAttCourseId(Number(e.target.value))}
                className="text-xs py-1.5 px-3 bg-white border border-slate-200 rounded-lg"
              >
                {courses.map(c => (
                  <option key={c.course_id} value={c.course_id}>{c.course_code} — {c.course_name}</option>
                ))}
              </select>
              <input
                type="date"
                value={attDate}
                onChange={e => setAttDate(e.target.value)}
                className="text-xs py-1.5 px-3 bg-white border border-slate-200 rounded-lg font-mono"
              />
            </div>
          </div>

          {/* Student roll call table */}
          <div className="overflow-x-auto">
            <table className="w-full text-left text-xs">
              <thead className="bg-slate-50 border-b border-slate-200 text-slate-500 font-semibold uppercase">
                <tr>
                  <th className="py-2.5 px-4">Roll No</th>
                  <th className="py-2.5 px-4">Student Name</th>
                  <th className="py-2.5 px-4">Mark Attendance</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-100">
                {students.filter(s => s.course_id === attCourseId).map(st => {
                  const currentStat = batchStatus[st.student_id] || 'Present';
                  return (
                    <tr key={st.student_id} className="hover:bg-slate-50">
                      <td className="py-2.5 px-4 font-mono font-bold text-blue-600">{st.student_code}</td>
                      <td className="py-2.5 px-4 font-semibold text-slate-900">{st.full_name}</td>
                      <td className="py-2.5 px-4">
                        <div className="flex items-center gap-3">
                          <label className="flex items-center gap-1 cursor-pointer">
                            <input
                              type="radio"
                              name={`stat_${st.student_id}`}
                              checked={currentStat === 'Present'}
                              onChange={() => setBatchStatus(prev => ({ ...prev, [st.student_id]: 'Present' }))}
                              className="text-emerald-600"
                            />
                            <span className="text-emerald-700 font-medium">Present</span>
                          </label>
                          <label className="flex items-center gap-1 cursor-pointer">
                            <input
                              type="radio"
                              name={`stat_${st.student_id}`}
                              checked={currentStat === 'Late'}
                              onChange={() => setBatchStatus(prev => ({ ...prev, [st.student_id]: 'Late' }))}
                              className="text-amber-600"
                            />
                            <span className="text-amber-700 font-medium">Late</span>
                          </label>
                          <label className="flex items-center gap-1 cursor-pointer">
                            <input
                              type="radio"
                              name={`stat_${st.student_id}`}
                              checked={currentStat === 'Absent'}
                              onChange={() => setBatchStatus(prev => ({ ...prev, [st.student_id]: 'Absent' }))}
                              className="text-rose-600"
                            />
                            <span className="text-rose-700 font-medium">Absent</span>
                          </label>
                        </div>
                      </td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          </div>

          <div className="flex justify-end pt-3 border-t border-slate-100">
            <button
              onClick={handleSaveAttendance}
              className="bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold px-4 py-2 rounded-lg transition-colors shadow-sm"
            >
              Save Attendance Session
            </button>
          </div>
        </div>
      )}

      {/* TAB 4: MARKS & RESULTS */}
      {activeTab === 'results' && (
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
          {/* Result Entry Form */}
          <div className="bg-white border border-slate-200 rounded-xl p-5 shadow-xs h-fit space-y-4">
            <h3 className="text-sm font-bold text-slate-900">Record Subject Marks</h3>
            <form onSubmit={handleSaveResult} className="space-y-3 text-xs">
              <div>
                <label className="block text-slate-600 font-medium mb-1">Examination</label>
                <select
                  value={markExamId}
                  onChange={e => setMarkExamId(Number(e.target.value))}
                  className="w-full py-1.5 px-3 border border-slate-200 rounded-lg bg-white"
                >
                  {examinations.map(ex => (
                    <option key={ex.exam_id} value={ex.exam_id}>{ex.exam_name}</option>
                  ))}
                </select>
              </div>

              <div>
                <label className="block text-slate-600 font-medium mb-1">Student</label>
                <select
                  value={markStudentId}
                  onChange={e => setMarkStudentId(Number(e.target.value))}
                  className="w-full py-1.5 px-3 border border-slate-200 rounded-lg bg-white"
                >
                  {students.map(st => (
                    <option key={st.student_id} value={st.student_id}>{st.student_code} — {st.full_name}</option>
                  ))}
                </select>
              </div>

              <div>
                <label className="block text-slate-600 font-medium mb-1">Subject</label>
                <select
                  value={markSubjectId}
                  onChange={e => setMarkSubjectId(Number(e.target.value))}
                  className="w-full py-1.5 px-3 border border-slate-200 rounded-lg bg-white"
                >
                  {subjects.map(sb => (
                    <option key={sb.subject_id} value={sb.subject_id}>{sb.subject_code} — {sb.subject_name}</option>
                  ))}
                </select>
              </div>

              <div className="grid grid-cols-2 gap-2">
                <div>
                  <label className="block text-slate-600 font-medium mb-1">Max Marks</label>
                  <input
                    type="number"
                    value={markMax}
                    onChange={e => setMarkMax(Number(e.target.value))}
                    className="w-full py-1.5 px-3 border border-slate-200 rounded-lg font-mono"
                  />
                </div>
                <div>
                  <label className="block text-slate-600 font-medium mb-1">Obtained</label>
                  <input
                    type="number"
                    value={markObt}
                    onChange={e => setMarkObt(Number(e.target.value))}
                    className="w-full py-1.5 px-3 border border-slate-200 rounded-lg font-mono"
                  />
                </div>
              </div>

              <div>
                <label className="block text-slate-600 font-medium mb-1">Remarks</label>
                <input
                  type="text"
                  placeholder="e.g. Good theoretical understanding"
                  value={markRemarks}
                  onChange={e => setMarkRemarks(e.target.value)}
                  className="w-full py-1.5 px-3 border border-slate-200 rounded-lg"
                />
              </div>

              <button
                type="submit"
                className="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 rounded-lg transition-colors"
              >
                Compute &amp; Save Mark
              </button>
            </form>
          </div>

          {/* Results List */}
          <div className="lg:col-span-2 bg-white border border-slate-200 rounded-xl p-5 shadow-xs">
            <h3 className="text-sm font-bold text-slate-900 mb-3">Evaluated Marksheets ({results.length})</h3>
            <div className="overflow-x-auto">
              <table className="w-full text-left text-xs">
                <thead className="bg-slate-50 border-b border-slate-200 text-slate-500 font-semibold uppercase">
                  <tr>
                    <th className="py-2.5 px-3">Student</th>
                    <th className="py-2.5 px-3">Subject</th>
                    <th className="py-2.5 px-3">Score</th>
                    <th className="py-2.5 px-3">Grade</th>
                    <th className="py-2.5 px-3">Status</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-100">
                  {results.map(res => {
                    const st = students.find(s => s.student_id === res.student_id);
                    const sb = subjects.find(s => s.subject_id === res.subject_id);
                    return (
                      <tr key={res.result_id} className="hover:bg-slate-50">
                        <td className="py-2.5 px-3 font-semibold text-slate-900">{st?.full_name}</td>
                        <td className="py-2.5 px-3 font-mono">{sb?.subject_code}</td>
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

      {/* TAB 5: FEES */}
      {activeTab === 'fees' && (
        <div className="bg-white border border-slate-200 rounded-xl shadow-xs p-5 space-y-4">
          <div className="flex items-center justify-between pb-3 border-b border-slate-200">
            <div>
              <h3 className="text-sm font-bold text-slate-900">Student Fee Ledgers &amp; Dues</h3>
              <p className="text-xs text-slate-500">Track tuition installments, verify balances, and record payments</p>
            </div>
            <button
              onClick={() => exportToCSV('fees')}
              className="inline-flex items-center gap-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold px-3 py-1.5 rounded-md"
            >
              <Download className="w-3.5 h-3.5" />
              Export Fee Ledger CSV
            </button>
          </div>

          <div className="overflow-x-auto">
            <table className="w-full text-left text-xs">
              <thead className="bg-slate-50 border-b border-slate-200 text-slate-500 font-semibold uppercase">
                <tr>
                  <th className="py-2.5 px-3">Invoice #</th>
                  <th className="py-2.5 px-3">Student Name</th>
                  <th className="py-2.5 px-3">Description</th>
                  <th className="py-2.5 px-3">Billed</th>
                  <th className="py-2.5 px-3">Paid</th>
                  <th className="py-2.5 px-3">Balance Due</th>
                  <th className="py-2.5 px-3">Status</th>
                  <th className="py-2.5 px-3 text-right">Action</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-100">
                {fees.map(f => {
                  const st = students.find(s => s.student_id === f.student_id);
                  const bal = f.total_amount - f.paid_amount;
                  return (
                    <tr key={f.fee_id} className="hover:bg-slate-50">
                      <td className="py-2.5 px-3 font-mono font-bold">#{f.fee_id}</td>
                      <td className="py-2.5 px-3 font-semibold text-slate-900">{st?.full_name}</td>
                      <td className="py-2.5 px-3 text-slate-600">{f.fee_type}</td>
                      <td className="py-2.5 px-3 font-mono">₹{f.total_amount.toLocaleString()}</td>
                      <td className="py-2.5 px-3 font-mono text-emerald-600">₹{f.paid_amount.toLocaleString()}</td>
                      <td className="py-2.5 px-3 font-mono font-bold text-rose-600">₹{bal.toLocaleString()}</td>
                      <td className="py-2.5 px-3">
                        <span className={`px-2 py-0.5 rounded font-medium ${f.payment_status === 'Paid' ? 'bg-emerald-50 text-emerald-700' : (f.payment_status === 'Partial' ? 'bg-amber-50 text-amber-700' : 'bg-rose-50 text-rose-700')}`}>
                          {f.payment_status}
                        </span>
                      </td>
                      <td className="py-2.5 px-3 text-right">
                        {bal > 0 ? (
                          <button
                            onClick={() => {
                              setPayFeeId(f.fee_id);
                              setPayAmount(bal);
                              setPayRef(`TXN-${Math.floor(10000 + Math.random() * 90000)}`);
                            }}
                            className="text-xs bg-blue-50 text-blue-700 hover:bg-blue-100 font-semibold px-2 py-1 rounded"
                          >
                            + Record Pay
                          </button>
                        ) : (
                          <span className="text-emerald-600 text-[11px] font-semibold">Cleared</span>
                        )}
                      </td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          </div>

          {/* Payment Modal */}
          {payFeeId && (
            <div className="fixed inset-0 bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4 z-50">
              <div className="bg-white rounded-xl shadow-xl max-w-sm w-full p-5 space-y-4">
                <h4 className="text-sm font-bold text-slate-900">Record Fee Installment</h4>
                <div className="space-y-3 text-xs">
                  <div>
                    <label className="block text-slate-600 font-medium mb-1">Amount to Pay (INR)</label>
                    <input
                      type="number"
                      value={payAmount}
                      onChange={e => setPayAmount(Number(e.target.value))}
                      className="w-full py-1.5 px-3 border border-slate-200 rounded-lg font-mono"
                    />
                  </div>
                  <div>
                    <label className="block text-slate-600 font-medium mb-1">Receipt / Reference No</label>
                    <input
                      type="text"
                      value={payRef}
                      onChange={e => setPayRef(e.target.value)}
                      className="w-full py-1.5 px-3 border border-slate-200 rounded-lg font-mono"
                    />
                  </div>
                  <div className="flex gap-2 justify-end pt-2">
                    <button
                      type="button"
                      onClick={() => setPayFeeId(null)}
                      className="px-3 py-1.5 border border-slate-200 rounded-lg text-slate-600 hover:bg-slate-50"
                    >
                      Cancel
                    </button>
                    <button
                      type="button"
                      onClick={() => {
                        onPayFee(payFeeId, payAmount, payRef);
                        setPayFeeId(null);
                      }}
                      className="px-3 py-1.5 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700"
                    >
                      Confirm Payment
                    </button>
                  </div>
                </div>
              </div>
            </div>
          )}
        </div>
      )}

      {/* TAB 6: NOTICES */}
      {activeTab === 'notices' && (
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
          {/* Create Notice */}
          <div className="bg-white border border-slate-200 rounded-xl p-5 shadow-xs space-y-4 h-fit">
            <h3 className="text-sm font-bold text-slate-900">Broadcast Announcement</h3>
            <form onSubmit={e => {
              e.preventDefault();
              if (!noticeTitle.trim() || !noticeContent.trim()) return;
              onAddNotice({
                title: noticeTitle,
                content: noticeContent,
                category: noticeCategory,
                published_by: 'Academic Office',
                publish_date: new Date().toISOString().split('T')[0],
                status: 'Published'
              });
              setNoticeTitle('');
              setNoticeContent('');
            }} className="space-y-3 text-xs">
              <div>
                <label className="block text-slate-600 font-medium mb-1">Title</label>
                <input
                  type="text"
                  placeholder="e.g. Practical Exam Schedule"
                  value={noticeTitle}
                  onChange={e => setNoticeTitle(e.target.value)}
                  className="w-full py-1.5 px-3 border border-slate-200 rounded-lg"
                  required
                />
              </div>
              <div>
                <label className="block text-slate-600 font-medium mb-1">Category</label>
                <select
                  value={noticeCategory}
                  onChange={e => setNoticeCategory(e.target.value as any)}
                  className="w-full py-1.5 px-3 border border-slate-200 rounded-lg bg-white"
                >
                  <option value="Academic">Academic</option>
                  <option value="Examination">Examination</option>
                  <option value="Event">Event</option>
                  <option value="Urgent">Urgent</option>
                  <option value="General">General</option>
                </select>
              </div>
              <div>
                <label className="block text-slate-600 font-medium mb-1">Notice Content</label>
                <textarea
                  rows={4}
                  value={noticeContent}
                  onChange={e => setNoticeContent(e.target.value)}
                  placeholder="Detailed announcement instructions..."
                  className="w-full py-1.5 px-3 border border-slate-200 rounded-lg"
                  required
                />
              </div>
              <button
                type="submit"
                className="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 rounded-lg transition-colors"
              >
                Publish Notice
              </button>
            </form>
          </div>

          {/* Active Notices */}
          <div className="lg:col-span-2 space-y-3">
            {notices.map(not => (
              <div key={not.notice_id} className="bg-white border border-slate-200 rounded-xl p-4 shadow-xs">
                <div className="flex items-center justify-between mb-2">
                  <div className="flex items-center gap-2">
                    <span className="font-semibold text-blue-700 text-xs px-2 py-0.5 rounded bg-blue-50">
                      {not.category}
                    </span>
                    <span className="text-slate-400 text-xs font-mono">{not.publish_date}</span>
                  </div>
                  <button
                    onClick={() => onDeleteNotice(not.notice_id)}
                    className="text-slate-400 hover:text-rose-600 p-1"
                    title="Delete Notice"
                  >
                    <Trash2 className="w-4 h-4" />
                  </button>
                </div>
                <h4 className="text-sm font-bold text-slate-900 mb-1">{not.title}</h4>
                <p className="text-xs text-slate-600 leading-relaxed">{not.content}</p>
              </div>
            ))}
          </div>
        </div>
      )}

      {/* TAB 7: REPORTS & CSV */}
      {activeTab === 'reports' && (
        <div className="bg-white border border-slate-200 rounded-xl shadow-xs p-6 space-y-6">
          <div>
            <h3 className="text-sm font-bold text-slate-900">Academic Reports &amp; Transcripts</h3>
            <p className="text-xs text-slate-500">Download formatted CSV reports for accreditation, institutional audits, and transcripts</p>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div className="p-4 border border-slate-200 rounded-lg flex items-center justify-between">
              <div>
                <div className="text-sm font-bold text-slate-900">Student Directory Transcript</div>
                <div className="text-xs text-slate-500">{students.length} admitted student profiles</div>
              </div>
              <button
                onClick={() => exportToCSV('students')}
                className="inline-flex items-center gap-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold px-3 py-1.5 rounded-lg shadow-sm"
              >
                <Download className="w-3.5 h-3.5" />
                Download CSV
              </button>
            </div>

            <div className="p-4 border border-slate-200 rounded-lg flex items-center justify-between">
              <div>
                <div className="text-sm font-bold text-slate-900">Fee Ledgers &amp; Accounts</div>
                <div className="text-xs text-slate-500">{fees.length} billing records, ₹{totalOutstanding.toLocaleString()} due</div>
              </div>
              <button
                onClick={() => exportToCSV('fees')}
                className="inline-flex items-center gap-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold px-3 py-1.5 rounded-lg shadow-sm"
              >
                <Download className="w-3.5 h-3.5" />
                Download CSV
              </button>
            </div>
          </div>
        </div>
      )}

      {/* Add Student Modal */}
      {showAddStudentModal && (
        <div className="fixed inset-0 bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4 z-50">
          <div className="bg-white rounded-xl shadow-xl max-w-lg w-full p-6 space-y-4">
            <h4 className="text-base font-bold text-slate-900">Enroll New Student</h4>
            <form onSubmit={handleCreateStudent} className="space-y-3 text-xs">
              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block text-slate-600 font-medium mb-1">Roll / Enrollment No</label>
                  <input
                    type="text"
                    value={newRoll}
                    onChange={e => setNewRoll(e.target.value)}
                    className="w-full py-1.5 px-3 border border-slate-200 rounded-lg font-mono font-bold"
                    required
                  />
                </div>
                <div>
                  <label className="block text-slate-600 font-medium mb-1">Enrolled Course</label>
                  <select
                    value={newCourseId}
                    onChange={e => setNewCourseId(Number(e.target.value))}
                    className="w-full py-1.5 px-3 border border-slate-200 rounded-lg bg-white"
                  >
                    {courses.map(c => (
                      <option key={c.course_id} value={c.course_id}>{c.course_code} — {c.course_name}</option>
                    ))}
                  </select>
                </div>
              </div>

              <div>
                <label className="block text-slate-600 font-medium mb-1">Full Student Name</label>
                <input
                  type="text"
                  placeholder="e.g. Vikramaditya Sen"
                  value={newName}
                  onChange={e => setNewName(e.target.value)}
                  className="w-full py-1.5 px-3 border border-slate-200 rounded-lg"
                  required
                />
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block text-slate-600 font-medium mb-1">Email Address</label>
                  <input
                    type="email"
                    placeholder="student@college.edu"
                    value={newEmail}
                    onChange={e => setNewEmail(e.target.value)}
                    className="w-full py-1.5 px-3 border border-slate-200 rounded-lg"
                    required
                  />
                </div>
                <div>
                  <label className="block text-slate-600 font-medium mb-1">Phone Number</label>
                  <input
                    type="text"
                    value={newPhone}
                    onChange={e => setNewPhone(e.target.value)}
                    className="w-full py-1.5 px-3 border border-slate-200 rounded-lg font-mono"
                    required
                  />
                </div>
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block text-slate-600 font-medium mb-1">Date of Birth</label>
                  <input
                    type="date"
                    value={newDob}
                    onChange={e => setNewDob(e.target.value)}
                    className="w-full py-1.5 px-3 border border-slate-200 rounded-lg font-mono"
                    required
                  />
                </div>
                <div>
                  <label className="block text-slate-600 font-medium mb-1">Gender</label>
                  <select
                    value={newGender}
                    onChange={e => setNewGender(e.target.value as any)}
                    className="w-full py-1.5 px-3 border border-slate-200 rounded-lg bg-white"
                  >
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                    <option value="Other">Other</option>
                  </select>
                </div>
              </div>

              <div>
                <label className="block text-slate-600 font-medium mb-1">Address</label>
                <input
                  type="text"
                  value={newAddress}
                  onChange={e => setNewAddress(e.target.value)}
                  className="w-full py-1.5 px-3 border border-slate-200 rounded-lg"
                />
              </div>

              <div className="flex gap-2 justify-end pt-3 border-t border-slate-100">
                <button
                  type="button"
                  onClick={() => setShowAddStudentModal(false)}
                  className="px-4 py-2 border border-slate-200 rounded-lg text-slate-600 hover:bg-slate-50"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  className="px-4 py-2 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 shadow-sm"
                >
                  Confirm Enrollment
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
};
