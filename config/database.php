<?php
/**
 * Database Configuration
 * Update these values with your Hostinger MySQL credentials
 */

// Database Configuration
define('DB_HOST', 'localhost');          // Usually 'localhost' for Hostinger
define('DB_NAME', 'your_database_name'); // Your MySQL database name
define('DB_USER', 'your_username');      // Your MySQL username
define('DB_PASS', 'your_password');      // Your MySQL password
define('DB_CHARSET', 'utf8mb4');

// Database connection error handling
define('DB_ERROR_DISPLAY', false); // Set to false in production
define('DB_ERROR_LOG', true);

/**
 * How to get your Hostinger database credentials:
 * 1. Login to Hostinger control panel (hPanel)
 * 2. Go to "Databases" section
 * 3. Click "MySQL Databases"
 * 4. Create a new database or use existing one
 * 5. Note down:
 *    - Database name
 *    - Username
 *    - Password
 *    - Server (usually localhost)
 */
