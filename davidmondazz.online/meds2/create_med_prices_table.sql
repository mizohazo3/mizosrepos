-- Table structure for storing medication prices
CREATE TABLE IF NOT EXISTS `med_prices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `med_id` int(11) NOT NULL,
  `mg_amount` decimal(10,2) DEFAULT NULL,
  `price_egp` decimal(10,2) DEFAULT NULL,
  `update_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `med_id` (`med_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;