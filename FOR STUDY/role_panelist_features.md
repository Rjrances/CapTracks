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

---

## 9. Methods Used (Simple Terms)

- `pluck('column')` - Gets only one column from query results (like IDs) instead of full rows.
- `whereIn('column', [...])` - Filters records that match any value in a list.
- `whereNotIn('column', [...])` - Excludes records that match values in a list.
- `withCount('relation')` - Adds relation counts without manual loops.
- `whereHas('relation', fn...)` - Filters by conditions inside related tables.
- `first()` - Gets the first matching row or `null`.
- `findOrFail(id)` - Finds by ID or throws a not found error.
- `create([...])` - Inserts a new database row.
- `update([...])` - Updates fields of existing rows.
- `delete()` - Removes a row.
- `exists()` - Returns true/false if any matching row exists.
- `collect([...])` - Creates a Laravel collection for chainable operations.
- `map(fn...)` - Transforms each item in a collection.
- `sortBy(...)` - Sorts collection items by one or more rules.
- `take(n)` - Gets only the first `n` items.
- `values()` - Reindexes collection keys to clean 0..n numbering.
- `unique('field')` - Removes duplicates by field.
- `toArray()` - Converts data to plain PHP array.
- `return back()->withErrors(...)->withInput()` - Returns user to form with errors and keeps previous input.
- `DB::beginTransaction()/commit()/rollback()` - All-or-nothing database save flow.
- `Carbon::parse(...)` - Converts date/time text into a date object.
- `response()->json([...])` - Returns JSON for frontend scripts.

### Symbols / Operators (Q&A quick guide)
- `?` (ternary) - Short if/else in one line.
- `??` (null coalescing) - Use fallback value when left side is `null`.
- `?:` (elvis shorthand) - Use left side if truthy, otherwise fallback.
- `?->` (null-safe operator) - Access property/method only if object is not `null`.
- `=>` - Key/value separator in arrays, and short function arrow syntax.
- `===` - Strict comparison (value and type must match).

## 10. Quick Oral Cheat Sheet (Top 10 Terms)

1. **`pluck`** - "Get only one column, like IDs, from many rows."
2. **`whereIn`** - "Filter rows that match any value in a list."
3. **`withCount`** - "Add relationship counts directly from DB, no manual loops."
4. **`whereHas`** - "Filter by a condition inside a related table."
5. **`create`** - "Insert a new database row quickly."
6. **`update`** - "Modify existing row values."
7. **`exists`** - "Fast yes/no check if a matching record exists."
8. **`sortBy`** - "Order results by a rule, like least workload first."
9. **`take(2)`** - "Get only the first two ranked candidates."
10. **`DB transaction`** - "All-or-nothing save: commit if all pass, rollback if any fail."
