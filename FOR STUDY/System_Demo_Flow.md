# 🚀 CapTrack Live Defense Demo Flow — Full Script

When you are standing in front of the panel and it is time to demonstrate the system, do not just click around randomly. You need to tell a **story**.

Follow this exact step-by-step workflow. **Read the text inside the quotation marks out loud to the panel** as you perform the actions on the screen. Lines labeled `[ACTION]` are what you DO; lines labeled `[SAY]` are what you SPEAK.

---

## 🛠️ Pre-Demo Setup (Do this BEFORE you present)

> **How browser sessions work in CapTrack:**
> - The `student` guard and the `web` (faculty) guard use **separate session keys** — so a student and a faculty/coordinator can be logged in at the same time in the same browser with no conflict.
> - **Two accounts of the same type** (e.g., two students, or two faculty) **share one session** — logging in as the second one will immediately log out the first. Use a separate browser or Incognito for those.

| What you need open | How to do it |
|---|---|
| Student + Coordinator | Same browser, different tabs ✅ |
| Student + Adviser | Same browser, different tabs ✅ |
| Coordinator + Chairperson | Need Incognito or a second browser ⚠️ |
| Two different students | Need Incognito or a second browser ⚠️ |

1. **Tab Setup:**
   - **Tab 1 (Normal browser):** Log in as the **Student**.
   - **Tab 2 (Normal browser):** Log in as the **Coordinator** — same browser works because they use different guards.
   - **Incognito window (or Edge/Firefox):** Log in as the **Chairperson** or **Panelist** — needed because they share the `web` guard with the Coordinator.
2. **Prepare a Dummy File:** Have a sample PDF on your desktop named `Chapter_1_Draft.pdf` ready to upload.
3. **Prepare a CSV:** Have a small CSV file with 2 fake students ready to demonstrate the import.
4. **Reset the Kanban board** so at least one task is in "Pending" — so you can drag it live during the demo.

---

## 🎬 Phase 1: The Setup (Chairperson)
*Start your presentation as the highest admin to show how a semester begins.*

**Step 1 — Login as Chairperson**

> [SAY] *"Good morning, panelists. To begin our demonstration, I will be showing you the full lifecycle of a capstone project inside our system, CapTrack, starting from the very first step — the Chairperson setting up the semester."*

[ACTION] Navigate to the login page. Type in the Chairperson credentials.

> [SAY] *"The Chairperson is the highest-level administrator in the system. Think of this account as the one who turns on the lights before everyone else arrives."*

[ACTION] Click Login.

---

**Step 2 — CSV Import**

[ACTION] Navigate to the Student Management page.

> [SAY] *"The first thing a Chairperson does at the start of a new semester is populate the system with students. Manually entering hundreds of students one by one is not practical, so we built a CSV import feature."*

[ACTION] Click "Import Students" and upload your prepared CSV file.

> [SAY] *"Now, a question that might come to mind is — what happens if the Chairperson accidentally uploads the same file twice? Will the database crash with duplicate errors?"*

[ACTION] Upload the same CSV file a second time.

> [SAY] *"It does not crash. We handle de-duplication inside the backend using Eloquent's `firstOrCreate` method. As the controller loops through each row in the CSV, it checks if a student with that ID already exists. If yes, it skips the row entirely. If no, it inserts the new student and automatically encrypts their default password. No duplicates, no errors."*

---

**Step 3 — Create a Class Offering**

[ACTION] Go to Offerings and create a new class section. Assign a Coordinator to it. Enroll one of the newly imported students.

> [SAY] *"With students in the system, the Chairperson can now create a class offering — essentially a section — and assign a Coordinator to manage it. We will come back to what the Coordinator can do in a moment."*

---

## 👨‍🎓 Phase 2: The Core Workflow (Student)
*Switch to your Incognito tab and log in as the student you just enrolled.*

**Step 4 — Forced Password Change (Security Middleware)**

[ACTION] Open the incognito tab. Go to the student login page. Type in the student ID and the default password `password123`. Click Login.

> [SAY] *"I am now logging in as one of our newly imported students using the default password assigned to them. Watch what happens."*

[ACTION] The system redirects to the Change Password page.

> [SAY] *"Notice that the system immediately blocks the student from accessing the dashboard and forces them to change their password first. This is not just a frontend trick — we implemented a security Middleware in Laravel for this. Every time any user logs in, the middleware runs a Hash Check in the background. It takes the stored encrypted password and checks if it matches the hash of our default 'password123'. If it matches, the middleware intercepts the request and redirects the user here, before they can see anything. Once the student changes their password, that Hash Check will fail on the next login, and they are granted full access. This ensures no student can ever operate the system under a default, insecure credential."*

[ACTION] Set a new password and log in.

---

**Step 5 — Form a Group and Invite an Adviser**

[ACTION] Navigate to My Group. Create a new group. Then go to the Invite Adviser section and send an invitation to a faculty member.

> [SAY] *"Once inside, the student's first task is to form their capstone group and invite a faculty adviser. The invitation is sent as a notification inside the system, and the adviser will be able to accept or decline it from their own dashboard."*

---

**Step 6 — The Kanban Board (AUTO-PROGRESS — MOST IMPORTANT DEMO)**

[ACTION] Navigate to Milestones. Open a milestone. The Kanban board is now visible with columns: Pending, In Progress, and Completed.

> [SAY] *"This is the heart of the student experience — the Milestone Kanban Board. Each card represents a requirement that the group must complete for their capstone project. The coordinator assigns these tasks to the group, and the students manage them here."*

[ACTION] Slowly drag a task card from the "Pending" column to the "Completed" column.

> [SAY] *"Watch the top-right corner as I drag this task to 'Completed'."*

[ACTION] Release the card. The progress percentage and progress bar update immediately without any page reload.

> [SAY] *"The progress percentage updated instantly — no page refresh needed. Let me explain what just happened behind the scenes, because this is an architecture decision we are proud of."*

> [SAY] *"When I dropped that card, our frontend sent a single asynchronous PATCH request to the backend. The backend updated the task's status in the database and then immediately calculated the new progress: it counts the total number of tasks in this milestone, counts how many are in the 'Done' column, and computes the percentage. That new number is saved directly into the milestone record and returned in the same API response. The frontend reads that number from the response and updates the progress bar on the spot — with zero extra network calls. No page reload, no second request. One drag, one call, one update. This makes the system feel fast and real-time, and it keeps the database consistent even if multiple students are working simultaneously."*

---

**Step 7 — Document Versioning**

[ACTION] Navigate to Project Proposals. Click "Upload" and submit the `Chapter_1_Draft.pdf`.

> [SAY] *"Now the student uploads their Chapter 1 draft for adviser review. But here is where our system is different from a basic file upload."*

[ACTION] Upload the same file again, simulating a revised version.

> [SAY] *"Instead of overwriting the existing file, our `ProjectSubmissionController` checks the highest version number already on record for this student, adds one to it, and creates a brand new database row. So Version 1 is never touched. Version 2 is stored separately. The adviser can now open both versions side-by-side and compare the student's revisions over time. This version history is permanent and cannot be deleted by the student."*

---

## 📋 Phase 3: First Review (Adviser)
*Switch to the Adviser's tab — the assigned faculty adviser reviews the proposal FIRST before the Coordinator.*

> **Browser note:** The Adviser uses the `web` guard (same as Coordinator). If they are different people, log the Adviser in on a separate Incognito window, or log out the Coordinator first.

**Step 8 — Adviser Reviews the Proposal**

[ACTION] Log in as the Adviser (e.g., Engr. Vicente Patalita III). Navigate to **Proposal Review** in the sidebar.

> [SAY] *"I am now logged in as the group's assigned faculty adviser. When the student submitted their proposal, the system automatically set its status to 'Under Review' and routed it here — to the adviser's inbox. The adviser is the first line of review."*

[ACTION] Open the student's submitted proposal. Click "Preview" to open the document inline.

> [SAY] *"The adviser can read the full document without downloading it. They can also open two versions side-by-side using our Version Compare feature, which pulls both files from storage and renders them in a split-pane view — useful for comparing what changed between revisions."*

[ACTION] Fill in a feedback comment. Select **Approved**. Click Submit.

> [SAY] *"Once the adviser submits, two things happen simultaneously: the proposal status updates to 'approved' in the database, and the student receives an in-system notification immediately. If the adviser had rejected it, the comment they wrote becomes the revision instruction the student sees on their dashboard."*

> [SAY] *"The adviser can only see proposals from groups they are assigned to. The backend query filters strictly by `faculty_id` — Adviser A cannot see or approve Adviser B's students. Access is scoped at the database layer, not just hidden in the UI."*

---

## 👨‍🏫 Phase 4: Management & Auto-Assign (Coordinator)
*Switch tabs to the Coordinator account.*

**Step 9 — Faculty Matrix Dashboard**

[ACTION] Log in as the Coordinator. Navigate to the Faculty Matrix dashboard.


> [SAY] *"I am now logged in as the Coordinator, who manages the operational side of the capstone program. On this dashboard, the Coordinator can see at a glance how many groups every faculty member is currently advising."*

> [SAY] *"What makes this fast is how we query it. We use Laravel's `withCount` method, which translates into a single SQL query using an aggregated subquery. The database does the counting for us at the SQL level — we are not looping through records in PHP, which would be extremely slow for large departments. This eliminates what is known as the 'N+1 Query Problem'."*

---

**Step 9 — Assign a Milestone to the Group**

[ACTION] Go to Milestones. Find the student's group in the group list. Select a milestone template from the dropdown (e.g., "Chapter 1 — Proposal") and set a due date. Click Assign.

> [SAY] *"Before the student can begin working on tasks, the Coordinator must assign a milestone template to their group. A milestone template is a pre-built checklist — created by the Coordinator — that contains all the tasks the group must complete for that phase of their project. When I click Assign here, the system does two things: it creates a new milestone record linked to this group, and it automatically generates a task card for every item inside that template. The students do not build their own task list — the Coordinator defines the requirements, and the system provisions them instantly."*

> [SAY] *"Another rule the system enforces: the same template cannot be assigned to the same group twice. If I try to assign 'Chapter 1' again, the backend checks the `group_milestones` table for an existing match and rejects the duplicate. This prevents accidental double-assignment."*

[ACTION] The group now shows the assigned milestone. The student's Kanban board is now populated.

---

**Step 10 — Approve a Proposal**

[ACTION] Go to Proposal Submissions. Find the student's uploaded document. Click Approve.

> [SAY] *"The Coordinator reviews and approves the student's submitted proposal. Once approved, the student is notified inside the system and can proceed to the next stage."*

---

**Step 11 — The Auto-Assign Algorithm (CRITICAL FEATURE)**


[ACTION] Navigate to Defense Scheduling. Find a group that has requested a defense. Click "Schedule Panel" and open the faculty dropdown.

> [SAY] *"This is one of the most technically significant features we built — the Auto-Assign Panel algorithm."*

> [SAY] *"When a group is ready to defend, the Coordinator needs to pick three faculty members to sit on the panel. But they cannot just pick anyone. There are three rules our system enforces automatically:"*

> [SAY] *"First — no double booking. The system checks the requested defense date and time, and completely filters out any faculty who already have a defense scheduled at that exact slot. You will not see them in this list at all."*

> [SAY] *"Second — no conflict of interest. The group's own adviser is automatically removed from the selectable pool. An adviser cannot be a panelist for their own advisee."*

> [SAY] *"Third — workload balancing. The remaining eligible faculty are sorted from the least number of assigned groups to the most. The top of this list shows the three least-busy teachers, so the Coordinator naturally gravitates toward balanced assignments."*

[ACTION] Select three panelists from the top of the list and save the schedule.

> [SAY] *"All three rules are enforced by the backend query — not by the UI. Even if someone tried to bypass the frontend, the controller would reject the invalid selection. The system is secure at the data layer."*

---

## 📝 Phase 4: The Final Grade (Adviser / Panelist)
*Log in as one of the assigned panelists.*

**Step 12 — Dynamic JSON Grading Rubric**

[ACTION] Log in as a Panelist. Navigate to Active Defenses. Open the Grading Sheet for the group.

> [SAY] *"I am now logged in as one of the panelists assigned to this defense. After the group presents, I need to submit my grades using this rubric."*

[ACTION] Fill in scores for the visible criteria — Methodology, Presentation, Delivery, etc. Click Submit.

> [SAY] *"As I submit this grading sheet, I want to highlight something about how we store this data, because it is a deliberate architectural decision."*

> [SAY] *"We did not create a separate database column for every grading criterion — Grammar, Methodology, Delivery, and so on. If we did that, and the university changed their rubric next semester, we would need to run a database migration just to add or remove columns. That is fragile and not scalable."*

> [SAY] *"Instead, our backend takes the entire submitted form and serializes it into a single JSON string, which is saved into one text column in the database. The criteria names, the weights, the scores — all of it lives inside that JSON blob. This means the university can completely overhaul their grading criteria next semester, and our system will support it immediately without touching the database schema."*

---

## 🏁 The Closing Statement

[ACTION] Return to the student's dashboard. Show the final progress bar and defense schedule card.

> [SAY] *"As you can see, panelists, CapTrack is not just a task tracker. It is a fully automated capstone management pipeline that secures student data at the middleware layer, auto-calculates progress in real time using a single-request architecture, versions documents without overwriting history, balances faculty workload algorithmically, and stores grading data in a flexible JSON format that can adapt to any rubric."*

> [SAY] *"Every design decision we made was driven by one question: 'What happens when this scales to hundreds of students and dozens of faculty?' We believe CapTrack answers that question. Thank you, panelists, and we are now open for your questions."*

---

## ❓ Anticipated Panel Questions & Answers

**Q: "What happens if two students drag the same task at the same time?"**
> *"Each drag-and-drop fires an independent PATCH request scoped to a specific task ID. The backend validates the task, updates its status, and recalculates progress atomically on the server side. The last write wins at the database level, and the next page load will reflect the correct state."*

**Q: "How do you prevent students from accessing other groups' data?"**
> *"Every controller method first retrieves the authenticated student, then retrieves only the groups that student belongs to, and scopes all queries through that group. A student cannot pass in a different group ID and get data — the backend ignores it because the query is always filtered by the session's authenticated user."*

**Q: "Why did you use Laravel instead of building it from scratch?"**
> *"Laravel provides a proven security foundation — CSRF protection, SQL injection prevention through Eloquent's parameterized queries, and authentication guards. Building those from scratch introduces risk. We used the framework for its security and infrastructure, and focused our development effort on the business logic that is unique to capstone management."*

**Q: "How does the system handle a coordinator who is also an adviser?"**
> *"We implemented a Switch View mechanism. A faculty member with dual roles — coordinator and adviser — sees a toggle in their sidebar. When they switch to Coordinator View, only coordinator features are visible. When they switch to Adviser View, only adviser features are visible. The backend checks the role on every request, so there is no permission bleed between the two contexts."*
