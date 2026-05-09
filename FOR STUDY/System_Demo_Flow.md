# CapTrack Live Defense Demo Flow — Full Script

When you demonstrate the system to a panel, follow a **story**, not random clicks.

Use this workflow as your guide. **Read the text inside the quotation marks out loud** when it helps the narrative. Lines labeled `[ACTION]` are what you **do**; lines labeled `[SAY]` are optional spoken narration.

---

## How this maps to CapTrack (quick reference)

| Area | Base URL (after your app host) |
|------|--------------------------------|
| Shared faculty login | `/login` |
| Chairperson | `/chairperson/...` |
| Coordinator | `/coordinator/...` |
| Adviser / Teacher (faculty) | `/adviser/...` |
| Student | `/student/...` (after logging in as a student from `/login`) |

**Guards:** The student session uses the `student` guard; faculty use the `web` guard. A **student** and a **faculty** account can be logged in together in one browser (different tabs). Two faculty accounts, or two student accounts, share one session—use **Incognito** or a second browser when you need two faculty users or two students at once.

---

## Pre-demo setup (before you present)

1. **Tabs**
   - **Normal browser:** Student on `/login` → student dashboard; Coordinator or Adviser on `/login` → their dashboard (faculty).
   - **Incognito (or second browser):** Another faculty role if needed (e.g., Chairperson plus Coordinator), or a second student.

2. **Academic term:** Ensure an **active academic term** exists (`/chairperson/academic-terms` — toggle active term). Without it, many flows warn or filter oddly.

3. **Sample file:** A PDF (e.g., `Chapter_1_Draft.pdf`) for project/proposal uploads.

4. **Student import CSV:** Must satisfy import validation (see `StudentsImport`): e.g. **student ID** (10 digits), **semester** exactly one of `2024-2025 First Semester`, `2024-2025 Second Semester`, `2024-2025 Summer`, **course**, **email**, and optional **offer_code** matching an existing offering’s `offer_code` for auto-enrollment. Use the project’s CSV template if one is provided.

5. **Kanban demo:** Have at least one milestone task in **Pending** on the student board so you can drag it during the demo.

---

## Phase 1: Setup (Chairperson)

*Show how a semester is prepared.*

### Step 1 — Log in as Chairperson

> [SAY] *"We begin with the Chairperson, who sets up the academic term, users, offerings, and enrollments."*

[ACTION] Open `/login`, sign in with Chairperson credentials, land on **`/chairperson/dashboard`** (sidebar: **Dashboard**, **Offerings**, **Teachers**, **Students**, **Calendar**).

---

### Step 2 — Import students (CSV)

[ACTION] Go to **`/chairperson/upload-students`** (sidebar: **Students** → import/upload flow as labeled in the UI).

> [SAY] *"Bulk student onboarding uses CSV import instead of manual entry."*

[ACTION] Upload your prepared CSV.

> [SAY] *"If we upload the same students again, the importer skips rows whose student ID or email already exists—it does not insert duplicates."*

[ACTION] Upload the same file a second time and show that new rows are skipped / messaging reflects existing students (see `StudentsImport` and `StudentImportService`).

---

### Step 3 — Offerings and enrollment

[ACTION] Open **`/chairperson/offerings`** → **Add New Offering**. Fill subject title, code, **offer code**, assign the **Teacher** (faculty in charge—this ties the offering to that faculty record), and term.

> [SAY] *"An offering is a class section for the active term. The assigned teacher is the faculty record linked to this section."*

[ACTION] Open the offering’s detail/enrollment UI and **enroll** the imported student(s), or rely on **offer_code** in the CSV if you demonstrated auto-enrollment.

[ACTION] Optionally **`/chairperson/teachers`** — use **Assign Coordinator** so a faculty member has the coordinator role when your demo needs a Coordinator who manages that offering’s groups.

---

## Phase 2: Core workflow (Student)

*Use the student tab (`/student/...`).*

### Step 4 — First login and password

[ACTION] Log out any student session if switching students. On **`/login`**, enter the **student ID** in **ID Number**. For accounts created by CSV import, **`StudentAccount`** may have **no password yet** with **`must_change_password`** set—the login form allows leaving **Password** blank for first-time setup.

> [SAY] *"Imported students are prompted to set a password before using the rest of the app. That is enforced by `CheckStudentPasswordChange` middleware, not by comparing against a shared default password string."*

[ACTION] Complete **`/student/change-password`** as prompted, then continue to **`/student/dashboard`**.

---

### Step 5 — Group and adviser invitation

[ACTION] **`/student/group`** (create/join group), then invite an adviser per your UI (**invite adviser**).

> [SAY] *"The group invites an adviser; notifications carry the invitation to faculty."*

---

### Step 6 — Milestones Kanban

[ACTION] Open **`/student/milestones`**, enter a milestone (**`/student/milestones/{id}`**). The board shows columns **Pending**, **In Progress**, and **Completed**.

> [SAY] *"Tasks are assigned from coordinator milestone templates; students move cards across columns."*

[ACTION] Drag a card from **Pending** toward **Completed** (or use the controls that move status toward **done**).

> [SAY] *"Moving a task triggers an update on the server (`moveTask` / related routes); progress is recalculated for the milestone."*

---

### Step 7 — Project uploads and versioning

[ACTION] Go to **`/student/project`** (**My Project** / project submissions). Upload a PDF; choose submission **type** appropriate to your demo (e.g., **proposal**).

> [SAY] *"Each upload gets the next version number for that student and type—handled in `ProjectSubmissionController` / `ProjectSubmission::getNextVersionFor`—so revisions accumulate instead of silently overwriting history."*

[ACTION] Upload again to show **version 2**.

---

## Phase 3: Adviser — proposal / project review

*Faculty use **`/adviser/...`** after logging in on **`/login`**.*

**Browser note:** Coordinator and Adviser both use the `web` guard—use Incognito if you need both logged in as different people.

### Step 8 — Adviser reviews work

[ACTION] Log in as the assigned **adviser** faculty user.

> [SAY] *"Advisers see assigned groups and submissions scoped to those groups."*

[ACTION] Open **`/adviser/proposals`** for proposal workflow (`AdviserProposalController`), and/or **`/adviser/projects`** for document review (`ProjectSubmissionController`), depending on what you uploaded. The sidebar highlights **Adviser Groups**, **Panel Groups**, etc.; **Proposal Review** is not a separate sidebar item—use the routes above or links from **Adviser Groups** / dashboard.

[ACTION] Preview a document, add feedback, approve or reject per the form.

> [SAY] *"Queries are scoped so an adviser only sees groups they advise."*

---

## Phase 4: Coordinator — operations

*Coordinator sidebar:** Dashboard, Groups, Class List, **Faculty Matrix**, **Proposal Review**, **Defense Management**, **Milestone Templates**, Calendar, Activity Log.*

### Step 9 — Faculty Matrix

[ACTION] **`/coordinator/faculty-matrix`** (**Faculty Matrix**).

> [SAY] *"This screen lists coordinated groups with adviser and defense panel roles and schedule stage—it is a group–faculty matrix for defenses, not a generic query of every teacher’s advisee counts."*

---

### Step 10 — Assign a milestone to a group

[ACTION] **`/coordinator/milestones`** (**Milestone Templates**). Scroll to **Group Milestone Assignments**, pick the group, click **Assign**, choose a template and optional due date.

> [SAY] *"The same template cannot be assigned twice to the same group—the backend checks existing `GroupMilestone` rows."*

[ACTION] Confirm tasks appear on the student milestone board.

---

### Step 11 — Coordinator proposal review

[ACTION] **`/coordinator/proposals`** (**Proposal Review**) — align coordinator decision with your adviser demo.

---

### Step 12 — Defense scheduling and panel rules

[ACTION] Students request defenses from **`/student/defense-requests`**. As Coordinator, open **`/coordinator/defense`** (**Defense Management**) and work pending items (e.g., **`/coordinator/defense-requests`** routes) to **schedule** a defense.

[ACTION] On the scheduling form, assign date, room, and panelists (use **`getAvailableFaculty`** / panel picker as implemented).

> [SAY] *"The backend filters faculty for conflicts, excludes the adviser from being a panelist for their own group where enforced, and applies workload-style ordering for fair distribution—validate on the server, not only in the browser."*

---

## Phase 5: Panel grading (faculty)

*Panelists are faculty users; they use the same **`/login`** and typically the **Adviser** UI.*

### Step 13 — Rating sheet (JSON rubric)

[ACTION] Log in as a faculty panelist. Use **`/adviser/panel-groups`** or **`/adviser/panel-invitations`**, then open the **Rating Sheet** for the scheduled defense (`/adviser/rating-sheets/{schedule}`).

> [SAY] *"Scores are stored in a flexible structure (JSON-friendly) so criteria can evolve without constant schema churn."*

[ACTION] Submit the rating form.

---

## Closing

[ACTION] Return to **`/student/dashboard`** — show progress and defense calendar cards if data exists.

> [SAY] *"CapTrack ties together term setup, enrollment, milestones, submissions with versioning, coordinated reviews, defense scheduling, and panel grading in one pipeline."*

---

## Anticipated panel questions (aligned with the codebase)

**Q: What happens if two students drag tasks at once?**  
> *"Updates are per task ID on the server; last successful write wins; refreshing shows the persisted state."*

**Q: How do you isolate student data?**  
> *"Controllers resolve the authenticated student and constrain queries to that student’s groups and IDs."*

**Q: Why Laravel?**  
> *"We rely on the framework for CSRF, validated queries via Eloquent, guards, and middleware, and focus custom code on capstone domain logic."*

**Q: Can someone be both Coordinator and Adviser?**  
> *"Faculty use one `web` session. The Coordinator sidebar can show **Switch to Adviser View** when applicable (`partials/coordinator-sidebar`), linking to the adviser dashboard—there is not a separate toggle for every role pair, but coordinators who advise can jump to the adviser area."*

---

*This document was aligned to routes in `routes/web.php` and the chairperson, coordinator, adviser, and student areas as of the CapTracks codebase. If labels change in Blade, follow the UI text and keep these URLs as fallbacks.*
