# Installation & Deployment Guide
## Project Management System (PMS)

This guide will walk you through deploying your PHP-based Project Management System on Hostinger.

---

## Table of Contents
1. [Prerequisites](#prerequisites)
2. [Hostinger Setup](#hostinger-setup)
3. [File Upload](#file-upload)
4. [Database Setup](#database-setup)
5. [Configuration](#configuration)
6. [Testing](#testing)
7. [Troubleshooting](#troubleshooting)

---

## Prerequisites

Before you begin, ensure you have:
- Active Hostinger hosting account (Basic or higher)
- FTP client (FileZilla recommended) or use Hostinger File Manager
- Your Hostinger login credentials
- Downloaded PMS system files

---

## Hostinger Setup

### Step 1: Access Your Hostinger Account

1. Login to Hostinger at https://www.hostinger.com
2. Navigate to your **hPanel** (Hostinger Control Panel)
3. Select the website/domain where you want to install PMS

### Step 2: Prepare Your Domain

1. If using a subdomain (e.g., pms.yourdomain.com):
   - Go to **Domains** → **Subdomains**
   - Create a new subdomain: `pms`
   
2. Note your domain path (usually `/public_html/` or `/public_html/subdomain/`)

---

## File Upload

### Option A: Using File Manager (Recommended for Beginners)

1. In hPanel, go to **Files** → **File Manager**
2. Navigate to `public_html` (or your subdomain folder)
3. Upload all PMS files:
   - Click **Upload Files**
   - Select all files from the `pms-system` folder
   - Wait for upload to complete
   
4. Verify folder structure looks like this:
   ```
   public_html/
   ├── config/
   ├── classes/
   ├── includes/
   ├── modules/
   ├── assets/
   ├── uploads/
   ├── index.php
   └── database.sql
   ```

### Option B: Using FTP (FileZilla)

1. Open FileZilla
2. Enter connection details:
   - **Host:** Your domain or IP (found in hPanel → FTP Accounts)
   - **Username:** Your FTP username
   - **Password:** Your FTP password
   - **Port:** 21
   
3. Connect and navigate to `/public_html/`
4. Drag and drop all PMS files to the server
5. Wait for transfer to complete

### File Permissions

Set proper permissions (use File Manager or FTP):
- Folders: `755` (rwxr-xr-x)
- Files: `644` (rw-r--r--)
- **uploads** folder: `777` (rwxrwxrwx) - for file uploads

---

## Database Setup

### Step 1: Create MySQL Database

1. In hPanel, go to **Databases** → **MySQL Databases**
2. Click **Create Database**
3. Enter database name: `pms_database` (or your preferred name)
4. Click **Create**
5. **IMPORTANT:** Note down:
   - Database name
   - Username
   - Password
   - Host (usually `localhost`)

### Step 2: Import Database Schema

**Using phpMyAdmin:**
1. In hPanel, go to **Databases** → **phpMyAdmin**
2. Click on your newly created database from the left sidebar
3. Click the **Import** tab
4. Click **Choose File** and select `database.sql`
5. Scroll down and click **Go**
6. Wait for import to complete (you should see "Import successful")

**Using File Manager (Alternative):**
1. In hPanel, go to **MySQL Databases**
2. Find your database and click **Manage**
3. Click **Import** tab
4. Upload and execute the `database.sql` file

### Step 3: Verify Database Tables

1. In phpMyAdmin, click on your database
2. You should see these tables:
   - users
   - projects
   - project_members
   - tasks
   - requirements
   - time_logs
   - expenses
   - comments
   - files
   - activity_logs
   - notifications

---

## Configuration

### Step 1: Update Database Configuration

1. Open `config/database.php` in File Manager
2. Click **Edit** and update these values:

```php
define('DB_HOST', 'localhost');           // Usually 'localhost'
define('DB_NAME', 'your_database_name');  // Your database name from Step 1
define('DB_USER', 'your_username');       // Your database username
define('DB_PASS', 'your_password');       // Your database password
```

**Example:**
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'u123456789_pms');
define('DB_USER', 'u123456789_pmsuser');
define('DB_PASS', 'SecurePassword123!');
```

3. Save the file

### Step 2: Update General Configuration

1. Open `config/config.php`
2. Update these settings:

```php
define('APP_URL', 'https://yourdomain.com'); // Your actual domain
```

**Example:**
```php
define('APP_URL', 'https://pms.yourdomain.com');
```

3. For production, set:
```php
define('DEBUG_MODE', false); // Turn off debugging
```

4. Save the file

### Step 3: Create Uploads Directory

1. Ensure the `uploads/` folder exists in your root directory
2. Set permissions to `777` (writable)
3. Create subdirectories if needed:
   ```
   uploads/
   ├── profiles/
   ├── documents/
   └── attachments/
   ```

---

## Testing

### Step 1: Access Your Application

1. Open your browser
2. Navigate to: `https://yourdomain.com` (or your subdomain)
3. You should be redirected to the login page

### Step 2: Login with Default Credentials

Use these default admin credentials:
- **Email:** admin@admin.com
- **Password:** admin123

### Step 3: Test Basic Functionality

1. **Dashboard:** Check if statistics load correctly
2. **Create Project:** 
   - Go to Projects → Create New
   - Fill in details and save
3. **Create Task:**
   - Go to Tasks → Create New
   - Assign to yourself
4. **User Management:**
   - Go to Users (Admin only)
   - Create a test user

---

## Troubleshooting

### Common Issues and Solutions

#### 1. "500 Internal Server Error"

**Cause:** Incorrect file permissions or PHP errors

**Solution:**
- Check file permissions (files: 644, folders: 755)
- Enable error reporting in `config/config.php`:
  ```php
  define('DEBUG_MODE', true);
  ```
- Check error logs in hPanel → **Error Logs**

---

#### 2. "Database Connection Failed"

**Cause:** Incorrect database credentials

**Solution:**
- Double-check `config/database.php` credentials
- Verify database exists in phpMyAdmin
- Confirm database user has proper privileges

---

#### 3. "Login page loads but can't login"

**Cause:** Database tables not imported or password hash mismatch

**Solution:**
- Re-import `database.sql` file
- Check if `users` table has the admin user
- In phpMyAdmin, run:
  ```sql
  SELECT * FROM users WHERE email = 'admin@admin.com';
  ```

---

#### 4. "Page not found / 404 Error"

**Cause:** Incorrect file paths or .htaccess issues

**Solution:**
- Check if all files uploaded correctly
- Verify you're accessing the correct URL
- Create `.htaccess` file with:
  ```apache
  RewriteEngine On
  RewriteCond %{REQUEST_FILENAME} !-f
  RewriteCond %{REQUEST_FILENAME} !-d
  RewriteRule ^(.*)$ index.php [L]
  ```

---

#### 5. "File Upload Not Working"

**Cause:** Insufficient permissions on uploads folder

**Solution:**
- Set `uploads/` folder permission to 777
- Check PHP upload limits in hPanel → **PHP Configuration**
- Increase `upload_max_filesize` and `post_max_size` if needed

---

#### 6. "Session Issues / Constant Logout"

**Cause:** Session path not writable

**Solution:**
- Check session save path in `php.ini`
- Ensure session directory is writable
- Contact Hostinger support if issue persists

---

## Security Recommendations

### After Installation:

1. **Change Default Admin Password:**
   - Login as admin
   - Go to Profile/Settings
   - Change password immediately

2. **Disable Debug Mode:**
   ```php
   define('DEBUG_MODE', false);
   ```

3. **Update .htaccess for Security:**
   ```apache
   # Prevent directory browsing
   Options -Indexes
   
   # Protect configuration files
   <Files "config.php">
       Order allow,deny
       Deny from all
   </Files>
   ```

4. **Regular Backups:**
   - Use Hostinger's backup feature
   - Download database backups weekly

5. **Enable HTTPS:**
   - In hPanel, go to **SSL** → **Manage SSL**
   - Install free Let's Encrypt SSL certificate
   - Force HTTPS redirect

6. **Update PHP Version:**
   - Use PHP 7.4 or higher
   - Check in hPanel → **PHP Configuration**

---

## Performance Optimization

1. **Enable PHP OPcache:**
   - In hPanel → **PHP Configuration**
   - Enable OPcache extension

2. **Enable Gzip Compression:**
   Add to `.htaccess`:
   ```apache
   <IfModule mod_deflate.c>
       AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css text/javascript application/javascript
   </IfModule>
   ```

3. **Browser Caching:**
   Add to `.htaccess`:
   ```apache
   <IfModule mod_expires.c>
       ExpiresActive On
       ExpiresByType image/jpg "access plus 1 year"
       ExpiresByType image/jpeg "access plus 1 year"
       ExpiresByType image/gif "access plus 1 year"
       ExpiresByType image/png "access plus 1 year"
       ExpiresByType text/css "access plus 1 month"
       ExpiresByType application/javascript "access plus 1 month"
   </IfModule>
   ```

---

## Next Steps

1. Customize the system for your needs
2. Add your company branding (logo, colors)
3. Create user accounts for your team
4. Set up email notifications (optional)
5. Configure regular database backups

---

## Support

For issues specific to:
- **Hostinger:** Contact Hostinger Support
- **PMS System:** Check documentation or create an issue

---

## Maintenance Checklist

**Weekly:**
- [ ] Backup database
- [ ] Check error logs
- [ ] Review user activity

**Monthly:**
- [ ] Update user permissions
- [ ] Clean up old files
- [ ] Review system performance

**Quarterly:**
- [ ] Security audit
- [ ] Update dependencies
- [ ] Performance optimization

---

## Congratulations!

Your Project Management System is now live and ready to use! 🎉

Remember to change default credentials and implement security best practices.
