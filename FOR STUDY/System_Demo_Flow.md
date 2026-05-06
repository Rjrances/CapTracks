# 🚀 CapTrack Live Defense Demo Flow (With Exact Scripts)

When you are standing in front of the panel and it is time to demonstrate the system, do not just click around randomly. You need to tell a **story**. 

Follow this exact step-by-step workflow. **Read the text inside the quotation marks out loud to the panel** as you perform the actions on the screen.

---

## 🛠️ Pre-Demo Setup (Do this BEFORE you present)
1. **Prepare 3 Browsers/Tabs:** Have an Incognito window ready so you can log in as a Chairperson, a Coordinator, and a Student simultaneously without having to log in and out repeatedly.
2. **Prepare a Dummy File:** Have a sample PDF on your desktop named `Chapter_1_Draft.pdf` ready to upload.
3. **Prepare a CSV:** Have a small CSV file with 2 fake students ready to demonstrate the import.

---

## 🎬 Phase 1: The Setup (Chairperson)
*Start your presentation as the highest admin to show how a semester begins.*

**1. Login as Chairperson.**
> *"Good morning panelists. To begin our demonstration, I am logging in as the Chairperson to set up the system for the new semester."*

**2. CSV Import:** Go to the Student list and upload your sample CSV file.
> *"To populate our system quickly, the Chairperson can mass-import students via CSV. Now, you might wonder: what if I accidentally upload the exact same file twice? Will the database crash with duplicate errors?*
> 
> *(Click Upload)*
> 
> *The database will not crash because we handle de-duplication inside the backend using Eloquent's `firstOrCreate` method. As the system loops through the CSV rows, it checks the Student ID. If it finds the ID already exists, it skips it. If it doesn't, it inserts the new student and encrypts their default password automatically."*

**3. Create a Class:** Go to Offerings, create a quick class section, assign a Coordinator to it, and manually enroll one of your newly imported students.

---

## 👨‍🎓 Phase 2: The Core Workflow (Student)
*Switch to your Incognito tab and log in as the student you just created.*

**1. Forced Password Change:** Log in with `password123`. 
> *"I am now logging in as one of the newly imported students using the default password. However, notice how the system immediately blocks me and forces a password change.*
> 
> *We implemented a security Middleware for this. Whenever a user logs in, the backend runs a Hash Check. If their encrypted password matches the default 'password123', the middleware intercepts the page load and redirects them here. Once I change it, the Hash Check fails, and I am allowed into the dashboard."*

**2. Form a Group:** Create a capstone group and send an invitation to an Adviser.

**3. The Kanban Board:** Open the Milestones page. Drag a task card from 'Pending' to 'Done'. Show them the progress bar immediately updating. 
> *"Here is the student's Kanban board. When I drag a task to 'Done', the progress percentage updates immediately.*
> 
> *To prevent our dashboard from slowing down, we don't calculate these percentages on the fly every time the page loads. Instead, an asynchronous backend trigger fires when the card drops. It counts the total tasks, calculates the math `(Done / Total) * 100`, and saves a static integer directly into the database. This keeps the system highly scalable."*

**4. Document Versioning:** Go to Proposals. Upload `Chapter_1_Draft.pdf`. 
> *"When a student uploads a document, we don't want them to accidentally overwrite or delete their past revisions.*
> 
> *(Click Upload)*
> 
> *Instead of updating the existing database row, our `ProjectSubmissionController` finds the highest version number the student already has, and automatically adds `+1` to it. It then creates a brand new row for Version 2. This ensures the old file is never overwritten, allowing the adviser to compare Version 1 against Version 2 side-by-side later."*

---

## 👨‍🏫 Phase 3: Management & Auto-Assign (Coordinator)
*Switch tabs to the Coordinator who handles the logistics.*

**1. Faculty Matrix:** Go to the Faculty Matrix dashboard.
> *"I am now logged in as the Coordinator. On this dashboard, the coordinator can monitor the workload of every teacher. We use Laravel's native `withCount` method to aggregate these numbers directly in SQL, avoiding the 'N+1 Query Problem' and keeping the page lightning fast."*

**2. Approve the Proposal:** Go to the Proposal list and approve the student's document.

**3. The Auto-Assign Algorithm (CRITICAL):** Go to Defense Scheduling. Pretend the student requested a defense. Click "Schedule Panel" and open the dropdown.
> *"When a student is ready to defend, the Coordinator uses our Auto-Assign algorithm to find panelists.* 
> 
> *(Point to the dropdown list)*
> 
> *The system doesn't just show every teacher. First, it looks at the requested date and completely filters out any faculty who already have a defense scheduled at this exact time, preventing double-booking.*
> 
> *Next, it automatically removes the group's adviser to prevent a conflict of interest. Finally, it counts how many groups the remaining eligible teachers are assigned to, and sorts them from lowest to highest. It gives the Coordinator the top 3 least-busy teachers to ensure workload is balanced fairly across the department."*

---

## 📝 Phase 4: The Final Grade (Adviser / Panelist)
*Log out and log in as one of the teachers you assigned to the panel.*

**1. The Live Defense:** Go to the Active Defenses calendar.

**2. Dynamic JSON Rubric:** Open the Grading Sheet. Input some scores into the criteria (Grammar, Logic, etc.) and submit.
> *"Finally, I am logged in as a Panelist grading the live defense. As I input scores for Grammar, Methodology, and Delivery...*
> 
> *(Click Submit)*
> 
> *...the system calculates the total grade. However, we did not hardcode these criteria as columns in our database. Instead, the backend takes this entire form and converts it into a raw JSON string, saving it into a single text column. This dynamic JSON architecture means the university can completely change their grading rubrics next semester, and our database will instantly support it without breaking or requiring SQL migrations."*

---

## 🏁 The Closing Statement
End the demo by saying:
> *"As you can see, panelists, CapTrack is not just a tracking system. It is a fully automated project management pipeline that secures student data natively, balances faculty workload algorithmically, and provides a highly flexible JSON architecture. Thank you, and we are now ready for your questions."*
