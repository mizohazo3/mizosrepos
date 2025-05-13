# Timer8 Online

## Database Setup

The website is currently showing an HTTP 500 error due to database connection issues. Follow these steps to fix it:

1. **Update Database Credentials**:
   - The database credentials in `api/db.php` have been updated to use local development settings.
   - Default configuration uses:
     - Host: `localhost`
     - Database: `timer8_online`
     - Username: `root`
     - Password: `` (empty)

2. **Set Up the Database**:
   - Navigate to: `http://localhost/timer8_online/setup_local_db.php`
   - This script will:
     - Create the database if it doesn't exist
     - Import the schema from the SQL file
     - Initialize basic data

3. **Test Connection**:
   - Navigate to: `http://localhost/timer8_online/test_db_connection.php`
   - Verify the database connection is working
   - Check that all tables are properly created

4. **Access the Application**:
   - Once database setup is complete, the application should work correctly
   - Access it at: `http://localhost/timer8_online/`

## Troubleshooting

If you still experience issues:

1. Check PHP error logs
2. Ensure MySQL server is running
3. Verify database permissions
4. Make sure the SQL import completed successfully

## Project Organization

- `/api` - Backend API endpoints
- `/includes` - PHP helper functions
- `/style` - CSS files
- `/uploads` - User uploaded content 