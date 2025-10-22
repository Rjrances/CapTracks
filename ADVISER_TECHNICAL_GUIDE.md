# Adviser Role - Technical Documentation

## Overview
Advisers are faculty members who guide student groups on their capstone projects. They review proposals, monitor progress, provide feedback, and support students throughout the project lifecycle.

---

## Controllers

### 1. AdviserController
**File:** `app/Http/Controllers/AdviserController.php`

**Purpose:** Main adviser operations including dashboard, group management, and invitations.

#### Functions:

**`dashboard()`**
- **What it does:** Shows adviser's main dashboard
- **Returns:** Dashboard view with overview
- **Data provided:**
  - Active academic term
  - Total groups advised
  - Total students in groups
  - Total pending proposals
  - Total approved proposals
  - Groups list with details
  - Pending adviser invitations
  - Recent group activities
  - Upcoming group deadlines

**`allGroups()`**
- **What it does:** Shows all groups assigned to this adviser
- **Returns:** Group list view
- **Displays:**
  - Group names
  - Members list
  - Progress percentage
  - Current milestone
  - Last activity
  - Proposal status

**`groupDetails($group)`**
- **What it does:** Shows detailed information about one group
- **Parameters:** Group ID
- **Returns:** Group details view
- **Displays:**
  - All group members
  - Member contact information
  - All milestones with progress
  - All tasks and their status
  - Submissions and documents
  - Defense requests/schedules
  - Activity timeline

**`adviserInvitations()`**
- **What it does:** Shows all adviser invitations received
- **Returns:** Invitation list view
- **Displays:**
  - Group name
  - Members
  - Invitation message
  - Sent date
  - Accept/Decline buttons

**`acceptAdviserInvitation($invitationId)`**
- **What it does:** Accepts invitation to be group adviser
- **Parameters:** Invitation ID
- **Process:**
  1. Validates invitation exists and is pending
  2. Assigns this adviser to the group
  3. Marks invitation as accepted
  4. Notifies group members
- **Returns:** Redirect to group details
- **Effect:** Adviser can now review group's work

**`declineAdviserInvitation($invitationId)`**
- **What it does:** Declines invitation to be group adviser
- **Parameters:** Invitation ID
- **Returns:** Redirect with message
- **Sets:** Invitation status to 'declined'
- **Effect:** Group can invite someone else

**`showMilestone($groupId, $milestoneId)`**
- **What it does:** Shows specific milestone details
- **Parameters:** Group ID, Milestone ID
- **Returns:** Milestone detail view
- **Displays:**
  - Milestone information
  - All tasks
  - Task assignments
  - Completion status
  - Progress percentage

**`showTask($groupId, $milestoneId, $taskId)`**
- **What it does:** Shows specific task details
- **Parameters:** Group ID, Milestone ID, Task ID
- **Returns:** Task detail view
- **Displays:**
  - Task information
  - Assigned student
  - Status
  - Submissions (if any)
  - Comments/feedback

**`addTaskComment(Request $request, $taskId)`**
- **What it does:** Adds adviser feedback to a task
- **Parameters:** Task ID, comment text
- **Validation:** Comment required, min 5 characters
- **Returns:** Redirect with success message
- **Notifications:** Notifies assigned student

**`getAuthenticatedAdviser()` (Private)**
- **What it does:** Gets currently logged-in adviser
- **Returns:** FacultyAccount model or null
- **Use:** Helper method for authorization checks

**`notifications()`**
- **What it does:** Shows all adviser notifications
- **Returns:** Notification list view
- **Displays:** Updates, student submissions, invitation responses

**`markNotificationAsRead($notificationId)`**
- **What it does:** Marks one notification as read
- **Parameters:** Notification ID
- **Returns:** JSON success/error response

**`markAllNotificationsAsRead()`**
- **What it does:** Marks all notifications as read
- **Returns:** JSON with count of notifications marked

**`deleteNotification($notificationId)`**
- **What it does:** Deletes a notification
- **Parameters:** Notification ID
- **Returns:** JSON success response

---

### 2. AdviserProposalController
**File:** `app/Http/Controllers/AdviserProposalController.php`

**Purpose:** Reviews and approves/rejects project proposals from advised groups.

#### Functions:

**`index()`**
- **What it does:** Shows all proposals from advised groups
- **Returns:** Proposal list view organized by group
- **Displays:**
  - Group name
  - Student who submitted
  - Proposal title
  - Status (pending, approved, rejected)
  - Submission date
  - Review buttons
- **Statistics:**
  - Total proposals
  - Pending review count
  - Approved count
  - Rejected count

**`show($id)`**
- **What it does:** Shows full proposal details
- **Parameters:** Proposal ID
- **Returns:** Detailed proposal view
- **Displays:**
  - Proposal title
  - Objectives
  - Methodology
  - Timeline
  - Expected outcomes
  - Attached file (download link)
  - Student information
  - Current status

**`edit($id)`**
- **What it does:** Shows form to review and provide feedback on proposal
- **Parameters:** Proposal ID
- **Returns:** Review form
- **Form includes:**
  - Approve/Reject options
  - Comment field (required)
  - Proposal details for reference

**`update(Request $request, $id)`**
- **What it does:** Approves or rejects a proposal
- **Parameters:** Proposal ID, status (approved/rejected), adviser comment
- **Validation:**
  - Status must be 'approved' or 'rejected'
  - Comment required (minimum 10 characters)
- **Returns:** Redirect to proposals list
- **Process:**
  1. Updates proposal status
  2. Saves adviser comment
  3. Records review timestamp
  4. Sends notification to student
  5. If approved, forwards to coordinator for second review
- **Notifications:** Notifies all group members

**`getStats()`**
- **What it does:** Gets proposal statistics for dashboard
- **Returns:** JSON with counts
- **Data:**
  - total_proposals: All proposals from advised groups
  - pending_review: Waiting for adviser review
  - approved: Approved by adviser
  - rejected: Rejected by adviser
- **Use:** Dashboard charts/cards for quick overview
- **Performance:** Uses eager loading for efficiency

---

### 3. AdviserPasswordController
**File:** `app/Http/Controllers/AdviserPasswordController.php`

**Purpose:** Handles password changes for advisers.

#### Functions:

**`showChangePasswordForm()`**
- **What it does:** Shows the change password page
- **Returns:** Password change form
- **Required:** Adviser must be logged in

**`updatePassword(Request $request)`**
- **What it does:** Updates adviser's password
- **Parameters:** Current password, new password, confirmation
- **Validation:**
  - Current password must be correct
  - New password minimum 8 characters
  - New and confirmation must match
- **Returns:** Redirect with success/error message
- **Security:**
  - Verifies current password
  - Hashes new password before saving
  - Logs out from other devices (optional)

---

## Key Terms

**Adviser**: Faculty member who guides and mentors a student group

**Advising**: Process of guiding students through their capstone project

**Proposal**: Document where students explain their project idea and plan

**Group**: Team of students working together on one capstone project

**Milestone**: Major checkpoint in the project (e.g., "Chapter 1 Complete")

**Task**: Smaller work item within a milestone

**Status Types**:
- `pending`: Waiting for review
- `approved`: Accepted by adviser
- `rejected`: Not accepted, needs revision
- `coordinator_approved`: Approved by both adviser and coordinator

**Review Process**:
1. Student submits proposal
2. Adviser reviews first (can approve or reject)
3. If adviser approves, goes to coordinator
4. If coordinator approves, proposal is fully approved
5. Both must approve for full approval

**Invitation**: Request from student group to be their adviser

**Feedback**: Comments and suggestions provided to students

**Academic Term**: School period (e.g., "2024-2025 First Semester")

**Submission**: Document or file uploaded by student

**Timeline**: Project schedule showing when tasks will be completed

**Methodology**: How students plan to complete their project

**Objectives**: Goals the project aims to achieve

**Expected Outcomes**: Results the project should produce

**Defense**: Presentation where students explain their project to a panel

**Progress**: Percentage of project completed

**Activity Timeline**: Chronological list of group actions and events

**Validation**: Checking if data is correct before saving

**JSON Response**: Data sent back to JavaScript for dynamic updates

**Eager Loading**: Database optimization that loads related data efficiently

**Collection**: Group of database records that can be filtered and sorted

**Pagination**: Splitting long lists into pages (e.g., 20 items per page)

---

## Adviser Responsibilities

### 1. Invitation Management
- **Receive invitations** from student groups
- **Review group details** before accepting
- **Accept or decline** based on availability and expertise
- **Can advise multiple groups** simultaneously

### 2. Proposal Review
- **First reviewer** of all proposals
- **Provide detailed feedback** to help students improve
- **Approve or reject** proposals
- **Approved proposals** move to coordinator for second review
- **Rejected proposals** can be revised and resubmitted

### 3. Progress Monitoring
- **Monitor milestone completion** for all advised groups
- **Review task progress** and assignments
- **Check submission quality** and timeliness
- **Identify struggling groups** early

### 4. Student Guidance
- **Provide feedback** on submissions
- **Answer questions** and clarify requirements
- **Suggest improvements** to project approach
- **Help resolve conflicts** within groups

### 5. Communication
- **Respond to invitations** promptly
- **Review proposals** in a timely manner
- **Provide constructive feedback** that helps students learn
- **Stay informed** through notifications

---

## Common Workflows

### Accepting Adviser Invitation
1. Log in as adviser
2. Go to Dashboard or Invitations page
3. Review group information and members
4. Click "Accept" or "Decline"
5. If accepted, group appears in "My Groups"

### Reviewing a Proposal
1. Go to Proposal Review page
2. See list of proposals grouped by student group
3. Click on proposal to view full details
4. Read all sections: title, objectives, methodology, timeline, outcomes
5. Download and review attached file
6. Click "Review" button
7. Choose Approve or Reject
8. Write detailed feedback (minimum 10 characters)
9. Submit review
10. Student receives notification

### Monitoring Group Progress
1. Go to "My Groups"
2. Click on a group to see details
3. View all milestones and their progress
4. Click on milestone to see tasks
5. Check task assignments and completions
6. Add comments/feedback as needed
7. Review recent submissions

### Managing Notifications
1. Bell icon shows unread count
2. Click to see all notifications
3. Mark individual as read
4. Mark all as read with one click
5. Delete unnecessary notifications

---

## Important Notes

### Proposal Review Guidelines
- **Be thorough**: Read entire proposal and attached file
- **Be constructive**: Focus on helping students improve
- **Be specific**: Point out exactly what needs fixing
- **Be encouraging**: Acknowledge good ideas and efforts
- **Be timely**: Review within a reasonable timeframe

### When to Approve
- ✅ Clear project objectives
- ✅ Feasible methodology
- ✅ Realistic timeline
- ✅ Well-defined expected outcomes
- ✅ Proper academic writing
- ✅ Complete proposal document

### When to Reject
- ❌ Unclear or missing objectives
- ❌ Unrealistic scope or timeline
- ❌ Poor methodology
- ❌ Incomplete information
- ❌ Plagiarism or academic dishonesty
- ❌ Topic not suitable for capstone

**Note**: Rejection is not failure - it's an opportunity for students to improve!

### Best Practices
1. **Check dashboard regularly** for new proposals and updates
2. **Accept invitations early** in the semester
3. **Review proposals within 3-5 days** of submission
4. **Provide detailed feedback** (not just "Needs work")
5. **Monitor group progress** at least weekly
6. **Be available** for student questions
7. **Document important discussions** in task comments
8. **Communicate clearly** and professionally

### Communication Tips
- Use professional but friendly tone
- Be specific about what needs improvement
- Highlight strengths and good ideas
- Provide examples when possible
- Encourage questions and discussion
- Respond to urgent matters quickly

### Workload Management
- **Set limits**: Don't accept more groups than you can handle
- **Prioritize**: Review urgent items first
- **Schedule time**: Set aside regular time for reviews
- **Use notifications**: Stay informed without constant checking
- **Delegate when appropriate**: Some questions can go to coordinator

### Security & Privacy
- Only view data for groups you advise
- Keep student information confidential
- Don't share proposal details outside the system
- Change password regularly
- Log out when done

---

## Troubleshooting

**Problem**: Can't see a group's information
- **Solution**: Check if you accepted their adviser invitation

**Problem**: Proposal doesn't have all sections
- **Solution**: Reject with feedback asking for complete information

**Problem**: Can't approve a proposal
- **Solution**: Ensure you filled in the required comment field (min 10 characters)

**Problem**: Too many notifications
- **Solution**: Use "Mark all as read" or adjust notification preferences

**Problem**: Student not responding to feedback
- **Solution**: Contact coordinator or use notification system to follow up

**Problem**: Unclear which proposals need review
- **Solution**: Check "Pending Review" count on proposal page dashboard

---

## Statistics & Reporting

The Adviser Proposal page shows:
- **Total Proposals**: All proposals from your groups
- **Pending Review**: Proposals waiting for your review
- **Approved**: Proposals you approved (may need coordinator approval still)
- **Rejected**: Proposals you rejected (students can revise)

These stats help you:
- Track your workload
- Identify pending tasks
- Monitor student progress
- Report to department heads

