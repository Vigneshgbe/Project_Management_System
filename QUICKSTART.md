# Quick Start Guide
## Get Your PMS System Running in 15 Minutes

Follow these steps to deploy your Project Management System on Hostinger.

---

## ⚡ Quick Setup (5 Steps)

### Step 1: Create Database (2 minutes)
1. Login to Hostinger hPanel
2. Go to **Databases** → **MySQL Databases**
3. Click **Create Database**
4. Note: Database name, Username, Password
5. Click **Manage** → **phpMyAdmin**

### Step 2: Import Database (2 minutes)
1. In phpMyAdmin, select your database
2. Click **Import** tab
3. Choose `database.sql` file
4. Click **Go**
5. ✅ Wait for "Import successful" message

### Step 3: Upload Files (5 minutes)
1. Go to **Files** → **File Manager**
2. Navigate to `public_html`
3. Upload all PMS files
4. Extract if uploaded as ZIP

### Step 4: Configure Database (2 minutes)
1. Open `config/database.php`
2. Update these 4 lines:
   ```php
   define('DB_NAME', 'your_database_name');
   define('DB_USER', 'your_username');
   define('DB_PASS', 'your_password');
   define('DB_HOST', 'localhost');
   ```
3. Save file

### Step 5: Access & Login (1 minute)
1. Visit: `https://yourdomain.com`
2. Login with:
   - **Email:** admin@admin.com
   - **Password:** admin123
3. ✅ You're in! Change password immediately

---

## 🎯 First Steps After Login

### 1. Change Admin Password
- Click your name (top right)
- Go to **Profile** → **Change Password**
- Set a strong password

### 2. Add Your Team
- Go to **Users** → **Create New**
- Fill in details:
  - Full Name
  - Email
  - Role (Manager/Team Member/Client)
  - Password
- Click **Save**

### 3. Create Your First Project
- Go to **Projects** → **Create New**
- Enter:
  - Project Name
  - Project Code
  - Start & End Dates
  - Budget (optional)
  - Assign Manager
- Click **Create**

### 4. Add Team Members to Project
- Open the project
- Click **Team** tab
- Click **Add Member**
- Select user and role
- Click **Add**

### 5. Create Tasks
- Go to **Tasks** → **Create New**
- Select project
- Enter task details
- Assign to team member
- Set priority and due date
- Click **Create**

---

## 📁 File Structure Overview

```
your-website/
├── config/           → Configuration files (UPDATE THESE!)
├── classes/          → Core logic (don't modify unless customizing)
├── includes/         → Reusable components
├── modules/          → Features (projects, tasks, users, etc.)
├── assets/           → CSS, JS, images
├── uploads/          → User uploaded files
└── index.php         → Entry point
```

---

## 🔒 Security Checklist

After installation:
- [ ] Changed admin password
- [ ] Updated `config/config.php` with your domain
- [ ] Set `DEBUG_MODE` to `false` in production
- [ ] Deleted or moved `database.sql` file
- [ ] Enabled HTTPS (in Hostinger SSL settings)
- [ ] Set proper file permissions

---

## 🛠️ Common Tasks

### Add New User
1. Users → Create New
2. Fill form → Save

### Create Project
1. Projects → Create New
2. Fill details → Create
3. Add team members

### Assign Task
1. Tasks → Create New
2. Select project
3. Assign to user
4. Set deadline

### Track Time
1. Open task
2. Click "Log Time"
3. Enter hours and description
4. Save

### View Reports
1. Go to Reports
2. Select report type
3. Apply filters
4. View or export

---

## 💡 Tips & Tricks

**Keyboard Shortcuts:**
- `Ctrl + F` - Search on current page
- `Esc` - Close modals

**Filtering:**
- Use search boxes to filter results
- Click column headers to sort
- Use dropdown filters for status/priority

**Mobile Access:**
- Fully responsive
- Use mobile browser
- Same login credentials

---

## 🔧 Troubleshooting

### Can't Login?
- Check database credentials in `config/database.php`
- Verify database was imported successfully
- Try default: admin@admin.com / admin123

### "500 Internal Server Error"?
- Check file permissions (files: 644, folders: 755)
- Enable `DEBUG_MODE` in `config/config.php`
- Check error logs in Hostinger

### Database Connection Failed?
- Verify credentials in `config/database.php`
- Check if database exists in phpMyAdmin
- Ensure database user has privileges

### Files Won't Upload?
- Set `uploads/` folder permission to 777
- Check PHP upload limits in Hostinger settings
- Increase `upload_max_filesize` if needed

---

## 📞 Need Help?

**Documentation:**
- README.md - Overview
- INSTALLATION.md - Detailed installation
- DEVELOPMENT.md - Customization guide
- FEATURES.md - Complete feature list

**Hostinger Support:**
- 24/7 Live Chat
- Email: support@hostinger.com
- Knowledge Base: https://support.hostinger.com

---

## 🎉 You're All Set!

Your Project Management System is now ready to use.

**Next Steps:**
1. Explore the dashboard
2. Create your first project
3. Invite your team
4. Start managing projects!

**Recommended Reading:**
- FEATURES.md - See all available features
- DEVELOPMENT.md - Learn to customize

---

## 📊 Default System Stats

- **Users:** 1 (admin)
- **Projects:** 1 (sample)
- **Tasks:** 2 (sample)
- **Storage:** Unlimited (depends on hosting)
- **User Limit:** Unlimited

---

## 🌟 Pro Tips

1. **Regular Backups:** Set up weekly database backups
2. **SSL Certificate:** Enable HTTPS for security
3. **Strong Passwords:** Require strong passwords for all users
4. **Activity Monitoring:** Regularly check activity logs
5. **Updates:** Keep PHP and MySQL updated

---

**Estimated Setup Time:** 15 minutes  
**Difficulty Level:** Beginner-friendly  
**Support:** Documentation + Hostinger Support

Good luck with your project management! 🚀
