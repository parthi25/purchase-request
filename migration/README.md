# Database Migration Scripts

This directory contains scripts to run database migrations for the PR Tracker system.

## Available Scripts

### Windows
- **run-migration.bat** - Windows batch script for running migrations

### Linux/Mac
- **run-migration.sh** - Bash script for running migrations on Linux/Mac systems

### PHP
- **../database/run-migration.php** - PHP script for running migrations (cross-platform)

## Prerequisites

1. **MySQL/MariaDB** - Database server must be running
2. **MySQL Command Line Client** - Must be in your system PATH
3. **PHP** (optional but recommended) - For reading configuration from .env file
4. **.env file** - Should be in the project root with database configuration

## Usage

### Windows

1. Open Command Prompt or PowerShell
2. Navigate to the migration directory:
   ```cmd
   cd migration
   ```
3. Run the batch script:
   ```cmd
   run-migration.bat
   ```
4. When prompted, enter the database name (or press Enter to use the default from .env)
5. Enter MySQL password if required

### Linux/Mac

1. Make the script executable (first time only):
   ```bash
   chmod +x migration/run-migration.sh
   ```
2. Navigate to the migration directory:
   ```bash
   cd migration
   ```
3. Run the shell script:
   ```bash
   ./run-migration.sh
   ```
4. When prompted, enter the database name (or press Enter to use the default from .env)
5. Enter MySQL password if required

### PHP Script

1. Navigate to the project root
2. Run the PHP script:
   ```bash
   php database/run-migration.php [migration_file.sql]
   ```
3. When prompted, enter the database name (or press Enter to use the default from .env)

## Database Selection

All scripts now support interactive database selection:

- The script will read the default database name from your `.env` file
- You will be prompted to enter a different database name if needed
- Press Enter to use the default database from `.env`
- The selected database will be shown before migration starts

## Configuration

The scripts read database configuration from the `.env` file in the project root:

```env
DB_HOST=127.0.0.1
DB_USER=root
DB_PASS=your_password
DB_NAME=jcrc_ch
DB_PORT=3307
```

If PHP is not available, the scripts will use default values:
- Host: 127.0.0.1
- User: root
- Password: (empty)
- Database: jcrc_ch
- Port: 3307

## Migration File

The scripts run the `complete_migration.sql` file which includes:
- Table renaming
- New table creation
- Foreign key relationships
- Indexes
- Master data insertion

## Troubleshooting

### MySQL not found
- Ensure MySQL is installed
- Add MySQL bin directory to your system PATH
- On Windows: Add `C:\Program Files\MySQL\MySQL Server X.X\bin` to PATH
- On Linux: Usually `/usr/bin/mysql` or `/usr/local/mysql/bin/mysql`

### Permission denied (Linux/Mac)
- Make the script executable: `chmod +x run-migration.sh`

### Database connection failed
- Check database server is running
- Verify credentials in `.env` file
- Ensure database exists
- Check user has necessary permissions (ALTER, CREATE, INSERT, etc.)

### Migration file not found
- Ensure `complete_migration.sql` exists in the `migration` directory
- Check you're running the script from the correct directory

## What Gets Migrated

1. **Table Renaming** - Renames tables to follow better naming conventions
2. **New Tables** - Creates permission and workflow tables
3. **Foreign Keys** - Adds foreign key relationships
4. **Indexes** - Adds performance indexes
5. **Master Data** - Inserts statuses, permissions, and workflows

## Post-Migration Steps

After successful migration:

1. Create users with appropriate roles:
   - admin
   - buyer
   - B_Head
   - PO_Team
   - PO_Team_Member

2. Map categories to buyer heads using `catbasbh` table

3. Map buyers to buyer heads using `buyers_info` table

4. Start using the system!

## Notes

- The migration scripts will modify your database structure and data
- Always backup your database before running migrations
- Some errors (like "table already exists") are expected if running migrations multiple times
- The scripts will continue even if some statements fail (for idempotency)
