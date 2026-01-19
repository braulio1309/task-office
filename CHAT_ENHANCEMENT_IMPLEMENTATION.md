# Chat System Enhancement Implementation

## Overview
This document describes the enhancements made to the Laravel-Vue-MySQL task-office chat system to address file visualization issues and add a notification system.

## Changes Made

### 1. File Visualization Enhancement

#### Problem
Uploaded files in the chat appeared as folder paths (e.g., "chat/69503b31b9314.png") without showing the original filename. Files inside the folder had random generated names like "wrYaVraBceG7BgBaKZEhZkHC5TOYKkk5TF3J0YAH.png".

#### Solution
- **Database Migration**: Added `original_filename` column to `attachments` table to store the original file name
- **Model Update**: Updated `Attachment` model to include `original_filename` in fillable fields
- **Controller Update**: Modified `MessageController::store()` to capture and save the original filename when uploading files:
  ```php
  $file->getClientOriginalName()
  ```
- **Vue Component Update**: Updated chat component (`Index.vue`) to display the original filename below the image thumbnail with a paperclip icon

#### Files Modified
- `database/migrations/2026_01_19_210800_add_original_filename_to_attachments_table.php` (new)
- `app/Models/App/Chat/Attachment.php`
- `app/Http/Controllers/App/Chat/MessageController.php`
- `resources/js/app/Components/Views/Demo/Pages/chat/Index.vue`

### 2. Notification System Implementation

#### Problem
No visual indicator when new chat messages arrive. Users had to manually check the chat to see if there were new messages.

#### Solution

##### A. Backend Changes

1. **Database Migration**: Added `is_read` column to `messages` table to track read/unread status
   - Default value: `false`
   - Updated to `true` when user opens the conversation

2. **Model Update**: Updated `Message` model to include `is_read` in fillable fields

3. **Controller Methods Added**:
   - `markAsRead($id)`: Marks all messages from a specific sender as read
   - `unreadCount()`: Returns total unread count and breakdown by sender/group
   
4. **New Routes**:
   ```php
   POST messages/{id}/mark-as-read
   GET messages-unread-count
   ```

##### B. Frontend Changes

1. **Chat Notification Bell Component** (`ChatNotificationDropdown.vue`):
   - Displays a bell icon with message-square icon
   - Shows red badge with unread count when messages are unread
   - Polls for unread messages every 30 seconds
   - Listens to real-time updates via Echo/broadcasting
   - Integrated into the main navbar next to the regular notification bell

2. **Chat Component Updates** (`Index.vue`):
   - Added `unreadCounts` object to track unread messages per contact
   - Added red badge pills next to contacts with unread messages
   - Automatically marks messages as read when conversation is opened
   - Refreshes unread counts when new messages arrive via Echo
   - Emits `chat-unread-count` event to update navbar notification

3. **Navbar Integration**:
   - Added `<app-navbar-chat-notification-dropdown />` component to navbar
   - Positioned between regular notifications and profile dropdown

4. **Language Translations**:
   - Added new translation keys in `resources/lang/en/default.php`:
     - `chat_messages`: "Chat Messages"
     - `no_new_messages`: "No new messages"
     - `you_have_unread_messages`: "You have :count unread message(s)"

#### Files Modified
- `database/migrations/2026_01_19_210900_add_is_read_to_messages_table.php` (new)
- `app/Models/App/Chat/Message.php`
- `app/Http/Controllers/App/Chat/MessageController.php`
- `routes/app/chat.php`
- `resources/js/app/Components/Views/Demo/Pages/chat/Index.vue`
- `resources/js/core/components/layouts/navbar-dropdowns/ChatNotificationDropdown.vue` (new)
- `resources/js/core/coreApp.js`
- `resources/js/core/components/layouts/Navbar.vue`
- `resources/lang/en/default.php`

## How It Works

### File Upload Flow
1. User selects a file to upload
2. File is uploaded to storage with generated name (for security)
3. Original filename is captured via `getClientOriginalName()`
4. Both generated path and original filename are stored in database
5. Chat component displays the image thumbnail with original filename below it

### Notification Flow
1. User receives a new message (via ChatEvent broadcast)
2. Message is stored with `is_read = false`
3. Notification bell shows red badge with count
4. Unread badge appears next to the sender in contact list
5. When user opens the conversation:
   - Messages are marked as read via API call
   - Unread count is refreshed
   - Badge is removed from contact and notification bell

### Real-time Updates
- Uses Laravel Echo for real-time communication
- Listens to `ChatEvent` on private channel `chat.{user.id}`
- Updates are instant when messages arrive
- Fallback polling every 30 seconds for unread counts

## Database Schema Changes

### Attachments Table
```sql
ALTER TABLE attachments ADD COLUMN original_filename VARCHAR(255) NULL AFTER path;
```

### Messages Table
```sql
ALTER TABLE messages ADD COLUMN is_read BOOLEAN DEFAULT FALSE AFTER message;
```

## Migration Instructions

1. Run migrations:
   ```bash
   php artisan migrate
   ```

2. Build frontend assets:
   ```bash
   npm run dev
   # or for production
   npm run production
   ```

3. Ensure broadcasting is properly configured:
   - Update `.env` with appropriate `BROADCAST_DRIVER` (pusher, redis, etc.)
   - Configure Echo in `resources/js/bootstrap.js`
   - Ensure Laravel Echo Server or Pusher is running

## Testing Recommendations

### Manual Testing Checklist

#### File Visualization
- [ ] Upload various file types (images, documents)
- [ ] Verify original filename displays below thumbnail
- [ ] Check that paperclip icon appears
- [ ] Verify files are still accessible via the generated path

#### Notifications
- [ ] Send message from User A to User B
- [ ] Verify User B sees notification bell with count
- [ ] Verify User B sees red badge next to User A in contact list
- [ ] Open conversation and verify badge disappears
- [ ] Verify notification count updates in real-time
- [ ] Test with multiple conversations
- [ ] Test with group chats

### API Endpoints Testing
```bash
# Get unread count
GET /messages-unread-count

# Mark conversation as read
POST /messages/{senderId}/mark-as-read
```

## Security Considerations

1. **File Upload**: Files are stored with generated names to prevent directory traversal attacks
2. **Authorization**: Ensure only authenticated users can access their own messages
3. **XSS Prevention**: Original filenames are displayed using Vue's text interpolation (safe by default)
4. **SQL Injection**: Using Eloquent ORM prevents SQL injection

## Future Enhancements

1. **Read Receipts**: Show when message was actually read with timestamp
2. **Typing Indicators**: Show when other user is typing
3. **Message Deletion**: Allow users to delete messages
4. **File Preview**: Add preview modal for uploaded files
5. **Push Notifications**: Add browser push notifications for offline users
6. **Message Search**: Add ability to search through chat history
7. **Delivery Status**: Show message delivery status (sent, delivered, read)

## Browser Compatibility

- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+

## Dependencies

- Laravel 8.x+
- Vue.js 2.x
- Laravel Echo
- Pusher or Laravel Reverb (for real-time broadcasting)
- Axios (for HTTP requests)

## Support

For issues or questions, please contact the development team or create an issue in the repository.
