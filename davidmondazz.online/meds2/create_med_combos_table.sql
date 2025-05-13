-- Create the med_combos table for saving medication combinations
CREATE TABLE IF NOT EXISTS `med_combos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `combo_name` varchar(255) NOT NULL,
  `medications` text NOT NULL,
  `created_date` varchar(50) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `combo_name` (`combo_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4; 