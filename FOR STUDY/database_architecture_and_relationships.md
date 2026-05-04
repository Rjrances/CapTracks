# CapTracks Database Architecture & Relationships

During a capstone defense, panelists frequently ask about database design, normalization, and how tables connect. This document serves as your cheat sheet for the CapTracks database structure and Laravel Eloquent relationships.

---

## 1. Authentication & Users
CapTracks separates students from faculty/staff to maintain strict role boundaries.

### **`users` (Faculty & Staff)**
- **Purpose:** Stores information for Advisers, Coordinators, and Chairpersons.
- **Relationships:**
  - `hasMany(Group::class)`: A faculty member can advise many groups.
  - `hasMany(DefensePanel::class)`: A faculty member can sit on many defense panels.
  - `hasMany(Offering::class)`: A teacher can handle many class offerings.

### **`students`**
- **Purpose:** Stores student profiles.
- **Relationships:**
  - `belongsTo(Group::class)`: A student belongs to exactly ONE group.
  - `belongsToMany(Offering::class)`: A student can enroll in classes (Many-to-Many via `offering_student` pivot table).
  - `hasMany(ProjectSubmission::class)`: A student can upload many project documents.

---

## 2. Academic Setup

### **`academic_terms`**
- **Purpose:** Represents a semester (e.g., "1st Semester 2025-2026"). Only **one** term can be `is_active` at a time.
- **Relationships:**
  - `hasMany(Offering::class)`: An academic term has many class offerings.
  - `hasMany(DefenseSchedule::class)`: Defenses are tied to a specific term.

### **`offerings` (Classes/Sections)**
- **Purpose:** A specific class section (e.g., "Capstone 1 - Section A").
- **Relationships:**
  - `belongsTo(AcademicTerm::class)`
  - `belongsTo(User::class, 'faculty_id')`: The subject teacher/coordinator.
  - `hasMany(Group::class)`: Groups are formed within a specific offering.
  - `belongsToMany(Student::class)`: Enrolled students.

---

## 3. Group Management

### **`groups`**
- **Purpose:** The core entity for capstone projects. Ties students, advisers, and defenses together.
- **Relationships:**
  - `belongsTo(Offering::class)`: The class they belong to.
  - `belongsTo(User::class, 'faculty_id')`: Their assigned Adviser.
  - `hasMany(Student::class)`: The group members.
  - `hasMany(GroupMilestone::class)`: The milestones they need to accomplish.
  - `hasMany(DefenseSchedule::class)`: A group can have defense schedules.

---

## 4. Milestones & Task Tracking
The system uses a "Blueprint" vs "Instance" pattern. The Coordinator makes a template, and when a group is formed, it copies that template into the group's own specific workspace.

### **Templates (The Blueprints)**
- **`milestone_templates`**: The overarching phase (e.g., "Chapter 1").
  - `hasMany(MilestoneTask::class)`: Specific requirements (e.g., "Upload Background of Study").

### **Group Instances (The Actual Trackers)**
- **`group_milestones`**: Tied to a specific `group_id`.
  - `hasMany(GroupMilestoneTask::class)`: The actual tasks the students drag on their Kanban board (`todo`, `in_progress`, `done`).

---

## 5. Submissions & Feedback

### **`project_submissions`**
- **Purpose:** For formal, large documents (Proposals, Final Reports). 
- **Key Columns:** `student_id`, `file_path`, `type`, `version`, `status`.
- **Relationships:**
  - `belongsTo(Student::class)`
  - `hasMany(SubmissionComment::class)`: Threaded comments by advisers.

### **`task_submissions`**
- **Purpose:** For smaller, specific artifacts tied to a Kanban task.
- **Relationships:**
  - `belongsTo(GroupMilestoneTask::class)`
  - `belongsTo(Student::class)`

---

## 6. Defense Scheduling & Grading

### **`defense_requests`**
- **Purpose:** A student submits this to ask for a defense date.
- **Relationships:** `belongsTo(Group::class)`.

### **`defense_schedules`**
- **Purpose:** Created by the coordinator. Holds the `schedule_date`, `start_time`, `end_time`, and `venue`.
- **Relationships:**
  - `belongsTo(Group::class)`
  - `hasMany(DefensePanel::class)`: The faculty members assigned to judge.

### **`defense_panels`**
- **Purpose:** Assigns a specific faculty member to a specific schedule with a role (`chair` or `member`).
- **Relationships:**
  - `hasOne(RatingSheet::class)`: The grading sheet submitted by this specific panel member.

### **`rating_sheets`**
- **Purpose:** Holds the actual grades.
- **Key Column:** `scores` (Stored as JSON so the rubric can change dynamically without breaking the database columns).

---

## 🎯 Defense Tips: How to Answer DB Questions

1. **"Why did you use a pivot table for students and offerings?"**
   *Answer:* "Because a student can enroll in multiple capstone classes over different semesters (if they retake or take Capstone 1 then Capstone 2), and a class offering obviously has many students. A Many-to-Many relationship using the `offering_student` pivot table is the most normalized approach."

2. **"How do you handle versions of documents without deleting old ones?"**
   *Answer:* "We keep all records in the `project_submissions` table. When a student uploads a revision, we create a brand new row, calculate the `MAX(version) + 1` for that specific document type, and save it. This preserves the historical file paths so we can do side-by-side comparisons."

3. **"Why are Rating Sheet scores stored as JSON?"**
   *Answer:* "Storing the criteria scores as a JSON string inside a single `scores` column provides flexibility. If the school decides to add or remove grading criteria next semester, we don't need to run a database migration to add or drop columns. The application just reads and writes the new JSON keys."
