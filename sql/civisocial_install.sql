--
-- Drop existing table
--

DROP TABLE IF EXISTS `civisocial_user`;

--
-- Table Structure for civisocial user
--

CREATE TABLE `civisocial_user` (
     `id` int unsigned NOT NULL AUTO_INCREMENT  COMMENT 'Unique User ID',
     `contact_id` int unsigned    COMMENT 'FK to Contact ID that owns that account',
     `backend` varchar(128)    COMMENT 'Backend OAuth Provider',
     `social_user_id` varchar(128) NULL  DEFAULT NULL COMMENT 'User ID for facebook, to be used to match friends etc.',
     `access_token` varchar(511) NULL  DEFAULT NULL COMMENT 'Access Token Provided by OAuth Provider',
     `oauth_object` varchar(1023) NULL  DEFAULT NULL COMMENT 'Access Token Provided by OAuth Provider',
     `created_date` timestamp NULL  DEFAULT CURRENT_TIMESTAMP COMMENT 'When was the civisocial user was created.',
     `modified_date` timestamp NULL  DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'When was the the civisocial user was created or modified or deleted.'
, PRIMARY KEY ( `id` )
, CONSTRAINT FK_civisocial_user_contact_id FOREIGN KEY (`contact_id`) REFERENCES `civicrm_contact`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;