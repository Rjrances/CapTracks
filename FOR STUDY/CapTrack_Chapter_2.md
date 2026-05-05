# CHAPTER 2
# SOFTWARE REQUIREMENTS AND DESIGN SPECIFICATION

## APPLICATION OVERVIEW
CapTrack is a web-based platform developed to streamline the management of capstone projects across academic institutions. It serves as a centralized hub where students, faculty advisers, panel members, and program coordinators can collaborate, track progress, and manage project milestones from proposal to final defense. The system is designed to reduce administrative overhead, improve communication, and enhance transparency throughout the project lifecycle.

For faculty members and administrators, CapTrack offers a comprehensive dashboard that enables them to monitor multiple projects, assign roles, review submissions, and schedule evaluations. Coordinators can oversee project timelines, assign panelists for defenses, and ensure that all milestones are met on schedule. Faculty advisers gain visibility into student progress and can provide timely feedback directly through the platform.

Students benefit from a structured environment where they can manage tasks, upload documents, and receive notifications about upcoming deadlines and evaluation schedules. CapTrack allows them to visualize their progress through intuitive Gantt charts and progress bars, helping them stay on track with their deliverables. The platform also supports team collaboration by enabling members to assign responsibilities, communicate through threaded comments, and maintain version control for shared documents.

CapTrack is built using modern web technologies to ensure reliability, scalability, and security. The backend is powered by a robust framework that supports real-time updates and role-based access control, while the frontend is designed for ease of use across devices. By integrating essential project management tools into a single platform, CapTrack aims to simplify the capstone experience, foster accountability, and improve overall project outcomes for all stakeholders involved.

## USE CASE DIAGRAM
Use case diagrams illustrate how actors relate to each other.

*[INSERT FIGURE 1: CapTrack Capstone Project Management System]*
*Figure 1: CapTrack Capstone Project Management System*

Figure 1 gives a clear view of how the CapTrack system works by showing the main tasks each user type handles. Whether it’s a student submitting work, a Faculty Member giving feedback, a Coordinator keeping track of timelines, or a Panelist evaluating final presentations, everyone has a specific role in the system. The diagram outlines key actions like signing up, tracking project progress, assigning tasks, uploading files, exchanging comments, and scheduling defenses. Overall, this figure lays out the basic structure of the system and helps explain who does what, making it easier to understand how everything fits together in the capstone process.

## ARCHITECTURAL DIAGRAM

*[INSERT FIGURE 2: Architectural Diagram]*
*Figure 2 - Architectural Diagram*

The architectural design in Figure 2 outlines how the three main user roles—Administrator, Faculty Member, and Student—interact with the CapTrack system through both web and mobile platforms, all connected by a central backend and real-time database. The Administrator manages the platform via a web interface, handling user accounts, overseeing project workflows, and ensuring the system operates smoothly. This is powered by a Laravel backend, which also supports the web portal used by Faculty Members to review student progress, provide feedback, and manage evaluations. Both the Administrator and Faculty Member interfaces rely on Firebase Realtime Database to keep data synchronized in real time.

Students primarily engage with the system using a mobile application built with Flutter, which connects directly to Firebase for instant data access and updates. This enables students to track their project milestones, submit documents, communicate with team members, and receive timely notifications—all updated in real time. The entire system is supported by Firebase Realtime Database, which ensures that any changes made by any user are immediately reflected across all interfaces. This architecture delivers a robust, scalable, and efficient platform for managing capstone projects, keeping all users—administrators, faculty, and students—connected and informed at every stage of the project lifecycle.

## USE CASE NARRATIVES

| Use Case No. 1 | |
| :--- | :--- |
| **Use Case Name:** | Import Students |
| **Actors:** | Chairperson/Coordinator (Administrator) |
| **Functional Requirements:** | CapTrack's system shall allow Administrators to import student records. |
| **Priority:** | Must Have |
| **Pre-Condition(s):** | <ul><li>Admin is logged in.</li><li>Student Excel file is prepared in required format.</li></ul> |
| **Post-Condition(s):** | <ul><li>Students are created with default credentials and (optionally) enrolled.</li></ul> |
| **FLOW OF EVENTS** | |
| **Actor Action** | **System** |
| 1. Admin opens Student Import. | 2. System shows file upload form and template guidance. |
| 3. Admin uploads Excel and confirms import. | 4. System validates file structure and row data. |
| 5. Admin waits for result. | 6. System creates student records with default password and reports counts. |
| **ALTERNATIVE SCENARIOS** | |
| **Actor Action** | **System Response** |
| 1a. Invalid file or wrong columns. | 1.1 Show validation errors; reject file. |
| 2a. Duplicate student IDs/emails. | 2.1 Flag duplicates; skip or stop per rule and report. |
| 3a. Admin cancels import. | 3.1 Discard upload; return to import page. |

<br>

| Use Case No. 2 | |
| :--- | :--- |
| **Use Case Name:** | Import/Create Faculty |
| **Actors:** | Chairperson |
| **Functional Requirements:** | CapTrack's system shall allow the Chairperson to create or import faculty accounts. |
| **Priority:** | Must Have |
| **Pre-Condition(s):** | <ul><li>Chairperson is logged in.</li><li>Faculty data is available.</li></ul> |
| **Post-Condition(s):** | <ul><li>Faculty accounts exist (teacher/adviser/panelist), must_change_password set.</li></ul> |
| **FLOW OF EVENTS** | |
| **Actor Action** | **System** |
| 1. Chairperson opens Faculty Management. | 2. System shows list and options (Import, Add Manually). |
| 3. Uploads Excel or fills manual form and submits. | 4. Validate data; enforce unique school_id/email; role in allowed set. |
| 5. Confirms creation. | 6. Create accounts; show success. |
| **ALTERNATIVE SCENARIOS** | |
| **Actor Action** | **System Response** |
| 1a. Duplicate school_id/email. | 1.1 Show error and block creation. |
| 2a. Invalid role/format. | 2.1 Normalize or reject with message. |
| 3a. Cancel. | 3.1 Discard input; return to list. |

<br>

| Use Case No. 3 | |
| :--- | :--- |
| **Use Case Name:** | Create Group |
| **Actors:** | Student |
| **Functional Requirements:** | CapTrack's system shall allow students to create project groups. |
| **Priority:** | Must Have |
| **Pre-Condition(s):** | <ul><li>Student logged in.</li><li>Student not already in a group.</li></ul> |
| **Post-Condition(s):** | <ul><li>Group created; student becomes leader.</li></ul> |
| **FLOW OF EVENTS** | |
| **Actor Action** | **System** |
| 1. Open Group page → Create Group. | 2. Show form. |
| 3. Enter group name/details; submit. | 4. Validate; create group; assign leader. |
| 5. View group dashboard. | 6. Show group dashboard. |
| **ALTERNATIVE SCENARIOS** | |
| **Actor Action** | **System Response** |
| 1a. Invalid/duplicate name. | 1.1 Show validation errors. |
| 2a. Already in a group. | 2.1 Block and show a message. |

<br>

| Use Case No. 4 | |
| :--- | :--- |
| **Use Case Name:** | Join Group |
| **Actors:** | Student |
| **Functional Requirements:** | CapTrack's system shall allow students to join an existing group. |
| **Priority:** | Must Have |
| **Pre-Condition(s):** | <ul><li>Student logged in.</li><li>Student not in a group.</li></ul> |
| **Post-Condition(s):** | <ul><li>Student added as member.</li></ul> |
| **FLOW OF EVENTS** | |
| **Actor Action** | **System** |
| 1. View available groups. | 2. Show groups accepting members. |
| 3. Select group → Join. | 4. Validate capacity/rules; add members. |
| **ALTERNATIVE SCENARIOS** | |
| **Actor Action** | **System Response** |
| 1a. Group full/closed. | 1.1 Show message; abort. |
| 2a. Already in a group. | 2.1 Block with message. |

<br>

| Use Case No. 5 | |
| :--- | :--- |
| **Use Case Name:** | Invite Adviser |
| **Actors:** | Student (Group Leader) |
| **Functional Requirements:** | CapTrack's system shall allow group leaders to invite a faculty adviser. |
| **Priority:** | Must Have |
| **Pre-Condition(s):** | <ul><li>Student is group leader.</li><li>Faculty list is available.</li></ul> |
| **Post-Condition(s):** | <ul><li>Invitation recorded as Pending; faculty notified.</li></ul> |
| **FLOW OF EVENTS** | |
| **Actor Action** | **System** |
| 1. Open Adviser section. | 2. Show searchable faculty list. |
| 3. Select faculty → Send Invite. | 4. Validate eligibility; create invitation; notify. |
| 5. View status. | 6. Show Pending status. |
| **ALTERNATIVE SCENARIOS** | |
| **Actor Action** | **System Response** |
| 1a. Adviser already assigned. | 1.1 Block; show message. |
| 2a. Faculty conflict (e.g., coordinator of offering). | 2.1 Deny with reason. |

<br>

| Use Case No. 6 | |
| :--- | :--- |
| **Use Case Name:** | Accept/Reject Adviser Invitation |
| **Actors:** | Faculty |
| **Functional Requirements:** | CapTrack's system shall allow Faculty to respond to group invitations. |
| **Priority:** | Must Have |
| **Pre-Condition(s):** | <ul><li>Faculty logged in.</li><li>Pending invitation exists.</li></ul> |
| **Post-Condition(s):** | <ul><li>On accept: adviser assigned.</li><li>On reject: invitation closed.</li></ul> |
| **FLOW OF EVENTS** | |
| **Actor Action** | **System** |
| 1. Open Adviser Invitations. | 2. Show pending invites with group details. |
| 3. Choose Accept/Reject. | 4. Validate conflicts; update status; notify group. |
| **ALTERNATIVE SCENARIOS** | |
| **Actor Action** | **System Response** |
| 1a. Conflict detected. | 1.1 Deny accept; explain conflict. |

<br>

| Use Case No. 7 | |
| :--- | :--- |
| **Use Case Name:** | Track Milestones |
| **Actors:** | Student; Adviser (read/review) |
| **Functional Requirements:** | CapTrack's system shall allow tracking of task status via a Kanban board. |
| **Priority:** | Must Have |
| **Pre-Condition(s):** | <ul><li>Group has milestones/tasks.</li></ul> |
| **Post-Condition(s):** | <ul><li>Task status updated; progress recalculated.</li></ul> |
| **FLOW OF EVENTS** | |
| **Actor Action** | **System** |
| 1. Open Kanban board. | 2. Load tasks by status. |
| 3. Drag task/change status. | 4. Validate; update task; recompute progress. |
| 5. See confirmation. | 6. Show success and refreshed board. |
| **ALTERNATIVE SCENARIOS** | |
| **Actor Action** | **System Response** |
| 1a. Unauthorized change. | 1.1 Error; revert. |
| 2a. Server failure. | 2.1 Show error; restore UI. |

<br>

| Use Case No. 8 | |
| :--- | :--- |
| **Use Case Name:** | Submit Task Document |
| **Actors:** | Student |
| **Functional Requirements:** | CapTrack's system shall allow submission of documents linked to specific tasks. |
| **Priority:** | Must Have |
| **Pre-Condition(s):** | <ul><li>Student member; task exists; assignment rules satisfied.</li></ul> |
| **Post-Condition(s):** | <ul><li>TaskSubmission saved; ProjectSubmission mirror created.</li></ul> |
| **FLOW OF EVENTS** | |
| **Actor Action** | **System** |
| 1. Click Submit on task. | 2. Show submission form. |
| 3. Choose type, upload file, add notes; submit. | 4. Validate file; save TaskSubmission; create ProjectSubmission; update task to Doing if needed. |
| | 5. Show success and counts. |
| **ALTERNATIVE SCENARIOS** | |
| **Actor Action** | **System Response** |
| 1a. Invalid/oversized file. | 1.1 Show validation errors. |
| 2a. Not assigned to students. | 2.1 Deny submission. |

<br>

| Use Case No. 9 | |
| :--- | :--- |
| **Use Case Name:** | Submit Project Document |
| **Actors:** | Student |
| **Functional Requirements:** | CapTrack's system shall allow submission of general project deliverables. |
| **Priority:** | Must Have |
| **Pre-Condition(s):** | <ul><li>Student logged in.</li></ul> |
| **Post-Condition(s):** | <ul><li>Project-Submission saved (type: Proposal/Final/Other).</li></ul> |
| **FLOW OF EVENTS** | |
| **Actor Action** | **System** |
| 1. Open Project Submissions → Upload. | 2. Show form (type, file, description). |
| 3. Select type, upload file, submit. | 4. Validate and save submission; show in list. |
| **ALTERNATIVE SCENARIOS** | |
| **Actor Action** | **System Response** |
| 1a. Invalid file/type. | 1.1 Show form (type, file, description). |
| 2a. Cancel. | 2.1 Validate and save submission; show in list. |

<br>

| Use Case No. 10 | |
| :--- | :--- |
| **Use Case Name:** | Request Defense |
| **Actors:** | Student |
| **Functional Requirements:** | CapTrack's system shall allow groups to formally request a defense schedule. |
| **Priority:** | Must Have |
| **Pre-Condition(s):** | <ul><li>Group exists; adviser assigned; progress meets threshold.</li></ul> |
| **Post-Condition(s):** | <ul><li>Defense-Request created (Pending).</li></ul> |
| **FLOW OF EVENTS** | |
| **Actor Action** | **System** |
| 1. Open Defense Requests → New. | 2. Show form with defense types. |
| 3. Select type; add message; submit. | 4. Validate thresholds and pending duplicates; create request; notify coordinator. |
| **ALTERNATIVE SCENARIOS** | |
| **Actor Action** | **System Response** |
| 1a. No adviser / threshold unmet / duplicate pending. | 1.1 Block with reason. |

<br>

| Use Case No. 11 | |
| :--- | :--- |
| **Use Case Name:** | Schedule Defense |
| **Actors:** | Coordinator |
| **Functional Requirements:** | CapTrack's system shall allow coordinators to schedule approved defense requests. |
| **Priority:** | Must Have |
| **Pre-Condition(s):** | <ul><li>Approved request; panel and room availability.</li></ul> |
| **Post-Condition(s):** | <ul><li>DefenseSchedule created; stakeholders notified.</li></ul> |
| **FLOW OF EVENTS** | |
| **Actor Action** | **System** |
| 1. Open Defense Requests; pick one. | 2. Show scheduling form. |
| 3. Set date/time/room; assign panel; confirm. | 4. Check conflicts; save schedule; notify participants. |
| **ALTERNATIVE SCENARIOS** | |
| **Actor Action** | **System Response** |
| 1a. Conflicts found. | 1.1 Show alternatives; require change. |

<br>

| Use Case No. 12 | |
| :--- | :--- |
| **Use Case Name:** | Provide Feedback |
| **Actors:** | Adviser/Teacher |
| **Functional Requirements:** | CapTrack's system shall allow Faculty to review and provide feedback on submissions. |
| **Priority:** | Must Have |
| **Pre-Condition(s):** | <ul><li>Faculty has access to the submission.</li></ul> |
| **Post-Condition(s):** | <ul><li>Feedback saved; status updated; student notified.</li></ul> |
| **FLOW OF EVENTS** | |
| **Actor Action** | **System** |
| 1. Open a submission. | 2. Show details and feedback form. |
| 3. Enter status (Approved/Rejected) and feedback; submit. | 4. Validate; save feedback; timestamp; notify student. |
| **ALTERNATIVE SCENARIOS** | |
| **Actor Action** | **System Response** |
| 1a. Empty feedback where required. | 1.1 Prompt to add comments. |
| 2a. No permission. | 2.1 Deny with message. |

<br>

| Use Case No. 13 | |
| :--- | :--- |
| **Use Case Name:** | Manage Academic Terms |
| **Actors:** | Coordinator |
| **Functional Requirements:** | CapTrack's system shall allow the creation and enforcement of active terms. |
| **Priority:** | Must Have |
| **Pre-Condition(s):** | <ul><li>Coordinator logged in.</li></ul> |
| **Post-Condition(s):** | <ul><li>Terms created/edited; single active term enforced.</li></ul> |
| **FLOW OF EVENTS** | |
| **Actor Action** | **System** |
| 1. Open Academic Terms. | 2. Show list and form. |
| 3. Add/edit term; set active. | 4. Save; enforce one active; confirm. |

<br>

| Use Case No. 14 | |
| :--- | :--- |
| **Use Case Name:** | Create Offerings and Enroll Students |
| **Actors:** | Coordinator |
| **Functional Requirements:** | CapTrack's system shall allow enrollment of students into specific sections. |
| **Priority:** | Must Have |
| **Pre-Condition(s):** | <ul><li>Active term exists.</li></ul> |
| **Post-Condition(s):** | <ul><li>Offering exists; students enrolled.</li></ul> |
| **FLOW OF EVENTS** | |
| **Actor Action** | **System** |
| 1. Create offering (course/section). | 2. Save offering. |
| 3. Enroll students (pick/upload). | 4. Attach students; confirm. |

<br>

| Use Case No. 15 | |
| :--- | :--- |
| **Use Case Name:** | Assign Faculty to Offerings |
| **Actors:** | Coordinator |
| **Functional Requirements:** | CapTrack's system shall allow coordinators to map faculty members to class offerings. |
| **Priority:** | Must Have |
| **Pre-Condition(s):** | <ul><li>Offering exists; faculty available.</li></ul> |
| **Post-Condition(s):** | <ul><li>Faculty assigned; roles reflected.</li></ul> |
| **FLOW OF EVENTS** | |
| **Actor Action** | **System** |
| 1. Open offering; choose faculty. | 2. Validate and assign; update effective roles. |

<br>

| Use Case No. 16 | |
| :--- | :--- |
| **Use Case Name:** | View Group Progress |
| **Actors:** | Adviser |
| **Functional Requirements:** | CapTrack's system shall allow advisers to track group milestones and risks. |
| **Priority:** | Must Have |
| **Pre-Condition(s):** | <ul><li>Adviser assigned to group(s).</li></ul> |
| **Post-Condition(s):** | <ul><li>Adviser sees current status and risks.</li></ul> |
| **FLOW OF EVENTS** | |
| **Actor Action** | **System** |
| 1. Open My Groups/Progress. | 2. Show KPIs and drill-down to milestones/submissions. |

<br>

| Use Case No. 17 | |
| :--- | :--- |
| **Use Case Name:** | View Calendar |
| **Actors:** | All Users |
| **Functional Requirements:** | CapTrack's system shall allow users to view role-relevant defense schedules. |
| **Priority:** | Must Have |
| **Pre-Condition(s):** | <ul><li>Logged in.</li></ul> |
| **Post-Condition(s):** | <ul><li>Events visible by role.</li></ul> |
| **FLOW OF EVENTS** | |
| **Actor Action** | **System** |
| 1. Open Calendar. | 2. Show defense schedules and events relevant to user. |

<br>

| Use Case No. 18 | |
| :--- | :--- |
| **Use Case Name:** | Manage Notifications |
| **Actors:** | System (send); All Users (view) |
| **Functional Requirements:** | CapTrack's system shall deliver event-based alerts to users. |
| **Priority:** | Must Have |
| **Pre-Condition(s):** | <ul><li>Triggering event occurs.</li></ul> |
| **Post-Condition(s):** | <ul><li>Notification delivered and can be marked read.</li></ul> |
| **FLOW OF EVENTS** | |
| **Actor Action** | **System** |
| 1. Users open notifications. | 2. List items; mark as read on view. |
| | 3. System detects events (invite, feedback, schedule). |
| | 4. Create and queue notification. |

<br>

| Use Case No. 19 | |
| :--- | :--- |
| **Use Case Name:** | Manage Groups |
| **Actors:** | Coordinator |
| **Functional Requirements:** | CapTrack's system shall allow coordinators to oversee and edit group configurations. |
| **Priority:** | Must Have |
| **Pre-Condition(s):** | <ul><li>Coordinator logged in; offering exists.</li></ul> |
| **Post-Condition(s):** | <ul><li>Groups created/updated; memberships maintained.</li></ul> |
| **FLOW OF EVENTS** | |
| **Actor Action** | **System** |
| 1. Open Groups management. | 2. Show groups by offering with actions. |
| 3. Create/edit group; add/remove members. | 4. Validate rules; persist changes. |
| 5. Review summary. | 6. Display updated roster and stats. |
| **ALTERNATIVE SCENARIOS** | |
| **Actor Action** | **System Response** |
| 1a. Member capacity/existing membership conflict. | 1.1 Show error; block change. |

<br>

| Use Case No. 20 | |
| :--- | :--- |
| **Use Case Name:** | Assign Defense Panel |
| **Actors:** | Coordinator |
| **Functional Requirements:** | CapTrack's system shall allow coordinators to map faculty to defense panels. |
| **Priority:** | Must Have |
| **Pre-Condition(s):** | <ul><li>Defense-Schedule exists; faculty available.</li></ul> |
| **Post-Condition(s):** | <ul><li>Panelists assigned with roles (chair/member).</li></ul> |
| **FLOW OF EVENTS** | |
| **Actor Action** | **System** |
| 1. Open a defense schedule. | 2. Show panel assignment form. |
| 3. Select panelists and roles; submit. | 4. Check conflicts/availability; save assignments; notify panelists. |
| **ALTERNATIVE SCENARIOS** | |
| **Actor Action** | **System Response** |
| 1a. Faculty conflict/unavailable. | 1.1 Deny and suggest alternatives. |

<br>

| Use Case No. 21 | |
| :--- | :--- |
| **Use Case Name:** | Submit Proposal |
| **Actors:** | Student |
| **Functional Requirements:** | CapTrack's system shall handle proposal uploads and notify the adviser. |
| **Priority:** | Must Have |
| **Pre-Condition(s):** | <ul><li>Student logged in; group exists.</li></ul> |
| **Post-Condition(s):** | <ul><li>Proposal recorded; adviser notified.</li></ul> |
| **FLOW OF EVENTS** | |
| **Actor Action** | **System** |
| 1. Open Proposal & Endorsement. | 2. Show proposal upload form. |
| 3. Upload proposal and details; submit. | 4. Validate and save; notify adviser. |
| **ALTERNATIVE SCENARIOS** | |
| **Actor Action** | **System Response** |
| 1a. Invalid/oversized file. | 1.1 Show validation errors. |

<br>

| Use Case No. 22 | |
| :--- | :--- |
| **Use Case Name:** | Review/Endorse Proposal |
| **Actors:** | Adviser |
| **Functional Requirements:** | CapTrack's system shall allow Advisers to formally endorse student proposals. |
| **Priority:** | Must Have |
| **Pre-Condition(s):** | <ul><li>Proposal exists for adviser’s group.</li></ul> |
| **Post-Condition(s):** | <ul><li>Endorsement/remarks saved; student notified.</li></ul> |
| **FLOW OF EVENTS** | |
| **Actor Action** | **System** |
| 1. Open proposal detail. | 2. Show document and history. |
| 3. Approve/Return with feedback; submit. | 4. Validate; record decision and feedback; notify student. |
| **ALTERNATIVE SCENARIOS** | |
| **Actor Action** | **System Response** |
| 1a. Missing feedback on return. | 1.1 Prompt to add comments. |

<br>

| Use Case No. 23 | |
| :--- | :--- |
| **Use Case Name:** | Generate Progress Reports |
| **Actors:** | Coordinator |
| **Functional Requirements:** | CapTrack's system shall compile progress metrics for coordinators. |
| **Priority:** | Must Have |
| **Pre-Condition(s):** | <ul><li>Groups and milestones exist.</li></ul> |
| **Post-Condition(s):** | <ul><li>Report generated and exportable.</li></ul> |
| **FLOW OF EVENTS** | |
| **Actor Action** | **System** |
| 1. Open Reports; set filters (offering, term). | 2. Aggregate progress metrics. |
| 3. Generate report. | 4. Render table/charts; enable export. |

<br>

| Use Case No. 24 | |
| :--- | :--- |
| **Use Case Name:** | Generate Institutional Reports |
| **Actors:** | Student |
| **Functional Requirements:** | CapTrack's system shall allow generation of high-level academic reports. |
| **Priority:** | Must Have |
| **Pre-Condition(s):** | <ul><li>Sufficient program data exists.</li></ul> |
| **Post-Condition(s):** | <ul><li>Program-level report produced.</li></ul> |
| **FLOW OF EVENTS** | |
| **Actor Action** | **System** |
| 1. Select report type/timeframe. | 2. Compile institution-wide metrics. |
| 3. Export PDF/CSV. | 4. Provide a downloadable file. |

<br>

| Use Case No. 25 | |
| :--- | :--- |
| **Use Case Name:** | Manage Events |
| **Actors:** | Coordinator |
| **Functional Requirements:** | CapTrack's system shall allow coordinators to add overarching events to the calendar. |
| **Priority:** | Must Have |
| **Pre-Condition(s):** | <ul><li>Coordinator logged in.</li></ul> |
| **Post-Condition(s):** | <ul><li>Events created/updated in calendar.</li></ul> |
| **FLOW OF EVENTS** | |
| **Actor Action** | **System** |
| 1. Open Calendar → Manage Events. | 2. Show event form and list. |
| 3. Create/edit/delete event. | 4. Validate; save; update attendees’ calendars/notifications. |
| **ALTERNATIVE SCENARIOS** | |
| **Actor Action** | **System Response** |
| 1a. Time conflict with defense schedule. | 1.1 Warn and require confirmation/change. |

---

## ACTIVITY DIAGRAM
An Activity Diagram gives a step-by-step visual of how processes unfold within the CapTrack system. It breaks down the sequence of actions users take when carrying out key tasks like setting up a project, tracking milestones, uploading documents, or scheduling a defense. By mapping out each step, the diagram makes it easier to see how different parts of the system connect and interact. This helps developers and designers spot potential issues early and ensures that the system flows smoothly from one action to the next. For CapTrack, this diagram is especially useful because it shows how students, faculty, and coordinators move through their respective tasks, making the entire project lifecycle clearer and more manageable to document and develop.

**Import Student**
*[INSERT FIGURE 3.1: Import Students]*
Figure 3.1 shows the process for importing students. It starts with the user opening the Student Import feature, after which the system displays a file upload form and template guidance. The user then uploads an Excel file and confirms the import. The system validates the file structure and row data. If the file is valid, the system creates student records with default passwords and reports the count of imported records. If the file is invalid, the system shows validation errors and rejects the file, allowing the admin to re-upload.

**Invite Adviser**
*[INSERT FIGURE 3.2: Invite Adviser]*
Figure 3.2 illustrates the process for inviting an adviser. The user first opens the Adviser section, and the system presents a searchable faculty list. The user selects a faculty member and sends an invitation. The system then validates the eligibility of the selected faculty. If the adviser is already assigned or there’s a faculty conflict, the system either blocks the action (showing a message) or denies the invitation with a reason. If no issues exist, the system creates the invitation, notifies the faculty, and displays the Pending status.

**Manage Groups**
*[INSERT FIGURE 3.3: Manage Groups]*
Figure 3.3 depicts the process for managing groups. The user opens the Groups management interface, and the system shows groups organized by offering with available actions. The user performs actions like creating/editing a group or adding/removing members. The system validates the rules for these changes. If a conflict or capacity issue is detected, the system shows an error and blocks the change. If no issues arise, the system persists the changes and displays the updated roster and statistics.

**Provide Feedback**
*[INSERT FIGURE 3.4: Provide Feedback]*
Figure 3.4 outlines the process for providing feedback. The user opens a submission, and the system displays its details along with a feedback form. The user enters a status (Approved/Rejected) and feedback, then submits. The system validates the input. If the feedback is valid and permitted, the system saves it with a timestamp and notifies the student. If the feedback is invalid, the system checks if it’s empty—if so, it prompts the user to add comments; if not, it denies the submission with a message.

**Schedule Defense**
*[INSERT FIGURE 3.5: Schedule Defense]*
Figure 3.5 shows the process for scheduling a defense. The user opens Defense Requests and selects one, after which the system displays a scheduling form. The user sets the date, time, room, assigns a panel, and confirms. The system checks for conflicts. If conflicts are found, it shows alternative options and requires changes; if no conflicts exist, it saves the schedule and notifies all participants.

**Submit Task Document**
*[INSERT FIGURE 3.6: Submit Task Document]*
Figure 3.6 illustrates the process for submitting a task document. The user clicks “Submit” on a task, and the system shows a submission form. The user chooses a document type, uploads a file, adds notes, and submits. The system validates the file. If the file is valid, it checks if the student is assigned to the task—if so, it saves the task submission, creates a project submission, updates the task status to “Doing” if needed, and shows success metrics. If the file is invalid, it displays validation errors; if the student isn’t assigned, it denies the submission.

**Track Milestones**
*[INSERT FIGURE 3.7: Track Milestones]*
Figure 3.7 describes the process for tracking milestones. The user opens a Kanban board, and the system loads tasks grouped by status. The user drags a task or changes its status. The system validates the action. If the user is authorized, it updates the task, recomputes progress, and refreshes the board to show success. If unauthorized, it shows an error and reverts the action.

---

## USER INTERFACE DESIGN

**Website Development**
*[INSERT FIGURE 4.1: Dashboard]*
Figures 5.1 Display the complete welcome page of the CapTrack platform. The landing section introduces CapTrack with its tagline, a short description, and clear call-to-action buttons inviting users to get started or learn more. Presents role-based features, outlining how students, advisers, coordinators, and chairpersons can use the system according to their responsibilities. This is followed by the features panel, which highlights CapTrack’s core functions such as project tracking, collaboration, analytics, scheduling, document management, and notifications. Provides a detailed explanation of CapTrack as a capstone project management system and emphasizes its key benefits, supported by project and user statistics that establish credibility. The call-to-action area encourages users to start using CapTrack or explore its features while highlighting its adoption among students and faculty. Finally, the footer completes the welcome page with the CapTrack logo, tagline, quick navigation links, and copyright, ensuring branding consistency and easy access to important sections.

**Log-in**
*[INSERT FIGURE 5.1: Log in]*
Figure 6.1 shows the login page for the CapTrack system, designed with a split - screen layout. The left side has a solid blue background. At the top of this side, there is a graduation cap icon (the CapTrack logo), followed by the text “CapTrack” in large white font. Below that, there is a promotional message: “Skip repetitive and manual capstone tasks. Get highly productive through automation and save tons of time!”. At the very bottom of the left side, there is a copyright notice: “© 2024 CapTrack. All rights reserved.”. The right side has a white background. At the top of this side, the CapTrack logo (graduation cap) and the text “CapTrack” appear again. Below them, there is a “Welcome Back!” heading. Underneath, there is a login form. The form includes a field labeled “ID Number” with a placeholder text “Enter your ID number”. Next to it is a “Password” field, and there is a note next to it saying “(Leave blank for first - time login or students)”. Below these fields, there is a blue “Login Now” button. At the bottom of the right side, there is a link that says “Forgot password? Click here.

**Student Dashboard**
*[INSERT FIGURE 6.1: Student Dashboard]*
Figure 6.1 shows the Student Dashboard for the CapTrack system, structured with a dark left sidebar and a main content area. In the left sidebar, the top features the CapTrack logo, followed by an “Active 2024–2025 – First Semester” indicator. The navigation menu includes options like “Dashboard” (highlighted), “My Group”, “Project Submissions”, “Proposal & Endorsement”, “Milestones”, “Defense Requests”, and “Calendar”. A small icon sits at the sidebar’s bottom. The main content area starts with a header showing “Student Dashboard” and a user profile (“John Student”) with a notification bell (displaying “2”). Below, a welcome message reads “Welcome, John Student!” with the subtitle “Track your capstone project progress”. Three buttons—“My Submissions”, “My Group”, and “Defense Requests”—sit beside the welcome text. Next, a “Current Academic Term Context” section displays “2024–2025 – First Semester” (marked “Active”) with a note: “Current term for all academic operations and project work”. The right side labels this view as “Student View”.
Four progress - tracking cards follow:
* A blue “Overall Progress” card showing “0%”.
* A green “Completed Tasks” card indicating “0 of 0 total”.
* A yellow “In Progress” card with “0 currently working”.
* A light - blue “Pending Tasks” card stating “0 needs attention”.
Beneath these, a “60% Defense Requirements Checklist” section lists items:
* “Proposal Approved” (note: “Your project proposal has been approved by your adviser”).
* “Demo/Prototype Ready” (checked, with “Have a working demo or prototype to present ✓ Completed”).
* “Presentation Ready” (checked, with “Prepare your defense presentation slides ✓ Completed”).
* “Progress Report” (instruction: “Submit a detailed progress report of your project”), accompanied by a “40%” progress bar and “60% Defense Ready” text.
A “Recent Tasks” section shows “No tasks assigned yet” and a note: “Tasks will appear here when your adviser assigns them”. A “Kanban Board” button is included. At the bottom right, a “Quick Actions” section has buttons: “Upload Document”, “View Group”, “Kanban Board”, and “Proposal & Endorsement”. Finally, a “Recent Activities” section displays “No recent activities”.

**Student My Group**
*[INSERT FIGURE 6.2: Student - MyGroup]*
Figure 6.2 shows the “Edit Group” page for the CapTrack system, designed for managing group details. The interface has a dark left sidebar with navigation options like Dashboard, My Group, Project Submissions, Proposal & Endorsement, Milestones, Defense Requests, and Calendar. In the main content area, a header displays “Edit Group” along with a “Back to Group” button. The page is split into several sections. The first section, “Basic Information,” includes fields for “Group Name” (already filled with “Web Development Team”) and “Description” (filled with “Building a modern web application for student management”), plus an “Update Information” button. Next, the “Members (2/3)” section lists current members: “John Student” (with the role “Leader”) and “Jane Student” (with the role “Member”). Each member has an “Actions” column with a delete button. Below the member list, there’s an “Add New Member” dropdown and an “Add Member” button, along with a note saying the group can add 1 more member to reach the maximum of 3. The “Adviser Management” section shows the assigned adviser, “Test Adviser” (email: adviser@test.com), with an “Assigned Adviser” button. It also states “No pending invitations.”Finally, the “Group Statistics” section has four metric cards: “2 Members,” “1 Adviser,” “0 Pending Invitations,” and “Sep 2025 Created.”

**Student Project Submissions**
*[INSERT FIGURE 6.3: Student Project Submissions]*
Figure 6.3 shows the “Upload Project Submission” page on the CapTrack platform, where you submit general project documents (not tied to milestone tasks). First, pick what kind of document you’re uploading:
* Project Proposal: Your initial project idea and concept paper.
* Final Report: The complete, finished project documentation.
* Additional Files: Presentations, demos, or supplementary materials.
Next, choose your file (supports PDF, Word, ZIP, etc.—max 10MB). There’s a note reminding you: “For milestones - specific tasks, use the Milestones section. This form is for general project docs.” Click “Upload Document” to submit or “Cancel” to exit. You’ll also see quick links to other pages (like Milestone Tasks, Proposal & Endorsement) at the bottom.

**Student Proposal & Endorsement**
*[INSERT FIGURE 6.4: Student Proposal & Endorsement]*
Figure 6.4 shows the “Proposal & Endorsement” page for the CapTrack system, where you submit and track your capstone project proposal for approval. At the top, there’s a header with the page title and a blue “Submit Proposal” button. Below that, the “Proposal Status” section tells you “No Proposal Submitted” and explains you need to submit a proposal before moving forward with your capstone project—there’s a blue “Submit Your First Proposal” button here too.Next, the “Next Steps to 60% Defense” section breaks down what you need to do: first, submit your project proposal; second, get your adviser to review it and give feedback; third, once approved, request your 60% defense. Then, the “Requirements” section lists what your proposal must include: a project title and description, clear goals and scope, how you’ll do the project (methodology), a timeline with milestones, what results you expect, and any supporting documents.Finally, the “Group Information” section shows details about your group: it’s called “Web Development Team” (building a web app for student management), has 2 members, and your adviser is “Test Adviser” (email: adviser@test.com).

**Student Milestones**
*[INSERT FIGURE 6.5: Student Milestones]*
Figure 6.5 shows the “Create New Milestone” page for the CapTrack system, where you set up a new project milestone for your capstone work. At the top, there’s a header with the page title and a “Back to Milestones” button. Below that, you start by picking a milestone template from a dropdown—options include “Project Proposal (4 tasks)”, “System Design (4 tasks)”, “Implementation (4 tasks)”, and “Testing & Documentation (4 tasks)”. You can also add a custom milestone title and description, set a due date using a calendar picker, and check tips like choosing a template that fits your project, customizing the title/description, setting a realistic deadline, and knowing tasks will auto - create from the template.At the bottom, there are “Cancel” and “Create Milestone” buttons to finish or exit the process.

**Student Defense Requests**
*[INSERT FIGURE 6.6: Student Defense Requests]*
Figure 6.6 shows the “Request Defense” page for the CapTrack system, where you submit a request for your defense schedule. At the top, there’s a header with the page title and a “Back to Requests” button. Below that, the “Defense Request Form” section has several parts:
* Group Information: Shows your group details—name (“Web Development Team”), members (“John Student, Jane Student”), adviser (“Test Adviser”), and project description (“Building a modern web application for student management”).
* Defense Type: You pick the type of defense you’re requesting from a dropdown. Options include “Proposal Defense” (initial project proposal review), “60% Progress Defense” (mid - project review), and “100% Final Defense” (final project defense).
* Preferred Date: You select a date (at least 3 days ahead) using a calendar picker. The coordinator will try to fit your preferred date, but final scheduling depends on faculty availability.
* Preferred Time: You choose your preferred time slot. Morning slots (9 AM - 12 PM) and afternoon slots (1 PM - 4 PM) are usually available. Defenses typically last 1 - 2 hours, and the coordinator will confirm the exact duration.
* Message to Coordinator (Optional): You can add special requests, notes, or info that might help the coordinator schedule your defense. An example is given: “We prefer morning slots due to group member availability. Our project focuses on [brief description]...” The message should be professional and concise.
There’s also a “Requirements Met” section that checks if you’re part of a group, your group has an adviser assigned, and you have no pending defense requests. At the bottom, there are “Submit Defense Request” and “Cancel” buttons to submit or exit the process. Below that, a “What Happens Next?” section explains the steps: your request goes to the coordinator for review, the coordinator reviews it within 1 - 2 business days, you get notified of the decision, and if approved, the coordinator creates the final defense schedule.

**Chairperson Dashboard**
*[INSERT FIGURE 7.1: Chairperson Dashboard]*
Figure 7.1 shows the Chairperson Dashboard for the CapTrack system, where chairpersons manage capstone projects and academic operations. At the top, there’s a header with the dashboard title, a notification bell (showing “1” unread alert), and a user profile dropdown (“Test Chairperson”).Below that, the “Current Academic Term Context” section highlights the active term: “2024 - 2025 - First Semester” (marked “Active”), with a note that it’s the current term for all academic operations and scheduling. On the right, there are buttons to “Manage Offerings” and “Manage Terms”. The dashboard features four summary cards: Active Projects, Faculty Members, Pending Defenses, and Course Offerings. Next, the “Upcoming Defense Schedules” section shows upcoming defense schedules. The “Latest Notifications” section lists recent alerts. Under “Quick Actions,” there are buttons for “Manage Offerings,” “View Teachers,” and “Roles.” At the bottom, the “Defense Statistics” section tracks overall completions.

**Chairperson Offerings**
*[INSERT FIGURE 7.2: Chairperson Offering]*
Figure 7.2 shows the “Offerings” section of the CapTrack system, where chairpersons manage academic offerings for the First Semester. The main content area includes an “Edit Offering” form with fields for Subject Title, Subject Code, Teacher, and Academic Term. To the right, there’s an “Enrolled Students” panel showing enrolled students and an “Import Students” button. Below that, a “Student Management” section explains how to add students. This setup lets chairpersons configure course details, track student enrollment, and manage who participates in each offering.

**Chairperson Teacher**
*[INSERT FIGURE 7.3: Chairperson Teacher]*
Figure 7.3 shows the “Faculty Management” section of the CapTrack system, where chairpersons oversee faculty and staff assignments. The main content area has a header with filters. Below that, a table lists faculty details: ID Number, Name, Email, Role, Department, and Actions. Key features include buttons to “Add Teacher,” and “Import Faculty.” This setup lets chairpersons add, edit, or import faculty, assign roles, and ensure the right staff are linked.

**Chairperson Student**
*[INSERT FIGURE 7.4: Chairperson Student]*
Figure 7.4 shows the “Student Management” section of the CapTrack system. The main content area has a search bar to filter students by name, ID, email, or course. Below that, a “Student List” table displays details like Student ID, Name, Course, Enrolled Offerings, Group Status, and Actions. There’s also an “Export to CSV” button and an “Import Students” button.

**Chairperson Roles**
*[INSERT FIGURE 7.5: Chairperson Roles]*
Figure 7.5 shows the “Roles” section of the CapTrack system, where chairpersons manage user roles and permissions for academic operations. The main content area has role cards describing the roles. Below that, a “Role Distribution Summary” shows the count of users per role. This setup lets chairpersons assign roles to users, define what each role can do (permissions), and track how many people hold each role in the system.

**Chairperson Calendar**
*[INSERT FIGURE 7.6: Chairperson Calendar]*
Figure 7.6 shows the “Defense Calendar” section of the CapTrack system, where chairpersons manage defense schedules. The main content area displays a monthly calendar. Navigation buttons allow the chairperson to move between months. This tool lets chairpersons view, schedule, and adjust defense dates for students, ensuring all academic deadlines are tracked in one centralized calendar.

**Coordinator Dashboard**
*[INSERT FIGURE 8.1: Coordinator Dashboard]*
Figure 8.1 shows the Coordinator Dashboard of the CapTrack system, which helps coordinators manage capstone projects, groups, and academic activities. At the top, a “Current Academic Term Context” banner displays the active term. Below that, four colored boxes display key metrics: Total Students, Active Groups, Faculty Members, and Submissions. The “Recent Activities” section lists recent group creations. On the right, “Quick Actions” include buttons to “Create Group” and “View Class List.” A “Pending Invitations” box tracks invites, and a “System Status” section shows group assignment progress.

**Coordinator Group**
*[INSERT FIGURE 8.2: Coordinator Group]*
Figure 8.2 shows the “Groups & Progress Management” page in the CapTrack system. This page helps coordinators monitor groups, track their progress, and manage capstone projects. At the top, there are four colored boxes with key stats. Below that, there’s a search bar. The main part of the page lists all groups, showing the number of members, who their adviser is, how much progress they’ve made, and action buttons. On the right side, “Group Statistics” repeats the adviser counts. At the bottom, “Quick Actions” includes a button for Defense Scheduling.

**Coordinator Class List**
*[INSERT FIGURE 8.3: Coordinator Class List]*
Figure 8.3 shows the “Class List” page in the CapTrack system, used to view and manage students by semester. At the top, there’s a header with a subtitle. Below that, a “Semester” dropdown and a “Search students” input field lets users filter the list. The main content displays the students enrolled followed by a table listing the students.

**Coordinator Defense Schedule**
*[INSERT FIGURE 8.4: Coordinator Defense Schedule]*
Figure 8.4 shows the “Defense Schedules” section of the CapTrack system, where coordinators manage defense schedules for capstone projects. The page has filters for “Academic Term” and “Offering”. Below that, a section titled “Defense Schedules” shows upcoming schedules or a "No Defense Schedules Found" state. A “Create Schedule” button invites users to set up new defenses. The form includes fields for “Group”, “Defense Stage”, “Room”, “Date”, and “Start/End Time”. There’s also a “Panel Members” section to select faculty.

**Coordinator Milestones Templates**
*[INSERT FIGURE 8.5: Coordinator Milestone Templates]*
Figure 8.5 shows the “Milestone Templates” section of the CapTrack system, where coordinators create and manage templates for project milestones. The main interface includes forms to create or edit milestone templates. The page has fields for “Template Name”, “Description”, and “Status”. Beneath the template details, a “Template Tasks” section lists required steps for the milestone, such as “Project Title and Description,” “Problem Statement,” and “Literature Review”. Coordinators can use a “Manage Tasks” button to add, edit, or reorder these tasks.

**Coordinator Calendar**
*[INSERT FIGURE 8.6: Coordinator Calendar]*
Figure 8.6 shows the Defense Calendar page in the CapTrack system. The main part of the page is a monthly calendar. Above the calendar, there are navigation buttons. In the top-right corner, a “Schedule Defense” button allows coordinators to input all the details for a new defense. This calendar is the coordinator’s “command center” for organizing defense schedules.

**Adviser Dashboard**
*[INSERT FIGURE 9.1: Adviser Dashboard]*
Figure 9.1 shows the Group Details page in the CapTrack app, designed for advisers to manage their student groups. At the top, the “Group Information” section lists key details like the group name, when it was created, and its description. Below that, the “Group Members” section shows students assigned to the group. The “Project Submissions” area manages documents uploaded by the group.

**Adviser All My Groups**
*[INSERT FIGURE 9.2: Adviser All My Groups]*
Figure 9.2 shows the “All My Groups” dashboard. At the top, four colored stat cards summarize key metrics: total groups, adviser groups, panel groups, and pending reviews. Below these stats, a tab navigation lets the adviser filter groups. The main content lists the groups the adviser oversees. Each group entry shows the group name, adviser role, number of members, academic term, overall progress, and recent submissions. A “Group Details” button is available for each group.

**Adviser Invitation**
*[INSERT FIGURE 9.3: Adviser Invitation]*
Figure 9.3 shows the “Invitations” page, designed for advisers to manage teacher invitations and group assignments. At the top, the page title clarifies its purpose for tracking requests to join or lead groups. The main content area displays pending invitations or an empty state if there are no pending requests. This page helps advisers stay organized by centralizing all incoming requests.

**Advise Proposal Review**
*[INSERT FIGURE 9.4: Adviser Proposal Review]*
Figure 9.4 shows the “Proposal Review” page, where advisers manage student project proposals. At the top, four colored stat cards summarize proposal statuses: Total Proposals, Pending Review, Approved, and Rejected. The main content area lists the proposals along with their specific statuses. A “Go to Dashboard” button lets the adviser return to their main dashboard. This page helps advisers track proposal submissions, prioritize feedback, and manage student work efficiently.

**Adviser Calendar**
*[INSERT FIGURE 9.5: Adviser Calendar]*
Figure 9.5 shows the “Defense Calendar” page, used by advisers to manage defense schedules. The Left Sidebar displays the navigation menu. The Main Content features a monthly calendar highlighting scheduled defense dates. Navigation buttons allow advisers to browse other months. This tool helps advisers visualize and organize defense timelines.
