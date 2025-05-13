-- This script will restore the original dates from backup columns if needed

-- Step 1: Restore data from backups to main columns
UPDATE details
SET start_date = start_date_backup
WHERE start_date_backup IS NOT NULL;

UPDATE details
SET end_date = end_date_backup
WHERE end_date_backup IS NOT NULL;

-- Step 2: Check if the data is restored
SELECT id, start_date_backup, start_date, end_date_backup, end_date
FROM details
LIMIT 20;

-- Note: If you need to revert the column types back to VARCHAR as well, run this:
-- ALTER TABLE details
-- MODIFY COLUMN start_date VARCHAR(255) NULL,
-- MODIFY COLUMN end_date VARCHAR(255) NULL; 