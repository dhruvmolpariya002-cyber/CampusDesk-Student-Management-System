import React, { useState } from 'react';
import { Database, Table, Key, Link2, Play, Terminal } from 'lucide-react';
import { initialStudents, initialCourses, initialDepartments, initialResults, initialAttendance, initialFees } from '../data/initialData';

export const DatabaseViewer: React.FC = () => {
  const [selectedTable, setSelectedTable] = useState<string>('students');
  const [sqlQuery, setSqlQuery] = useState<string>(
    'SELECT s.student_code, s.full_name, c.course_code, s.email, s.enrollment_status FROM students s JOIN courses c ON s.course_id = c.course_id;'
  );
  const [queryOutput, setQueryOutput] = useState<any[]>(initialStudents.map(s => ({
    student_code: s.student_code,
    full_name: s.full_name,
    course_code: initialCourses.find(c => c.course_id === s.course_id)?.course_code,
    email: s.email,
    enrollment_status: s.enrollment_status
  })));

  const tables = [
    { name: 'users', count: 4, desc: 'Authentication accounts, bcrypt password hashes, and user roles' },
    { name: 'departments', count: 3, desc: 'Academic faculties and divisions (DCA, DIT, DMS)' },
    { name: 'courses', count: 3, desc: 'Degrees and programs (BCA, BSCIT, MCA)' },
    { name: 'students', count: 4, desc: 'Core student directory, roll numbers, biodata, contact' },
    { name: 'subjects', count: 6, desc: 'Course syllabus modules categorized by semester & credits' },
    { name: 'examinations', count: 2, desc: 'Examination sessions and test schedules' },
    { name: 'results', count: 8, desc: 'Subject marks, calculated letter grades, and pass/fail standing' },
    { name: 'attendance', count: 15, desc: 'Classroom roll-call records (Present, Absent, Late)' },
    { name: 'fees', count: 6, desc: 'Tuition and exam fees, installments, and payment references' },
    { name: 'notices', count: 3, desc: 'Campus announcements with category and expiry dates' },
  ];

  const handleRunQuery = () => {
    const q = sqlQuery.toLowerCase();
    if (q.includes('from courses')) {
      setQueryOutput(initialCourses);
    } else if (q.includes('from departments')) {
      setQueryOutput(initialDepartments);
    } else if (q.includes('from fees')) {
      setQueryOutput(initialFees);
    } else if (q.includes('from attendance')) {
      setQueryOutput(initialAttendance);
    } else if (q.includes('from results')) {
      setQueryOutput(initialResults);
    } else {
      setQueryOutput(initialStudents.map(s => ({
        student_code: s.student_code,
        full_name: s.full_name,
        course_code: initialCourses.find(c => c.course_id === s.course_id)?.course_code,
        email: s.email,
        enrollment_status: s.enrollment_status
      })));
    }
  };

  return (
    <div className="space-y-6">
      {/* Schema Header */}
      <div className="bg-white border border-slate-200 rounded-xl p-5 shadow-xs flex flex-wrap items-center justify-between gap-4">
        <div>
          <div className="flex items-center gap-2">
            <Database className="w-5 h-5 text-blue-600" />
            <h3 className="text-base font-bold text-slate-900">Database Schema: campusdesk_db (3NF Normalized)</h3>
          </div>
          <p className="text-xs text-slate-500 mt-1">
            Engineered for MySQL 8.x / MariaDB with InnoDB storage engine, foreign key constraints, and utf8mb4 collation
          </p>
        </div>
        <span className="font-mono text-xs px-2.5 py-1 rounded-md bg-emerald-50 text-emerald-700 font-semibold border border-emerald-200">
          10 Normalized Tables · ACID Compliant
        </span>
      </div>

      {/* Tables Grid */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
        {tables.map(t => (
          <div
            key={t.name}
            onClick={() => {
              setSelectedTable(t.name);
              setSqlQuery(`SELECT * FROM ${t.name};`);
            }}
            className={`p-3.5 rounded-xl border cursor-pointer transition-all ${
              selectedTable === t.name 
                ? 'bg-blue-50/70 border-blue-400 shadow-xs ring-1 ring-blue-400' 
                : 'bg-white border-slate-200 hover:border-slate-300'
            }`}
          >
            <div className="flex items-center justify-between">
              <span className="font-mono font-bold text-xs text-slate-900">{t.name}</span>
              <span className="text-[11px] font-mono px-1.5 py-0.5 rounded bg-slate-100 text-slate-600">
                {t.count} rows
              </span>
            </div>
            <p className="text-[11px] text-slate-500 mt-1.5 line-clamp-2 leading-tight">
              {t.desc}
            </p>
          </div>
        ))}
      </div>

      {/* SQL Query Console for Viva Demonstration */}
      <div className="bg-slate-900 rounded-xl p-5 border border-slate-800 text-slate-100 space-y-4">
        <div className="flex items-center justify-between">
          <div className="flex items-center gap-2 text-xs font-mono text-blue-400">
            <Terminal className="w-4 h-4" />
            <span>Interactive SQL Terminal Simulator</span>
          </div>
          <button
            onClick={handleRunQuery}
            className="inline-flex items-center gap-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold px-3 py-1.5 rounded-md transition-colors shadow-sm"
          >
            <Play className="w-3.5 h-3.5 fill-current" />
            Execute SQL
          </button>
        </div>

        <div>
          <textarea
            rows={2}
            value={sqlQuery}
            onChange={e => setSqlQuery(e.target.value)}
            className="w-full bg-slate-950 font-mono text-xs text-emerald-400 p-3 rounded-lg border border-slate-800 focus:outline-none focus:ring-1 focus:ring-blue-500"
          />
        </div>

        {/* Query Output Table */}
        <div className="bg-slate-950 rounded-lg border border-slate-800 overflow-x-auto max-h-56">
          {queryOutput.length > 0 ? (
            <table className="w-full text-left font-mono text-[11px]">
              <thead className="bg-slate-900 border-b border-slate-800 text-slate-400 uppercase">
                <tr>
                  {Object.keys(queryOutput[0]).map(col => (
                    <th key={col} className="py-2 px-3">{col}</th>
                  ))}
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-900 text-slate-300">
                {queryOutput.map((row, idx) => (
                  <tr key={idx} className="hover:bg-slate-900/50">
                    {Object.values(row).map((val: any, cidx) => (
                      <td key={cidx} className="py-1.5 px-3 whitespace-nowrap">
                        {String(val ?? 'NULL')}
                      </td>
                    ))}
                  </tr>
                ))}
              </tbody>
            </table>
          ) : (
            <div className="p-4 text-center text-xs text-slate-500 font-mono">No records returned.</div>
          )}
        </div>
      </div>
    </div>
  );
};
