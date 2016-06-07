--
-- Drop existing table
--

DROP TABLE IF EXISTS `civicrm_civisocial_user`;

--
-- Table Structure for civisocial user
--

CREATE TABLE `civicrm_civisocial_user` (
     `id` int unsigned NOT NULL AUTO_INCREMENT  COMMENT 'Unique User ID',
     `contact_id` int unsigned NULL DEFAULT NULL COMMENT 'FK to Contact ID that owns that account',
     `oauth_provider` varchar(128) NOT NULL COMMENT 'OAuth Provider',
     `social_user_id` varchar(128) NULL DEFAULT NULL COMMENT 'User identifier given by OAuth Provider',
     `oauth_token` varchar(512) NULL DEFAULT NULL COMMENT 'Access Token Provided by OAuth Provider',
     `oauth_secret` varchar(1024) NULL DEFAULT NULL COMMENT 'Access Token Secret Provided by OAuth Provider',
     `created_date` timestamp NULL DEFAULT NULL COMMENT 'When was the civisocial user was created.',
     `modified_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'When was the the civisocial user was created or modified or deleted.'
, PRIMARY KEY ( `id` )
, CONSTRAINT FK_civicrm_civisocial_user_contact_id FOREIGN KEY (`contact_id`) REFERENCES `civicrm_contact`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;