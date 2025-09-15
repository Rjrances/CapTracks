# CapTracks Database Setup Guide

This guide will help your teammate set up the CapTracks database in MySQL Workbench to match your current database structure.

## Prerequisites

- MySQL Server 8.0+ or MariaDB 10.3+
- MySQL Workbench installed
- Access to create databases on the MySQL server

## Quick Setup Instructions

### Method 1: Using MySQL Workbench (Recommended)

1. **Open MySQL Workbench** and connect to your MySQL server

2. **Open the SQL Script**:
   - Go to `File` → `Open SQL Script`
   - Navigate to the `captracks_database.sql` file
   - Click `Open`

3. **Execute the Script**:
   - Click the `Execute` button (⚡) or press `Ctrl+Shift+Enter`
   - Wait for the script to complete (should take 10-30 seconds)

4. **Verify Setup**:
   - In the Navigator panel, refresh the database list
   - You should see `captracks` database with all tables
   - Check that sample data was inserted correctly

### Method 2: Using Command Line

```bash
# Connect to MySQL
mysql -u your_username -p

# Execute the SQL script
source /path/to/captracks_database.sql

# Or alternatively:
mysql -u your_username -p < captracks_database.sql
```

## Database Structure Overview

The CapTracks database includes the following main tables:

### Core Tables
- **`users`** - Faculty/Staff members (chairperson, coordinator, adviser, panelist)
- **`students`** - Student information and authentication
- **`academic_terms`** - School year and semester management
- **`offerings`** - Course offerings and subjects
- **`groups`** - Student project groups

### Project Management Tables
- **`group_members`** - Student group membership
- **`milestone_templates`** - Reusable milestone templates
- **`milestone_tasks`** - Individual tasks within milestones
- **`group_milestones`** - Group-specific milestone instances
- **`group_milestone_tasks`** - Task assignments and progress tracking

### Defense System Tables
- **`defense_requests`** - Student defense requests
- **`defense_schedules`** - Scheduled defense sessions
- **`defense_panels`** - Faculty panel assignments

### Supporting Tables
- **`project_submissions`** - File submissions from students
- **`notifications`** - System notifications
- **`enrollments`** - Student semester enrollments
- **`offering_student`** - Course enrollment relationships
- **`user_roles`** - User role assignments

### Laravel Framework Tables
- **`sessions`** - User session management
- **`password_reset_tokens`** - Password reset functionality
- **`jobs`**, **`job_batches`**, **`failed_jobs`** - Queue system
- **`cache`**, **`cache_locks`** - Caching system

## Sample Data Included

The script includes sample data for testing:

- **4 Faculty Users**: Chairperson, Coordinator, Adviser, Panelist
- **4 Students**: Sample capstone project students
- **3 Academic Terms**: Current and previous semesters
- **3 Course Offerings**: Capstone Project courses
- **3 Student Groups**: Team Alpha, Beta, Gamma
- **Sample Milestones**: Complete milestone templates with tasks
- **Test Notifications**: System notifications for different roles
- **Sample Defense Data**: Defense requests and schedules

## Default Login Credentials

### Faculty/Staff Users
- **Chairperson**: `FAC001` / `password`
- **Coordinator**: `FAC002` / `password`
- **Adviser**: `FAC003` / `password`
- **Panelist**: `FAC004` / `password`

### Students
- **Student 1**: `2021-12345` / `password`
- **Student 2**: `2021-12346` / `password`
- **Student 3**: `2021-12347` / `password`
- **Student 4**: `2021-12348` / `password`

> **Note**: All passwords are hashed using Laravel's default hashing. In production, these should be changed.

## Troubleshooting

### Common Issues

1. **Permission Denied Error**:
   ```sql
   -- Grant necessary permissions
   GRANT ALL PRIVILEGES ON captracks.* TO 'your_username'@'localhost';
   FLUSH PRIVILEGES;
   ```

2. **Foreign Key Constraint Errors**:
   - Make sure to run the script in the correct order
   - Check that all referenced tables exist before creating foreign keys

3. **Character Set Issues**:
   ```sql
   -- Set proper character set
   ALTER DATABASE captracks CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

4. **Table Already Exists**:
   - The script includes `DROP TABLE IF EXISTS` statements
   - If you get errors, manually drop the database first:
   ```sql
   DROP DATABASE IF EXISTS captracks;
   ```

### Verification Queries

Run these queries to verify the setup:

```sql
-- Check all tables exist
SHOW TABLES;

-- Check sample data
SELECT COUNT(*) as user_count FROM users;
SELECT COUNT(*) as student_count FROM students;
SELECT COUNT(*) as group_count FROM groups;

-- Check foreign key relationships
SELECT 
    TABLE_NAME,
    COLUMN_NAME,
    CONSTRAINT_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE REFERENCED_TABLE_SCHEMA = 'captracks';
```

## Database Configuration for Laravel

Update your `.env` file:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=captracks
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

## Additional Notes

- The database uses UTF8MB4 character set for full Unicode support
- All timestamps use Laravel's standard format
- Foreign key constraints ensure data integrity
- Indexes are created for optimal query performance
- The schema supports both MySQL and MariaDB

## Support

If you encounter any issues during setup:

1. Check the MySQL error logs
2. Verify your MySQL version compatibility
3. Ensure proper user permissions
4. Check for conflicting database names

For additional help, refer to the Laravel migration files in the `database/migrations/` directory for the original schema definitions.
