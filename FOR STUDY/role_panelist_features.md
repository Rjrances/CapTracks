# Panelist Features (For Study)

This document outlines the core features and responsibilities assigned to the **Panelist** role within the CapTrack system, serving as a study guide for understanding this specific administrative persona.

---

## 1. Role Overview
The **Panelist** acts as an evaluator during student group project defenses. They are primarily responsible for reviewing defense documents and grading groups based on institutional rubrics.

## 2. Dashboard Interface
The Panelist dashboard provides a focused view of upcoming defense schedules they are assigned to, distinct from the regular faculty (adviser) dashboard.

**Key Components:**
*   **Upcoming Defenses Widget:** Displays schedules assigned to the panelist.
*   **Quick Actions:** Direct access to grading rubrics for active defenses.
*   **Recent Activity:** Highlights completed evaluations and updates.

## 3. Defense Schedule Management
Panelists can review the defense schedules assigned to them by the Coordinator.

**Features:**
*   **View Defense Details:** Panelists can see the group name, project title, defense date, time, and venue.
*   **View Project Documents:** They can download and review the most recent project submissions (e.g., Proposal, Final Document) before the defense begins.

## 4. Grading System
The most critical function of a Panelist is to grade the group during or after the defense.

**Features:**
*   **Dynamic JSON Rubrics:** Traditional paper grading sheets are replaced with digital forms.
*   **Criteria Scoring:** Panelists input scores for various criteria (e.g., Presentation, Technical Depth, Documentation).
*   **Auto-Calculation:** The system automatically calculates the total weighted score based on the inputted sub-scores.
*   **Secure Storage:** The entire evaluation, including individual criteria scores, is saved securely in the database as a JSON object, ensuring flexibility for future rubric changes.

## 5. Notifications
Panelists receive system notifications to stay updated on their assignments.

**Features:**
*   **New Panel Assignment:** Alert when the coordinator assigns them to a new defense panel.
*   **Schedule Updates:** Notifications if the date, time, or venue of an assigned defense changes.
*   **Document Uploads:** Alerts when the assigned group uploads a new revision of their defense document.

---

## Technical Context
*   **Controller:** `AdviserController.php` (for dashboard & invitations), `RatingSheetController.php` (for grading)
*   **Middleware:** `auth` (Shared with other faculty roles)
*   **Key Models:** `User`, `DefenseSchedule`, `DefensePanel`, `RatingSheet`
*   **Views:** `resources/views/adviser/` directory (Adviser dashboard doubles as Panelist dashboard)
