# CapTrack: The Ultimate System Lifecycle Flow

This document covers **everything**. It traces the exact chronological journey of a capstone project from the very first database entry to the final grade, including every "little thing", security check, and background calculation.

---

## 🟢 Phase 1: Database & Admin Setup (The Chairperson)
1. **Secure Login:** Chairperson logs in. The system authenticates them via the `web` guard and triggers `session()->regenerate()` to prevent session fixation hacking. The PHP `match($role)` logic perfectly redirects them to the Chairperson dashboard.
2. **Semester Initialization:** Chairperson goes to Academic Terms. They create "1st Sem 2026" and toggle it to active. The system automatically runs a bulk update setting all *other* terms to inactive (`is_active = false`).
3. **Faculty Import:** Chairperson uploads `faculty.csv`. The system parses the array and uses `firstOrCreate` logic. If a teacher is uploaded twice, the system skips the duplicate to prevent fatal SQL crashes. It sets default passwords to encrypted `password123`.
4. **Student Import:** Chairperson uploads `students.csv` using the exact same safe deduplication logic.
5. **Role Adjustments:** Chairperson edits a specific faculty member, changing their role from 'Teacher' to 'Coordinator'. (A safeguard triggers preventing the Chairperson from accidentally changing their *own* role and losing admin rights).
6. **Class Setup:** Chairperson creates a new Offering ("IT4A") and assigns the newly made Coordinator to lead it.
7. **Enrollment:** Chairperson uses the Pivot Table logic (`attach()`) to link specific imported students into the "IT4A" Offering.

---

## 🟢 Phase 2: Class Initialization (The Coordinator)
8. **Dashboard Access:** Coordinator logs in and checks their Classlist to verify all students were successfully enrolled by the Chairperson.
9. **Milestone Setup:** Coordinator creates a Milestone Template ("Proposal Defense"). They add specific requirements to it ("Chapter 1 Draft", "Chapter 2 Draft"). The Coordinator then explicitly **assigns** this template to specific active groups, establishing their initial progress requirements.
10. **Workload Monitoring:** Coordinator opens the Faculty Matrix. The system executes complex `withCount` queries in the background to count exactly how many groups and panels every teacher is currently handling *this active semester*.

---

## 🟢 Phase 3: Student Onboarding & Group Formation
11. **Bypass Middleware:** A Student logs in. A custom middleware (`CheckStudentPasswordChange`) intercepts them because their password hash resolves to the default `password123`. They are totally blocked from the system until they type a new, secure password.
12. **Team Building:** The Student navigates to Group Formation, names their group "Tech Innovators", and searches for classmates by ID to invite them.
13. **Adviser Hunting:** The Group Leader browses the available Faculty list and sends an "Adviser Invitation" to a teacher. The system inserts a `pending` row in the database.
14. **Real-Time Alert:** The system immediately generates an in-app Notification for that specific faculty member.

---

## 🟢 Phase 4: Adviser Acceptance & Mentoring Setup
15. **Clearing Notifications:** The Teacher logs in, sees their notification bell ring, and clicks "Mark All as Read". The system runs a blazing fast `whereIn` bulk update to clear them.
16. **Accepting the Group:** The Teacher goes to their Invitations tab and clicks "Accept". The invitation row turns to 'accepted', and the Group table natively updates its `faculty_id` to officially link the teacher and students.

---

## 🟢 Phase 5: The Capstone Grind (Kanban & Submissions)
17. **Task Management:** Students open their Milestone Dashboard. A student drags the "Draft Intro" card from `Todo` to `Done` on the Kanban board.
18. **Dynamic Calculation:** A backend trigger fires instantly. It counts total tasks vs done tasks, calculates `(completed/total)*100`, rounds it, and saves the percentage directly to the database. The Adviser's dashboard reads this static number, keeping the app lightning fast.
19. **Versioning Uploads:** The Student uploads their first draft `chapter1.pdf`. The system queries the DB for `MAX(version)` of that document type, calculates it is Version 1, and saves the file path.
20. **Document Review:** The Adviser receives a "New Submission" alert. They open the in-browser Document Previewer to read the PDF.
21. **Threaded Chat:** The Adviser leaves a comment: *"Fix the grammar on page 2."* The system logs this exact action in the Global Activity Feed using Polymorphic relationships.
22. **Nesting Replies:** The Student replies: *"Done!"* The system uses an Adjacency List model (`parent_id`) to nest the student's reply cleanly under the Adviser's original comment without needing a separate database table.
23. **Historical Rollback:** The Student uploads the fixed document. The system calculates `MAX(version) + 1` and marks it as Version 2. Both files are kept in the database so the Adviser can compare them side-by-side.

---

## 🟢 Phase 6: Defense Scheduling Magic
24. **The Request:** The group's Kanban progress hits 100%. The Student clicks "Request Defense", picking May 9th on the calendar picker.
25. **Auto-Assign Logic (The Flex):** The Coordinator sees the pending request and clicks "Schedule". They open the Panel assignment dropdown. Behind the scenes, the 5-Step Auto-Assign algorithm runs:
   - *Step 1:* Pulls all valid faculty.
   - *Step 2:* Removes the group's Adviser.
   - *Step 3:* Removes the Subject Coordinator.
   - *Step 4:* Checks `defense_schedules` to permanently hide anyone who is already busy at that exact date and time.
   - *Step 5:* Sorts the remaining, eligible teachers by workload, suggesting the least-busy teachers at the very top of the list.
26. **Finalizing the Date:** The Coordinator selects the suggested Chair and Member, and sets the venue. The system creates the `DefenseSchedule` row and generates pending `DefensePanel` invites.
27. **Calendar Sync:** The scheduled defense instantly populates as a Red block on the shared FullCalendar dashboard for all involved users.

---

## 🟢 Phase 7: Defense Day & Grading
28. **Panel Acceptance:** The assigned Panelists log in and accept their panel invitations.
29. **Digital Rubric:** During the physical presentation on May 9th, the Panelists open the Rating Sheet module on their laptops.
30. **JSON Scoring:** They enter their scores (e.g., Presentation: 20/20, Content: 15/20). The system runs an `array_sum()` to calculate the total score. Crucially, it encodes the individual criteria breakdown into a flexible `json_encode()` string and saves it, meaning if the school changes the rubric next year, the database won't break.
31. **The Finale:** The Coordinator opens the schedule, sees that all panelists have submitted their scores, reviews the averages, and clicks "Finalize". The project is officially complete, logged, and archived in the system.
