# Faculty Roles System - CapTrack

## Overview
The CapTrack system now implements a subject-type based role assignment system that differentiates between Capstone and Thesis offerings.

## Role Definitions

### 🎯 **COORDINATORS**
**Purpose**: Manage Capstone Project offerings and approve student proposals

**Responsibilities**:
- Handle **Capstone Project I** (CS-CAP-401) and **Capstone Project II** (CS-CAP-402)
- Review and approve/reject student project proposals
- Manage capstone project workflow and milestones
- Access to **Proposal Review** section in coordinator dashboard
- Oversee capstone project implementation

**Access Rights**:
- ✅ Proposal Review Dashboard
- ✅ Approve/Reject Proposals
- ✅ Capstone Project Management
- ✅ Group Management for Capstone Projects
- ❌ Cannot be advisers for their own coordinated offerings

### 👨‍🏫 **TEACHERS**
**Purpose**: Handle Thesis offerings and general teaching responsibilities

**Responsibilities**:
- Handle **Thesis I** (CS-THS-301) and **Thesis II** (CS-THS-302)
- Provide thesis guidance and academic mentoring
- General teaching and course instruction
- Focus on research methodology and academic writing

**Access Rights**:
- ✅ Thesis Guidance and Mentoring
- ✅ General Teaching Functions
- ✅ Student Academic Support
- ❌ Cannot approve proposals
- ❌ No access to Proposal Review section

### 👨‍💼 **ADVISERS**
**Purpose**: Guide student groups through project implementation

**Responsibilities**:
- Provide technical guidance to student groups
- Review milestone submissions and task progress
- Offer feedback on project development
- Support students throughout implementation phase

**Access Rights**:
- ✅ Milestone Review and Feedback
- ✅ Task Submission Review
- ✅ Group Progress Monitoring
- ✅ Student Implementation Support
- ❌ Cannot approve proposals (only coordinators can)

### 👥 **PANELISTS**
**Purpose**: Evaluate final project presentations

**Responsibilities**:
- Serve on defense panels
- Evaluate final project presentations
- Provide assessment during defense sessions
- Contribute to final project grading

**Access Rights**:
- ✅ Defense Panel Participation
- ✅ Final Project Evaluation
- ✅ Presentation Assessment
- ❌ Cannot approve proposals
- ❌ No access to Proposal Review section

### 🏛️ **CHAIRPERSON**
**Purpose**: Overall system administration and oversight

**Responsibilities**:
- Manage academic terms and semesters
- Assign faculty to offerings
- System-wide oversight and administration
- User management and role assignments

**Access Rights**:
- ✅ Full System Administration
- ✅ Academic Term Management
- ✅ Faculty Assignment
- ✅ User Role Management
- ✅ All System Functions

## Automatic Role Assignment

### When Creating Offerings:
- **Capstone Project I/II** → Teacher automatically becomes **Coordinator**
- **Thesis I/II** → Teacher remains **Teacher**

### When Updating Offerings:
- **Capstone → Capstone**: No role change
- **Thesis → Thesis**: No role change
- **Capstone → Thesis**: Coordinator becomes Teacher
- **Thesis → Capstone**: Teacher becomes Coordinator

### When Deleting Offerings:
- **Capstone Deleted**: Coordinator becomes Teacher (if no other Capstone offerings)
- **Thesis Deleted**: No role change

## Workflow Examples

### Capstone Project Workflow:
1. **Student** submits proposal
2. **Coordinator** reviews and approves/rejects proposal
3. **Adviser** guides implementation
4. **Panelist** evaluates final presentation

### Thesis Workflow:
1. **Student** works on thesis
2. **Teacher** provides guidance and mentoring
3. **Adviser** (if assigned) provides additional support
4. **Panelist** evaluates final presentation

## Database Schema

### Subject Codes:
- **CS-CAP-401**: Capstone Project I
- **CS-CAP-402**: Capstone Project II
- **CS-THS-301**: Thesis I
- **CS-THS-302**: Thesis II

### Role Assignment Logic:
```php
// Only Capstone offerings get coordinator role
private function isCapstoneOffering($offering)
{
    $capstoneSubjects = ['Capstone Project I', 'Capstone Project II'];
    $capstoneCodes = ['CS-CAP-401', 'CS-CAP-402'];
    
    return in_array($offering->subject_title, $capstoneSubjects) || 
           in_array($offering->subject_code, $capstoneCodes);
}
```

## Benefits

### ✅ Clear Separation of Responsibilities:
- **Coordinators**: Focus on Capstone project management and proposal approval
- **Teachers**: Focus on Thesis guidance and general teaching
- **Advisers**: Focus on implementation guidance
- **Panelists**: Focus on final evaluation

### ✅ Logical Workflow:
- Capstone projects require proposal approval (coordinator responsibility)
- Thesis projects focus on research guidance (teacher responsibility)
- Clear distinction between project types

### ✅ Automatic Management:
- No manual role assignment needed
- Roles automatically adjust based on offering assignments
- Clean role transitions when offerings change

### ✅ System Integrity:
- Only Capstone coordinators can access proposal review
- Thesis teachers cannot accidentally access proposal approval
- Clear audit trail of role changes

## Migration Notes

### Existing Data:
- Current coordinators will remain coordinators
- Role transitions will occur when offerings are updated
- Use `forceUpdateAllRoles()` command to update existing assignments

### New Assignments:
- Assign Capstone offerings to coordinators
- Assign Thesis offerings to teachers
- System will automatically manage role assignments

## Commands

### Update All Roles:
```bash
php artisan tinker
>>> (new App\Http\Controllers\ChairPersonController)->forceUpdateAllRoles()
```

### Check Role Assignments:
```bash
php artisan tinker
>>> User::with('offerings')->whereIn('role', ['coordinator', 'teacher'])->get()
```

This system ensures that only coordinators (who handle Capstone offerings) can approve proposals, while teachers (who handle Thesis offerings) focus on guidance and mentoring without proposal approval responsibilities.
