# CHAPTER II

## USE CASE DIAGRAM

The CapTracks Use Case Diagram provides a visual representation of the system's key functionalities and interactions between users and the application. It illustrates the distinct roles of the Student, Adviser, Coordinator, Chairperson, and Panelist, highlighting features such as milestone tracking, document versioning, defense scheduling, and real-time grading. This diagram serves as a blueprint for understanding the scope of the CapTracks application, ensuring that all role-based requirements are clearly defined and isolated by the Multi-Guard authentication system.

*[INSERT CAPTRACK USE CASE DIAGRAM IMAGE HERE]*
*Figure 1 - Use Case Diagram*

## USE CASE NARRATIVES

The Use Case Narratives provide detailed descriptions of the interactions between users and the CapTracks system. These narratives describe how students submit documents, how advisers leave threaded feedback, and how coordinators manage defense schedules.

| Use Case No. 1 | |
| :--- | :--- |
| **Use Case Name:** | Submit Project Proposal Version |
| **Actors:** | Student (Primary), CapTracks System (Secondary) |
| **Functional Requirement:** | The system shall allow students to upload PDF proposals and track version history. |
| **Priority:** | Must Have |
| **Pre-Condition(s):** | <ul><li>Student must be logged in via the Student Guard.</li><li>Student must be enrolled in an active Group.</li></ul> |
| **Post-Condition(s):** | <ul><li>The new proposal version is saved in the database.</li><li>The assigned Adviser receives a real-time notification.</li></ul> |
| **FLOW OF EVENTS** | |
| **Actor Action** | **System** |
| 1. Student navigates to the Proposal Submission screen and uploads a PDF. | 2. The system validates the file format and auto-increments the version number. |
| 3. Student clicks "Submit". | 4. The system saves the file to storage and updates the `ProjectSubmission` database table. |
| | 5. The system dispatches an alert to the Adviser using the NotificationService. |

*Table 1 - Submit Project Proposal Version*

<br>

| Use Case No. 2 | |
| :--- | :--- |
| **Use Case Name:** | Review and Comment on Proposal |
| **Actors:** | Adviser (Primary), CapTracks System (Secondary) |
| **Functional Requirement:** | The system shall allow Advisers to view student documents side-by-side and leave threaded comments. |
| **Priority:** | Must Have |
| **Pre-Condition(s):** | <ul><li>Adviser must be authenticated via the Web Guard.</li><li>Adviser must be officially assigned to the student's Group.</li></ul> |
| **Post-Condition(s):** | <ul><li>The comment is saved and attached to the specific document version.</li></ul> |
| **FLOW OF EVENTS** | |
| **Actor Action** | **System** |
| 1. Adviser clicks on a pending document from their dashboard. | 2. The system renders the document using the DocumentPreviewService. |
| 3. Adviser types feedback into the threaded comment section and clicks "Post". | 4. The system updates the `SubmissionComment` table. |

*Table 2 - Review and Comment on Proposal*

<br>

| Use Case No. 3 | |
| :--- | :--- |
| **Use Case Name:** | Assign Milestone Template |
| **Actors:** | Coordinator (Primary), CapTracks System (Secondary) |
| **Functional Requirement:** | The system shall allow Coordinators to apply pre-configured task blueprints to specific groups. |
| **Priority:** | Must Have |
| **Pre-Condition(s):** | <ul><li>Coordinator must possess the `role:coordinator` Spatie permission.</li></ul> |
| **Post-Condition(s):** | <ul><li>The group's Kanban board is populated with the required tasks.</li></ul> |
| **FLOW OF EVENTS** | |
| **Actor Action** | **System** |
| 1. Coordinator selects a Milestone Template (e.g., Chapter 1) and assigns it to a group. | 2. The system duplicates the template tasks into `GroupMilestoneTask` records. |
| | 3. The system recalculates the group's progress percentage to 0%. |

*Table 3 - Assign Milestone Template*

<br>

| Use Case No. 4 | |
| :--- | :--- |
| **Use Case Name:** | Grade Oral Defense |
| **Actors:** | Panelist (Primary), CapTracks System (Secondary) |
| **Functional Requirement:** | The system shall dynamically generate a digital rating sheet based on JSON rubrics. |
| **Priority:** | Must Have |
| **Pre-Condition(s):** | <ul><li>Panelist must be assigned to the specific `DefenseSchedule`.</li></ul> |
| **Post-Condition(s):** | <ul><li>The group's final grade is calculated and locked.</li></ul> |
| **FLOW OF EVENTS** | |
| **Actor Action** | **System** |
| 1. Panelist opens the active Defense Schedule. | 2. The system parses the JSON rubric and renders the grading criteria inputs. |
| 3. Panelist inputs scores and clicks "Submit Final Grade". | 4. The system validates the inputs, runs `array_sum` to calculate the total, and saves it to the `RatingSheet` table. |

*Table 4 - Grade Oral Defense*

---

## ACTIVITY DIAGRAMS

The Activity Diagrams illustrate the step-by-step control flow of specific complex processes within CapTracks. 

*[INSERT ACTIVITY DIAGRAM: KANBAN TASK MOVEMENT HERE]*
*Figure 2: Kanban Task Movement*
Figure 2 illustrates the flow when a student drags a task card across the Kanban board. The process begins with the frontend intercepting the drag event and sending an AJAX request to the `StudentMilestoneController`. The system verifies the new status, updates the database, automatically recalculates the overall completion percentage of the milestone, and returns a success response to instantly update the UI.

*[INSERT ACTIVITY DIAGRAM: DEFENSE SCHEDULING HERE]*
*Figure 3: Defense Scheduling Algorithm*
Figure 3 details the Coordinator's defense scheduling process. After the coordinator selects a date and time, the system checks for potential conflicts. It queries the database to ensure the selected Panelists do not have overlapping schedules, and verifies that the group's Adviser is not accidentally assigned as a grading panelist.

---

## CLASS DIAGRAM

The Class Diagram illustrates the various Object-Oriented classes involved in the CapTracks system, governed by the Laravel MVC framework. Each class is defined by its database attributes and Eloquent methods, which work together to provide functionalities such as user management, dynamic JSON grading, and document tracking. 

*[INSERT CLASS DIAGRAM HERE]*
*Figure 4: Class Diagram*

Key classes include:
* **User & Student:** Distinct entities isolated by guards, responsible for authentication.
* **Group & Offering:** Manages the academic enrollment structure.
* **MilestoneTemplate:** The blueprint class managed by the Coordinator.
* **ProjectSubmission:** Handles file paths and tracks iteration versions.
* **RatingSheet:** Manages the dynamic JSON payload for grading criteria.

---

## ENTITY RELATIONSHIP DIAGRAM

The Entity Relationship Diagram (ERD) showcases how each table in the relational database interacts with others to facilitate the management of the capstone lifecycle. 

*[INSERT ERD HERE]*
*Figure 5: Entity Relationship Diagram*

**Student & Group**
Students belong to exactly one Group, which operates as the central hub for submissions, tasks, and defense scheduling. 

**GroupMilestone & GroupMilestoneTask**
A One-to-Many relationship where a parent Milestone (e.g., System Development) contains multiple specific Tasks (e.g., Database Design, UI Mockup). Completion of child tasks directly influences the progress attribute of the parent milestone.

**DefenseSchedule & DefensePanel**
A Defense Schedule links a Group to a specific time slot. It utilizes a pivot relationship (`defense_panels`) to connect multiple Faculty Users (Panelists) to the schedule, defining their specific role (Chair or Member) for that session.

---

## USER INTERFACE

The User Interface helps students and faculty navigate the complex capstone workflows effortlessly. Below are the core interfaces for CapTracks.

**Multi-Guard Login Screen**
*[INSERT LOGIN SCREENSHOT HERE]*
*Figure 6: Login Screen*
Figure 6 illustrates the centralized login screen. Utilizing Laravel's validation rules, it securely processes credentials and automatically routes faculty members to their respective dashboards while routing students to the student portal.

**Student Kanban Board**
*[INSERT KANBAN SCREENSHOT HERE]*
*Figure 7: Student Milestone Kanban Board*
Figure 7 displays the dynamic milestone tracker. Students can view assigned tasks and move them between "To Do", "Doing", and "Done" columns, providing visual feedback on project progress.

**Adviser Proposal Reviewer**
*[INSERT ADVISER DOCUMENT VIEWER SCREENSHOT HERE]*
*Figure 8: Adviser Document Viewer*
Figure 8 shows the side-by-side document preview interface. Advisers can read the submitted PDF on the left pane while leaving threaded, real-time comments on the right pane without needing to download the file.

**Coordinator Faculty Workload Matrix**
*[INSERT MATRIX SCREENSHOT HERE]*
*Figure 9: Faculty Workload Matrix*
Figure 9 illustrates the administrative dashboard where Coordinators can monitor the number of advisory and panelist roles assigned to each teacher, preventing over-assignment and ensuring fair workload distribution.
