--
-- Drop existing table
--

DROP TABLE IF EXISTS `civisocial_user`;

--
-- Table Structure for civisocial user
--

CREATE TABLE IF NOT EXISTS `civisocial_user` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `contact_id` int(10) NOT NULL,
  `email_address` varchar(255) DEFAULT NULL,
  `extra_data` varchar(1023) DEFAULT NULL,
  PRIMARY KEY (`id`)
  ) ENGINE=InnoDB DEFAULT CHARSET=latin1;