UPDATE `kolumbus_email_templates_applications` SET `application` = 'mobile_app_forgotten_password' WHERE `application` = 'student_login';

ALTER TABLE `customer_db_2` ADD `app_forgotten_password_template` SMALLINT UNSIGNED NOT NULL AFTER `app_image`;