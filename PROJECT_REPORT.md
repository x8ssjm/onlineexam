
# Online Exam Portal - Project Report Summary

## 1. Project Overview
The **Online Exam Portal** is a web-based application designed to facilitate secure and efficient online examinations. It provides a robust platform for administrators to manage students, question banks, and exam schedules, while offering students a user-friendly interface to take exams, view history, and track performance.

The system emphasizes **security**, **integrity**, and **usability**, featuring randomized questions, strict timer enforcement, and secure authentication methods including "Magic Links".

## 2. Technology Stack
The application is built using a modern LAMP-stack approach:
*   **Backend**: PHP 8.x (Vanilla/Core PHP)
*   **Database**: MySQL 8.0 (Relational Schema)
*   **Frontend**: HTML5, CSS3, JavaScript (ES6+), Bootstrap 5
*   **Libraries**:
    *   **PHPMailer**: For secure SMTP email delivery.
    *   **SweetAlert2**: For interactive and user-friendly alerts.
    *   **Bootstrap BI**: For iconography.

## 3. Key Modules & Features

### A. Admin Module
The administrator panel is the command center of the application.
1.  **Dashboard**: Real-time statistics on students, active exams, and question banks.
2.  **Student Management**:
    *   Add/Edit/Delete student records.
    *   **Group Management**: Organize students into classes or batches (e.g., "Class 10A").
    *   **Bulk Actions**: Efficiently manage large cohorts.
3.  **Examination Management**:
    *   **Scheduling**: precise start/end times and durations.
    *   **Explicit Assignment**: Assign exams to specific groups or all students. Assignments are "snapshot-based", meaning future group changes do not affect past exam records.
    *   **Question Banks**: Create reusable sets of questions (Single Choice).
    *   **Configuration**: Set passing marks, negative marking, and question weights.
4.  **Results & Monitoring**: View detailed score reports and student performance.

### B. Student Module
The student portal focuses on a distraction-free assessment environment.
1.  **Dashboard**: Overview of upcoming, ongoing, and completed exams.
2.  **Secure Login**:
    *   Standard Email/Password.
    *   **Magic Link**: Password-less login via secure email tokens.
    *   **Password Reset**: Self-service reset via SMTP-delivered OTPs.
3.  **Exam Interface**:
    *   **Strict Timer**: Auto-submission when time expires.
    *   **Randomization**: Questions and Options (A, B, C, D) are shuffled for *every* student to prevent cheating.
    *   **Security**: Browser strictness (preventing navigation away) and session validation.
4.  **History & Analytics**:
    *   View past attempts, scores, and status (Passed/Failed/Missed).
    *   "Missed" exams are accurately tracked based on assignment dates.

## 4. Database Schema
The system uses a normalized relational database (`online_exam_portal`).

| Table | Description |
| :--- | :--- |
| `admins` | Stores administrator credentials. |
| `groups` | Categorizes students (e.g., Classes/Sections). |
| `students` | Stores student profiles and group associations. |
| `question_banks` | Collections of questions (Topics/Subjects). |
| `questions` | Individual questions linked to banks with options & correct answers. |
| `exams` | Defines exam metadata (Timing, Rules, Target Group). |
| `exam_assignments` | **Critical**: Explicit link between `exams` and `students`. Ensures permissions persist even if groups change. |
| `exam_submissions` | Tracks a student's attempt status (Ongoing, Submitted, Expired) and final score. |
| `student_answers` | Stores individual question responses and randomization seeds. |
| `settings` | System-wide configurations (e.g., SMTP settings). |

## 5. Key Algorithms & Logic

### Explicit Exam Assignment
To solve the issue of retroactively changing history when students change groups, the system uses an **Explicit Assignment Model**:
*   When an exam is created for "Group A", the system takes a **snapshot** of all students currently in Group A and inserts records into `exam_assignments`.
*   Future members of Group A do *not* automatically get old exams.
*   Moving a student out of Group A does *not* erase their history of Group A exams.

### Randomization Engine
To ensure integrity:
1.  **Question Shuffling**: Questions are fetched and shuffled using PHP's `shuffle()` for each submission.
2.  **Option Shuffling**: Options (A, B, C, D) are shuffled securely. A specific "seed" based on `submission_id` and `question_id` ensures that if a student refreshes the page, the order remains consistent *for them* (avoiding confusion), but is different for their neighbor.

### Session Security
*   **Session Fixation Protection**: Sessions are regenerated upon login.
*   **Conflict Resolution**: Magic Links invalidate existing sessions to prevent stale state issues.

## 6. Recent Technical Enhancements
*   **SMTP Integration**: Replaced legacy client-side email tools (EmailJS) with server-side **PHPMailer** for reliable delivery of Magic Links and Reset emails.
*   **Input Validation**: Implemented a "Defense in Depth" strategy for exam creation, enforcing validity checks (non-negative numbers, valid dates) on both the Frontend (JS/HTML5) and Backend (PHP).
*   **UX Improvements**: "Leave Site" warnings are intelligently suppressed during valid exam submissions to prevent user panic.

---
*Generated for Project Report Documentation*
