# Faculty Roles System - CapTrack

## Overview
The CapTrack system implements a simplified role assignment system where any faculty member who handles a subject automatically becomes a coordinator.

## Role Definitions

### 🎯 **COORDINATORS**
**Purpose**: Manage all subject offerings and approve student proposals

**Responsibilities**:
- Handle all subject offerings (Capstone Projects, Thesis, etc.)
- Review and approve/reject student project proposals
- Manage project workflow and milestones
- Access to **Proposal Review** section in coordinator dashboard
- Oversee project implementation

**Access Rights**:
- ✅ Proposal Review Dashboard
- ✅ Approve/Reject Proposals
- ✅ Project Management
- ✅ Group Management
- ❌ Cannot be advisers for their own coordinated offerings

### 👨‍🏫 **TEACHERS**
**Purpose**: General teaching responsibilities (without offerings)

**Responsibilities**:
- Provide academic mentoring
- General teaching and course instruction
- Focus on research methodology and academic writing

**Access Rights**:
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
- **Any Subject** → Teacher automatically becomes **Coordinator**

### When Updating Offerings:
- **Any Subject** → Teacher automatically becomes **Coordinator** (if not already)

### When Deleting Offerings:
- **Any Subject Deleted**: No automatic role change (coordinator remains coordinator)

## Workflow Examples

### General Project Workflow:
1. **Student** submits proposal
2. **Coordinator** reviews and approves/rejects proposal
3. **Adviser** guides implementation
4. **Panelist** evaluates final presentation

## Database Schema

### Role Assignment Logic:
```php
// All offerings automatically assign coordinator role
if ($teacher && !$teacher->hasRole('coordinator')) {
    $teacher->role = 'coordinator';
    $teacher->save();
}
```

## Benefits

### ✅ Simplified Role Management:
- **Coordinators**: Handle all subjects and proposal approval
- **Teachers**: General teaching without subject assignments
- **Advisers**: Focus on implementation guidance
- **Panelists**: Focus on final evaluation

### ✅ Simplified Workflow:
- All subjects treated equally
- Any faculty with subjects becomes coordinator
- No complex role transitions

### ✅ Automatic Management:
- No manual role assignment needed
- Simple rule: faculty with subjects = coordinator
- No complex role consistency checks

### ✅ System Integrity:
- All coordinators can access proposal review
- Clear, predictable role assignment
- Reduced complexity and maintenance

## Migration Notes

### Existing Data:
- Current coordinators will remain coordinators
- Faculty with any offerings will become coordinators
- No complex role transitions needed

### New Assignments:
- Assign any subject to faculty
- Faculty automatically becomes coordinator
- Simple, predictable system

## Commands

### Check Role Assignments:
```bash
php artisan tinker
>>> User::with('offerings')->whereIn('role', ['coordinator', 'teacher'])->get()
```

This simplified system ensures that any faculty member who handles subjects becomes a coordinator with full access to proposal approval and management functions.
