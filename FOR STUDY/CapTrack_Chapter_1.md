# CHAPTER I

## INTRODUCTION

The culmination of an Information Technology or Computer Science degree is the Capstone Project, a rigorous academic requirement that demands intensive collaboration, systematic documentation, and continuous faculty evaluation. Currently, the administration of capstone projects often relies on fragmented communication channels, manual tracking of milestones via spreadsheets, and physical or disjointed digital submissions of project documents. This lack of a centralized system leads to significant administrative overhead for coordinators, delayed feedback from advisers, and confusion among students regarding deadlines and requirements. 

In many academic institutions, the process of scheduling oral defenses and distributing grading rubrics is highly manual, often resulting in faculty schedule conflicts and lost evaluation sheets. Furthermore, tracking the version history of project proposals is cumbersome, as students and advisers frequently lose track of the most recent revisions across various email threads or messaging applications.

Seeking to mitigate these administrative bottlenecks, this study aims to develop CapTracks, a comprehensive, role-based web application tailored specifically for managing the capstone project lifecycle. The platform provides distinct, secure portals for Students, Advisers, Coordinators, Chairpersons, and Defense Panelists. By centralizing milestone tracking via Kanban boards, automating document versioning, facilitating threaded communication, and introducing an algorithmic approach to defense scheduling, CapTracks aims to foster a more structured, transparent, and efficient environment for both students and faculty members.

## REVIEW OF RELATED LITERATURE

**Project Management in Academic Settings**
The integration of project management software in educational environments has been widely explored. Standard tools such as Trello, Asana, and Jira offer robust task management capabilities, utilizing Kanban boards to track progress. However, while effective for general software development, these tools lack the specific academic workflows required for a university capstone setting, such as formal defense scheduling, faculty load matrix monitoring, and integrated academic grading rubrics.

**Academic Learning Management Systems (LMS)**
Platforms like Canvas and Moodle are staples in academic institutions for assignment submissions and grade tracking. While they excel in traditional classroom settings, they are not optimized for the highly collaborative, multi-role nature of capstone projects. An LMS typically follows a simple Teacher-to-Student relationship, failing to accommodate the complex dynamics of a capstone group that requires interaction with a Subject Coordinator, a dedicated Group Adviser, and a separate panel of examining faculty.

**Usability and Security in Academic Portals**
Usability and role-based security are critical considerations in academic software. Systems must ensure that students cannot access administrative controls or view private grading rubrics. The implementation of Multi-Guard Authentication and strict Role-Based Access Control (RBAC) ensures that data privacy is maintained, preventing unauthorized access while providing a seamless user experience tailored to the specific needs of each faculty role.

## REVIEW OF RELATED WORKS

The development of CapTracks aligns with several existing technologies and localized academic systems aimed at digitizing thesis management. 

One notable example is the standard Thesis Management System developed by various universities, which typically digitizes the submission of final manuscripts and the archiving of approved studies. While these systems provide excellent digital repositories, they often act only at the *end* of the capstone lifecycle, failing to monitor the day-to-day progress, task delegations, and iterative document revisions that occur during the development phase.

Other works have introduced automated scheduling tools for academic defenses. These systems attempt to match student availability with faculty schedules. However, they frequently lack intelligent conflict-resolution algorithms that account for specific academic rules—such as ensuring a group's adviser is never assigned as a grading panelist for their own group, or prioritizing faculty members with lighter workloads to ensure fair distribution of panel duties.

CapTracks stands out by offering a holistic, end-to-end solution. It goes beyond simple document archiving by actively managing the project lifecycle through Coordinator-assigned Milestone Templates. Furthermore, CapTracks introduces a Dynamic JSON-based Grading Rubric, allowing coordinators to alter grading criteria on the fly without requiring database migrations, a feature largely absent in legacy academic systems.

## PROJECT OBJECTIVE

This study aims to develop a comprehensive web-based Capstone Management System that enhances the coordination, tracking, and evaluation of student capstone projects. 

Specifically, it aims to:
1. Implement a secure, Multi-Guard authentication system with distinct portals for Students, Advisers, Coordinators, Chairpersons, and Panelists.
2. Develop a Kanban-style milestone and task tracking system, allowing Coordinators to assign specific requirement blueprints to student groups.
3. Enable automated document version control and threaded, nested feedback to streamline proposal revisions between students and advisers.
4. Integrate an intelligent defense scheduling algorithm that automatically suggests available panelists while preventing time collisions and conflicts of interest.
5. Provide a dynamic, JSON-based digital rating sheet for panelists to seamlessly grade oral defenses in real-time.

## SCOPE AND LIMITATION OF THE STUDY

This study focuses on developing a web application designed specifically for the management of Capstone 1 and Capstone 2 projects within the School of Computer Studies (or relevant IT department). The application aims to support Students, Advisers, Subject Coordinators, Defense Panelists, and the Department Chairperson. Key functionalities include group formation, milestone tracking via Kanban boards, document versioning, automated faculty panel assignment, and digital grading.

The primary limitation of this study is its reliance on a stable internet connection; the platform is entirely web-based and does not feature an offline mode. Furthermore, while the system manages the internal capstone workflow, it is not integrated into the university's main enrollment or tuition system, requiring the Chairperson or Coordinator to manually import student and faculty class lists via CSV files. Finally, all notifications are handled strictly within the application (in-app alerts), meaning the system does not support external SMS or third-party email integrations for deadline reminders.
