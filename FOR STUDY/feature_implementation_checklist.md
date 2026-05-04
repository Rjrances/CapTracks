# CapTrack: Feature Implementation Checklist

This document maps the project scope "MUST HAVES" and "NICE TO HAVES" against what is actually implemented in the CapTracks codebase. 

**Legend:**
🟢 **Complete** - Fully implemented and working in the system.
🟡 **In Progress / Partially Complete** - Core logic exists, but some specific UI or edge cases might need polish.
🔴 **Not Yet Started** - Not implemented.

---

## 🟢 MUST HAVES: System Modules

### 🟢 Capstone Milestone Tracker - ORTIZ
- [x] **Visual progress tracking:** Kanban boards and checklist views implemented.
- [x] **Includes Gantt chart or progress bar:** Progress bars automatically calculate percentage based on completed tasks.
- [x] **Milestones based on Capstone Must Haves:** Configurable by Coordinators via `MilestoneTemplateController`.
- [x] **Tasks assigned to specific members:** Implemented inside `GroupMilestoneTask`.
- [x] **Task completion contributes to percentage:** Auto-recalculation logic exists in `StudentMilestoneController`.
- [x] **Comments on individual tasks:** Threaded comments implemented (`TaskComment`).
- [x] **Status columns:** Pending (todo), Doing (in_progress), Done.

### 🟢 Task Scheduler - ORTIZ
- [x] **Set/assign deadlines or specific tasks:** Implemented via task editing.
- [x] **Mark tasks as complete to add to milestone %:** Implemented via Kanban drag-and-drop.
- [x] **Add comments on tasks:** Threaded comments implemented.

### 🟢 Team Management - RANCES
- [x] **Assign responsibilities per member:** Task assignments implemented.
- [x] **View contribution logs:** Activity logs and `TaskSubmission` logs track who did what.
- [x] **Faculty role mapping:** Robust role management (Coordinator, Adviser, Panel, Chair) via `RoleController`.

### 🟢 Document Uploads and Feedback - ORTIZ
- [x] **Upload chapter drafts/proposals:** Handled by `ProjectSubmissionController`.
- [x] **Faculty can comment (threaded style):** Implemented via `SubmissionComment` and `TaskComment`.
- [x] **Version history tracking:** File versioning, side-by-side preview, comparison, and rollback fully implemented.

### 🟢 Notification System - RANCES
- [x] **In-app alerts:** `NotificationService` handles deadlines, approvals, feedback, and schedules.

### 🟢 Defense Schedule Manager - RANCES & ORTIZ
- [x] **Students request available slots:** `DefenseRequestController` handles this.
- [x] **Coordinators approve/reject and finalize:** `DefenseScheduleController` implemented.
- [x] **Panel members accept/decline:** `DefensePanel` accept/decline logic is complete.
- [x] **Automatic panel assignments:** System filters busy faculty and suggests available panelists.
- [x] **View defense schedules (list/calendar):** Integrated into dashboards and `CalendarController`.

### 🟢 Project Timeline View / Calendar - RANCES & ORTIZ
- [x] **Calendar View For All Roles:** `FullCalendar` integrated with specific filtering for Coordinators, Advisers, and Students.

### 🟢 Activity Logs per Member - ORTIZ
- [x] **Logs actions (uploads, comments, tasks):** `ActivityLogService` fully covers this for accountability.

---

## 🟢 MUST HAVES: Role-Based Access Control

### 🟢 SuperAdmin – Chairperson - ORTIZ
- [x] **Add, edit, and delete offerings:** Implemented in `ChairpersonOfferingController`.
- [x] **Assign offerings to teachers:** Implemented during offering creation.
- [x] **Set School Year / Semester:** `AcademicTermController` toggle functionality.
- [x] **Filter by Class Year / Archive:** Built into academic terms.
- [x] **Display current offerings beside student list:** Enrolled students logic implemented.

### 🟢 Coordinator - RANCES
- [x] **Upload and manage classlist:** CSV upload via `StudentImportService` complete.
- [x] **Propose, update, delete schedules:** Implemented.
- [x] **Assign panel members:** Validation prevents adviser/coordinator from being chair/member.
- [x] **View faculty assignment matrix:** `CoordinatorController@facultyMatrix` tracks faculty load.
- [x] **Access and manage rating sheets:** `RatingSheetController` aggregates scores.
- [x] **Receive notifications for offerings:** Implemented.

### 🟢 Faculty Member - RANCES & ORTIZ
- [x] **View notifications for assignments/invitations:** Implemented on the dashboard.
- [x] **Approve/decline hearing and defense schedules:** Panel invitation response working.
- [x] **Advising functions:** Monitor group milestones, review documents, threaded task feedback.
- [x] **Participate in group messaging:** (Handled via Task/Submission threaded comments).
- [x] **View assigned roles table:** Matrix and dashboards split Adviser groups vs Panel groups.

### 🟢 Students - RANCES & ORTIZ
- [x] **Forced password change on first login:** `CheckStudentPasswordChange` middleware is active.
- [x] **Create/manage group:** ID search to invite students, adviser invitations working.
- [x] **Upload, edit, delete proposals:** Versioning loop is active.
- [x] **Track milestones / Task management:** Checklist, Kanban board, and task assignments fully functional.

---

## 🟡 / 🔴 NICE TO HAVES

- 🔴 **SMS notification system:** Not Started (Requires external API like Twilio or Semaphore, generally out of scope for a standard web defense unless explicitly demanded).
- 🟢 **PDF file viewer for in-platform previews:** Complete (`DocumentPreviewService` handles browser previews and side-by-side comparisons).
- 🔴 **Calendar sync (Google Calendar/iCal):** Not Started (Technically complex, internal `FullCalendar` is usually sufficient for defense).
- 🟢 **Analytics dashboard:** Complete (Widgets showing percentage progress, overdue tasks, pending submissions).
- 🟢 **Chat-style feedback:** Complete (Threaded comments on tasks and submissions serve this exact purpose without needing WebSockets).

---

### Conclusion for Defense:
**You have successfully implemented 100% of your listed "MUST HAVES".** The system is fully compliant with the core requirements outlined in your scope document. You even accomplished 3 of the 5 "Nice to Haves". You are in an excellent position to present this system!
