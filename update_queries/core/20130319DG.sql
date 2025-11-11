ALTER TABLE `tc_communication_messages` CHANGE `type` `type` ENUM( 'email', 'sms', 'notice' ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'email';

