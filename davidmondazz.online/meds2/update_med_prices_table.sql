-- Add price_per_dose column to med_prices table
ALTER TABLE `med_prices` 
ADD COLUMN `price_per_dose` decimal(10,2) DEFAULT NULL AFTER `price_egp`,
ADD COLUMN `dose_amount` decimal(10,2) DEFAULT NULL AFTER `mg_amount`;