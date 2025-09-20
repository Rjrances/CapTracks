# CapTrack Import Templates

This directory contains CSV templates for importing data into the CapTrack system.

## Available Templates

### 1. Student Import Template (`student_import_template_final.csv`)

**Purpose:** Import students with their enrollment information for all academic terms.

**Required Fields:**
- `student_id` (string, 10 digits): Unique student identifier (e.g., "2024000001")
- `name` (string): Full student name
- `email` (string): Student email address (must be unique)
- `semester` (string): Academic semester format (e.g., "2024-1", "2024-2", "2024-S")
- `course` (string): Course program (e.g., "BS Computer Science", "BS Information Technology", "BS Entertainment and Multimedia Computing")
- `offer_code` (string): Code of the offering to enroll in (e.g., "11000", "11001", "11002", "11003", "11004")

**Sample Data:**
- 28 students across 3 terms (First Semester: 10, Second Semester: 10, Summer: 8)
- Students are distributed across different offerings with mixed courses
- All three courses represented: BS Computer Science, BS Information Technology, BS Entertainment and Multimedia Computing
- All students have valid offer codes that correspond to existing offerings

### 2. Faculty Import Template (`faculty_import_template_updated.csv`)

**Purpose:** Import faculty members with their roles and department information.

**Required Fields:**
- `faculty_id` (string, 5 digits): Unique faculty identifier (e.g., "10001")
- `name` (string): Full faculty name with title
- `email` (string): Faculty email address
- `role` (string): Primary role (chairperson, coordinator, teacher, adviser, panelist)
- `department` (string): Department name (e.g., "Computer Science")

**Sample Data:**
- 12 faculty members with diverse roles
- Professional names with appropriate titles
- All faculty have unique 5-digit IDs

### 3. Offering Import Template (`offering_import_template.csv`)

**Purpose:** Import course offerings for all academic terms.

**Required Fields:**
- `offer_code` (string): Unique offering code (e.g., "11000")
- `subject_title` (string): Full subject title (e.g., "Capstone Project I")
- `subject_code` (string): Subject code (e.g., "CS-CAP-401")
- `faculty_id` (string): Faculty member teaching the course
- `academic_term` (integer): Term ID (1=First Semester, 2=Second Semester, 3=Summer)

**Sample Data:**
- 14 offerings across 3 terms
- First Semester: 5 offerings (11000-11004)
- Second Semester: 5 offerings (12000-12004)
- Summer: 4 offerings (13000-13003)

## Import Order

**Important:** Import data in the following order to maintain referential integrity:

1. **Academic Terms** (via seeder or manual creation)
2. **Faculty Members** (`faculty_import_template_updated.csv`)
3. **Course Offerings** (`offering_import_template.csv`)
4. **Students** (`student_import_template_final.csv`)

## Usage Instructions

### For Students:
1. Download `student_import_template_final.csv`
2. Modify the data as needed (keep the header row)
3. Ensure all offer codes exist in the offerings table
4. Use the import functionality in the Chairperson section

### For Faculty:
1. Download `faculty_import_template_updated.csv`
2. Modify the data as needed (keep the header row)
3. Ensure faculty IDs are unique and 5 digits
4. Use the import functionality in the Chairperson section

### For Offerings:
1. Download `offering_import_template.csv`
2. Modify the data as needed (keep the header row)
3. Ensure faculty IDs exist in the users table
4. Ensure academic term IDs are correct

## Data Validation

### Student Data:
- Student IDs must be exactly 10 digits (e.g., "2024000001")
- Emails must be unique and valid format
- Offer codes must exist in offerings table
- Semester format: YYYY-S (e.g., "2024-1", "2024-2", "2024-S")
- Course must be one of: "BS Computer Science", "BS Information Technology", "BS Entertainment and Multimedia Computing"
- Students are automatically enrolled in their specified offering based on offer_code

### Faculty Data:
- Faculty IDs must be 5 digits
- Emails must be unique
- Roles must be valid: chairperson, coordinator, teacher, adviser, panelist

### Offering Data:
- Offer codes must be unique
- Faculty IDs must exist
- Academic term IDs must exist (1, 2, or 3)

## Notes

- All templates include sample data that matches the current seeder structure
- Students are automatically enrolled in their specified offerings upon import
- Faculty accounts are created automatically with default password "password"
- Student accounts are created automatically with default password "password"
- All imported users will need to change their passwords on first login

## Support

If you encounter issues with the import process:
1. Check that all required fields are filled
2. Verify that foreign key references exist
3. Ensure data formats match the requirements
4. Check the system logs for detailed error messages
