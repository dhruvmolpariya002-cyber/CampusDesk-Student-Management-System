export interface Department {
  dept_id: number;
  dept_name: string;
  dept_code: string;
  status: 'active' | 'inactive';
}

export interface Course {
  course_id: number;
  dept_id: number;
  course_name: string;
  course_code: string;
  duration_years: number;
  description: string;
  status: 'active' | 'inactive';
}

export interface Student {
  student_id: number;
  student_code: string;
  user_id?: number | null;
  course_id: number;
  full_name: string;
  email: string;
  phone: string;
  dob: string;
  gender: 'Male' | 'Female' | 'Other';
  address: string;
  admission_date: string;
  enrollment_status: 'Active' | 'Graduated' | 'Suspended' | 'Withdrawn';
}

export interface Subject {
  subject_id: number;
  course_id: number;
  subject_name: string;
  subject_code: string;
  semester: number;
  credits: number;
}

export interface Examination {
  exam_id: number;
  course_id: number;
  exam_name: string;
  semester: number;
  exam_date: string;
}

export interface Result {
  result_id: number;
  exam_id: number;
  student_id: number;
  subject_id: number;
  max_marks: number;
  obtained_marks: number;
  grade: string;
  status: 'Pass' | 'Fail';
  remarks?: string;
}

export interface AttendanceRecord {
  attendance_id: number;
  student_id: number;
  course_id: number;
  attendance_date: string;
  status: 'Present' | 'Absent' | 'Late';
  remarks?: string;
}

export interface FeeRecord {
  fee_id: number;
  student_id: number;
  fee_type: string;
  total_amount: number;
  paid_amount: number;
  due_date: string;
  payment_status: 'Paid' | 'Partial' | 'Pending';
  payment_date?: string;
  reference_no?: string;
}

export interface Notice {
  notice_id: number;
  title: string;
  content: string;
  category: 'Academic' | 'Examination' | 'Event' | 'Urgent' | 'General';
  published_by: string;
  publish_date: string;
  expiry_date?: string;
  status: 'Published' | 'Draft';
}

export interface UserAccount {
  user_id: number;
  username: string;
  email: string;
  role: 'admin' | 'student';
  status: 'active' | 'inactive';
  password_hint?: string;
}

export const initialDepartments: Department[] = [
  { dept_id: 1, dept_name: 'Department of Computer Applications', dept_code: 'DCA', status: 'active' },
  { dept_id: 2, dept_name: 'Department of Information Technology', dept_code: 'DIT', status: 'active' },
  { dept_id: 3, dept_name: 'Department of Management Studies', dept_code: 'DMS', status: 'active' },
];

export const initialCourses: Course[] = [
  { course_id: 1, dept_id: 1, course_name: 'Bachelor of Computer Applications', course_code: 'BCA', duration_years: 3.0, description: 'Undergraduate 3-year degree focusing on web programming, database systems, software engineering, and networking.', status: 'active' },
  { course_id: 2, dept_id: 2, course_name: 'B.Sc Information Technology', course_code: 'BSCIT', duration_years: 3.0, description: 'Undergraduate curriculum covering systems architecture, cloud computing, and cyber security fundamentals.', status: 'active' },
  { course_id: 3, dept_id: 1, course_name: 'Master of Computer Applications', course_code: 'MCA', duration_years: 2.0, description: 'Postgraduate degree covering advanced algorithms, distributed databases, and enterprise software design.', status: 'active' },
];

export const initialStudents: Student[] = [
  { student_id: 1, student_code: 'BCA2024001', user_id: 2, course_id: 1, full_name: 'Aarav Sharma', email: 'aarav.sharma@campusdesk.edu', phone: '9876543210', dob: '2004-05-14', gender: 'Male', address: '42 MG Road, Bangalore, Karnataka', admission_date: '2024-07-15', enrollment_status: 'Active' },
  { student_id: 2, student_code: 'BCA2024002', user_id: 3, course_id: 1, full_name: 'Diya Patel', email: 'diya.patel@campusdesk.edu', phone: '9823456789', dob: '2004-09-22', gender: 'Female', address: '18 Navrangpura, Ahmedabad, Gujarat', admission_date: '2024-07-15', enrollment_status: 'Active' },
  { student_id: 3, student_code: 'IT2024010', user_id: 4, course_id: 2, full_name: 'Rohan Mehta', email: 'rohan.mehta@campusdesk.edu', phone: '9712345678', dob: '2003-11-08', gender: 'Male', address: '76 Park Street, Kolkata, West Bengal', admission_date: '2024-07-18', enrollment_status: 'Active' },
  { student_id: 4, student_code: 'BCA2024003', user_id: 5, course_id: 1, full_name: 'Ananya Roy', email: 'ananya.roy@campusdesk.edu', phone: '9833445566', dob: '2004-02-18', gender: 'Female', address: '12 Salt Lake, Kolkata, West Bengal', admission_date: '2024-07-20', enrollment_status: 'Active' },
];

export const initialSubjects: Subject[] = [
  { subject_id: 1, course_id: 1, subject_name: 'Database Management Systems & MySQL', subject_code: 'BCA301', semester: 3, credits: 4 },
  { subject_id: 2, course_id: 1, subject_name: 'Web Development with PHP', subject_code: 'BCA302', semester: 3, credits: 4 },
  { subject_id: 3, course_id: 1, subject_name: 'Data Structures & Algorithms', subject_code: 'BCA303', semester: 3, credits: 4 },
  { subject_id: 4, course_id: 1, subject_name: 'Computer Organization & Architecture', subject_code: 'BCA304', semester: 3, credits: 3 },
  { subject_id: 5, course_id: 2, subject_name: 'Object Oriented Programming in Java', subject_code: 'IT301', semester: 3, credits: 4 },
  { subject_id: 6, course_id: 2, subject_name: 'Operating Systems Concepts', subject_code: 'IT302', semester: 3, credits: 4 },
];

export const initialExaminations: Examination[] = [
  { exam_id: 1, course_id: 1, exam_name: 'BCA Semester 3 Mid-Term Examination 2024', semester: 3, exam_date: '2024-10-10' },
  { exam_id: 2, course_id: 2, exam_name: 'B.Sc IT Semester 3 Mid-Term Examination 2024', semester: 3, exam_date: '2024-10-12' },
];

export const initialResults: Result[] = [
  { result_id: 1, exam_id: 1, student_id: 1, subject_id: 1, max_marks: 100, obtained_marks: 88, grade: 'A+', status: 'Pass', remarks: 'Outstanding query design and normalization understanding' },
  { result_id: 2, exam_id: 1, student_id: 1, subject_id: 2, max_marks: 100, obtained_marks: 92, grade: 'O', status: 'Pass', remarks: 'Excellent PDO and session architecture execution' },
  { result_id: 3, exam_id: 1, student_id: 1, subject_id: 3, max_marks: 100, obtained_marks: 78, grade: 'A', status: 'Pass', remarks: 'Solid implementation of trees and sorting algorithms' },
  { result_id: 4, exam_id: 1, student_id: 2, subject_id: 1, max_marks: 100, obtained_marks: 82, grade: 'A+', status: 'Pass', remarks: 'Great grasp of ACID transactions and keys' },
  { result_id: 5, exam_id: 1, student_id: 2, subject_id: 2, max_marks: 100, obtained_marks: 85, grade: 'A+', status: 'Pass', remarks: 'Very good CSRF and prepared statement handling' },
  { result_id: 6, exam_id: 1, student_id: 2, subject_id: 3, max_marks: 100, obtained_marks: 69, grade: 'B+', status: 'Pass', remarks: 'Good grasp of linear data structures' },
  { result_id: 7, exam_id: 1, student_id: 4, subject_id: 1, max_marks: 100, obtained_marks: 74, grade: 'A', status: 'Pass', remarks: 'Competent relational modeling' },
  { result_id: 8, exam_id: 1, student_id: 4, subject_id: 2, max_marks: 100, obtained_marks: 79, grade: 'A', status: 'Pass', remarks: 'Clean form validation logic' },
];

export const initialAttendance: AttendanceRecord[] = [
  { attendance_id: 1, student_id: 1, course_id: 1, attendance_date: '2024-10-01', status: 'Present', remarks: 'Regular class on MySQL Indexes' },
  { attendance_id: 2, student_id: 1, course_id: 1, attendance_date: '2024-10-03', status: 'Present', remarks: 'PHP PDO Lab demonstration' },
  { attendance_id: 3, student_id: 1, course_id: 1, attendance_date: '2024-10-04', status: 'Present', remarks: 'Data structures recursion workshop' },
  { attendance_id: 4, student_id: 1, course_id: 1, attendance_date: '2024-10-07', status: 'Late', remarks: 'Late arrival due to transit delay' },
  { attendance_id: 5, student_id: 1, course_id: 1, attendance_date: '2024-10-08', status: 'Present', remarks: 'Regular class' },
  { attendance_id: 6, student_id: 2, course_id: 1, attendance_date: '2024-10-01', status: 'Present', remarks: 'Regular class' },
  { attendance_id: 7, student_id: 2, course_id: 1, attendance_date: '2024-10-03', status: 'Absent', remarks: 'Medical leave application submitted' },
  { attendance_id: 8, student_id: 2, course_id: 1, attendance_date: '2024-10-04', status: 'Present', remarks: 'Lab session' },
  { attendance_id: 9, student_id: 2, course_id: 1, attendance_date: '2024-10-07', status: 'Present', remarks: 'Regular class' },
  { attendance_id: 10, student_id: 2, course_id: 1, attendance_date: '2024-10-08', status: 'Present', remarks: 'Regular class' },
  { attendance_id: 11, student_id: 3, course_id: 2, attendance_date: '2024-10-01', status: 'Present', remarks: 'Java OOP intro' },
  { attendance_id: 12, student_id: 3, course_id: 2, attendance_date: '2024-10-03', status: 'Present', remarks: 'Operating systems memory paging' },
  { attendance_id: 13, student_id: 4, course_id: 1, attendance_date: '2024-10-01', status: 'Present', remarks: 'Present' },
  { attendance_id: 14, student_id: 4, course_id: 1, attendance_date: '2024-10-03', status: 'Present', remarks: 'Present' },
  { attendance_id: 15, student_id: 4, course_id: 1, attendance_date: '2024-10-04', status: 'Absent', remarks: 'Absent' },
];

export const initialFees: FeeRecord[] = [
  { fee_id: 1, student_id: 1, fee_type: 'Semester 3 Tuition Fee', total_amount: 35000, paid_amount: 35000, due_date: '2024-08-30', payment_status: 'Paid', payment_date: '2024-08-15', reference_no: 'TXN-BCA-2024-8841' },
  { fee_id: 2, student_id: 1, fee_type: 'Semester 3 Exam Fee', total_amount: 2500, paid_amount: 2500, due_date: '2024-09-25', payment_status: 'Paid', payment_date: '2024-09-20', reference_no: 'TXN-EXAM-2024-1102' },
  { fee_id: 3, student_id: 2, fee_type: 'Semester 3 Tuition Fee', total_amount: 35000, paid_amount: 20000, due_date: '2024-08-30', payment_status: 'Partial', payment_date: '2024-08-25', reference_no: 'TXN-BCA-2024-9104' },
  { fee_id: 4, student_id: 2, fee_type: 'Semester 3 Exam Fee', total_amount: 2500, paid_amount: 0, due_date: '2024-09-25', payment_status: 'Pending' },
  { fee_id: 5, student_id: 3, fee_type: 'Semester 3 Tuition Fee', total_amount: 32000, paid_amount: 32000, due_date: '2024-08-30', payment_status: 'Paid', payment_date: '2024-08-18', reference_no: 'TXN-IT-2024-3321' },
  { fee_id: 6, student_id: 4, fee_type: 'Semester 3 Tuition Fee', total_amount: 35000, paid_amount: 35000, due_date: '2024-08-30', payment_status: 'Paid', payment_date: '2024-08-10', reference_no: 'TXN-BCA-2024-7712' },
];

export const initialNotices: Notice[] = [
  { notice_id: 1, title: 'BCA Semester 3 Internal Practical Exam Schedule', content: 'All BCA Semester 3 students are hereby notified that the Practical Examinations for Subject BCA302 (Web Development with PHP) will be conducted from November 12, 2024. Students must bring their lab record books signed by the faculty guide.', category: 'Examination', published_by: 'Academic Office', publish_date: '2024-10-15', expiry_date: '2024-11-20', status: 'Published' },
  { notice_id: 2, title: 'Annual College Technical Symposium — ByteQuest 2024', content: 'The Department of Computer Applications invites registrations for ByteQuest 2024 featuring Web Designing, Blind Coding, and SQL Hackathon events. Register before November 5, 2024 at the Department Office.', category: 'Event', published_by: 'Academic Office', publish_date: '2024-10-18', expiry_date: '2024-11-06', status: 'Published' },
  { notice_id: 3, title: 'Tuition Fee Payment Reminder for Outstanding Dues', content: 'Students with pending fee installments for Semester 3 are requested to clear their accounts before the end of this month to avoid late fee penalties and hall ticket delays.', category: 'Academic', published_by: 'Accounts Office', publish_date: '2024-10-20', expiry_date: '2024-11-15', status: 'Published' },
];
