# Calendar View - Tasks Integration

## Overview
This document describes the changes made to adapt the calendar view to display Tasks instead of Events.

## Changes Made

### Backend Changes

1. **CalendarService.php** - Modified to fetch and format tasks for calendar display
   - Changed to use Task model instead of Calendar model
   - Added `formatTaskForCalendar()` method to format tasks with FullCalendar structure
   - Includes stage information in the task title
   - Added color coding based on stages
   - Loads related data (stage, assignee, supervisor) via eager loading

2. **CalendarController.php** - Updated response messages
   - Changed success messages from "event" to "task"
   - Maintains all CRUD operations through the same endpoints

3. **CalendarRequest.php** - Updated validation
   - Now uses Task model validation rules instead of Calendar model

4. **Task.php Model** - Enhanced with relationships and validation
   - Added TaskValidationRules trait
   - Fixed supervisor relationship to use 'supervisor' column
   - Maintains relationships with Stage, User (assignee), and User (supervisor)

5. **TaskValidationRules.php** - Enhanced validation
   - Added required validation for stage_id
   - Added existence check for stage_id against stages table

6. **TaskFactory.php** - Enhanced for testing
   - Added default values for all task fields
   - Auto-creates related Stage when needed
   - Includes realistic test data

### Frontend Changes

1. **CalendarView.vue** - Updated to use tasks
   - Changed from `eventData` to `taskData`
   - Changed from `createEvent` to `createTask`
   - Changed from `selectedEvent` to `selectedTask`
   - Updated modal component to `task-add-edit-modal-calendar`

2. **TaskAddEditModal.vue** - New modal for task management in calendar
   - Added stage selection field (required)
   - Removed description field (not needed for tasks)
   - Changed supervisor_id to supervisor (matches Task model)
   - Loads stages from API on component mount
   - Properly handles task creation with end_date from calendar selection
   - Properly handles task editing with stage information

3. **DemoComponent.js** - Component registration
   - Registered new `task-add-edit-modal-calendar` component

### Testing

Added comprehensive test suite in `CalendarTaskTest.php`:
- Tests listing tasks for calendar view
- Tests creating tasks via calendar endpoint
- Tests updating tasks via calendar endpoint
- Tests deleting tasks via calendar endpoint
- Tests stage information inclusion
- Tests user relationships inclusion
- Tests validation for required fields

## API Endpoints

All endpoints remain the same, but now handle tasks:
- `GET /calendars` - Lists all tasks formatted for calendar
- `POST /calendars` - Creates a new task
- `GET /calendars/{id}` - Shows a specific task
- `PUT /calendars/{id}` - Updates a task
- `DELETE /calendars/{id}` - Deletes a task

## Data Structure

### Task Model Fields
- `id` - Task identifier
- `title` - Task title
- `owner_name` - Owner/creator name
- `stage_id` - Related stage (required, foreign key)
- `end_date` - Task end/due date
- `supervisor` - Supervisor user ID (foreign key to users)
- `assigned_to` - Assigned user ID (foreign key to users)
- `status` - Task status (pending, completed, overdue)

### Calendar Event Format
Tasks are transformed to FullCalendar format:
```javascript
{
    id: task.id,
    title: "Task Title (Stage Name)",
    start: task.end_date,
    end: task.end_date,
    backgroundColor: "#color",
    borderColor: "#color",
    extendedProps: {
        description: task.title,
        stage_id: task.stage_id,
        stage_name: "Stage Name",
        assigned_to: task.assigned_to,
        supervisor_id: task.supervisor,
        status: task.status,
        end_date: task.end_date,
        owner_name: task.owner_name
    }
}
```

## Relationships

### Task Relationships
- `belongsTo` Stage - Each task belongs to one stage
- `belongsTo` User (assignee) - Each task has one assigned user
- `belongsTo` User (supervisor) - Each task has one supervisor

### Stage Relationship
- `hasMany` Tasks - Each stage can have many tasks

## Color Coding

Tasks are color-coded by stage using a predefined color palette:
- Blue (#007bff)
- Green (#28a745)
- Yellow (#ffc107)
- Red (#dc3545)
- Cyan (#17a2b8)
- Indigo (#6610f2)
- Pink (#e83e8c)
- Orange (#fd7e14)

Colors are assigned based on stage ID modulo 8, ensuring consistent colors for each stage.

## Benefits

1. **Preserves existing structure** - Tasks maintain their relationship with stages
2. **No data loss** - Original Calendar events are preserved (table still exists)
3. **Flexible and scalable** - Easy to add more task attributes or relationships
4. **Maintains good practices** - Uses Laravel conventions, validation, and relationships
5. **Backward compatible** - The `/calendars` endpoint remains the same

## Future Enhancements

Possible future improvements:
1. Filter tasks by stage in calendar view
2. Drag-and-drop to change task dates
3. Multi-stage task support
4. Task priority indicators
5. Recurring tasks support
