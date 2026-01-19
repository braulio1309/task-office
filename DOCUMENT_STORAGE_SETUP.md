# Document Storage Setup

## Overview
This application uses Laravel's storage system to manage document uploads. Documents are stored in `storage/app/public/documents/` and accessed through a symbolic link.

## Initial Setup

### 1. Create Storage Symlink
The application requires a symbolic link from `public/storage` to `storage/app/public` to access uploaded documents.

**Option A: Using Laravel Artisan (Recommended)**
```bash
php artisan storage:link
```

**Option B: Manual Symlink Creation**
```bash
ln -sf "$PWD/storage/app/public" "$PWD/public/storage"
```

**Option C: Using the Application**
Navigate to: `/symlink` in your browser (requires authentication)

### 2. Verify Directory Structure
Ensure the following directories exist with proper permissions:
```bash
mkdir -p storage/app/public/documents
chmod -R 775 storage
```

### 3. Verify Symlink
Check that the symlink was created successfully:
```bash
ls -la public/storage
```
You should see: `public/storage -> /path/to/storage/app/public`

## Document Preview Functionality

### Supported File Types
The application can preview the following file types in-browser:

- **Images**: JPG, PNG, GIF, SVG, BMP, WebP
- **PDFs**: Displayed using browser's native PDF viewer
- **Other Files**: Download-only (Word, Excel, ZIP, etc.)

### Features
1. **Upload**: Upload documents to any folder
2. **Preview**: Click the eye icon to preview supported file types
3. **Download**: Click the download icon to download any file
4. **Rename**: Edit document names
5. **Delete**: Remove documents (also deletes the physical file)
6. **Organize**: Create folders to organize documents

## Storage Configuration

The storage configuration is defined in `config/filesystems.php`:

```php
'public' => [
    'driver' => 'local',
    'root' => storage_path('app/public'),
    'url' => env('APP_URL') . '/storage',
    'visibility' => 'public',
],
```

Make sure `APP_URL` is properly set in your `.env` file:
```
APP_URL=http://your-domain.com
```

## Troubleshooting

### "Not Found" Errors
If documents show "notfound" errors:
1. Verify the storage symlink exists: `ls -la public/storage`
2. Check directory permissions: `chmod -R 775 storage`
3. Verify `APP_URL` in `.env` matches your domain
4. Clear application cache: `php artisan config:clear`

### Symlink Not Working on Shared Hosting
Some shared hosting providers don't allow symlinks. Solutions:
1. Contact your hosting provider to enable symlinks
2. Move files to `public/documents` and update the storage configuration
3. Use a cloud storage service (S3, DigitalOcean Spaces)

### File Upload Issues
If file uploads fail:
1. Check PHP upload limits in `php.ini`:
   - `upload_max_filesize = 10M`
   - `post_max_size = 10M`
2. Check Laravel upload limits in `DocumentController.php`
3. Verify directory permissions: `chmod -R 775 storage`

## Security Considerations

1. **File Validation**: Files are validated for size and type before upload
2. **Access Control**: Implement permission checks in your routes
3. **Secure Storage**: Sensitive files should be stored in `storage/app` (not `storage/app/public`)
4. **Virus Scanning**: Consider implementing virus scanning for uploaded files

## API Endpoints

### Document Management
- `GET /documents/list?folder_id={id}` - List documents in a folder
- `POST /documents/upload` - Upload a new document
- `GET /documents/view/{id}` - View/preview a document
- `POST /documents/file/{id}/rename` - Rename a document
- `DELETE /documents/file/{id}` - Delete a document

### Folder Management
- `POST /documents/folder` - Create a new folder
- `DELETE /documents/folder/{id}` - Delete a folder and its contents

## Maintenance

### Clean Up Old Files
Periodically clean up deleted documents that might still exist in storage:
```bash
# Remove documents that are not in the database
php artisan app:cleanup-storage
```

### Backup Documents
Regularly backup the `storage/app/public/documents` directory:
```bash
tar -czf documents-backup-$(date +%Y%m%d).tar.gz storage/app/public/documents/
```

## Cloud Storage (Optional)

To use Amazon S3 or similar services:

1. Update `.env`:
```
FILESYSTEM_DRIVER=s3
AWS_ACCESS_KEY_ID=your-key
AWS_SECRET_ACCESS_KEY=your-secret
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=your-bucket
```

2. Update `config/filesystems.php` to use 's3' as default disk

3. Documents will automatically be stored in the cloud
