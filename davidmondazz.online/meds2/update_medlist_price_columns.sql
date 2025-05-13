-- Add price columns to medlist table
ALTER TABLE `medlist` 
ADD COLUMN `price` decimal(10,2) DEFAULT NULL COMMENT 'Medication package price',
ADD COLUMN `doses_per_package` int(11) DEFAULT NULL COMMENT 'Number of doses in a package'; 