-- SQL query to change accumulated_seconds field back to time_spent in activity table
ALTER TABLE activity CHANGE COLUMN accumulated_seconds time_spent BIGINT(20) DEFAULT 0; 