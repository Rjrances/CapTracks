# Milestones Kanban Feature

## Overview

The Milestones Kanban feature provides students with a modern, interactive way to manage their capstone project tasks using a three-column Kanban board with drag-and-drop functionality.

## Features Implemented

### 1. Database Changes
- **Migration**: Added `status` enum field to `group_milestone_tasks` table
  - Values: `pending`, `doing`, `done`
  - Maintains backward compatibility with `is_completed` field
  - Default value: `pending`

### 2. Model Updates
- **GroupMilestoneTask Model**:
  - Added `status` to fillable fields
  - New methods: `updateStatus()`, `getStatusBadgeClassAttribute()`
  - Query scopes: `pending()`, `doing()`, `done()`
  - Enhanced status text and badge class attributes

- **GroupMilestone Model**:
  - Updated `calculateProgressPercentage()` to use `status` field
  - Progress calculation based on tasks with `done` status

### 3. Controller Enhancements
- **StudentMilestoneController**:
  - `moveTask()`: Move tasks between status columns via AJAX
  - `bulkUpdateTasks()`: Update multiple tasks at once
  - `recomputeProgress()`: Recalculate milestone progress
  - `getMilestoneTasksByStatus()`: Group tasks by status for Kanban display
  - Enhanced existing methods to work with new status field

### 4. Routes
```php
// Kanban functionality routes
Route::patch('/milestones/tasks/{taskId}/move', 'moveTask')->name('milestones.move-task');
Route::patch('/milestones/{milestoneId}/bulk-update', 'bulkUpdateTasks')->name('milestones.bulk-update');
Route::post('/milestones/{milestoneId}/recompute-progress', 'recomputeProgress')->name('milestones.recompute-progress');
```

### 5. UI Components

#### Kanban Board (`resources/views/student/milestones/show.blade.php`)
- **Three-column layout**: Pending, In Progress, Completed
- **Drag & Drop**: Using SortableJS library
- **Real-time updates**: AJAX calls for status changes
- **Progress bars**: Per milestone and overall project
- **Responsive design**: Works on desktop and mobile

#### Task Cards (`resources/views/student/milestones/partials/task-card.blade.php`)
- **Modern design**: Clean, card-based layout
- **Status indicators**: Color-coded badges
- **Quick actions**: Status change buttons
- **Task information**: Assignment, deadlines, notes
- **Drag handles**: Visual feedback for drag operations

#### Enhanced Index Page (`resources/views/student/milestones/index.blade.php`)
- **Task summary**: Breakdown by status
- **Kanban preview**: Visual overview of task distribution
- **Progress tracking**: Enhanced progress bars
- **Modern UI**: Updated styling and layout

### 6. JavaScript Functionality
- **SortableJS integration**: Smooth drag-and-drop
- **AJAX calls**: Real-time status updates
- **Progress updates**: Automatic progress bar updates
- **Error handling**: User-friendly error messages
- **Visual feedback**: Loading states and animations

## Usage

### For Students

1. **Access Kanban Board**:
   - Navigate to "My Milestones" from student dashboard
   - Click "Kanban Board" button on any milestone

2. **Move Tasks**:
   - Drag and drop tasks between columns
   - Use quick action buttons on task cards
   - Status changes are saved automatically

3. **Track Progress**:
   - View progress bars for each milestone
   - See overall project completion
   - Monitor task assignments and deadlines

### For Group Leaders

1. **Assign Tasks**:
   - Click assign button on unassigned tasks
   - Select group member from dropdown
   - Tasks can be reassigned as needed

2. **Manage Workflow**:
   - Monitor task distribution across columns
   - Track individual member progress
   - Recompute progress when needed

## Technical Implementation

### Database Schema
```sql
ALTER TABLE group_milestone_tasks 
ADD COLUMN status ENUM('pending', 'doing', 'done') DEFAULT 'pending' AFTER is_completed;
```

### Status Flow
```
Pending → Doing → Done
   ↑         ↓
   └─────────┘
```

### Progress Calculation
- **Milestone Progress**: `(done_tasks / total_tasks) * 100`
- **Overall Progress**: Average of all milestone progress percentages

### AJAX Endpoints
- `PATCH /student/milestones/tasks/{id}/move`: Move task to new status
- `PATCH /student/milestones/{id}/bulk-update`: Update multiple tasks
- `POST /student/milestones/{id}/recompute-progress`: Recalculate progress

## Dependencies

### Frontend
- **SortableJS**: Drag-and-drop functionality
- **Bootstrap 5**: UI framework
- **Font Awesome**: Icons
- **jQuery**: AJAX requests (optional)

### Backend
- **Laravel 10**: PHP framework
- **MySQL/PostgreSQL**: Database
- **Eloquent ORM**: Database interactions

## Installation & Setup

1. **Run Migration**:
   ```bash
   php artisan migrate
   ```

2. **Seed Sample Data** (optional):
   ```bash
   php artisan db:seed --class=GroupMilestoneTaskStatusSeeder
   ```

3. **Clear Cache**:
   ```bash
   php artisan cache:clear
   php artisan view:clear
   ```

## Browser Compatibility

- **Modern Browsers**: Chrome, Firefox, Safari, Edge
- **Mobile Support**: Responsive design for tablets and phones
- **JavaScript Required**: Drag-and-drop functionality requires JavaScript

## Performance Considerations

- **Lazy Loading**: Tasks loaded on demand
- **Efficient Queries**: Optimized database queries with eager loading
- **Caching**: Progress calculations cached where appropriate
- **AJAX**: Minimal page reloads for better UX

## Future Enhancements

1. **Real-time Updates**: WebSocket integration for live collaboration
2. **Task Dependencies**: Visual dependency mapping
3. **Time Tracking**: Built-in time tracking for tasks
4. **Notifications**: Real-time notifications for task changes
5. **Export/Import**: Kanban board export functionality
6. **Custom Columns**: User-defined status columns
7. **Task Templates**: Reusable task templates
8. **Advanced Filtering**: Filter tasks by assignee, deadline, etc.

## Troubleshooting

### Common Issues

1. **Drag and Drop Not Working**:
   - Check if SortableJS is loaded
   - Verify JavaScript console for errors
   - Ensure CSRF token is present

2. **Status Not Updating**:
   - Check network tab for AJAX errors
   - Verify route permissions
   - Check database constraints

3. **Progress Not Calculating**:
   - Run `recomputeProgress()` manually
   - Check task status values in database
   - Verify milestone-task relationships

### Debug Commands
```bash
# Check task statuses
php artisan tinker
>>> App\Models\GroupMilestoneTask::select('id', 'status', 'is_completed')->get()

# Recalculate all progress
php artisan tinker
>>> App\Models\GroupMilestone::all()->each(fn($m) => $m->calculateProgressPercentage())
```

## Contributing

When contributing to the Kanban feature:

1. **Follow Laravel conventions** for naming and structure
2. **Test thoroughly** with different task scenarios
3. **Update documentation** for any new features
4. **Consider backward compatibility** with existing data
5. **Optimize for performance** with large task sets

## License

This feature is part of the CapTracks project and follows the same licensing terms.
