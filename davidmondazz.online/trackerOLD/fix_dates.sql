-- Step 1: Backup the current NULL columns (create backups if they don't exist)
ALTER TABLE details
ADD COLUMN IF NOT EXISTS start_date_backup VARCHAR(255),
ADD COLUMN IF NOT EXISTS end_date_backup VARCHAR(255);

-- Step 2: Store any non-NULL values in backup
UPDATE details
SET start_date_backup = start_date
WHERE start_date IS NOT NULL;

UPDATE details
SET end_date_backup = end_date
WHERE end_date IS NOT NULL;

-- Step 3: Modify the columns to DATETIME type
ALTER TABLE details
MODIFY COLUMN start_date DATETIME NULL,
MODIFY COLUMN end_date DATETIME NULL;

-- Step 4: For dates that are in proper format already, let's try to convert them
-- These commands use different format strings to try to accommodate various formats

-- First, try with the standard format
UPDATE details 
SET start_date = STR_TO_DATE(start_date_backup, '%d %b, %Y %H:%i:%s')
WHERE start_date IS NULL AND start_date_backup IS NOT NULL;

UPDATE details 
SET end_date = STR_TO_DATE(end_date_backup, '%d %b, %Y %H:%i:%s')
WHERE end_date IS NULL AND end_date_backup IS NOT NULL;

-- Next, try with AM/PM format
UPDATE details 
SET start_date = STR_TO_DATE(start_date_backup, '%d %b, %Y %h:%i:%s %p')
WHERE start_date IS NULL AND start_date_backup IS NOT NULL;

UPDATE details 
SET end_date = STR_TO_DATE(end_date_backup, '%d %b, %Y %h:%i:%s %p')
WHERE end_date IS NULL AND end_date_backup IS NOT NULL;

-- Try with full month name
UPDATE details 
SET start_date = STR_TO_DATE(start_date_backup, '%d %M, %Y %H:%i:%s')
WHERE start_date IS NULL AND start_date_backup IS NOT NULL;

UPDATE details 
SET end_date = STR_TO_DATE(end_date_backup, '%d %M, %Y %H:%i:%s')
WHERE end_date IS NULL AND end_date_backup IS NOT NULL;

-- Try with MySQL default format
UPDATE details 
SET start_date = STR_TO_DATE(start_date_backup, '%Y-%m-%d %H:%i:%s')
WHERE start_date IS NULL AND start_date_backup IS NOT NULL;

UPDATE details 
SET end_date = STR_TO_DATE(end_date_backup, '%Y-%m-%d %H:%i:%s')
WHERE end_date IS NULL AND end_date_backup IS NOT NULL;

-- Step 5: View the results
SELECT id, start_date_backup, start_date, end_date_backup, end_date
FROM details
LIMIT 20; 