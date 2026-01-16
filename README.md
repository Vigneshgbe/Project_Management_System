# 🚀 Modern Project Management System

A professional, full-featured project management system built with PHP and MySQL, featuring a modern glassmorphism UI design, comprehensive user management, project tracking, task management, and real-time analytics.

---

## 📋 Table of Contents

- [Overview](#overview)
- [Features](#features)
- [Tech Stack](#tech-stack)
- [Screenshots](#screenshots)
- [Installation](#installation)
- [Configuration](#configuration)
- [Usage](#usage)
- [Project Structure](#project-structure)
- [Database Schema](#database-schema)
- [Security Features](#security-features)
- [Browser Support](#browser-support)
- [Contributing](#contributing)
- [License](#license)
- [Contact](#contact)

---

## 🎯 Overview

This Modern Project Management System is a comprehensive web application designed to streamline project workflows, team collaboration, and resource management. Built with a focus on user experience and visual appeal, it combines powerful functionality with a contemporary glassmorphism design aesthetic.

### Key Highlights

- **Modern UI/UX**: Clean, professional interface with gradient accents and smooth animations
- **Role-Based Access Control**: Three-tier user system (Admin, Manager, Member)
- **Responsive Design**: Fully optimized for desktop, tablet, and mobile devices
- **Real-Time Analytics**: Comprehensive dashboard with live statistics and insights
- **Secure Authentication**: Industry-standard security practices and session management

---

## ✨ Features

### 👥 User Management
- Create, read, update, and delete user accounts
- Role-based permissions (Admin, Manager, Member)
- User status management (Active/Inactive)
- Password strength validation
- User profile customization
- Activity tracking

### 📊 Project Management
- Create and manage multiple projects
- Assign team members to projects
- Track project progress and status
- Project-specific analytics
- Member collaboration tools
- Project timeline visualization

### ✅ Task Management
- Create and assign tasks
- Priority levels and due dates
- Task status tracking
- Task filtering and sorting
- Real-time updates
- Task dependencies

### 📈 Analytics & Reporting
- Interactive dashboard with live statistics
- Project completion metrics
- User activity reports
- Task distribution analysis
- Performance insights
- Exportable reports

### 🎨 Modern Design Features
- Glassmorphism UI elements
- Gradient color scheme (Indigo/Purple)
- Smooth animations and transitions
- Animated statistics counters
- Interactive hover effects
- Responsive breakpoints (320px - 1920px+)

---

## 🛠️ Tech Stack

### Backend
- **PHP 7.4+**: Server-side scripting
- **MySQL 5.7+**: Database management
- **Session Management**: Secure user authentication

### Frontend
- **HTML5**: Semantic markup
- **CSS3**: Modern styling with variables and animations
- **JavaScript (ES6)**: Interactive functionality
- **jQuery 3.x**: DOM manipulation and AJAX

### Frameworks & Libraries
- **Bootstrap 3.3.7**: Responsive grid system
- **Font Awesome 4.7**: Icon library
- **Custom CSS**: Glassmorphism and gradient effects

### Design Patterns
- **MVC Architecture**: Separation of concerns
- **Component-Based**: Reusable PHP components
- **Object-Oriented**: Structured code organization

---

## 📸 Screenshots

### Dashboard
![Dashboard](https://github.com/Vigneshgbe/Project_Management_System/blob/main/Demo_Screenshots/Dashboard%20Page.png?raw=true)
*Modern dashboard with live statistics and project overview*

### User Management
![User Management](https://github.com/Vigneshgbe/Project_Management_System/blob/main/Demo_Screenshots/User%20Management.png?raw=true)
*Comprehensive user management interface*

### Project View
![Projects](https://github.com/Vigneshgbe/Project_Management_System/blob/main/Demo_Screenshots/Project%20Management.png?raw=true)
*Project tracking and team collaboration*

### Task Management
![Tasks](https://github.com/Vigneshgbe/Project_Management_System/blob/main/Demo_Screenshots/Task%20Management.png?raw=true)
*Task assignment and progress tracking*

---

## 💻 Installation

### Prerequisites
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- Composer (optional, for dependencies)

### Step 1: Clone the Repository
```bash
git clone https://github.com/Vigneshgbe/Project_Management_System.git
cd project-management-system
```

### Step 2: Database Setup
```bash
# Create database
mysql -u root -p

# In MySQL console:
CREATE DATABASE project_management;
exit;

# Import database schema
mysql -u root -p project_management < database.sql
```

### Step 3: Configure Database Connection
Edit `config.php` with your database credentials:
```php
<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
define('DB_NAME', 'project_management');
?>
```

### Step 4: Set Permissions
```bash
# Set proper permissions
chmod 755 -R .
chmod 644 config.php
```

### Step 5: Access the Application
Navigate to `http://localhost/project-management-system/`

### Default Login Credentials
```
Username: admin
Password: admin123
```
**⚠️ Important: Change the default password immediately after first login!**

---

## ⚙️ Configuration

### Base URL Configuration
Update the base URL in `includes/header.php`:
```php
$base_url = 'http://localhost/project-management-system';
```

### Session Configuration
Adjust session settings in `config.php`:
```php
ini_set('session.cookie_lifetime', 3600); // 1 hour
ini_set('session.gc_maxlifetime', 3600);
```

### Email Configuration (Optional)
For email notifications, configure SMTP settings in `config.php`:
```php
define('SMTP_HOST', 'smtp.example.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'your-email@example.com');
define('SMTP_PASS', 'your-password');
```

---

## 📖 Usage

### Admin Functions
- **User Management**: Create/edit/delete users, assign roles
- **System Configuration**: Manage system-wide settings
- **Analytics Access**: View comprehensive reports
- **Full Project Control**: Manage all projects and tasks

### Manager Functions
- **Project Management**: Create and manage assigned projects
- **Team Coordination**: Assign tasks to team members
- **Progress Tracking**: Monitor project and task completion
- **Reports**: Generate team and project reports

### Member Functions
- **Task Management**: View and update assigned tasks
- **Project Participation**: Collaborate on assigned projects
- **Profile Management**: Update personal information
- **Activity Tracking**: View personal activity history

---

## 📁 Project Structure
```
project-management-system/
├── admin/                      # Admin-specific pages
│   ├── users.php              # User management
│   ├── user-create.php        # Create new user
│   ├── user-edit.php          # Edit user
│   ├── user-delete.php        # Delete user
│   ├── analytics.php          # System analytics
│   └── activity.php           # Activity logs
├── components/                 # Reusable PHP components
│   ├── auth.php               # Authentication class
│   ├── user.php               # User management class
│   ├── project.php            # Project management class
│   ├── task.php               # Task management class
│   ├── pricing.php            # Pricing management class
│   └── requirement.php        # Requirements class
├── includes/                   # Common includes
│   ├── header.php             # Header template
│   ├── footer.php             # Footer template
│   └── config.php             # Configuration file
├── dashboard.php              # Main dashboard
├── projects.php               # Projects listing
├── project-detail.php         # Project details
├── project-create.php         # Create project
├── project-edit.php           # Edit project
├── tasks.php                  # Tasks listing
├── task-create.php            # Create task
├── task-edit.php              # Edit task
├── profile.php                # User profile
├── login.php                  # Login page
├── logout.php                 # Logout handler
├── index.php                  # Landing page
└── database.sql               # Database schema
```

---

## 🗄️ Database Schema

### Users Table
```sql
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('admin', 'manager', 'user') DEFAULT 'user',
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### Projects Table
```sql
CREATE TABLE projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    description TEXT,
    client_name VARCHAR(100),
    start_date DATE,
    end_date DATE,
    budget DECIMAL(15,2),
    status ENUM('planning', 'active', 'on_hold', 'completed', 'cancelled'),
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id)
);
```

### Tasks Table
```sql
CREATE TABLE tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    assigned_to INT,
    priority ENUM('low', 'medium', 'high', 'urgent'),
    status ENUM('pending', 'in_progress', 'completed', 'cancelled'),
    due_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_to) REFERENCES users(id)
);
```

*For complete schema, see `database.sql`*

---

## 🔒 Security Features

- **Password Hashing**: Bcrypt algorithm for secure password storage
- **SQL Injection Protection**: Prepared statements for all database queries
- **XSS Prevention**: HTML sanitization and output escaping
- **CSRF Protection**: Token-based form validation
- **Session Security**: Secure session management with timeout
- **Role-Based Access Control**: Permission checking on all restricted pages
- **Input Validation**: Server-side and client-side validation
- **Output Buffering**: Prevents header manipulation attacks

---

## 🌐 Browser Support

- ✅ Chrome 90+
- ✅ Firefox 88+
- ✅ Safari 14+
- ✅ Edge 90+
- ✅ Opera 76+

---

## 🤝 Contributing

Contributions are welcome! Please follow these steps:

1. **Fork the Repository**
```bash
   git clone https://github.com/Vigneshgbe/Project_Management_System.git
```

2. **Create a Feature Branch**
```bash
   git checkout -b feature/AmazingFeature
```

3. **Commit Your Changes**
```bash
   git commit -m 'Add some AmazingFeature'
```

4. **Push to the Branch**
```bash
   git push origin feature/AmazingFeature
```

5. **Open a Pull Request**

### Coding Standards
- Follow PSR-12 coding standards for PHP
- Use meaningful variable and function names
- Comment complex logic
- Maintain consistent indentation (4 spaces)
- Write clean, readable code

---

## 📝 Changelog

### Version 2.0.0 (Current)
- ✨ Complete UI/UX redesign with glassmorphism
- 🎨 Modern gradient color scheme
- 📱 Enhanced responsive design
- ⚡ Performance optimizations
- 🔧 Bug fixes and improvements

### Version 1.0.0
- 🎉 Initial release
- 👥 User management system
- 📊 Project and task tracking
- 📈 Basic analytics dashboard

---

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.
```
MIT License

Copyright (c) 2026 Vigneshgbe

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
```

---

## 👨‍💻 Author

**Your Name**
- GitHub: [@Vigneshgbe](https://github.com/Vigneshgbe)
- LinkedIn: [Vigneshgbe](https://www.linkedin.com/in/vigneshgbe/)

---

## 🙏 Acknowledgments

- Bootstrap team for the responsive framework
- Font Awesome for the icon library
- The PHP community for continuous support
- All contributors who have helped improve this project

---

## 📞 Support

For support, please:
- 📧 Email: kuttydravid463@gmail.com
- 💬 Open an issue on GitHub
- 📚 Check the [Wiki](https://github.com/Vigneshgbe/Project_Management_System/wiki)
- 🐛 Report bugs in [Issues](https://github.com/Vigneshgbe/Project_Management_System/issues)

---

## 🗺️ Roadmap

### Upcoming Features
- [ ] Email notifications system
- [ ] File attachment support
- [ ] Advanced reporting and exports
- [ ] Real-time chat functionality
- [ ] Calendar integration
- [ ] Mobile app (iOS/Android)
- [ ] API development for third-party integrations
- [ ] Dark mode theme
- [ ] Multi-language support
- [ ] Advanced search and filters

---

## ⭐ Star History

[![Star History Chart](https://api.star-history.com/svg?repos=Vigneshgbe/Project_Management_System&type=Date)](https://star-history.com/Vigneshgbe/Project_Management_System&Date)

---

<div align="center">

**Made with ❤️ by [Vigneshgbe](https://github.com/Vigneshgbe)**

If you found this project helpful, please consider giving it a ⭐!

[⬆ Back to Top](https://github.com/Vigneshgbe/)

</div>
```

---

## 📝 GitHub Repository Description

**Short Description (for GitHub "About" section):**
```
A modern, professional project management system with glassmorphism UI, user management, project tracking, task management, and real-time analytics. Built with PHP, MySQL, and Bootstrap.
```

**Topics/Tags:**
```
project-management
php
mysql
bootstrap
glassmorphism
task-management
user-management
analytics
responsive-design
web-application
modern-ui
gradient-design
jquery
dashboard
admin-panel
