import React, { useState } from 'react';
import { 
  GraduationCap, Shield, User, Download, Database, BookOpen, 
  RotateCcw, Sparkles, CheckCircle, Code, Layers, Laptop
} from 'lucide-react';
import { 
  Student, Course, Department, Subject, Examination, Result, 
  AttendanceRecord, FeeRecord, Notice,
  initialStudents, initialCourses, initialDepartments, initialSubjects,
  initialExaminations, initialResults, initialAttendance, initialFees, initialNotices
} from './data/initialData';
import { AdminPortal } from './components/AdminPortal';
import { StudentPortal } from './components/StudentPortal';
import { CodeExplorer } from './components/CodeExplorer';
import { DatabaseViewer } from './components/DatabaseViewer';
import { VivaGuide } from './components/VivaGuide';
import { downloadCampusDeskZip } from './utils/zipGenerator';

export default function App() {
  // Navigation Modes
  const [mainView, setMainView] = useState<'app' | 'code' | 'database' | 'viva'>('app');
  const [userRole, setUserRole] = useState<'admin' | 'student'>('admin');

  // Application In-Memory Relational State
  const [students, setStudents] = useState<Student[]>(initialStudents);
  const [courses, setCourses] = useState<Course[]>(initialCourses);
  const [departments, setDepartments] = useState<Department[]>(initialDepartments);
  const [subjects, setSubjects] = useState<Subject[]>(initialSubjects);
  const [examinations, setExaminations] = useState<Examination[]>(initialExaminations);
  const [results, setResults] = useState<Result[]>(initialResults);
  const [attendance, setAttendance] = useState<AttendanceRecord[]>(initialAttendance);
  const [fees, setFees] = useState<FeeRecord[]>(initialFees);
  const [notices, setNotices] = useState<Notice[]>(initialNotices);

  // Active student in student portal view
  const [selectedStudentId, setSelectedStudentId] = useState<number>(1);
  const currentStudent = students.find(s => s.student_id === selectedStudentId) || students[0];

  // Quick reset to initial demonstration state
  const handleResetData = () => {
    if (confirm('Reset in-memory demonstration state back to original sample data?')) {
      setStudents(initialStudents);
      setCourses(initialCourses);
      setDepartments(initialDepartments);
      setSubjects(initialSubjects);
      setExaminations(initialExaminations);
      setResults(initialResults);
      setAttendance(initialAttendance);
      setFees(initialFees);
      setNotices(initialNotices);
      setSelectedStudentId(1);
    }
  };

  // State handlers
  const handleAddStudent = (newStudent: Omit<Student, 'student_id'>) => {
    const nextId = Math.max(...students.map(s => s.student_id), 0) + 1;
    setStudents(prev => [...prev, { ...newStudent, student_id: nextId }]);
  };

  const handleDeleteStudent = (id: number) => {
    setStudents(prev => prev.filter(s => s.student_id !== id));
    // Cascade remove attendance, results, fees for this student
    setAttendance(prev => prev.filter(a => a.student_id !== id));
    setResults(prev => prev.filter(r => r.student_id !== id));
    setFees(prev => prev.filter(f => f.student_id !== id));
  };

  const handleAddAttendance = (records: Omit<AttendanceRecord, 'attendance_id'>[]) => {
    let nextId = Math.max(...attendance.map(a => a.attendance_id), 0) + 1;
    const newRecordsWithId: AttendanceRecord[] = records.map(r => ({
      ...r,
      attendance_id: nextId++
    }));
    // Upsert logic (replace if same student and date)
    setAttendance(prev => {
      const filtered = prev.filter(a => 
        !records.some(r => r.student_id === a.student_id && r.attendance_date === a.attendance_date)
      );
      return [...filtered, ...newRecordsWithId];
    });
  };

  const handleAddResult = (newResult: Omit<Result, 'result_id'>) => {
    const nextId = Math.max(...results.map(r => r.result_id), 0) + 1;
    setResults(prev => {
      const filtered = prev.filter(r => 
        !(r.exam_id === newResult.exam_id && r.student_id === newResult.student_id && r.subject_id === newResult.subject_id)
      );
      return [...filtered, { ...newResult, result_id: nextId }];
    });
  };

  const handleAddFee = (newFee: Omit<FeeRecord, 'fee_id'>) => {
    const nextId = Math.max(...fees.map(f => f.fee_id), 0) + 1;
    setFees(prev => [...prev, { ...newFee, fee_id: nextId }]);
  };

  const handlePayFee = (feeId: number, amount: number, refNo: string) => {
    setFees(prev => prev.map(f => {
      if (f.fee_id === feeId) {
        const newPaid = f.paid_amount + amount;
        const newStatus = newPaid >= f.total_amount ? 'Paid' : 'Partial';
        return {
          ...f,
          paid_amount: newPaid,
          payment_status: newStatus,
          payment_date: new Date().toISOString().split('T')[0],
          reference_no: refNo
        };
      }
      return f;
    }));
  };

  const handleAddNotice = (newNotice: Omit<Notice, 'notice_id'>) => {
    const nextId = Math.max(...notices.map(n => n.notice_id), 0) + 1;
    setNotices(prev => [{ ...newNotice, notice_id: nextId }, ...prev]);
  };

  const handleDeleteNotice = (id: number) => {
    setNotices(prev => prev.filter(n => n.notice_id !== id));
  };

  return (
    <div className="min-h-screen bg-slate-50 text-slate-900 flex flex-col font-sans">
      {/* Top Universal Header */}
      <header className="bg-slate-950 text-white border-b border-slate-800 sticky top-0 z-40">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between gap-4">
          {/* Brand */}
          <div className="flex items-center gap-3">
            <div className="w-9 h-9 rounded-lg bg-blue-600 flex items-center justify-center text-white shadow-sm">
              <GraduationCap className="w-5 h-5" />
            </div>
            <div>
              <div className="flex items-center gap-2">
                <span className="font-bold text-sm tracking-tight text-white">CampusDesk</span>
                <span className="text-[10px] uppercase font-mono px-1.5 py-0.5 rounded bg-blue-900/60 text-blue-300 font-semibold border border-blue-700/50">
                  PHP 8.2 &amp; MySQL
                </span>
              </div>
              <div className="text-[11px] text-slate-400">Student Management System · BCA Sem 3</div>
            </div>
          </div>

          {/* Center Navigation Segmented Control */}
          <div className="hidden md:flex items-center gap-1 bg-slate-900 p-1 rounded-lg border border-slate-800">
            <button
              onClick={() => setMainView('app')}
              className={`flex items-center gap-1.5 px-3 py-1.5 rounded-md text-xs font-semibold transition-all ${
                mainView === 'app' 
                  ? 'bg-blue-600 text-white shadow-sm' 
                  : 'text-slate-400 hover:text-slate-200'
              }`}
            >
              <Laptop className="w-3.5 h-3.5" />
              Live System
            </button>
            <button
              onClick={() => setMainView('code')}
              className={`flex items-center gap-1.5 px-3 py-1.5 rounded-md text-xs font-semibold transition-all ${
                mainView === 'code' 
                  ? 'bg-blue-600 text-white shadow-sm' 
                  : 'text-slate-400 hover:text-slate-200'
              }`}
            >
              <Code className="w-3.5 h-3.5" />
              Source Code &amp; ZIP
            </button>
            <button
              onClick={() => setMainView('database')}
              className={`flex items-center gap-1.5 px-3 py-1.5 rounded-md text-xs font-semibold transition-all ${
                mainView === 'database' 
                  ? 'bg-blue-600 text-white shadow-sm' 
                  : 'text-slate-400 hover:text-slate-200'
              }`}
            >
              <Database className="w-3.5 h-3.5" />
              MySQL Schema
            </button>
            <button
              onClick={() => setMainView('viva')}
              className={`flex items-center gap-1.5 px-3 py-1.5 rounded-md text-xs font-semibold transition-all ${
                mainView === 'viva' 
                  ? 'bg-blue-600 text-white shadow-sm' 
                  : 'text-slate-400 hover:text-slate-200'
              }`}
            >
              <BookOpen className="w-3.5 h-3.5" />
              Viva Guide &amp; Tests
            </button>
          </div>

          {/* Right Action Buttons */}
          <div className="flex items-center gap-2">
            <button
              onClick={() => downloadCampusDeskZip()}
              className="inline-flex items-center gap-1.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold px-3 py-1.5 rounded-lg transition-colors shadow-sm"
              title="Download entire ready-to-run ZIP for XAMPP htdocs"
            >
              <Download className="w-3.5 h-3.5" />
              <span className="hidden sm:inline">Download ZIP</span>
            </button>

            <button
              onClick={handleResetData}
              className="p-1.5 text-slate-400 hover:text-slate-200 rounded-lg hover:bg-slate-800 transition-colors"
              title="Reset sample demonstration data"
            >
              <RotateCcw className="w-4 h-4" />
            </button>
          </div>
        </div>

        {/* Mobile View Navigation Strip */}
        <div className="md:hidden flex overflow-x-auto border-t border-slate-800 px-4 py-2 gap-2 text-xs">
          <button
            onClick={() => setMainView('app')}
            className={`px-3 py-1 rounded-md font-semibold ${mainView === 'app' ? 'bg-blue-600 text-white' : 'text-slate-400'}`}
          >
            Live System
          </button>
          <button
            onClick={() => setMainView('code')}
            className={`px-3 py-1 rounded-md font-semibold ${mainView === 'code' ? 'bg-blue-600 text-white' : 'text-slate-400'}`}
          >
            Source Code
          </button>
          <button
            onClick={() => setMainView('database')}
            className={`px-3 py-1 rounded-md font-semibold ${mainView === 'database' ? 'bg-blue-600 text-white' : 'text-slate-400'}`}
          >
            Database
          </button>
          <button
            onClick={() => setMainView('viva')}
            className={`px-3 py-1 rounded-md font-semibold ${mainView === 'viva' ? 'bg-blue-600 text-white' : 'text-slate-400'}`}
          >
            Viva Guide
          </button>
        </div>
      </header>

      {/* Main Workspace Body */}
      <main className="flex-grow max-w-7xl mx-auto w-full px-4 sm:px-6 lg:px-8 py-6">
        {mainView === 'app' && (
          <div className="space-y-6">
            {/* Role Switcher Toolbar */}
            <div className="bg-white border border-slate-200 rounded-xl p-3.5 shadow-xs flex flex-wrap items-center justify-between gap-3">
              <div className="flex items-center gap-2">
                <span className="text-xs font-semibold text-slate-500 uppercase tracking-wider">Active Role:</span>
                <div className="flex bg-slate-100 p-0.5 rounded-lg">
                  <button
                    onClick={() => setUserRole('admin')}
                    className={`inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md text-xs font-semibold transition-all ${
                      userRole === 'admin'
                        ? 'bg-blue-600 text-white shadow-xs'
                        : 'text-slate-600 hover:text-slate-900'
                    }`}
                  >
                    <Shield className="w-3.5 h-3.5" />
                    Administrator Panel
                  </button>
                  <button
                    onClick={() => setUserRole('student')}
                    className={`inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md text-xs font-semibold transition-all ${
                      userRole === 'student'
                        ? 'bg-emerald-600 text-white shadow-xs'
                        : 'text-slate-600 hover:text-slate-900'
                    }`}
                  >
                    <User className="w-3.5 h-3.5" />
                    Student Portal ({currentStudent.full_name})
                  </button>
                </div>
              </div>

              <div className="text-xs text-slate-500 hidden sm:block">
                {userRole === 'admin' ? (
                  <span>Logged in as: <strong className="text-slate-900">admin</strong> (Full Academic Control)</span>
                ) : (
                  <span>Logged in as: <strong className="text-slate-900">{currentStudent.student_code}</strong> (Student Self-Service)</span>
                )}
              </div>
            </div>

            {/* Portal Switcher */}
            {userRole === 'admin' ? (
              <AdminPortal
                students={students}
                courses={courses}
                departments={departments}
                subjects={subjects}
                examinations={examinations}
                results={results}
                attendance={attendance}
                fees={fees}
                notices={notices}
                onAddStudent={handleAddStudent}
                onDeleteStudent={handleDeleteStudent}
                onAddAttendance={handleAddAttendance}
                onAddResult={handleAddResult}
                onAddFee={handleAddFee}
                onPayFee={handlePayFee}
                onAddNotice={handleAddNotice}
                onDeleteNotice={handleDeleteNotice}
              />
            ) : (
              <StudentPortal
                currentStudent={currentStudent}
                courses={courses}
                departments={departments}
                subjects={subjects}
                examinations={examinations}
                results={results}
                attendance={attendance}
                fees={fees}
                notices={notices}
                onSelectStudent={setSelectedStudentId}
                allStudents={students}
              />
            )}
          </div>
        )}

        {mainView === 'code' && <CodeExplorer />}
        {mainView === 'database' && <DatabaseViewer />}
        {mainView === 'viva' && <VivaGuide />}
      </main>

      {/* Universal Footer */}
      <footer className="bg-white border-t border-slate-200 py-4 px-4 sm:px-6 lg:px-8 mt-auto text-xs text-slate-500">
        <div className="max-w-7xl mx-auto flex flex-wrap justify-between items-center gap-2">
          <div>
            CampusDesk — Student Management System &copy; {new Date().getFullYear()} · Academic Project for BCA Semester 3
          </div>
          <div className="flex items-center gap-4 text-slate-400">
            <span>PHP 8.2+ PDO Prepared Statements</span>
            <span>·</span>
            <span>MySQL / MariaDB</span>
            <span>·</span>
            <span>Bootstrap 5</span>
          </div>
        </div>
      </footer>
    </div>
  );
}
